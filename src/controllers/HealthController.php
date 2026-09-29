<?php
namespace Controllers;
use Config\Database;
use Config\Security;

/**
 * Controller genérico para registos de saúde:
 * allergy | food_restriction | vaccine | observation
 * (medicine é tratado pelo MedicamentosController)
 */
class HealthController {
    private $db;
    public function __construct() { $this->db = Database::getConnection(); }

    private static $titles = [
        'allergy'          => ['Alergias',               'alergias'],
        'food_restriction' => ['Restrições Alimentares',  'alergias'],
        'vaccine'          => ['Vacinas',                 'vacinas'],
        'observation'      => ['Observações de Saúde',   'observacoes'],
    ];

    public function index(string $type) {
        $role   = $_SESSION['user_role'] ?? '';
        $userId = $_SESSION['user_id']   ?? 0;
        $isParent = in_array($role, ['parent', 'baba']);

        if ($isParent) {
            $stmt = $this->db->prepare("
                SELECT hr.*, ch.name as child_name, u.name as author_name
                FROM health_records hr
                JOIN children ch ON ch.id = hr.child_id
                JOIN users u ON u.id = hr.author_id
                WHERE hr.record_type = ?
                AND ch.id IN (SELECT child_id FROM parent_child WHERE parent_id = ?)
                ORDER BY hr.date_recorded DESC
            ");
            $stmt->execute([$type, $userId]);
            // Crianças do pai para o formulário
            $childrenStmt = $this->db->prepare("SELECT ch.id, ch.name FROM children ch JOIN parent_child pc ON pc.child_id = ch.id WHERE pc.parent_id = ? ORDER BY ch.name");
            $childrenStmt->execute([$userId]);
            $children = $childrenStmt->fetchAll();
        } else {
            $stmt = $this->db->prepare("
                SELECT hr.*, ch.name as child_name, u.name as author_name
                FROM health_records hr
                JOIN children ch ON ch.id = hr.child_id
                JOIN users u ON u.id = hr.author_id
                WHERE hr.record_type = ?
                ORDER BY hr.date_recorded DESC
            ");
            $stmt->execute([$type]);
            $children = $this->db->query("SELECT id, name FROM children ORDER BY name")->fetchAll();
        }
        $records = $stmt->fetchAll();

        // Decrypt dados sensíveis
        foreach ($records as &$r) {
            try { $r['description'] = Security::decryptData($r['description']); }
            catch (\Exception $e) {}
        }
        unset($r);
        [$pageTitle, $backAction] = self::$titles[$type] ?? ['Registo de Saúde', 'dashboard'];

        $viewFile = __DIR__ . '/../views/health_record.php';
        require __DIR__ . '/../views/layout.php';
    }

    public function create($data, string $type) {
        Security::verifyCSRFToken($data['csrf_token'] ?? '');
        $child_id    = (int)$data['child_id'];
        $description = Security::sanitizeInput($data['description']);
        $role        = $_SESSION['user_role'] ?? '';
        $userId      = $_SESSION['user_id']   ?? 0;

        if (!$child_id || empty($description)) {
            $_SESSION['flash_msg'] = 'Preencha todos os campos.';
            $_SESSION['flash_type'] = 'danger';
            header("Location: /?action=" . self::$titles[$type][1] ?? 'dashboard'); exit;
        }

        // Pais só podem criar registos para os seus filhos
        if (in_array($role, ['parent', 'baba'])) {
            $chk = $this->db->prepare("SELECT COUNT(*) FROM parent_child WHERE parent_id = ? AND child_id = ?");
            $chk->execute([$userId, $child_id]);
            if ((int)$chk->fetchColumn() === 0) {
                $_SESSION['flash_msg'] = 'Não tem permissão para criar registos para esta criança.';
                $_SESSION['flash_type'] = 'danger';
                header("Location: /?action=" . (self::$titles[$type][1] ?? 'dashboard')); exit;
            }
        }

        // LGPD: Encriptar dados sensíveis de saúde (OWASP A02)
        $encrypted = Security::encryptData($description);
        $stmt = $this->db->prepare("INSERT INTO health_records (child_id, record_type, description, author_id) VALUES (?,?,?,?)");
        $stmt->execute([$child_id, $type, $encrypted, $_SESSION['user_id']]);

        $_SESSION['flash_msg'] = 'Registo guardado com sucesso!';
        $_SESSION['flash_type'] = 'success';
        header("Location: /?action=" . (self::$titles[$type][1] ?? 'dashboard')); exit;
    }

    public function delete($data, string $type) {
        Security::verifyCSRFToken($data['csrf_token'] ?? '');
        // Só pode apagar o próprio registo ou admin
        $id = (int)$data['id'];
        $role = $_SESSION['user_role'] ?? '';
        if (in_array($role, ['super_admin','admin'])) {
            $this->db->prepare("DELETE FROM health_records WHERE id = ? AND record_type = ?")->execute([$id, $type]);
        } else {
            $this->db->prepare("DELETE FROM health_records WHERE id = ? AND record_type = ? AND author_id = ?")->execute([$id, $type, $_SESSION['user_id']]);
        }
        $_SESSION['flash_msg'] = 'Registo removido.';
        $_SESSION['flash_type'] = 'warning';
        header("Location: /?action=" . (self::$titles[$type][1] ?? 'dashboard')); exit;
    }
}
