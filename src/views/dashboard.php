<?php
// Buscar métricas reais do BD
use Config\Database;
$db = Database::getConnection();

$totalCriancas  = $db->query("SELECT COUNT(*) FROM children")->fetchColumn();
$totalTurmas    = $db->query("SELECT COUNT(*) FROM classes")->fetchColumn();
$totalUsers     = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$medPendentes   = $db->query("SELECT COUNT(*) FROM medicine_administrations WHERE administered = 0 AND scheduled_time >= NOW()")->fetchColumn();
$medAtrasados   = $db->query("SELECT COUNT(*) FROM medicine_administrations WHERE administered = 0 AND scheduled_time < NOW()")->fetchColumn();


$role = $_SESSION['user_role'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;
$isStaff = in_array($role, ['super_admin','admin','professor']);

if (in_array($role, ['parent', 'baba'])) {
    $stmt = $db->prepare("
        SELECT c.type, c.content, c.created_at, u.name as sender_name
        FROM communications c 
        JOIN users u ON u.id = c.sender_id
        WHERE (
            c.receiver_id = ? 
            OR c.class_id IN (SELECT class_id FROM children ch JOIN parent_child pc ON pc.child_id = ch.id WHERE pc.parent_id = ?)
            OR (c.receiver_id IS NULL AND c.class_id IS NULL)
        )
        ORDER BY c.created_at DESC LIMIT 5
    ");
    $stmt->execute([$userId, $userId]);
    $ultimasComunic = $stmt->fetchAll();
} else {
    $ultimasComunic = $db->query("
        SELECT c.type, c.content, c.created_at, u.name as sender_name
        FROM communications c JOIN users u ON u.id = c.sender_id
        ORDER BY c.created_at DESC LIMIT 5
    ")->fetchAll();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0" style="color:var(--primary-color)">
        Bem-vindo(a), <?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>!
    </h2>
    <span class="text-muted small"><?= date('l, d \d\e F \d\e Y') ?></span>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <?php if($isStaff): ?>
    <div class="col-6 col-md-3">
        <a href="/?action=children" class="text-decoration-none">
            <div class="glass-card p-4 text-center">
                <div style="font-size:2rem">👧</div>
                <h3 class="fw-bold mb-0 mt-2"><?= $totalCriancas ?></h3>
                <small class="text-muted">Crianças</small>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="/?action=turmas" class="text-decoration-none">
            <div class="glass-card p-4 text-center">
                <div style="font-size:2rem">🚪</div>
                <h3 class="fw-bold mb-0 mt-2"><?= $totalTurmas ?></h3>
                <small class="text-muted">Turmas</small>
            </div>
        </a>
    </div>
    <?php endif; ?>
    <div class="col-6 col-md-3">
        <a href="/?action=medicamentos" class="text-decoration-none">
            <div class="glass-card p-4 text-center <?= $medAtrasados > 0 ? 'border border-danger' : '' ?>">
                <div style="font-size:2rem">💊</div>
                <h3 class="fw-bold mb-0 mt-2 <?= $medAtrasados > 0 ? 'text-danger' : '' ?>">
                    <?= $medPendentes + $medAtrasados ?>
                </h3>
                <small class="text-muted">Med. Pendentes</small>
                <?php if($medAtrasados > 0): ?>
                <div class="badge bg-danger mt-1"><?= $medAtrasados ?> em atraso</div>
                <?php endif; ?>
            </div>
        </a>
    </div>
    <?php if(in_array($_SESSION['user_role'], ['super_admin','admin'])): ?>
    <div class="col-6 col-md-3">
        <a href="/?action=users" class="text-decoration-none">
            <div class="glass-card p-4 text-center">
                <div style="font-size:2rem">👥</div>
                <h3 class="fw-bold mb-0 mt-2"><?= $totalUsers ?></h3>
                <small class="text-muted">Utilizadores</small>
            </div>
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Últimas Comunicações + Acesso Rápido -->
<div class="row g-4">
    <div class="col-lg-7">
        <div class="glass-card p-4">
            <h5 class="fw-bold mb-3">📋 Últimas Comunicações</h5>
            <?php if(empty($ultimasComunic)): ?>
                <p class="text-muted text-center py-3">Sem comunicações recentes.</p>
            <?php else: ?>
            <ul class="list-unstyled mb-0">
                <?php foreach($ultimasComunic as $com): ?>
                <?php $types = ['message'=>['💬','info'], 'warning'=>['⚠️','warning'], 'announcement'=>['📢','primary'], 'reminder'=>['🔔','secondary'], 'task'=>['✅','success']]; $t = $types[$com['type']] ?? ['📌','secondary']; ?>
                <li class="d-flex align-items-start gap-3 py-2 border-bottom">
                    <span style="font-size:1.3rem"><?= $t[0] ?></span>
                    <div class="flex-grow-1">
                        <div class="fw-semibold small"><?= htmlspecialchars(substr($com['content'], 0, 80)) ?><?= strlen($com['content'])>80?'…':'' ?></div>
                        <small class="text-muted">Por <?= htmlspecialchars($com['sender_name']) ?> · <?= date('d/m H:i', strtotime($com['created_at'])) ?></small>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="glass-card p-4">
            <h5 class="fw-bold mb-3">⚡ Acesso Rápido</h5>
            <div class="d-grid gap-2">
                <?php if($isStaff): ?>
                    <a href="/?action=avisos" class="btn btn-outline-primary rounded-3">📢 Enviar Aviso WhatsApp</a>
                <?php else: ?>
                    <a href="/?action=avisos" class="btn btn-outline-primary rounded-3">📢 Visualizar Avisos</a>
                <?php endif; ?>
                <a href="/?action=medicamentos" class="btn btn-outline-warning rounded-3 text-dark">💊 Registo de Medicamentos</a>
                <a href="/?action=messages" class="btn btn-outline-info rounded-3 text-dark">💬 Mensagens</a>
                <?php if(in_array($_SESSION['user_role'], ['super_admin','admin'])): ?>
                <a href="/?action=children" class="btn btn-outline-success rounded-3">👧 Gerir Crianças</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
