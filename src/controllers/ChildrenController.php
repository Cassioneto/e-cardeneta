<?php
namespace Controllers;
use Config\Database;
use Config\Security;

class ChildrenController {
    private $db;
    public function __construct() { $this->db = Database::getConnection(); }

    public function index() {
        $stmt = $this->db->query("
            SELECT ch.*, c.name as class_name,
                   GROUP_CONCAT(DISTINCT u.name ORDER BY u.name SEPARATOR ', ') as parents
            FROM children ch
            LEFT JOIN classes c ON ch.class_id = c.id
            LEFT JOIN parent_child pc ON pc.child_id = ch.id
            LEFT JOIN users u ON u.id = pc.parent_id
            GROUP BY ch.id
            ORDER BY ch.name ASC
        ");
        $children = $stmt->fetchAll();

        $classes = $this->db->query("SELECT id, name FROM classes ORDER BY name")->fetchAll();

        $viewFile = __DIR__ . '/../views/children.php';
        require __DIR__ . '/../views/layout.php';
    }

    public function create($data) {
        Security::verifyCSRFToken($data['csrf_token'] ?? '');
        $name       = Security::sanitizeInput($data['name']);
        $birth_date = $data['birth_date'] ?? '';
        $class_id   = !empty($data['class_id']) ? (int)$data['class_id'] : null;

        if (empty($name) || empty($birth_date)) {
            $_SESSION['flash_msg'] = 'Nome e data de nascimento são obrigatórios.';
            $_SESSION['flash_type'] = 'danger';
            header("Location: /?action=children"); exit;
        }

        $stmt = $this->db->prepare("INSERT INTO children (name, birth_date, class_id) VALUES (?,?,?)");
        $stmt->execute([$name, $birth_date, $class_id]);

        $_SESSION['flash_msg'] = "Criança \"$name\" registada com sucesso!";
        $_SESSION['flash_type'] = 'success';
        header("Location: /?action=children"); exit;
    }

    public function edit($data) {
        Security::verifyCSRFToken($data['csrf_token'] ?? '');
        $id         = (int)$data['id'];
        $name       = Security::sanitizeInput($data['name']);
        $birth_date = $data['birth_date'] ?? '';
        $class_id   = !empty($data['class_id']) ? (int)$data['class_id'] : null;

        $stmt = $this->db->prepare("UPDATE children SET name=?, birth_date=?, class_id=? WHERE id=?");
        $stmt->execute([$name, $birth_date, $class_id, $id]);

        $_SESSION['flash_msg'] = "Dados de \"$name\" atualizados.";
        $_SESSION['flash_type'] = 'success';
        header("Location: /?action=children"); exit;
    }

    public function delete($data) {
        Security::verifyCSRFToken($data['csrf_token'] ?? '');
        $id = (int)$data['id'];
        $this->db->prepare("DELETE FROM children WHERE id=?")->execute([$id]);
        $_SESSION['flash_msg'] = 'Registo removido.';
        $_SESSION['flash_type'] = 'warning';
        header("Location: /?action=children"); exit;
    }
}
