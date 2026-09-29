<?php
namespace Controllers;
use Config\Database;
use Config\Security;

class ActivitiesController {
    private $db;
    public function __construct() { $this->db = Database::getConnection(); }

    public function index() {
        $role   = $_SESSION['user_role'] ?? '';
        $userId = $_SESSION['user_id']   ?? 0;
        $isStaff = in_array($role, ['super_admin', 'admin', 'professor']);

        Security::requireAuth(['super_admin', 'admin', 'professor', 'parent', 'baba']);
        
        if (in_array($role, ['parent', 'baba'])) {
            $stmt = $this->db->prepare("
                SELECT a.*, c.name as class_name 
                FROM activities a
                LEFT JOIN classes c ON c.id = a.class_id
                WHERE a.class_id IN (
                    SELECT class_id FROM children ch 
                    JOIN parent_child pc ON pc.child_id = ch.id 
                    WHERE pc.parent_id = ?
                )
                ORDER BY a.activity_date DESC, a.created_at DESC
                LIMIT 50
            ");
            $stmt->execute([$userId]);
            $activities = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Buscar notas individuais para os filhos deste pai
            $stmtNotes = $this->db->prepare("
                SELECT ar.activity_id, ar.notes, ch.name as child_name
                FROM activity_registrations ar
                JOIN children ch ON ch.id = ar.child_id
                JOIN parent_child pc ON pc.child_id = ch.id
                WHERE pc.parent_id = ?
            ");
            $stmtNotes->execute([$userId]);
            $allNotes = $stmtNotes->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_ASSOC);
        } else {
            $stmt = $this->db->query("
                SELECT a.*, c.name as class_name 
                FROM activities a
                LEFT JOIN classes c ON c.id = a.class_id
                ORDER BY a.activity_date DESC, a.created_at DESC
                LIMIT 50
            ");
            $activities = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Buscar todas as notas individuais para staff
            $stmtNotes = $this->db->query("
                SELECT ar.activity_id, ar.notes, ch.name as child_name
                FROM activity_registrations ar
                JOIN children ch ON ch.id = ar.child_id
            ");
            $allNotes = $stmtNotes->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_ASSOC);
        }

        foreach ($activities as &$act) {
            $act['child_notes'] = $allNotes[$act['id']] ?? [];
        }
        unset($act);

        $classes = $this->db->query("SELECT id, name FROM classes ORDER BY name")->fetchAll();
        $children = $this->db->query("SELECT id, name, class_id FROM children ORDER BY name")->fetchAll();

        $viewFile = __DIR__ . '/../views/activities.php';
        require __DIR__ . '/../views/layout.php';
    }

    public function create($data) {
        Security::requireAuth(['super_admin', 'admin', 'professor']);
        Security::verifyCSRFToken($data['csrf_token'] ?? '');

        $title       = Security::sanitizeInput($data['title']);
        $description = Security::sanitizeInput($data['description']);
        $class_id    = (int)$data['class_id'];
        $date        = $data['activity_date'] ?? date('Y-m-d');
        
        // Simulação de upload de imagem (placeholder para POC)
        $image_path = null;
        if (!empty($_FILES['image']['name'])) {
            // Em aplicação real, validar extensão, tamanho e mover para public/uploads
            $image_path = 'uploads/activities/' . time() . '_' . $_FILES['image']['name'];
            // move_uploaded_file(...);
        }

        $stmt = $this->db->prepare("INSERT INTO activities (class_id, title, description, activity_date, image_path) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$class_id, $title, $description, $date, $image_path]);
        $activity_id = $this->db->lastInsertId();

        // Se houver notas específicas por criança
        if (!empty($data['child_notes']) && is_array($data['child_notes'])) {
            $stmtReg = $this->db->prepare("INSERT INTO activity_registrations (activity_id, child_id, notes) VALUES (?, ?, ?)");
            foreach ($data['child_notes'] as $child_id => $note) {
                if (!empty($note)) {
                    $stmtReg->execute([$activity_id, (int)$child_id, Security::sanitizeInput($note)]);
                }
            }
        }

        if (!empty($data['send_whatsapp'])) {
            $this->notifyParents($title, $class_id, $date);
        }

        $_SESSION['flash_msg'] = "Atividade registada com sucesso!";
        $_SESSION['flash_type'] = 'success';
        header("Location: /?action=atividades");
        exit;
    }

    private function notifyParents($title, $class_id, $date) {
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
            error_log("[e-cardeneta] Notificação de atividade bloqueada fora do horário: {$horaAtual}");
            return; 
        }

        $formattedDate = date('d/m/Y', strtotime($date));

        $msg = "*E-Cardeneta - Nova Atividade*\n\nHoje as crianças participaram na atividade: *{$title}*\nData: {$formattedDate}\n\nConsulte os detalhes e fotos na app!";

        // Buscar pais da turma específica
        $stmtPhones = $this->db->prepare("
            SELECT DISTINCT u.phone 
            FROM users u
            JOIN parent_child pc ON pc.parent_id = u.id
            JOIN children ch ON ch.id = pc.child_id
            WHERE ch.class_id = ? AND u.phone IS NOT NULL AND u.phone != ''
        ");
        $stmtPhones->execute([$class_id]);
        
        while($p = $stmtPhones->fetch()) {
            $payload = json_encode(['token' => $apiToken, 'number' => $p['phone'], 'message' => $msg]);
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
