<?php
$role = $_SESSION['user_role'] ?? '';
$isStaff = in_array($role, ['super_admin', 'admin', 'professor']);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold" style="color:var(--primary-color)">🍽️ Refeições e Ementas</h2>
    <?php if($isStaff): ?>
    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalNovaRefeicao">
        + Planear Refeição
    </button>
    <?php endif; ?>
</div>

<div class="row g-4">
    <!-- Coluna da Ementa -->
    <div class="<?= $isStaff ? 'col-lg-8' : 'col-12' ?>">
        <div class="glass-card p-4">
            <h5 class="fw-bold mb-4">📅 Próximas Refeições</h5>
            
            <?php if(empty($meals)): ?>
                <div class="text-center text-muted py-5">
                    <p>Nenhuma refeição planeada para os próximos dias.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Data/Hora</th>
                                <th>Tipo</th>
                                <th>Descrição</th>
                                <?php if($isStaff): ?><th>Acções</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($meals as $m): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?= date('d/m', strtotime($m['scheduled_date'])) ?></div>
                                    <small class="text-muted"><?= date('H:i', strtotime($m['scheduled_time'])) ?></small>
                                </td>
                                <td>
                                    <?php
                                    $badgeClass = match($m['meal_type']) {
                                        'breakfast' => 'bg-info text-dark',
                                        'lunch'     => 'bg-success',
                                        'snack'     => 'bg-warning text-dark',
                                        'dinner'    => 'bg-primary',
                                        default     => 'bg-secondary'
                                    };
                                    $labels = [
                                        'breakfast' => 'Peq. Almoço',
                                        'lunch'     => 'Almoço',
                                        'snack'     => 'Lanche',
                                        'dinner'    => 'Jantar'
                                    ];
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= $labels[$m['meal_type']] ?? $m['meal_type'] ?></span>
                                </td>
                                <td><?= htmlspecialchars($m['description']) ?></td>
                                <?php if($isStaff): ?>
                                <td>
                                    <?php if(in_array($_SESSION['user_role'], ['super_admin', 'admin'])): ?>
                                    <form method="POST" action="/?action=refeicoes&op=delete" onsubmit="return confirm('Apagar esta refeição?')">
                                        <input type="hidden" name="csrf_token" value="<?= \Config\Security::generateCSRFToken() ?>">
                                        <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0">🗑️</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if($isStaff): ?>
    <!-- Coluna de Restrições -->
    <div class="col-lg-4">
        <div class="glass-card p-4 border border-warning" style="background: rgba(255, 193, 7, 0.05);">
            <h5 class="fw-bold mb-3 text-warning-emphasis">⚠️ Atenção: Restrições & Alergias</h5>
            <p class="small text-muted mb-4">Lista de crianças que requerem atenção especial durante as refeições.</p>

            <?php if(empty($childrenWithRestrictions)): ?>
                <div class="text-center text-muted py-3">
                    <small>Nenhuma restrição registada.</small>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach($childrenWithRestrictions as $c): ?>
                    <div class="list-group-item bg-transparent border-0 px-0 pb-3">
                        <div class="fw-bold text-dark small"><?= htmlspecialchars($c['name']) ?></div>
                        <div class="text-muted small mb-1"><?= htmlspecialchars($c['class_name'] ?? 'Sem turma') ?></div>
                        <span class="badge <?= $c['record_type'] === 'allergy' ? 'bg-danger' : 'bg-warning text-dark' ?> rounded-pill" style="font-size: 0.75rem;">
                            <?= $c['record_type'] === 'allergy' ? 'Alergia' : 'Restrição' ?>
                        </span>
                        <div class="mt-1 small fw-medium"><?= htmlspecialchars($c['description']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if($isStaff): ?>
<!-- Modal Nova Refeição -->
<div class="modal fade" id="modalNovaRefeicao" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-card border-0">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">Planear Nova Refeição</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="/?action=refeicoes&op=create">
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= \Config\Security::generateCSRFToken() ?>">
          
          <div class="mb-3">
            <label class="form-label fw-semibold">Data</label>
            <input type="date" name="scheduled_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Tipo de Refeição</label>
            <select name="meal_type" class="form-select">
                <option value="breakfast">Pequeno Almoço</option>
                <option value="lunch" selected>Almoço</option>
                <option value="snack">Lanche</option>
                <option value="dinner">Jantar</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Hora (Aproximada)</label>
            <input type="time" name="scheduled_time" class="form-control" value="12:30" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Menu / Descrição</label>
            <textarea name="description" class="form-control" rows="3" required placeholder="Ex: Sopa de legumes, Arroz de pato e fruta da época."></textarea>
          </div>

          <div class="form-check mt-3">
            <input class="form-check-input" type="checkbox" name="send_whatsapp" id="send_wa_meal" value="1" checked>
            <label class="form-check-label" for="send_wa_meal">
                <i class="bi bi-whatsapp text-success"></i> Notificar pais via WhatsApp
            </label>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary-custom">Salvar Refeição</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>
