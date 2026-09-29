<?php
$role = $_SESSION['user_role'] ?? '';
$isStaff = in_array($role, ['super_admin', 'admin', 'professor']);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold" style="color:var(--primary-color)">🎨 Atividades</h2>
    <?php if ($isStaff): ?>
    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalNovaAtividade">
        + Registar Atividade
    </button>
    <?php endif; ?>
</div>

<div class="row g-4">
    <?php if(empty($activities)): ?>
        <div class="col-12">
            <div class="glass-card p-5 text-center text-muted">
                <h5>Nenhuma atividade registada ainda.</h5>
                <?php if($isStaff): ?>
                    <p>Crie a primeira atividade para partilhar com os pais.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <?php foreach($activities as $a): ?>
        <div class="col-md-6 col-xl-4">
            <div class="glass-card h-100 overflow-hidden d-flex flex-column">
                <?php if($a['image_path']): ?>
                    <img src="<?= htmlspecialchars($a['image_path']) ?>" class="card-img-top" style="height: 180px; object-fit: cover;" alt="Imagem da Atividade">
                <?php else: ?>
                    <div style="height: 180px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); opacity: 0.1; display:flex; align-items:center; justify-content:center;">
                        <i class="bi bi-camera text-primary" style="font-size: 2rem;"></i>
                    </div>
                <?php endif; ?>
                
                <div class="p-4 flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-primary rounded-pill small"><?= htmlspecialchars($a['class_name'] ?? 'Geral') ?></span>
                        <small class="text-muted"><?= date('d/m/Y', strtotime($a['activity_date'])) ?></small>
                    </div>
                    <h5 class="fw-bold"><?= htmlspecialchars($a['title']) ?></h5>
                    <p class="text-muted small truncate-3"><?= htmlspecialchars($a['description']) ?></p>
                </div>
                
                <div class="p-3 border-top bg-light text-center">
                    <button class="btn btn-sm btn-link text-decoration-none" data-bs-toggle="modal" data-bs-target="#modalDetalhes<?= $a['id'] ?>">Ver Detalhes</button>
                </div>
            </div>
        </div>

        <!-- Modal Detalhes da Atividade -->
        <div class="modal fade" id="modalDetalhes<?= $a['id'] ?>" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card border-0">
              <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Detalhes da Atividade</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <div class="text-center mb-4">
                    <?php if($a['image_path']): ?>
                        <img src="<?= htmlspecialchars($a['image_path']) ?>" class="img-fluid rounded-4 shadow-sm" style="max-height: 250px;" alt="Imagem">
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <span class="badge bg-light text-primary border mb-2"><?= htmlspecialchars($a['class_name'] ?? 'Geral') ?></span>
                    <h4 class="fw-bold"><?= htmlspecialchars($a['title']) ?></h4>
                    <p class="text-muted"><i class="bi bi-calendar-event me-1"></i> <?= date('d/m/Y', strtotime($a['activity_date'])) ?></p>
                </div>

                <div class="bg-light p-3 rounded-3 mb-4">
                    <h6 class="fw-bold small text-uppercase text-muted mb-2">Descrição</h6>
                    <p class="mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars($a['description']) ?></p>
                </div>

                <?php if(!empty($a['child_notes'])): ?>
                <div class="border-top pt-3">
                    <h6 class="fw-bold small text-uppercase text-muted mb-3">📍 Notas Individuais</h6>
                    <?php foreach($a['child_notes'] as $cn): ?>
                        <div class="p-2 mb-2 border-start border-primary border-4 bg-light rounded-end">
                            <strong class="d-block small"><?= htmlspecialchars($cn['child_name']) ?>:</strong>
                            <span class="small"><?= htmlspecialchars($cn['notes']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
              </div>
              <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Fechar</button>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if ($isStaff): ?>
<!-- Modal Nova Atividade -->
<div class="modal fade" id="modalNovaAtividade" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content glass-card border-0">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">Registar Atividade de Grupo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="/?action=atividades&op=create" enctype="multipart/form-data">
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= \Config\Security::generateCSRFToken() ?>">
          
          <div class="row mb-3">
              <div class="col-md-8">
                  <label class="form-label fw-semibold">Título da Atividade</label>
                  <input type="text" name="title" class="form-control" placeholder="Ex: Pintura com guache, Hora do Conto..." required>
              </div>
              <div class="col-md-4">
                  <label class="form-label fw-semibold">Data</label>
                  <input type="date" name="activity_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
              </div>
          </div>

          <div class="mb-3">
              <label class="form-label fw-semibold">Turma</label>
              <select name="class_id" class="form-select" id="select-turma-atividade" required>
                  <option value="">-- Selecione a Turma --</option>
                  <?php foreach($classes as $c): ?>
                      <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                  <?php endforeach; ?>
              </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Descrição / Observações Gerais</label>
            <textarea name="description" class="form-control" rows="3" placeholder="O que foi feito? Quais os objetivos?"></textarea>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold">Foto (Opcional)</label>
            <input type="file" name="image" class="form-control" accept="image/*">
          </div>

          <div class="mb-3 border-top pt-3">
              <label class="form-label fw-semibold mb-3">📍 Notas Individuais por Criança (Opcional)</label>
              <div id="lista-criancas-atividade" class="row g-2" style="max-height: 250px; overflow-y: auto;">
                  <p class="small text-muted text-center py-3">Selecione uma turma para carregar os alunos.</p>
              </div>
          </div>

          <div class="form-check mt-3">
            <input class="form-check-input" type="checkbox" name="send_whatsapp" id="send_wa_activity" value="1" checked>
            <label class="form-check-label" for="send_wa_activity">
                <i class="bi bi-whatsapp text-success"></i> Notificar pais via WhatsApp
            </label>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary-custom">Registar Atividade</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const children = <?= json_encode($children) ?>;
document.getElementById('select-turma-atividade').addEventListener('change', function() {
    const classId = this.value;
    const container = document.getElementById('lista-criancas-atividade');
    container.innerHTML = '';
    
    const filtered = children.filter(c => c.class_id == classId);
    
    if (filtered.length === 0) {
        container.innerHTML = '<p class="small text-muted text-center py-3">Esta turma não tem crianças ou nenhuma selecionada.</p>';
        return;
    }
    
    filtered.forEach(c => {
        const div = document.createElement('div');
        div.className = 'col-md-6';
        div.innerHTML = `
            <div class="p-2 border rounded bg-light d-flex flex-column gap-1">
                <label class="small fw-bold mb-0">${c.name}</label>
                <input type="text" name="child_notes[${c.id}]" class="form-control form-control-sm" placeholder="Nota específica para ${c.name.split(' ')[0]}...">
            </div>
        `;
        container.appendChild(div);
    });
});
</script>
<?php endif; ?>

<style>
.truncate-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;  
    overflow: hidden;
}
</style>
