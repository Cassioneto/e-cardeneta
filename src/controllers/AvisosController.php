<?php
namespace Controllers;
use Config\Database;
use Config\Security;

class AvisosController {
    private $db;
    public function __construct() { $this->db = Database::getConnection(); }

    public function index() {
        $role = $_SESSION['user_role'] ?? '';
        $userId = $_SESSION['user_id'] ?? 0;

        if (in_array($role, ['parent', 'baba'])) {
            $stmt = $this->db->prepare("
                SELECT c.*, u.name as sender_name,
                       u2.name as receiver_name, cl.name as class_name
                FROM communications c
                JOIN users u ON u.id = c.sender_id
                LEFT JOIN users u2 ON u2.id = c.receiver_id
                LEFT JOIN classes cl ON cl.id = c.class_id
                WHERE c.type IN ('warning','announcement','reminder','task','event')
                AND (
                    c.receiver_id = ? 
                    OR c.class_id IN (SELECT class_id FROM children ch JOIN parent_child pc ON pc.child_id = ch.id WHERE pc.parent_id = ?)
                    OR (c.receiver_id IS NULL AND c.class_id IS NULL)
                )
                ORDER BY c.created_at DESC LIMIT 50
            ");
            $stmt->execute([$userId, $userId]);
            $avisos = $stmt->fetchAll();
        } else {
            $avisos = $this->db->query("
                SELECT c.*, u.name as sender_name,
                       u2.name as receiver_name, cl.name as class_name
                FROM communications c
                JOIN users u ON u.id = c.sender_id
                LEFT JOIN users u2 ON u2.id = c.receiver_id
                LEFT JOIN classes cl ON cl.id = c.class_id
                WHERE c.type IN ('warning','announcement','reminder','task','event')
                ORDER BY c.created_at DESC LIMIT 50
            ")->fetchAll();
        }

        $turmas = $this->db->query("SELECT id, name FROM classes ORDER BY name")->fetchAll();
        $pais   = $this->db->query("SELECT id, name FROM users WHERE role IN ('parent','baba') ORDER BY name")->fetchAll();

        $viewFile = __DIR__ . '/../views/avisos.php';
        require __DIR__ . '/../views/layout.php';
    }

    public function create($data) {
        Security::requireAuth(['super_admin','admin','professor']);
        Security::verifyCSRFToken($data['csrf_token'] ?? '');
        $type         = in_array($data['type'], ['warning','announcement','reminder','task','event']) ? $data['type'] : 'warning';
        $content      = Security::sanitizeInput($data['content']);
        $target_type  = $data['target_type'] ?? 'all';
        $class_id     = !empty($data['class_id'])    ? (int)$data['class_id']    : null;
        $receiver_id  = !empty($data['receiver_id']) ? (int)$data['receiver_id'] : null;
        $sendWA       = !empty($data['send_whatsapp']);

        if ($target_type !== 'class')  { $class_id    = null; }
        if ($target_type !== 'parent') { $receiver_id = null; }

        if (empty($content)) {
            $_SESSION['flash_msg'] = 'A mensagem não pode estar vazia.';
            $_SESSION['flash_type'] = 'danger';
            header("Location: /?action=avisos"); exit;
        }

        $stmt = $this->db->prepare("
            INSERT INTO communications (sender_id, receiver_id, class_id, type, content, sent_via_whatsapp)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $receiver_id, $class_id, $type, $content, $sendWA ? 1 : 0]);

        // Disparar WhatsApp se pedido
        if ($sendWA) {
            $this->dispatchWhatsApp($target_type, $class_id, $receiver_id, $content, $type);
        }

        $_SESSION['flash_msg'] = 'Aviso enviado com sucesso!';
        $_SESSION['flash_type'] = 'success';
        header("Location: /?action=avisos"); exit;
    }

    private function dispatchWhatsApp($target_type, $class_id, $receiver_id, $content, $type) {
        $settings = $this->db->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
        $cfg = [];
        foreach ($settings as $s) { $cfg[$s['setting_key']] = $s['setting_value']; }
        $apiUrl   = $cfg['whatsapp_api_url']   ?? 'http://127.0.0.1:3000/send';
        $apiToken = $cfg['whatsapp_api_token'] ?? 'e_cardeneta_super_secure_key_32b';

        // Buscar números a notificar
        $phones = [];
        if ($target_type === 'parent' && $receiver_id) {
            $r = $this->db->prepare("SELECT phone FROM users WHERE id = ? AND phone != ''");
            $r->execute([$receiver_id]);
            $row = $r->fetch();
            if ($row) $phones[] = $row['phone'];
        } elseif ($target_type === 'class' && $class_id) {
            $r = $this->db->prepare("
                SELECT DISTINCT u.phone FROM users u
                JOIN parent_child pc ON pc.parent_id = u.id
                JOIN children ch ON ch.id = pc.child_id
                WHERE ch.class_id = ? AND u.phone != ''
            ");
            $r->execute([$class_id]);
            $phones = array_column($r->fetchAll(), 'phone');
        } else {
            $r = $this->db->query("SELECT phone FROM users WHERE role IN ('parent','baba') AND phone != ''");
            $phones = array_column($r->fetchAll(), 'phone');
        }

        $typeLabel = match($type) {
            'warning'      => '⚠️ Aviso',
            'announcement' => '📢 Comunicado',
            'reminder'     => '🔔 Lembrete',
            'task'         => '✅ Tarefa',
            'event'        => '📅 Evento',
            default        => 'ℹ️',
        };
        $msg = "*E-Cardeneta – $typeLabel*\n\n$content";

        foreach ($phones as $phone) {
            $payload = json_encode(['token' => $apiToken, 'number' => $phone, 'message' => $msg]);
            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_HTTPHEADER=>['Content-Type: application/json'], CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>$payload, CURLOPT_TIMEOUT=>4]);
            curl_exec($ch);
            curl_close($ch);
        }
    }
}
