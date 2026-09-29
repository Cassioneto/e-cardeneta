<?php
require_once __DIR__ . '/../config/db.php';
use Config\Database;

try {
    $db = Database::getConnection();
    $db->exec("
        CREATE TABLE IF NOT EXISTS reminders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            author_id INT NOT NULL,
            type ENUM('internal', 'external') DEFAULT 'external',
            content TEXT NOT NULL,
            target_class_id INT NULL,
            target_user_id INT NULL,
            scheduled_at TIMESTAMP NULL,
            sent BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (target_class_id) REFERENCES classes(id) ON DELETE SET NULL,
            FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ");
    echo "Tabela reminders criada com sucesso.";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
