<?php
namespace Controllers;
use Config\Database;
use Config\Security;

class TurmasController {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function index() {
        $stmt = $this->db->query("
            SELECT c.*, u.name as professor_name,
                   COUNT(ch.id) as total_children
            FROM classes c
            LEFT JOIN users u ON c.professor_id = u.id
            LEFT JOIN children ch ON ch.class_id = c.id
            GROUP BY c.id
            ORDER BY c.name ASC
        ");
        $turmas = $stmt->fetchAll();

        // Para o select de professores no formulário de criação
        $stmt2 = $this->db->query("SELECT id, name FROM users WHERE role = 'professor' ORDER BY name");
        $professors = $stmt2->fetchAll();

        $viewFile = __DIR__ . '/../views/turmas.php';
        require __DIR__ . '/../views/layout.php';
    }

    public function create($data) {
        Security::verifyCSRFToken($data['csrf_token'] ?? '');
        $name = Security::sanitizeInput($data['name']);
        $professor_id = !empty($data['professor_id']) ? (int)$data['professor_id'] : null;

        if (empty($name)) {
            $_SESSION['flash_msg'] = 'Nome da turma é obrigatório.';
            $_SESSION['flash_type'] = 'danger';
            header("Location: /?action=turmas");
            exit;
        }

        $stmt = $this->db->prepare("INSERT INTO classes (name, professor_id) VALUES (?, ?)");
        $stmt->execute([$name, $professor_id]);

        $_SESSION['flash_msg'] = "Turma \"$name\" criada com sucesso!";
        $_SESSION['flash_type'] = 'success';
        header("Location: /?action=turmas");
        exit;
    }

    public function edit($data) {
        Security::verifyCSRFToken($data['csrf_token'] ?? '');
        $id = (int)$data['id'];
        $name = Security::sanitizeInput($data['name']);
        $professor_id = !empty($data['professor_id']) ? (int)$data['professor_id'] : null;

        $stmt = $this->db->prepare("UPDATE classes SET name = ?, professor_id = ? WHERE id = ?");
        $stmt->execute([$name, $professor_id, $id]);

        $_SESSION['flash_msg'] = "Turma atualizada com sucesso!";
        $_SESSION['flash_type'] = 'success';
        header("Location: /?action=turmas");
        exit;
    }

    public function delete($data) {
        Security::verifyCSRFToken($data['csrf_token'] ?? '');
        $id = (int)$data['id'];
        $stmt = $this->db->prepare("DELETE FROM classes WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['flash_msg'] = "Turma removida.";
        $_SESSION['flash_type'] = 'warning';
        header("Location: /?action=turmas");
        exit;
    }
}
