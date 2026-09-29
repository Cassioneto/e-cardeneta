<?php
namespace Controllers;
use Config\Database;
use Config\Security;

class UserController {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function index() {
        Security::requireAuth(['super_admin', 'admin']);
        $stmt = $this->db->query("SELECT id, name, email, role, phone, created_at FROM users ORDER BY created_at DESC");
        $users = $stmt->fetchAll();

        $viewFile = __DIR__ . '/../views/users.php';
        require __DIR__ . '/../views/layout.php';
    }

    public function create($data) {
        Security::requireAuth(['super_admin', 'admin']);
        Security::verifyCSRFToken($data['csrf_token'] ?? '');

        $name  = Security::sanitizeInput($data['name']);
        $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
        $role  = in_array($data['role'], ['super_admin','admin','professor','parent','baba'])
                    ? $data['role'] : 'parent';
        $phone = Security::sanitizeInput($data['phone'] ?? '');
        $password = $data['password'] ?? '';

        if (!$email || empty($name) || strlen($password) < 8) {
            $_SESSION['flash_msg'] = 'Preencha todos os campos. A senha deve ter pelo menos 8 caracteres.';
            $_SESSION['flash_type'] = 'danger';
            header("Location: /?action=users");
            exit;
        }

        // Verificar se email já existe (OWASP A07 – evitar enumeration mas aqui é admin)
        $check = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $_SESSION['flash_msg'] = 'Este e-mail já está cadastrado no sistema.';
            $_SESSION['flash_type'] = 'danger';
            header("Location: /?action=users");
            exit;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password_hash, role, phone) VALUES (?,?,?,?,?)");
        $stmt->execute([$name, $email, $hash, $role, $phone]);

        $_SESSION['flash_msg'] = "Utilizador \"$name\" criado com sucesso!";
        $_SESSION['flash_type'] = 'success';
        header("Location: /?action=users");
        exit;
    }

    public function delete($data) {
        Security::requireAuth(['super_admin', 'admin']);
        Security::verifyCSRFToken($data['csrf_token'] ?? '');
        $id = (int)$data['id'];
        // Nunca apagar a si próprio
        if ($id === (int)$_SESSION['user_id']) {
            $_SESSION['flash_msg'] = 'Não pode apagar o seu próprio utilizador.';
            $_SESSION['flash_type'] = 'danger';
            header("Location: /?action=users");
            exit;
        }
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['flash_msg'] = 'Utilizador removido.';
        $_SESSION['flash_type'] = 'warning';
        header("Location: /?action=users");
        exit;
    }
}
