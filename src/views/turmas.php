<!-- VIEW: Turmas -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold" style="color:var(--primary-color)">Turmas</h2>
    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalNovaTurma">
        + Nova Turma
    </button>
</div>

<div class="row g-4">
    <?php if(empty($turmas)): ?>
        <div class="col-12">
            <div class="glass-card p-5 text-center text-muted">
                <h5>Nenhuma turma cadastrada ainda.</h5>
                <p>Clique em "+ Nova Turma" para começar.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach($turmas as $t): ?>
        <div class="col-md-4">
            <div class="glass-card p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="fw-bold mb-1"><?= htmlspecialchars($t['name']) ?></h5>
                        <p class="text-muted small mb-1">
                            <strong>Professor(a):</strong> <?= htmlspecialchars($t['professor_name'] ?? 'Não atribuído') ?>
                        </p>
                        <span class="badge bg-primary"><?= (int)$t['total_children'] ?> crianças</span>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">⋮</button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <button class="dropdown-item"
                                    onclick='editarTurma(<?= htmlspecialchars(json_encode($t), ENT_QUOTES) ?>)'>
                                    ✏️ Editar
                                </button>
                            </li>
                            <li>
                                <form method="POST" action="/?action=turmas&op=delete" onsubmit="return confirm('Apagar a turma <?= htmlspecialchars($t['name'], ENT_QUOTES) ?>?')">
                                    <input type="hidden" name="csrf_token" value="<?= \Config\Security::generateCSRFToken() ?>">
                                    <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                                    <button type="submit" class="dropdown-item text-danger">🗑️ Apagar</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal Nova Turma -->
<div class="modal fade" id="modalNovaTurma" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-card border-0">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">Nova Turma</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="/?action=turmas&op=create">
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= \Config\Security::generateCSRFToken() ?>">
          <div class="mb-3">
            <label class="form-label fw-semibold">Nome da Turma</label>
            <input type="text" name="name" class="form-control" required placeholder="Ex: Sala dos Peixinhos">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Professor(a) Responsável</label>
            <select name="professor_id" class="form-select">
              <option value="">-- Sem professor atribuído --</option>
              <?php foreach($professors as $p): ?>
              <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary-custom">Criar Turma</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Editar Turma -->
<div class="modal fade" id="modalEditarTurma" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-card border-0">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">Editar Turma</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="/?action=turmas&op=edit">
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= \Config\Security::generateCSRFToken() ?>">
          <input type="hidden" name="id" id="edit_id">
          <div class="mb-3">
            <label class="form-label fw-semibold">Nome da Turma</label>
            <input type="text" name="name" id="edit_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Professor(a) Responsável</label>
            <select name="professor_id" id="edit_professor_id" class="form-select">
              <option value="">-- Sem professor atribuído --</option>
              <?php foreach($professors as $p): ?>
              <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary-custom">Guardar Alterações</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function editarTurma(turma) {
    document.getElementById('edit_id').value = turma.id;
    document.getElementById('edit_name').value = turma.name;
    document.getElementById('edit_professor_id').value = turma.professor_id || '';
    new bootstrap.Modal(document.getElementById('modalEditarTurma')).show();
}
</script>
