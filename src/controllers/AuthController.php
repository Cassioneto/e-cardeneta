<?php
namespace Controllers;
use Config\Database;
use Config\Security;

class AuthController {
    public function showLogin() {
        Security::startSession();
        if (isset($_SESSION['user_id'])) {
            header("Location: /?action=dashboard");
            exit;
        }
        $csrf_token = Security::generateCSRFToken();
        $viewFile = __DIR__ . '/../views/login.php';
        require __DIR__ . '/../views/layout.php';
    }

    public function login($email, $password, $csrf_token) {
        Security::startSession();
        Security::verifyCSRFToken($csrf_token); // OWASP A01/A05

        $db = Database::getConnection();
        
        // OWASP A03: SQL Injection Prevention with Prepared Statements
        $stmt = $db->prepare('SELECT id, name, password_hash, role FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        // Use password_verify para checagem segura contra timing attacks (OWASP A07 / A02)
        if ($user && password_verify($password, $user['password_hash'])) {
            Security::regenerateSession(); // OWASP A07: Fixation Protection
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            
            header("Location: /?action=dashboard");
            exit;
        } else {
            // OWASP A07: Mensagens genéricas para evitar Username Enumeration
            $_SESSION['flash_msg'] = 'E-mail ou senha incorretos.';
            $_SESSION['flash_type'] = 'danger';
            header("Location: /?action=login");
            exit;
        }
    }

    public function logout() {
        Security::startSession();
        session_unset();
        session_destroy();
        // OWASP A01: Clear session cookies
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        header("Location: /?action=login");
        exit;
    }
}
