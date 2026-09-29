<?php
namespace Controllers;
use Config\Database;
use Config\Security;

class ParentsController {
    private $db;
    public function __construct() { $this->db = Database::getConnection(); }

    public function index() {
        $stmt = $this->db->query("
            SELECT u.*,
                   GROUP_CONCAT(DISTINCT ch.name ORDER BY ch.name SEPARATOR ', ') as children_names
            FROM users u
            LEFT JOIN parent_child pc ON pc.parent_id = u.id
            LEFT JOIN children ch ON ch.id = pc.child_id
            WHERE u.role IN ('parent','baba')
            GROUP BY u.id
            ORDER BY u.name ASC
        ");
        $parents = $stmt->fetchAll();

        $allChildren = $this->db->query("SELECT id, name FROM children ORDER BY name")->fetchAll();

        $viewFile = __DIR__ . '/../views/parents.php';
        require __DIR__ . '/../views/layout.php';
    }

    public function linkChild($data) {
        Security::verifyCSRFToken($data['csrf_token'] ?? '');
        $parent_id       = (int)$data['parent_id'];
        $child_id        = (int)$data['child_id'];
        $relationship    = in_array($data['relationship'], ['mother','father','nanny','other'])
                            ? $data['relationship'] : 'other';

        $stmt = $this->db->prepare("INSERT IGNORE INTO parent_child (parent_id, child_id, relationship_type) VALUES (?,?,?)");
        $stmt->execute([$parent_id, $child_id, $relationship]);

        $_SESSION['flash_msg'] = 'Associação criança-responsável guardada.';
        $_SESSION['flash_type'] = 'success';
        header("Location: /?action=parents"); exit;
    }
}
