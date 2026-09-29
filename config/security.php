<?php
namespace Config;

class Security {
    // Chave de criptografia (Em produção, deve estar em variáveis de ambiente)
    private static $encryption_key = 'e_cardeneta_super_secure_key_32b'; 
    private static $cipher_algo = 'aes-256-cbc';

    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // OWASP A05: Security Misconfiguration - secure sessions
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            // ini_set('session.cookie_secure', 1); // Descomentar se usar HTTPS (OWASP A02)
            session_start();
        }
    }

    public static function regenerateSession() {
        session_regenerate_id(true); // OWASP A07: Session Fixation protection
    }

    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            die('Erro de Validação CSRF. Possível ataque detectado.');
        }
    }

    public static function sanitizeInput($data) {
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8'); // OWASP A03: XSS Protection
    }

    // OWASP A02: Cryptographic Failures - Protect sensitive med data (LGPD)
    public static function encryptData($data) {
        $ivLength = openssl_cipher_iv_length(self::$cipher_algo);
        $iv = openssl_random_pseudo_bytes($ivLength);
        $encrypted = openssl_encrypt($data, self::$cipher_algo, self::$encryption_key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }

    public static function decryptData($data) {
        list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
        return openssl_decrypt($encrypted_data, self::$cipher_algo, self::$encryption_key, 0, $iv);
    }
    
    public static function requireAuth($allowedRoles = []) {
        self::startSession();
        if (!isset($_SESSION['user_id'])) {
            header("Location: /?action=login");
            exit;
        }
        
        if (!empty($allowedRoles) && !in_array($_SESSION['user_role'], $allowedRoles)) {
            die('Acesso negado. Privilégios insuficientes.'); // OWASP A01: Broken Access Control
        }
    }
}
