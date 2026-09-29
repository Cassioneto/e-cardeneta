<?php
namespace Controllers;
use Config\Database;
use Config\Security;

class EventsController {
    private $db;
    public function __construct() { $this->db = Database::getConnection(); }

    public function index() {
        $userId = $_SESSION['user_id'] ?? 0;
        
        // Listar eventos futuros e passados recentes
        $stmt = $this->db->query("
            SELECT e.*, 
                   (SELECT status FROM event_responses WHERE event_id = e.id AND user_id = " . (int)$userId . ") as user_status
            FROM events e
            ORDER BY e.event_date ASC, e.event_time ASC
        ");
        $events = $stmt->fetchAll();

        // Buscar quem vai para cada evento (para os modais) - Incluindo nomes dos filhos
        $attendees = [];
        $stmtA = $this->db->query("
            SELECT er.event_id, u.name as parent_name, 
                   GROUP_CONCAT(ch.name SEPARATOR ', ') as children_names
            FROM event_responses er
            JOIN users u ON u.id = er.user_id
            LEFT JOIN parent_child pc ON pc.parent_id = u.id
            LEFT JOIN children ch ON ch.id = pc.child_id
            WHERE er.status = 'going'
            GROUP BY er.event_id, u.id, u.name
        ");
        while($row = $stmtA->fetch()) {
            $attendees[$row['event_id']][] = [
                'parent' => $row['parent_name'],
                'children' => $row['children_names']
            ];
        }

        $viewFile = __DIR__ . '/../views/events.php';
        require __DIR__ . '/../views/layout.php';
    }

    public function responder($data) {
        Security::requireAuth();
        Security::verifyCSRFToken($data['csrf_token'] ?? '');
        
        $eventId = (int)$data['event_id'];
        $userId  = $_SESSION['user_id'];
        $status  = $data['status'] ?? 'going';

        $stmt = $this->db->prepare("
            INSERT INTO event_responses (event_id, user_id, status) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE status = VALUES(status)
        ");
        $stmt->execute([$eventId, $userId, $status]);

        $_SESSION['flash_msg'] = "Presença confirmada!";
        $_SESSION['flash_type'] = 'success';
        header("Location: /?action=eventos");
        exit;
    }

    public function create($data) {
        Security::requireAuth(['super_admin', 'admin']);
        Security::verifyCSRFToken($data['csrf_token'] ?? '');

        $title       = Security::sanitizeInput($data['title']);
        $description = Security::sanitizeInput($data['description']);
        $date        = $data['event_date'] ?? date('Y-m-d');
        $time        = $data['event_time'] ?? '00:00';
        $location    = Security::sanitizeInput($data['location']);
        $sendWA      = !empty($data['send_whatsapp']);

        if (empty($title) || empty($date)) {
            $_SESSION['flash_msg'] = 'Título e data são obrigatórios.';
            $_SESSION['flash_type'] = 'danger';
            header("Location: /?action=eventos");
            exit;
        }

        $stmt = $this->db->prepare("INSERT INTO events (title, description, event_date, event_time, location) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $date, $time, $location]);

        if ($sendWA) {
            $this->notifyParents($title, $date, $time, $location);
        }

        $_SESSION['flash_msg'] = "Evento registado com sucesso!";
        $_SESSION['flash_type'] = 'success';
        header("Location: /?action=eventos");
        exit;
    }

    public function delete($data) {
        Security::requireAuth(['super_admin', 'admin']);
        Security::verifyCSRFToken($data['csrf_token'] ?? '');
        $id = (int)$data['id'];
        
        $this->db->prepare("DELETE FROM events WHERE id = ?")->execute([$id]);

        $_SESSION['flash_msg'] = "Evento removido.";
        $_SESSION['flash_type'] = 'warning';
        header("Location: /?action=eventos");
        exit;
    }

    private function notifyParents($title, $date, $time, $location) {
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
            error_log("[e-cardeneta] Notificação de evento bloqueada fora do horário: {$horaAtual}");
            return; 
        }

        $formattedDate = date('d/m/Y', strtotime($date));

        $msg = "*E-Cardeneta - Convite para Evento*\n\n🌟 *{$title}*\n📅 Data: {$formattedDate}\n🕒 Hora: {$time}\n📍 Local: {$location}\n\nEsperamos por si!";

        $stmtPhones = $this->db->query("SELECT phone FROM users WHERE role = 'parent' AND phone IS NOT NULL AND phone != ''");
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
