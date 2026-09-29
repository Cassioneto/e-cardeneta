<!-- VIEW: Medicamentos -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold" style="color:var(--primary-color)">💊 Medicamentos</h2>
    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalNovoMed">
        + Registar Medicamento
    </button>
</div>

<div class="glass-card p-4 mb-4">
    <?php if(empty($medicamentos)): ?>
        <div class="text-center text-muted py-4">
            <h5>Nenhum medicamento registado.</h5>
        </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Criança</th>
                    <th>Medicamento / Dosagem</th>
                    <th>Horário</th>
                    <th>Estado</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($medicamentos as $m): ?>
            <tr class="<?= $m['administered'] ? 'table-success' : '' ?>">
                <td class="fw-semibold"><?= htmlspecialchars($m['child_name']) ?></td>
                <td><?= htmlspecialchars($m['description']) ?></td>
                <td><?= $m['scheduled_time'] ? date('d/m/Y H:i', strtotime($m['scheduled_time'])) : '—' ?></td>
                <td>
                    <?php if($m['administered']): ?>
                        <span class="badge bg-success">✅ Administrado</span>
                        <br><small class="text-muted">por <?= htmlspecialchars($m['administered_by'] ?? '?') ?></small>
                        <br><small class="text-muted"><?= date('d/m H:i', strtotime($m['administered_at'])) ?></small>
                    <?php else: ?>
                        <?php
                        $agora = new DateTime();
                        $hora = new DateTime($m['scheduled_time']);
                        $diff = $agora->diff($hora);
                        $atrasado = $hora < $agora;
                        ?>
                        <span class="badge <?= $atrasado ? 'bg-danger' : 'bg-warning text-dark' ?>">
                            <?= $atrasado ? '⚠️ Em atraso' : '⏳ Pendente' ?>
                        </span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if(!$m['administered'] && $m['admin_id']): ?>
                    <form method="POST" action="/?action=medicamentos&op=administrar">
                        <input type="hidden" name="csrf_token" value="<?= \Config\Security::generateCSRFToken() ?>">
                        <input type="hidden" name="admin_id" value="<?= (int)$m['admin_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-success">✔ Administrado</button>
                    </form>
                    <?php else: ?>
                        <span class="text-muted small">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<div class="alert alert-info small">
    <strong>ℹ️ Alertas automáticos:</strong> O microserviço WhatsApp enviará uma notificação à educadora 2 minutos antes do horário de administração e avisará os pais após a confirmação.
</div>

<!-- Modal Novo Medicamento -->
<div class="modal fade" id="modalNovoMed" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-card border-0">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">Registar Medicamento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="/?action=medicamentos&op=create">
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
            <label class="form-label fw-semibold">Medicamento, Dosagem e Instruções</label>
            <textarea name="descricao" class="form-control" rows="3" required
                      placeholder="Ex: Paracetamol 250mg - 1 supositório às refeições"></textarea>
            <div class="form-text text-muted">
                🔐 Esta informação será <strong>criptografada</strong> em conformidade com a LGPD.
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Hora da Primeira Administração</label>
            <input type="datetime-local" name="scheduled_time" id="scheduled_time" class="form-control" required>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Repetição</label>
              <select name="schedule_type" id="schedule_type" class="form-select">
                <option value="once">Apenas uma vez</option>
                <option value="interval">A cada X horas</option>
                <option value="daily">1 vez por dia (24h)</option>
              </select>
            </div>
            <div class="col-md-6 mb-3" id="div_interval" style="display:none">
              <label class="form-label fw-semibold">Horas entre doses</label>
              <input type="number" name="interval_hours" id="interval_hours" class="form-control" min="1" value="8">
            </div>
          </div>

          <div class="mb-3" id="div_num_applications" style="display:none">
            <label class="form-label fw-semibold">Total de Aplicações</label>
            <input type="number" name="num_applications" id="num_applications" class="form-control" min="1" value="1">
            <div class="form-text text-primary fw-medium mt-2" id="preview_last_dose"></div>
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

<script>
const scheduleType   = document.getElementById('schedule_type');
const intervalDiv    = document.getElementById('div_interval');
const intervalHours  = document.getElementById('interval_hours');
const numApplDiv     = document.getElementById('div_num_applications');
const numApplInput   = document.getElementById('num_applications');
const startTimeInput = document.getElementById('scheduled_time');
const previewLast    = document.getElementById('preview_last_dose');

function updateVisibility() {
    const val = scheduleType.value;
    intervalDiv.style.display = (val === 'interval') ? 'block' : 'none';
    numApplDiv.style.display  = (val !== 'once') ? 'block' : 'none';
    calculateLastDose();
}

function calculateLastDose() {
    const startVal = startTimeInput.value;
    if (!startVal) { previewLast.innerText = ''; return; }
    
    const count = parseInt(numApplInput.value) || 1;
    if (count <= 1 || scheduleType.value === 'once') {
        previewLast.innerText = '';
        return;
    }

    let interval = 0;
    if (scheduleType.value === 'daily') interval = 24;
    else if (scheduleType.value === 'interval') interval = parseInt(intervalHours.value) || 0;

    if (interval <= 0) { previewLast.innerText = ''; return; }

    const d = new Date(startVal);
    d.setHours(d.getHours() + (interval * (count - 1)));
    
    const formatted = d.toLocaleDateString('pt-PT') + ' ' + d.toLocaleTimeString('pt-PT', {hour: '2-digit', minute:'2-digit'});
    previewLast.innerHTML = `<i class="bi bi-calendar-event"></i> Última dose prevista: <strong>${formatted}</strong>`;
}

scheduleType.addEventListener('change', updateVisibility);
intervalHours.addEventListener('input', calculateLastDose);
numApplInput.addEventListener('input', calculateLastDose);
startTimeInput.addEventListener('change', calculateLastDose);
</script>
