-- banco de dados e-cardeneta
CREATE DATABASE IF NOT EXISTS ecardeneta;
USE ecardeneta;

-- Tabela de Usuários (Admin, Professor, Pais/Responsáveis)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'professor', 'parent') NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Turmas
CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    professor_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabela de Crianças
CREATE TABLE IF NOT EXISTS children (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    birth_date DATE NOT NULL,
    class_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL
);

-- Relacionamento Responsável <-> Criança
CREATE TABLE IF NOT EXISTS parent_child (
    parent_id INT NOT NULL,
    child_id INT NOT NULL,
    relationship_type ENUM('mother', 'father', 'nanny', 'other') NOT NULL,
    PRIMARY KEY (parent_id, child_id),
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE
);

-- Tabela de Saúde e Observações (Dados Sensiveis, em produção os campos sensiveis estarão encriptados)
CREATE TABLE IF NOT EXISTS health_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    child_id INT NOT NULL,
    record_type ENUM('allergy', 'food_restriction', 'vaccine', 'observation', 'medicine') NOT NULL,
    description TEXT NOT NULL, -- será salvo criptografado na aplicação
    author_id INT NOT NULL,
    date_recorded TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(id)
);

-- Medicamentos/Administração
CREATE TABLE IF NOT EXISTS medicine_administrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    health_record_id INT NOT NULL,
    scheduled_time DATETIME NOT NULL,
    administered BOOLEAN DEFAULT FALSE,
    administered_at DATETIME NULL,
    administered_by_id INT NULL,
    FOREIGN KEY (health_record_id) REFERENCES health_records(id) ON DELETE CASCADE,
    FOREIGN KEY (administered_by_id) REFERENCES users(id)
);

-- Tabela de Mensagens e Avisos
CREATE TABLE IF NOT EXISTS communications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NULL, -- NULL significa que é geral ou para turma
    class_id INT NULL,
    type ENUM('message', 'warning', 'announcement', 'reminder', 'task', 'event', 'meal') NOT NULL,
    content TEXT NOT NULL,
    sent_via_whatsapp BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (receiver_id) REFERENCES users(id),
    FOREIGN KEY (class_id) REFERENCES classes(id)
);

-- Tabela de Refeições
CREATE TABLE IF NOT EXISTS meals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    description TEXT NOT NULL,
    meal_type ENUM('breakfast', 'lunch', 'snack', 'dinner') NOT NULL,
    scheduled_date DATE NOT NULL,
    scheduled_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inserir Admin inicial (senha: admin123)
-- bcrypt hash gerado para 'admin123'
INSERT INTO users (name, email, password_hash, role) 
VALUES ('Administrador', 'admin@ecardeneta.com', '$2y$12$B7q8GTxvNEOgooRhlS0sC.N4GdO/ZzkxMi0AHGLFrnTLiztaa/L6.', 'admin')
ON DUPLICATE KEY UPDATE name=name;
