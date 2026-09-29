<?php
namespace Controllers;
use Config\Database;
use Config\Security;

class LembretesController {
    private $db;
    public function __construct() { $this->db = Database::getConnection(); }

    public function index() {
        $role   = $_SESSION['user_role'] ?? '';
        $userId = $_SESSION['user_id']   ?? 0;
        $isParent = in_array($role, ['parent', 'baba']);

        if ($isParent) {
            // Pais vêem lembretes dirigidos a si ou à turma dos seus filhos
            $stmt = $this->db->prepare("
                SELECT r.*, u.name as author_name, c.name as class_name, u2.name as target_name
                FROM reminders r
                JOIN users u ON u.id = r.author_id
                LEFT JOIN classes c ON c.id = r.target_class_id
                LEFT JOIN users u2 ON u2.id = r.target_user_id
                WHERE (r.target_user_id = ?
                   OR r.target_class_id IN (
                       SELECT class_id FROM children ch
                       JOIN parent_child pc ON pc.child_id = ch.id
                       WHERE pc.parent_id = ?
                   )
                   OR (r.target_user_id IS NULL AND r.target_class_id IS NULL))
                   AND r.type = 'external'
                ORDER BY r.created_at DESC
            ");
            $stmt->execute([$userId, $userId]);
        } elseif ($role === 'super_admin' || $role === 'admin') {
            $stmt = $this->db->query("
                SELECT r.*, u.name as author_name, c.name as class_name, u2.name as target_name
                FROM reminders r
                JOIN users u ON u.id = r.author_id
                LEFT JOIN classes c ON c.id = r.target_class_id
                LEFT JOIN users u2 ON u2.id = r.target_user_id
                ORDER BY r.created_at DESC
            ");
        } else {
            $stmt = $this->db->prepare("
                SELECT r.*, u.name as author_name, c.name as class_name, u2.name as target_name
                FROM reminders r
                JOIN users u ON u.id = r.author_id
                LEFT JOIN classes c ON c.id = r.target_class_id
                LEFT JOIN users u2 ON u2.id = r.target_user_id
                WHERE r.author_id = ? OR r.target_class_id IN (SELECT id FROM classes WHERE professor_id = ?)
                ORDER BY r.created_at DESC
            ");
            $stmt->execute([$userId, $userId]);
        }
        $reminders = $stmt->fetchAll();

        $classes = $this->db->query("SELECT id, name FROM classes ORDER BY name")->fetchAll();
        $parents = $this->db->query("SELECT id, name FROM users WHERE role = 'parent' ORDER BY name")->fetchAll();

        $viewFile = __DIR__ . '/../views/reminders.php';
        require __DIR__ . '/../views/layout.php';
    }

    public function create($data) {
        Security::requireAuth(['super_admin', 'admin', 'professor']);
        Security::verifyCSRFToken($data['csrf_token'] ?? '');

        $content = Security::sanitizeInput($data['content']);
        $type    = $data['reminder_type'] ?? 'external';
        $classId = !empty($data['class_id']) ? (int)$data['class_id'] : null;
        $userId  = !empty($data['user_id'])  ? (int)$data['user_id']  : null;
        $sendWA  = ($type === 'external' && !empty($data['send_whatsapp']));

        if (empty($content)) {
            $_SESSION['flash_msg'] = 'O conteúdo do lembrete é obrigatório.';
            $_SESSION['flash_type'] = 'danger';
            header("Location: /?action=lembretes"); exit;
        }

        $stmt = $this->db->prepare("INSERT INTO reminders (author_id, type, content, target_class_id, target_user_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $type, $content, $classId, $userId]);

        if ($sendWA) {
            $this->notifyWhatsApp($content, $classId, $userId);
        }

        $_SESSION['flash_msg'] = "Lembrete registado!";
        $_SESSION['flash_type'] = 'success';
        header("Location: /?action=lembretes"); exit;
    }

    public function delete($data) {
        Security::requireAuth(['super_admin', 'admin', 'professor']);
        Security::verifyCSRFToken($data['csrf_token'] ?? '');
        $id = (int)$data['id'];
        
        $this->db->prepare("DELETE FROM reminders WHERE id = ?")->execute([$id]);

        $_SESSION['flash_msg'] = "Lembrete removido.";
        $_SESSION['flash_type'] = 'warning';
        header("Location: /?action=lembretes"); exit;
    }

    private function notifyWhatsApp($content, $classId, $userId) {
        $stmt = $this->db->query("SELECT setting_key, setting_value FROM settings");
        $cfg = [];
        while($row = $stmt->fetch()) { $cfg[$row['setting_key']] = $row['setting_value']; }
        $apiUrl   = $cfg['whatsapp_api_url']   ?? 'http://127.0.0.1:3000/send';
        $apiToken = $cfg['whatsapp_api_token'] ?? 'e_cardeneta_super_secure_key_32b';

        // Verificar horário de funcionamento
        $horaInicio = $cfg['business_hour_start'] ?? '08:00';
        $horaFim    = $cfg['business_hour_end']   ?? '18:00';
        $horaAtual  = date('H:i');
        if ($horaAtual < $horaInicio || $horaAtual > $horaFim) {
            error_log("[e-cardeneta] WhatsApp bloqueado fora do horário: {$horaAtual} (permitido {$horaInicio}-{$horaFim})");
            return; // Não envia fora do horário de funcioamento
        }

        $msg = "*E-Cardeneta - Lembrete*\n\n🔔 {$content}";

        $phones = [];
        if ($userId) {
            $r = $this->db->prepare("SELECT phone FROM users WHERE id = ? AND phone IS NOT NULL AND phone != ''");
            $r->execute([$userId]);
            if ($row = $r->fetch()) $phones[] = $row['phone'];
        } elseif ($classId) {
            $r = $this->db->prepare("SELECT DISTINCT u.phone FROM users u JOIN parent_child pc ON pc.parent_id = u.id JOIN children ch ON ch.id = pc.child_id WHERE ch.class_id = ? AND u.phone IS NOT NULL AND u.phone != ''");
            $r->execute([$classId]);
            $phones = array_column($r->fetchAll(), 'phone');
        } else {
            $r = $this->db->query("SELECT phone FROM users WHERE role IN ('parent','baba') AND phone IS NOT NULL AND phone != ''");
            $phones = array_column($r->fetchAll(), 'phone');
        }

        foreach ($phones as $phone) {
            $payload = json_encode(['token' => $apiToken, 'number' => $phone, 'message' => $msg]);
            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_TIMEOUT => 3]);
            curl_exec($ch);
            curl_close($ch);
        }
    }
}
