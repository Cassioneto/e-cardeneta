<?php
require __DIR__ . "/../config/db.php";
$db = \Config\Database::getConnection();

try {
    // Atualiza o ENUM na tabela users
    $db->exec("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin', 'professor', 'parent', 'baba') NOT NULL;");
    
    // Cria tabela de configurações do sistema (cores, logo, whatsapp)
    $db->exec("
        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value VARCHAR(255) NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );
    ");

    // Preenche cores padrões
    $db->exec("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('color_primary', '#6C5CE7');");
    $db->exec("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('color_secondary', '#FD79A8');");
    $db->exec("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('logo_path', '');");
    $db->exec("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('whatsapp_api_url', 'http://127.0.0.1:3000/send');");
    $db->exec("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('whatsapp_api_token', 'e_cardeneta_super_secure_key_32b');");

    echo "Migration fix0.1 executed successfully!\n";
} catch (Exception $e) {
    echo "Error running migration: " . $e->getMessage() . "\n";
}
