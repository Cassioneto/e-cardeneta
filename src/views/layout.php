<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="E-Cardeneta - Sistema de Gestão de Comunicação de Creche Infantil">
    <title>E-Cardeneta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: <?= $systemSettings['color_primary'] ?? '#6C5CE7' ?>;
            --secondary-color: <?= $systemSettings['color_secondary'] ?? '#FD79A8' ?>;
            --sidebar-width: 260px;
        }
        body {
            background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);
            font-family: 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
        }
        /* ── Navbar ── */
        .app-navbar {
            background: #fff;
            box-shadow: 0 2px 12px rgba(0,0,0,.06);
            height: 60px;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1030;
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            gap: 1rem;
        }
        .app-navbar .brand {
            font-weight: 800;
            font-size: 1.3rem;
            color: var(--primary-color);
            text-decoration: none;
            white-space: nowrap;
        }
        .app-navbar .brand span { color: var(--secondary-color); }
        .app-navbar .ms-auto { margin-left: auto !important; }
        /* ── Sidebar ── */
        .app-sidebar {
            position: fixed;
            top: 60px; left: 0; bottom: 0;
            width: var(--sidebar-width);
            background: #fff;
            border-right: 1px solid #f0f0f0;
            overflow-y: auto;
            z-index: 1020;
            padding: 1rem 0.75rem;
            transition: transform 0.3s;
        }
        .sidebar-section {
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #aaa;
            margin: 1.2rem 0 .4rem .5rem;
        }
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: .6rem;
            padding: .55rem .9rem;
            border-radius: 10px;
            color: #444;
            text-decoration: none;
            font-size: .9rem;
            font-weight: 500;
            transition: background .15s, color .15s;
            margin-bottom: 2px;
        }
        .sidebar-link:hover, .sidebar-link.active {
            background: rgba(108,92,231,.1);
            color: var(--primary-color);
        }
        .sidebar-link i { font-size: 1rem; opacity: .8; }
        /* ── Main ── */
        .app-main {
            margin-left: var(--sidebar-width);
            margin-top: 60px;
            padding: 1.75rem;
            min-height: calc(100vh - 60px);
        }
        /* ── Glass Card ── */
        .glass-card {
            background: rgba(255,255,255,.85);
            backdrop-filter: blur(10px);
            border-radius: 18px;
            border: 1px solid rgba(255,255,255,.4);
            box-shadow: 0 6px 24px rgba(31,38,135,.07);
        }
        /* ── Buttons ── */
        .btn-primary-custom {
            background: linear-gradient(45deg, var(--primary-color), #897BFA);
            border: none; border-radius: 10px;
            padding: 9px 22px; font-weight: 600; color: #fff;
            box-shadow: 0 4px 14px rgba(108,92,231,.3);
            transition: all .25s;
        }
        .btn-primary-custom:hover {
            background: linear-gradient(45deg, #5A4BDE, #7062FA);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(108,92,231,.4);
            color: #fff;
        }
        /* ── Flash Alert ── */
        .flash-bar {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 10px rgba(0,0,0,.05);
            margin-bottom: 1.5rem;
        }
        /* ── Responsive: hide sidebar on mobile ── */
        @media(max-width:767px) {
            .app-sidebar { transform: translateX(-100%); }
            .app-sidebar.open { transform: translateX(0); }
            .app-main { margin-left: 0; }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="app-navbar">
    <?php if(isset($_SESSION['user_id'])): ?>
    <button class="btn btn-sm btn-light d-md-none" id="toggle-sidebar"><i class="bi bi-list fs-5"></i></button>
    <?php endif; ?>
    <a class="brand" href="/?action=dashboard">e-<span>Cardeneta</span></a>
    <?php if(!empty($systemSettings['logo_path'])): ?>
    <img src="<?= htmlspecialchars($systemSettings['logo_path']) ?>" alt="Logo" style="height:36px;object-fit:contain;margin-left:.5rem">
    <?php endif; ?>
    <?php if(isset($_SESSION['user_id'])): ?>
    <div class="ms-auto d-flex align-items-center gap-3">
        <span class="text-muted small d-none d-md-inline">Olá, <strong><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></strong></span>
        <a href="/?action=logout" class="btn btn-outline-danger btn-sm rounded-pill px-3">Sair</a>
    </div>
    <?php endif; ?>
</nav>

<?php if(isset($_SESSION['user_id'])): 
    $currentAction = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? 'dashboard';
    $role = $_SESSION['user_role'] ?? '';
    $isAdmin = in_array($role, ['super_admin','admin']);
    $isStaff  = in_array($role, ['super_admin','admin','professor']);
?>
<!-- Sidebar -->
<aside class="app-sidebar" id="appSidebar">
    <a href="/?action=dashboard" class="sidebar-link <?= $currentAction==='dashboard'?'active':'' ?>">
        <i class="bi bi-house-door"></i> Dashboard
    </a>

    <?php if($isAdmin): ?>
    <div class="sidebar-section">Gestão Geral</div>
    <a href="/?action=users"     class="sidebar-link <?= $currentAction==='users'?'active':'' ?>"><i class="bi bi-people"></i> Utilizadores</a>
    <a href="/?action=turmas"    class="sidebar-link <?= $currentAction==='turmas'?'active':'' ?>"><i class="bi bi-door-open"></i> Turmas</a>
    <a href="/?action=children"  class="sidebar-link <?= $currentAction==='children'?'active':'' ?>"><i class="bi bi-emoji-smile"></i> Crianças</a>
    <a href="/?action=parents"   class="sidebar-link <?= $currentAction==='parents'?'active':'' ?>"><i class="bi bi-person-heart"></i> Pais / Responsáveis</a>
    <a href="/?action=reports"   class="sidebar-link <?= $currentAction==='reports'?'active':'' ?>"><i class="bi bi-graph-up"></i> Relatórios</a>
    <a href="/?action=settings"  class="sidebar-link <?= $currentAction==='settings'?'active':'' ?>"><i class="bi bi-gear"></i> Configurações</a>
    <?php endif; ?>

    <?php if($isStaff || in_array($role, ['parent', 'baba'])): ?>
    <div class="sidebar-section">Operacional</div>
    <?php if($isStaff && !$isAdmin): ?>
    <a href="/?action=children" class="sidebar-link <?= $currentAction==='children'?'active':'' ?>"><i class="bi bi-emoji-smile"></i> Crianças da Turma</a>
    <?php endif; ?>
    <a href="/?action=atividades" class="sidebar-link <?= $currentAction==='atividades'?'active':'' ?>"><i class="bi bi-journal-check"></i> Atividades</a>
    <a href="/?action=refeicoes"  class="sidebar-link <?= $currentAction==='refeicoes'?'active':'' ?>"><i class="bi bi-egg-fried"></i> Refeições</a>
    <a href="/?action=eventos"    class="sidebar-link <?= $currentAction==='eventos'?'active':'' ?>"><i class="bi bi-calendar-event"></i> Eventos</a>
    <a href="/?action=lembretes"  class="sidebar-link <?= $currentAction==='lembretes'?'active':'' ?>"><i class="bi bi-bell"></i> Lembretes</a>
    <?php endif; ?>

    <div class="sidebar-section">Saúde & Bem-estar</div>
    <a href="/?action=medicamentos" class="sidebar-link <?= $currentAction==='medicamentos'?'active':'' ?>"><i class="bi bi-capsule"></i> Medicamentos</a>
    <a href="/?action=alergias"     class="sidebar-link <?= $currentAction==='alergias'?'active':'' ?>"><i class="bi bi-shield-exclamation"></i> Alergias & Restrições</a>
    <a href="/?action=vacinas"      class="sidebar-link <?= $currentAction==='vacinas'?'active':'' ?>">💉 Vacinas</a>
    <a href="/?action=observacoes"  class="sidebar-link <?= $currentAction==='observacoes'?'active':'' ?>"><i class="bi bi-clipboard2-pulse"></i> Observações</a>

    <div class="sidebar-section">Comunicação</div>
    <a href="/?action=avisos"   class="sidebar-link <?= $currentAction==='avisos'?'active':'' ?>"><i class="bi bi-megaphone"></i> Avisos & Comunicados</a>
    <a href="/?action=messages" class="sidebar-link <?= $currentAction==='messages'?'active':'' ?>"><i class="bi bi-chat-dots"></i> Mensagens</a>
</aside>
<?php endif; ?>

<!-- Main Content -->
<main class="app-main">
    <?php if(isset($_SESSION['flash_msg'])): ?>
    <div class="alert alert-<?= htmlspecialchars($_SESSION['flash_type'] ?? 'info') ?> flash-bar alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['flash_msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <?php require $viewFile; ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
<script>
// Mobile sidebar toggle
document.getElementById('toggle-sidebar')?.addEventListener('click', () => {
    document.getElementById('appSidebar')?.classList.toggle('open');
});
</script>
</body>
</html>
