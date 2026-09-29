<?php
namespace Controllers;
use Config\Database;
use Config\Security;

class SettingsController {
    public function index() {
        global $systemSettings;
        $viewFile = __DIR__ . '/../views/settings.php';
        require __DIR__ . '/../views/layout.php';
    }

    public function update($data) {
        Security::verifyCSRFToken($data['csrf_token'] ?? '');
        $db = Database::getConnection();

        // Limita a configuração de configurações conhecidas
        $allowed = ['color_primary', 'color_secondary', 'logo_path', 'whatsapp_api_url', 'whatsapp_api_token', 'business_hour_start', 'business_hour_end'];

        foreach ($allowed as $key) {
            if (isset($data[$key])) {
                $value = Security::sanitizeInput($data[$key]);
                $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$key, $value, $value]);
            }
        }

        $_SESSION['flash_msg'] = 'Configurações atualizadas com sucesso!';
        $_SESSION['flash_type'] = 'success';
        header("Location: /?action=settings");
        exit;
    }
}
