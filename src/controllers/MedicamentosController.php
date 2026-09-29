<?php
namespace Controllers;
use Config\Database;
use Config\Security;

class MedicamentosController {
    private $db;
    public function __construct() { $this->db = Database::getConnection(); }

    public function index() {
        $role   = $_SESSION['user_role'] ?? '';
        $userId = $_SESSION['user_id']   ?? 0;
        $isParent = in_array($role, ['parent', 'baba']);

        if ($isParent) {
            // Pais só vêem medicamentos dos seus filhos
            $stmt = $this->db->prepare("
                SELECT hr.id, hr.description, hr.date_recorded, hr.author_id,
                       ch.name as child_name,
                       u.name as author_name,
                       ma.id as admin_id, ma.scheduled_time, ma.administered,
                       ma.administered_at, ua.name as administered_by
                FROM health_records hr
                JOIN children ch ON ch.id = hr.child_id
                JOIN users u ON u.id = hr.author_id
                LEFT JOIN medicine_administrations ma ON ma.health_record_id = hr.id
                LEFT JOIN users ua ON ua.id = ma.administered_by_id
                WHERE hr.record_type = 'medicine'
                AND ch.id IN (SELECT child_id FROM parent_child WHERE parent_id = ?)
                ORDER BY ma.scheduled_time ASC
            ");
            $stmt->execute([$userId]);
            // Filtra crianças do pai para o formulário
            $childrenStmt = $this->db->prepare("SELECT ch.id, ch.name FROM children ch JOIN parent_child pc ON pc.child_id = ch.id WHERE pc.parent_id = ? ORDER BY ch.name");
            $childrenStmt->execute([$userId]);
            $children = $childrenStmt->fetchAll();
        } else {
            $stmt = $this->db->query("
                SELECT hr.id, hr.description, hr.date_recorded, hr.author_id,
                       ch.name as child_name,
                       u.name as author_name,
                       ma.id as admin_id, ma.scheduled_time, ma.administered,
                       ma.administered_at, ua.name as administered_by
                FROM health_records hr
                JOIN children ch ON ch.id = hr.child_id
                JOIN users u ON u.id = hr.author_id
                LEFT JOIN medicine_administrations ma ON ma.health_record_id = hr.id
                LEFT JOIN users ua ON ua.id = ma.administered_by_id
                WHERE hr.record_type = 'medicine'
                ORDER BY ma.scheduled_time ASC
            ");
            $children = $this->db->query("SELECT id, name FROM children ORDER BY name")->fetchAll();
        }
        $medicamentos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Decifrar descrições (LGPD – dados de saúde criptografados)
        foreach ($medicamentos as &$m) {
            try { $m['description'] = Security::decryptData($m['description']); }
            catch(\Exception $e) {}
        }
        unset($m);

        $viewFile = __DIR__ . '/../views/medicamentos.php';
        require __DIR__ . '/../views/layout.php';
    }

    public function create($data) {
        Security::verifyCSRFToken($data['csrf_token'] ?? '');
        $child_id      = (int)$data['child_id'];
        $descricao     = Security::sanitizeInput($data['descricao']);
        $scheduled     = $data['scheduled_time'] ?? '';
        $scheduleType  = $data['schedule_type']  ?? 'once';
        $intervalHours = (int)($data['interval_hours'] ?? 0);
        $numAppl       = (int)($data['num_applications'] ?? 1);
        $role          = $_SESSION['user_role'] ?? '';
        $userId        = $_SESSION['user_id']   ?? 0;

        if (!$child_id || empty($descricao) || empty($scheduled)) {
            $_SESSION['flash_msg'] = 'Preencha todos os campos obrigatórios.';
            $_SESSION['flash_type'] = 'danger';
            header("Location: /?action=medicamentos"); exit;
        }

        // Pais só podem registar medicamentos para os seus filhos
        if (in_array($role, ['parent', 'baba'])) {
            $chk = $this->db->prepare("SELECT COUNT(*) FROM parent_child WHERE parent_id = ? AND child_id = ?");
            $chk->execute([$userId, $child_id]);
            if ((int)$chk->fetchColumn() === 0) {
                $_SESSION['flash_msg'] = 'Não tem permissão para registar medicamentos nesta criança.';
                $_SESSION['flash_type'] = 'danger';
                header("Location: /?action=medicamentos"); exit;
            }
        }

        // Criptografar dados de saúde (LGPD / OWASP A02)
        $descEncriptada = Security::encryptData($descricao);

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO health_records (child_id, record_type, description, author_id) VALUES (?,?,?,?)");
            $stmt->execute([$child_id, 'medicine', $descEncriptada, $_SESSION['user_id']]);
            $hrId = $this->db->lastInsertId();

            // Determinar intervalo em horas
            $step = 0;
            if ($scheduleType === 'daily')     $step = 24;
            elseif ($scheduleType === 'interval') $step = $intervalHours;

            if ($scheduleType === 'once') $numAppl = 1;
            if ($numAppl < 1) $numAppl = 1;

            $stmt2 = $this->db->prepare("INSERT INTO medicine_administrations (health_record_id, scheduled_time) VALUES (?,?)");
            
            $currentDate = new \DateTime($scheduled);
            for ($i = 0; $i < $numAppl; $i++) {
                $stmt2->execute([$hrId, $currentDate->format('Y-m-d H:i:s')]);
                if ($step > 0) {
                    $currentDate->modify("+{$step} hours");
                }
            }
            
            $this->db->commit();
            $_SESSION['flash_msg'] = "Medicamento registado: $numAppl aplicação(ões) agendada(s).";
            $_SESSION['flash_type'] = 'success';
        } catch (\Exception $e) {
            $this->db->rollBack();
            $_SESSION['flash_msg'] = 'Erro ao registar: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }

        header("Location: /?action=medicamentos"); exit;
    }

    public function marcarAdministrado($data) {
        Security::verifyCSRFToken($data['csrf_token'] ?? '');
        $admin_id = (int)$data['admin_id'];

        $stmt = $this->db->prepare("UPDATE medicine_administrations SET administered=1, administered_at=NOW(), administered_by_id=? WHERE id=?");
        $stmt->execute([$_SESSION['user_id'], $admin_id]);

        // Notificar pais
        $this->notifyWhatsApp($admin_id);

        $_SESSION['flash_msg'] = 'Medicamento marcado como administrado. Pais serão notificados via WhatsApp.';
        $_SESSION['flash_type'] = 'success';
        header("Location: /?action=medicamentos"); exit;
    }

    private function notifyWhatsApp($admin_id) {
        // Obter detalhes para a mensagem
        $stmt = $this->db->prepare("
            SELECT ma.administered_at, hr.description, ch.name as child_name, u.name as staff_name, ch.id as child_id
            FROM medicine_administrations ma
            JOIN health_records hr ON hr.id = ma.health_record_id
            JOIN children ch ON ch.id = hr.child_id
            JOIN users u ON u.id = ma.administered_by_id
            WHERE ma.id = ?
        ");
        $stmt->execute([$admin_id]);
        $data = $stmt->fetch();
        if (!$data) return;

        // Decifrar descrição
        try { $medName = Security::decryptData($data['description']); }
        catch(\Exception $e) { $medName = "Medicamento"; }

        // Configurações
        $stmtCfg = $this->db->query("SELECT setting_key, setting_value FROM settings");
        $cfg = [];
        while($row = $stmtCfg->fetch()) { $cfg[$row['setting_key']] = $row['setting_value']; }
        
        $apiUrl   = $cfg['whatsapp_api_url']   ?? 'http://127.0.0.1:3000/send';
        $apiToken = $cfg['whatsapp_api_token'] ?? 'e_cardeneta_super_secure_key_32b';

        // Verificar horário de funcionamento
        $horaInicio = $cfg['business_hour_start'] ?? '08:00';
        $horaFim    = $cfg['business_hour_end']   ?? '18:00';
        $horaAtual  = date('H:i');
        if ($horaAtual < $horaInicio || $horaAtual > $horaFim) {
            error_log("[e-cardeneta] Notificação de medicamento bloqueada fora do horário: {$horaAtual}");
            return; 
        }

        $msg = "*E-Cardeneta - Saúde*\n\n✅ O medicamento *{$medName}* foi administrado a *{$data['child_name']}* às " . date('H:i', strtotime($data['administered_at'])) . " por {$data['staff_name']}.";

        // Buscar telefones dos pais da criança
        $stmtPhones = $this->db->prepare("
            SELECT u.phone 
            FROM users u
            JOIN parent_child pc ON pc.parent_id = u.id
            WHERE pc.child_id = ? AND u.phone IS NOT NULL AND u.phone != ''
        ");
        $stmtPhones->execute([$data['child_id']]);
        $phones = array_column($stmtPhones->fetchAll(), 'phone');

        foreach ($phones as $phone) {
            $payload = json_encode(['token' => $apiToken, 'number' => $phone, 'message' => $msg]);
            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_TIMEOUT => 3]);
            curl_exec($ch);
            curl_close($ch);
        }
    }
}
