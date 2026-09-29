<!-- VIEW: Registos de Saúde (Alergias / Vacinas / Observações) -->
<?php
$icons = [
    'allergy'     => ['🚨','danger',  'Alergia'],
    'vaccine'     => ['💉','primary', 'Vacina'],
    'observation' => ['📋','info',    'Observação'],
];
[$icon, $badgeColor, $singularLabel] = $icons[$type] ?? ['📌','secondary','Registo'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold" style="color:var(--primary-color)"><?= $icon ?> <?= htmlspecialchars($pageTitle) ?></h2>
    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalNovoRegisto">
        + Novo Registo
    </button>
</div>

<div class="glass-card p-4">
    <?php if(empty($records)): ?>
        <div class="text-center text-muted py-5">
            <div style="font-size:3rem"><?= $icon ?></div>
            <h5 class="mt-3">Nenhum registo de <?= htmlspecialchars(strtolower($pageTitle)) ?> encontrado.</h5>
        </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Criança</th>
                    <th>Descrição</th>
                    <th>Registado por</th>
                    <th>Data</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($records as $r): ?>
            <tr>
                <td class="fw-semibold"><?= htmlspecialchars($r['child_name']) ?></td>
                <td>
                    <span class="badge bg-<?= $badgeColor ?> me-2"><?= $singularLabel ?></span>
                    <?= htmlspecialchars($r['description']) ?>
                </td>
                <td><small class="text-muted"><?= htmlspecialchars($r['author_name']) ?></small></td>
                <td><small class="text-muted"><?= date('d/m/Y', strtotime($r['date_recorded'])) ?></small></td>
                <td>
                    <form method="POST" action="/?action=<?= htmlspecialchars($backAction) ?>&op=delete"
                          onsubmit="return confirm('Apagar este registo?')">
                        <input type="hidden" name="csrf_token" value="<?= \Config\Security::generateCSRFToken() ?>">
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Apagar</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<div class="alert alert-info small mt-3">
    🔐 As informações de saúde são <strong>criptografadas</strong> em repouso (AES-256) em conformidade com a <strong>LGPD</strong>.
</div>

<!-- Modal Novo Registo -->
<div class="modal fade" id="modalNovoRegisto" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-card border-0">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold"><?= $icon ?> Novo Registo – <?= htmlspecialchars($singularLabel) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="/?action=<?= htmlspecialchars($backAction) ?>&op=create">
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= \Config\Security::generateCSRFToken() ?>">
          <div class="mb-3">
            <label class="form-label fw-semibold">Criança</label>
            <select name="child_id" class="form-select" required>
              <option value="">-- Selecionar criança --</option>
              <?php foreach($children as $ch): ?>
              <option value="<?= (int)$ch['id'] ?>"><?= htmlspecialchars($ch['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Descrição</label>
            <textarea name="description" class="form-control" rows="3" required
                      placeholder="Descreva detalhadamente..."></textarea>
            <div class="form-text">🔐 Será criptografado automaticamente (LGPD).</div>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary-custom">Guardar Registo</button>
        </div>
      </form>
    </div>
  </div>
</div>
