<?php
require_once __DIR__ . '/../config/db.php';
use Config\Database;

try {
    $db = Database::getConnection();
    echo "Iniciando migração de tabelas em falta...\n";

    // 1. Tabela de Eventos
    $db->exec("
        CREATE TABLE IF NOT EXISTS events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            event_date DATE NOT NULL,
            event_time TIME NOT NULL,
            location VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;
    ");
    echo "✅ Tabela 'events' criada.\n";

    // 2. Tabela de Confirmações de Eventos
    $db->exec("
        CREATE TABLE IF NOT EXISTS event_confirmations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            user_id INT NOT NULL,
            status ENUM('confirmed', 'declined', 'maybe') DEFAULT 'confirmed',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_event_user (event_id, user_id)
        ) ENGINE=InnoDB;
    ");
    echo "✅ Tabela 'event_confirmations' criada.\n";

    // 3. Tabela de Atividades
    $db->exec("
        CREATE TABLE IF NOT EXISTS activities (
            id INT AUTO_INCREMENT PRIMARY KEY,
            class_id INT,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            activity_date DATE NOT NULL,
            image_path VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL
        ) ENGINE=InnoDB;
    ");
    echo "✅ Tabela 'activities' criada.\n";

    // 4. Tabela de Registos de Atividades (Individual)
    $db->exec("
        CREATE TABLE IF NOT EXISTS activity_registrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            activity_id INT NOT NULL,
            child_id INT NOT NULL,
            notes TEXT,
            FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE,
            FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE
        ) ENGINE=InnoDB;
    ");
    echo "✅ Tabela 'activity_registrations' criada.\n";

    echo "\nMigração concluída com sucesso!";

} catch (Exception $e) {
    echo "❌ Erro na migração: " . $e->getMessage();
}
