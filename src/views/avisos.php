<!-- VIEW: Avisos & Comunicados -->
<?php
use Config\Database;
$db = Database::getConnection();
$role = $_SESSION['user_role'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;
$isStaff = in_array($role, ['super_admin', 'admin', 'professor']);

if (in_array($role, ['parent', 'baba'])) {
    $stmt = $db->prepare("
        SELECT c.*, u.name as sender_name,
               u2.name as receiver_name, cl.name as class_name
        FROM communications c
        JOIN users u ON u.id = c.sender_id
        LEFT JOIN users u2 ON u2.id = c.receiver_id
        LEFT JOIN classes cl ON cl.id = c.class_id
        WHERE c.type IN ('warning','announcement','reminder','task','event')
        AND (
            c.receiver_id = ? 
            OR c.class_id IN (SELECT class_id FROM children ch JOIN parent_child pc ON pc.child_id = ch.id WHERE pc.parent_id = ?)
            OR (c.receiver_id IS NULL AND c.class_id IS NULL)
        )
        ORDER BY c.created_at DESC LIMIT 50
    ");
    $stmt->execute([$userId, $userId]);
    $avisos = $stmt->fetchAll();
} else {
    $avisos = $db->query("
        SELECT c.*, u.name as sender_name,
               u2.name as receiver_name, cl.name as class_name
        FROM communications c
        JOIN users u ON u.id = c.sender_id
        LEFT JOIN users u2 ON u2.id = c.receiver_id
        LEFT JOIN classes cl ON cl.id = c.class_id
        WHERE c.type IN ('warning','announcement','reminder','task','event')
        ORDER BY c.created_at DESC LIMIT 50
    ")->fetchAll();
}

$turmas = $db->query("SELECT id, name FROM classes ORDER BY name")->fetchAll();
$pais   = $db->query("SELECT id, name FROM users WHERE role IN ('parent','baba') ORDER BY name")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold" style="color:var(--primary-color)">📢 Avisos & Comunicados</h2>
    <?php if($isStaff): ?>
    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalNovoAviso">
        + Novo Aviso / Comunicado
    </button>
    <?php endif; ?>
</div>

<div class="glass-card p-4">
    <?php if(empty($avisos)): ?>
        <div class="text-center text-muted py-5">
            <h5>Nenhum aviso enviado ainda.</h5>
        </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Tipo</th>
                    <th>Mensagem</th>
                    <th>Para</th>
                    <th>WhatsApp</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($avisos as $a): ?>
            <?php
                $typeColors = [
                    'warning'      => ['bg-warning text-dark', '⚠️ Aviso'],
                    'announcement' => ['bg-primary',           '📢 Comunicado'],
                    'reminder'     => ['bg-secondary',         '🔔 Lembrete'],
                    'task'         => ['bg-success',           '✅ Tarefa'],
                    'event'        => ['bg-info text-dark',    '📅 Evento'],
                ];
                [$badgeCls, $label] = $typeColors[$a['type']] ?? ['bg-secondary','📌'];
                if (!empty($a['receiver_name']))     $para = '👤 '.$a['receiver_name'];
                elseif (!empty($a['class_name']))    $para = '🚪 '.$a['class_name'];
                else                                 $para = '🌐 Geral';
            ?>
            <tr>
                <td><span class="badge <?= $badgeCls ?>"><?= $label ?></span></td>
                <td><?= htmlspecialchars(substr($a['content'], 0, 80)) ?><?= strlen($a['content'])>80?'…':'' ?></td>
                <td><small><?= htmlspecialchars($para) ?></small></td>
                <td>
                    <?php if($a['sent_via_whatsapp']): ?>
                        <span class="badge bg-success">✔ Enviado</span>
                    <?php else: ?>
                        <span class="badge bg-light text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td><small class="text-muted"><?= date('d/m/Y H:i', strtotime($a['created_at'])) ?></small></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php if($isStaff): ?>
<!-- Modal Novo Aviso -->
<div class="modal fade" id="modalNovoAviso" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content glass-card border-0">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">Novo Aviso / Comunicado</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="/?action=avisos&op=create">
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= \Config\Security::generateCSRFToken() ?>">
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Tipo</label>
              <select name="type" class="form-select" required>
                <option value="warning">⚠️ Aviso</option>
                <option value="announcement">📢 Comunicado</option>
                <option value="reminder">🔔 Lembrete</option>
                <option value="task">✅ Tarefa</option>
                <option value="event">📅 Evento</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Enviar para</label>
              <select name="target_type" class="form-select" id="target-type-sel">
                <option value="all">🌐 Todos os pais</option>
                <option value="class">🚪 Uma Turma</option>
                <option value="parent">👤 Um Responsável específico</option>
              </select>
            </div>
          </div>
          <div class="mb-3" id="target-class-div" style="display:none">
            <label class="form-label fw-semibold">Turma</label>
            <select name="class_id" class="form-select">
              <option value="">-- Selecionar turma --</option>
              <?php foreach($turmas as $t): ?>
              <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3" id="target-parent-div" style="display:none">
            <label class="form-label fw-semibold">Responsável</label>
            <select name="receiver_id" class="form-select">
              <option value="">-- Selecionar responsável --</option>
              <?php foreach($pais as $p): ?>
              <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Mensagem</label>
            <textarea name="content" class="form-control" rows="4" required
                      placeholder="Escreva a mensagem que será enviada via WhatsApp..."></textarea>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="send_whatsapp" id="send_wa" value="1" checked>
            <label class="form-check-label" for="send_wa">
                <i class="bi bi-whatsapp text-success"></i> Enviar via WhatsApp
            </label>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary-custom">Enviar Aviso</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById('target-type-sel').addEventListener('change', function() {
    document.getElementById('target-class-div').style.display  = this.value === 'class'  ? '' : 'none';
    document.getElementById('target-parent-div').style.display = this.value === 'parent' ? '' : 'none';
});
</script>
<?php endif; ?>
