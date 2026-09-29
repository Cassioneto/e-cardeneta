<?php
namespace Controllers;
use Config\Database;
use Config\Security;

class MealsController {
    private $db;
    public function __construct() { $this->db = Database::getConnection(); }

    public function index() {
        $role = $_SESSION['user_role'] ?? '';
        $userId = $_SESSION['user_id'] ?? 0;
        $isStaff = in_array($role, ['super_admin', 'admin', 'professor']);

        Security::requireAuth(['super_admin', 'admin', 'professor', 'parent', 'baba']);
        
        // Buscar o cardápio da semana (do dia atual em diante)
        $stmt = $this->db->query("
            SELECT * FROM meals 
            WHERE scheduled_date >= CURDATE() 
            ORDER BY scheduled_date ASC, scheduled_time ASC
        ");
        $meals = $stmt->fetchAll();

        // Buscar crianças com restrições alimentares ou alergias - APENAS STAFF
        $childrenWithRestrictions = [];
        if ($isStaff) {
            $stmtChildren = $this->db->query("
                SELECT ch.name, ch.class_id, cl.name as class_name, hr.description, hr.record_type
                FROM children ch
                JOIN health_records hr ON hr.child_id = ch.id
                LEFT JOIN classes cl ON cl.id = ch.class_id
                WHERE hr.record_type IN ('allergy', 'food_restriction')
            ");
            $childrenWithRestrictions = $stmtChildren->fetchAll();
            
            foreach ($childrenWithRestrictions as &$c) {
                try {
                    $c['description'] = Security::decryptData($c['description']);
                } catch (\Exception $e) { }
            }
        }

        $viewFile = __DIR__ . '/../views/meals.php';
        require __DIR__ . '/../views/layout.php';
    }

    public function create($data) {
        Security::requireAuth(['super_admin', 'admin', 'professor']);
        Security::verifyCSRFToken($data['csrf_token'] ?? '');

        $description  = Security::sanitizeInput($data['description']);
        $type         = $data['meal_type'] ?? 'breakfast';
        $date         = $data['scheduled_date'] ?? date('Y-m-d');
        $time         = $data['scheduled_time'] ?? date('H:i');
        $sendWhatsApp = !empty($data['send_whatsapp']);

        if (empty($description)) {
            $_SESSION['flash_msg'] = 'A descrição da refeição é obrigatória.';
            $_SESSION['flash_type'] = 'danger';
            header("Location: /?action=refeicoes");
            exit;
        }

        $stmt = $this->db->prepare("INSERT INTO meals (description, meal_type, scheduled_date, scheduled_time) VALUES (?, ?, ?, ?)");
        $stmt->execute([$description, $type, $date, $time]);

        if ($sendWhatsApp) {
            $this->notifyParents($description, $type, $date);
        }

        $_SESSION['flash_msg'] = "Refeição registada com sucesso!";
        $_SESSION['flash_type'] = 'success';
        header("Location: /?action=refeicoes");
        exit;
    }

    public function delete($data) {
        Security::requireAuth(['super_admin', 'admin']);
        Security::verifyCSRFToken($data['csrf_token'] ?? '');
        $id = (int)$data['id'];
        
        $stmt = $this->db->prepare("DELETE FROM meals WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['flash_msg'] = "Refeição removida.";
        $_SESSION['flash_type'] = 'warning';
        header("Location: /?action=refeicoes");
        exit;
    }

    private function notifyParents($description, $type, $date) {
        // Obter configurações do WhatsApp
        $stmt = $this->db->query("SELECT setting_key, setting_value FROM settings");
        $cfg = [];
        while($row = $stmt->fetch()) { $cfg[$row['setting_key']] = $row['setting_value']; }
        
        $apiUrl = $cfg['whatsapp_api_url'] ?? 'http://127.0.0.1:3000/send';
        $apiToken = $cfg['whatsapp_api_token'] ?? 'e_cardeneta_super_secure_key_32b';

        // Verificar horário de funcionamento
        $horaInicio = $cfg['business_hour_start'] ?? '08:00';
        $horaFim    = $cfg['business_hour_end']   ?? '18:00';
        $horaAtual  = date('H:i');
        if ($horaAtual < $horaInicio || $horaAtual > $horaFim) {
            error_log("[e-cardeneta] Notificação de refeição bloqueada fora do horário: {$horaAtual}");
            return; 
        }

        $types = ['breakfast' => 'Pequeno-almoço', 'lunch' => 'Almoço', 'snack' => 'Lanche', 'dinner' => 'Jantar'];
        $typeName = $types[$type] ?? $type;
        $formattedDate = date('d/m/Y', strtotime($date));

        $msg = "*E-Cardeneta - Ementa*\n\nRefeição: *{$typeName}*\nData: {$formattedDate}\n\nDescrição: {$description}";

        // Notificar todos os pais (Exemplo simplificado para POC)
        $stmtPhones = $this->db->query("SELECT DISTINCT phone FROM users WHERE role = 'parent' AND phone IS NOT NULL AND phone != ''");
        while($p = $stmtPhones->fetch()) {
            $payload = json_encode([
                'token' => $apiToken,
                'number' => $p['phone'],
                'message' => $msg
            ]);

            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_exec($ch);
            curl_close($ch);
        }
    }
}
