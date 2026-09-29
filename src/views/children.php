<!-- VIEW: Crianças -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold" style="color:var(--primary-color)">Crianças</h2>
    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalNovaCrianca">
        + Registar Criança
    </button>
</div>

<!-- Filtro rápido -->
<div class="glass-card p-3 mb-4">
    <input type="text" id="filtro-criancas" class="form-control" placeholder="🔍 Pesquisar por nome ou turma...">
</div>

<div class="row g-3" id="lista-criancas">
    <?php if(empty($children)): ?>
        <div class="col-12">
            <div class="glass-card p-5 text-center text-muted">
                <h5>Nenhuma criança registada ainda.</h5>
            </div>
        </div>
    <?php else: ?>
        <?php foreach($children as $c): ?>
        <?php
            $idade = '';
            if (!empty($c['birth_date'])) {
                $nasc = new DateTime($c['birth_date']);
                $hoje = new DateTime();
                $diff = $hoje->diff($nasc);
                $idade = $diff->y > 0 ? $diff->y . ' anos' : $diff->m . ' meses';
            }
        ?>
        <div class="col-md-4 crianca-card" data-nome="<?= strtolower(htmlspecialchars($c['name'])) ?>" data-turma="<?= strtolower(htmlspecialchars($c['class_name'] ?? '')) ?>">
            <div class="glass-card p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                             style="width:50px;height:50px;background:var(--primary-color);font-size:1.3rem">
                            <?= mb_strtoupper(mb_substr($c['name'], 0, 1)) ?>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0"><?= htmlspecialchars($c['name']) ?></h6>
                            <small class="text-muted"><?= $idade ?> · <?= htmlspecialchars($c['class_name'] ?? 'Sem turma') ?></small>
                        </div>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">⋮</button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <button class="dropdown-item"
                                    onclick='editarCrianca(<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)'>
                                    ✏️ Editar
                                </button>
                            </li>
                            <li>
                                <a class="dropdown-item" href="/?action=medicamentos&child_id=<?= (int)$c['id'] ?>">
                                    💊 Medicamentos
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="/?action=children&op=delete"
                                      onsubmit="return confirm('Apagar <?= htmlspecialchars($c['name'], ENT_QUOTES) ?>?')">
                                    <input type="hidden" name="csrf_token" value="<?= \Config\Security::generateCSRFToken() ?>">
                                    <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                    <button type="submit" class="dropdown-item text-danger">🗑️ Apagar</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
                <?php if(!empty($c['parents'])): ?>
                <div class="mt-3 pt-3 border-top">
                    <small class="text-muted">👨‍👩‍👧 <?= htmlspecialchars($c['parents']) ?></small>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal Nova Criança -->
<div class="modal fade" id="modalNovaCrianca" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-card border-0">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">Registar Criança</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="/?action=children&op=create">
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= \Config\Security::generateCSRFToken() ?>">
          <div class="mb-3">
            <label class="form-label fw-semibold">Nome Completo</label>
            <input type="text" name="name" class="form-control" required placeholder="Nome da criança">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Data de Nascimento</label>
            <input type="date" name="birth_date" class="form-control" required
                   max="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d', strtotime('-7 years')) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Turma</label>
            <select name="class_id" class="form-select">
              <option value="">-- Sem turma atribuída --</option>
              <?php foreach($classes as $cl): ?>
              <option value="<?= (int)$cl['id'] ?>"><?= htmlspecialchars($cl['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary-custom">Registar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Editar Criança -->
<div class="modal fade" id="modalEditarCrianca" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-card border-0">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">Editar Criança</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="/?action=children&op=edit">
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= \Config\Security::generateCSRFToken() ?>">
          <input type="hidden" name="id" id="ec_id">
          <div class="mb-3">
            <label class="form-label fw-semibold">Nome Completo</label>
            <input type="text" name="name" id="ec_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Data de Nascimento</label>
            <input type="date" name="birth_date" id="ec_birth" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Turma</label>
            <select name="class_id" id="ec_class" class="form-select">
              <option value="">-- Sem turma atribuída --</option>
              <?php foreach($classes as $cl): ?>
              <option value="<?= (int)$cl['id'] ?>"><?= htmlspecialchars($cl['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary-custom">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function editarCrianca(c) {
    document.getElementById('ec_id').value = c.id;
    document.getElementById('ec_name').value = c.name;
    document.getElementById('ec_birth').value = c.birth_date;
    document.getElementById('ec_class').value = c.class_id || '';
    new bootstrap.Modal(document.getElementById('modalEditarCrianca')).show();
}

// Filtro dinâmico
document.getElementById('filtro-criancas').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.crianca-card').forEach(el => {
        const match = el.dataset.nome.includes(q) || el.dataset.turma.includes(q);
        el.style.display = match ? '' : 'none';
    });
});
</script>
