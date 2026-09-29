<?php
require_once __DIR__ . '/../config/db.php';
use Config\Database;

try {
    $db = Database::getConnection();
    $db->exec("
        CREATE TABLE IF NOT EXISTS event_confirmations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT,
            user_id INT,
            status ENUM('confirmed', 'declined', 'maybe') DEFAULT 'confirmed',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_event_user (event_id, user_id)
        )
    ");
    echo "Tabela event_confirmations criada/verificada com sucesso.";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
