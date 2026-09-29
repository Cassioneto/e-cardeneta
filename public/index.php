<?php
// ════════════════════════════════════════════
//  E-Cardeneta – Front Controller
// ════════════════════════════════════════════
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/DashboardController.php';
require_once __DIR__ . '/../src/controllers/CommunicationController.php';
require_once __DIR__ . '/../src/controllers/SettingsController.php';
require_once __DIR__ . '/../src/controllers/UserController.php';
require_once __DIR__ . '/../src/controllers/TurmasController.php';
require_once __DIR__ . '/../src/controllers/ChildrenController.php';
require_once __DIR__ . '/../src/controllers/ParentsController.php';
require_once __DIR__ . '/../src/controllers/MedicamentosController.php';
require_once __DIR__ . '/../src/controllers/AvisosController.php';
require_once __DIR__ . '/../src/controllers/HealthController.php';
require_once __DIR__ . '/../src/controllers/MealsController.php';
require_once __DIR__ . '/../src/controllers/ActivitiesController.php';
require_once __DIR__ . '/../src/controllers/EventsController.php';
require_once __DIR__ . '/../src/controllers/LembretesController.php';
require_once __DIR__ . '/../src/controllers/ReportController.php';

use Config\Security;
use Config\Database;

Security::startSession();

// Carrega settings globais (cores, logo, etc.)
$systemSettings = [];
try {
    $db   = Database::getConnection();
    $rows = $db->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
    foreach ($rows as $r) { $systemSettings[$r['setting_key']] = $r['setting_value']; }
} catch (\Exception $e) {}

$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? 'login';
$op     = filter_input(INPUT_GET, 'op',     FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';

// ─── Helper para renderizar views sem controller especial ──────────────────
function renderView(string $view, array $vars = []): void {
    global $systemSettings;
    extract($vars);
    $viewFile = __DIR__ . '/../src/views/' . $view . '.php';
    require __DIR__ . '/../src/views/layout.php';
}

// ─── Instanciar controllers ────────────────────────────────────────────────
$auth         = new \Controllers\AuthController();
$dashboard    = new \Controllers\DashboardController();
$settings     = new \Controllers\SettingsController();
$users        = new \Controllers\UserController();
$turmas       = new \Controllers\TurmasController();
$children     = new \Controllers\ChildrenController();
$parents      = new \Controllers\ParentsController();
$medicamentos = new \Controllers\MedicamentosController();
$avisos       = new \Controllers\AvisosController();
$health       = new \Controllers\HealthController();
$comm         = new \Controllers\CommunicationController();
$meals        = new \Controllers\MealsController();
$activities   = new \Controllers\ActivitiesController();
$events       = new \Controllers\EventsController();
$lembretes   = new \Controllers\LembretesController();
$reports      = new \Controllers\ReportController();

// ─── Roteamento ────────────────────────────────────────────────────────────
try {
    $isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

    switch ($action) {

        // ── Autenticação ────────────────────────────────────────────────
        case 'login':
            $isPost
                ? $auth->login($_POST['email'], $_POST['password'], $_POST['csrf_token'] ?? '')
                : $auth->showLogin();
            break;

        case 'logout':
            $auth->logout();
            break;

        // ── Dashboard ───────────────────────────────────────────────────
        case 'dashboard':
            Security::requireAuth();
            $dashboard->index();
            break;

        // ── Configurações ───────────────────────────────────────────────
        case 'settings':
            Security::requireAuth(['super_admin','admin']);
            $isPost ? $settings->update($_POST) : $settings->index();
            break;

        // ── Utilizadores ────────────────────────────────────────────────
        case 'users':
            Security::requireAuth(['super_admin','admin']);
            if      ($op === 'create' && $isPost) { $users->create($_POST); }
            elseif  ($op === 'delete' && $isPost) { $users->delete($_POST); }
            else    { $users->index(); }
            break;

        // ── Turmas ──────────────────────────────────────────────────────
        case 'turmas':
            Security::requireAuth(['super_admin','admin']);
            if      ($op === 'create' && $isPost) { $turmas->create($_POST); }
            elseif  ($op === 'edit'   && $isPost) { $turmas->edit($_POST); }
            elseif  ($op === 'delete' && $isPost) { $turmas->delete($_POST); }
            else    { $turmas->index(); }
            break;

        // ── Crianças ────────────────────────────────────────────────────
        case 'children':
            Security::requireAuth(['super_admin','admin','professor']);
            if      ($op === 'create' && $isPost) { $children->create($_POST); }
            elseif  ($op === 'edit'   && $isPost) { $children->edit($_POST); }
            elseif  ($op === 'delete' && $isPost) { $children->delete($_POST); }
            else    { $children->index(); }
            break;

        // ── Pais / Responsáveis ─────────────────────────────────────────
        case 'parents':
            Security::requireAuth(['super_admin','admin']);
            if ($op === 'link' && $isPost) { $parents->linkChild($_POST); }
            else { $parents->index(); }
            break;

        // ── Professores (stub) ──────────────────────────────────────────
        case 'professors':
            Security::requireAuth(['super_admin','admin']);
            renderView('stub');
            break;

        // ── Medicamentos ────────────────────────────────────────────────
        case 'medicamentos':
            Security::requireAuth();
            if      ($op === 'create'     && $isPost) { $medicamentos->create($_POST); }
            elseif  ($op === 'administrar' && $isPost) { $medicamentos->marcarAdministrado($_POST); }
            else    { $medicamentos->index(); }
            break;

        // ── Avisos & Comunicados ────────────────────────────────────────
        case 'avisos':
            Security::requireAuth(['super_admin','admin','professor','parent','baba']);
            if ($op === 'create' && $isPost) { $avisos->create($_POST); }
            else { $avisos->index(); }
            break;

        // ── Mensagens ───────────────────────────────────────────────────
        case 'messages':
            Security::requireAuth();
            if ($op === 'send' && $isPost) { $comm->send($_POST); }
            else { renderView('messages'); }
            break;

        // ── Saúde: alergias, vacinas, observações ───────────────────────
        case 'alergias':
            Security::requireAuth();
            if      ($op === 'create' && $isPost) { $health->create($_POST, 'allergy'); }
            elseif  ($op === 'delete' && $isPost) { $health->delete($_POST, 'allergy'); }
            else    { $health->index('allergy'); }
            break;

        case 'vacinas':
            Security::requireAuth();
            if      ($op === 'create' && $isPost) { $health->create($_POST, 'vaccine'); }
            elseif  ($op === 'delete' && $isPost) { $health->delete($_POST, 'vaccine'); }
            else    { $health->index('vaccine'); }
            break;

        case 'observacoes':
            Security::requireAuth();
            if      ($op === 'create' && $isPost) { $health->create($_POST, 'observation'); }
            elseif  ($op === 'delete' && $isPost) { $health->delete($_POST, 'observation'); }
            else    { $health->index('observation'); }
            break;

        // ── Operacional (stubs aguardam próximas iterações) ─────────────
        case 'refeicoes':
            Security::requireAuth(['super_admin','admin','professor','parent','baba']);
            if ($op === 'create' && $isPost) { $meals->create($_POST); }
            elseif ($op === 'delete' && $isPost) { $meals->delete($_POST); }
            else { $meals->index(); }
            break;

        case 'atividades':
            Security::requireAuth(['super_admin','admin','professor','parent','baba']);
            if ($op === 'create' && $isPost) { $activities->create($_POST); }
            else { $activities->index(); }
            break;

        case 'eventos':
            Security::requireAuth();
            if ($op === 'create' && $isPost) { $events->create($_POST); }
            elseif ($op === 'delete' && $isPost) { $events->delete($_POST); }
            elseif ($op === 'responder' && $isPost) { $events->responder($_POST); }
            else { $events->index(); }
            break;

        case 'lembretes':
            Security::requireAuth(['super_admin', 'admin', 'professor', 'parent', 'baba']);
            if ($op === 'create' && $isPost) { $lembretes->create($_POST); }
            elseif ($op === 'delete' && $isPost) { $lembretes->delete($_POST); }
            else { $lembretes->index(); }
            break;

        case 'reports':
            Security::requireAuth(['super_admin', 'admin']);
            $reports->index();
            break;

        default:
            http_response_code(404);
            echo "<h1 style='font-family:sans-serif;padding:2rem'>404 – Página não encontrada.</h1>";
            break;
    }

} catch (\Exception $e) {
    error_log("[e-cardeneta] " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    echo "<div style='font-family:monospace;padding:20px;background:#fff3f3;border:1px solid #f00;border-radius:8px;margin:20px'>";
    echo "<h3>⚠️ Erro Interno</h3>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "\n" . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</pre>";
    echo "</div>";
}
