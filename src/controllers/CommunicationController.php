<?php
namespace Controllers;

use Config\Database;
use Config\Security;

class CommunicationController {
    
    public function send($data) {
        Security::requireAuth();
        Security::verifyCSRFToken($data['csrf_token'] ?? '');

        $target  = Security::sanitizeInput($data['target']);
        $message = Security::sanitizeInput($data['message']);

        if (empty($message)) {
            $_SESSION['flash_msg'] = 'A mensagem não pode estar vazia.';
            $_SESSION['flash_type'] = 'danger';
            header("Location: /?action=dashboard"); exit;
        }

        $db = Database::getConnection();

        // Obter configurações da API WhatsApp da base de dados
        $cfgStmt = $db->query("SELECT setting_key, setting_value FROM settings");
        $cfg = [];
        while ($row = $cfgStmt->fetch()) { $cfg[$row['setting_key']] = $row['setting_value']; }
        $nodeApiUrl   = $cfg['whatsapp_api_url']   ?? 'http://127.0.0.1:3000/send';
        $secretToken  = $cfg['whatsapp_api_token'] ?? 'e_cardeneta_super_secure_key_32b';

        // Salvar comunicação na BD
        $stmt = $db->prepare("INSERT INTO communications (sender_id, type, content, sent_via_whatsapp) VALUES (?, 'message', ?, 1)");
        $stmt->execute([$_SESSION['user_id'], $message]);

        // Buscar números reais
        $phones = [];
        if (is_numeric($target)) {
            // Mensagem para utilizador específico
            $r = $db->prepare("SELECT phone FROM users WHERE id = ? AND phone IS NOT NULL AND phone != ''");
            $r->execute([(int)$target]);
            $row = $r->fetch();
            if ($row) $phones[] = $row['phone'];
        } else {
            // Mensagem para todos os pais/babás
            $r = $db->query("SELECT phone FROM users WHERE role IN ('parent','baba') AND phone IS NOT NULL AND phone != ''");
            $phones = array_column($r->fetchAll(), 'phone');
        }

        $sent  = 0;
        $errors = 0;
        $msgFormatted = "*E-Cardeneta* [{$_SESSION['user_name']}]:\n\n{$message}";

        foreach ($phones as $phone) {
            $payload = json_encode(['token' => $secretToken, 'number' => $phone, 'message' => $msgFormatted]);
            $ch = curl_init($nodeApiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_TIMEOUT        => 5,
            ]);
            $response = json_decode(curl_exec($ch), true);
            curl_close($ch);
            if (!empty($response['success'])) { $sent++; } else { $errors++; }
        }

        if ($sent > 0) {
            $_SESSION['flash_msg'] = "Mensagem enviada via WhatsApp para {$sent} destinatário(s).";
            $_SESSION['flash_type'] = 'success';
        } elseif (empty($phones)) {
            $_SESSION['flash_msg'] = 'Nenhum número de WhatsApp registado para envio.';
            $_SESSION['flash_type'] = 'warning';
        } else {
            $_SESSION['flash_msg'] = "Mensagem guardada, mas ocorreram erros no envio WhatsApp ({$errors} falha(s)).";
            $_SESSION['flash_type'] = 'warning';
        }

        header("Location: /?action=dashboard"); exit;
    }
}
