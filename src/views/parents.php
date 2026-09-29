<!-- VIEW: Pais / Responsáveis -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold" style="color:var(--primary-color)">Pais e Responsáveis</h2>
    <div class="d-flex gap-2">
        <a href="/?action=users&role=parent" class="btn btn-outline-primary">+ Criar Conta de Pai/Mãe</a>
        <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalLinkar">
            🔗 Associar Filho
        </button>
    </div>
</div>

<div class="glass-card p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Nome</th>
                    <th>Perfil</th>
                    <th>Telefone (WhatsApp)</th>
                    <th>Crianças Associadas</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($parents)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">Nenhum responsável encontrado. Crie utilizadores com perfil "Pai/Mãe" ou "Babá".</td></tr>
                <?php else: ?>
                <?php foreach($parents as $p): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                                 style="width:38px;height:38px;background:var(--secondary-color);font-size:.9rem;flex-shrink:0">
                                <?= mb_strtoupper(mb_substr($p['name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="fw-semibold"><?= htmlspecialchars($p['name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($p['email']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php $badge = $p['role'] === 'baba' ? 'bg-secondary' : 'bg-success'; ?>
                        <span class="badge <?= $badge ?>"><?= $p['role'] === 'baba' ? 'Babá' : 'Pai/Mãe' ?></span>
                    </td>
                    <td><?= htmlspecialchars($p['phone'] ?: '—') ?></td>
                    <td>
                        <?php if(!empty($p['children_names'])): ?>
                            <span class="badge bg-primary rounded-pill"><?= htmlspecialchars($p['children_names']) ?></span>
                        <?php else: ?>
                            <span class="text-muted small">Sem filhos associados</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Associar Filho -->
<div class="modal fade" id="modalLinkar" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-card border-0">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">🔗 Associar Responsável a Criança</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="/?action=parents&op=link">
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= \Config\Security::generateCSRFToken() ?>">
          <div class="mb-3">
            <label class="form-label fw-semibold">Responsável</label>
            <select name="parent_id" class="form-select" required>
              <option value="">-- Selecionar --</option>
              <?php foreach($parents as $p): ?>
              <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= $p['role'] ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Criança</label>
            <select name="child_id" class="form-select" required>
              <option value="">-- Selecionar --</option>
              <?php foreach($allChildren as $ch): ?>
              <option value="<?= (int)$ch['id'] ?>"><?= htmlspecialchars($ch['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Relação</label>
            <select name="relationship" class="form-select">
              <option value="mother">Mãe</option>
              <option value="father">Pai</option>
              <option value="nanny">Babá</option>
              <option value="other">Outro</option>
            </select>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary-custom">Associar</button>
        </div>
      </form>
    </div>
  </div>
</div>
