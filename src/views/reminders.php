<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold" style="color:var(--primary-color)">🔔 Lembretes</h2>
    <?php if($isStaff): ?>
    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalNovoLembrete">
        + Criar Lembrete
    </button>
    <?php endif; ?>
</div>

<div class="row g-4">
    <?php if($isStaff): ?>
    <!-- Lembretes Internos (Somente Professores/Admin) -->
    <div class="col-lg-6">
        <div class="glass-card p-4 h-100">
            <h5 class="fw-bold mb-3 text-secondary border-bottom pb-2"><i class="bi bi-journal-text me-2"></i> Notas Internas (Professores)</h5>
            <div class="list-group list-group-flush mt-3">
                <?php 
                $internals = array_filter($reminders, fn($r) => $r['type'] === 'internal');
                if(empty($internals)): 
                ?>
                    <p class="text-muted small text-center py-4">Sem notas internas.</p>
                <?php else: ?>
                    <?php foreach($internals as $r): ?>
                    <div class="list-group-item bg-transparent border-0 px-0 pb-3">
                        <div class="d-flex justify-content-between">
                            <small class="text-primary fw-bold"><?= date('d/m H:i', strtotime($r['created_at'])) ?></small>
                            <form method="POST" action="/?action=lembretes&op=delete">
                                <input type="hidden" name="csrf_token" value="<?= \Config\Security::generateCSRFToken() ?>">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-link text-danger p-0 border-0"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                        <div class="small fw-medium"><?= htmlspecialchars($r['content']) ?></div>
                        <small class="text-muted" style="font-size: 0.7rem;">Criado por: <?= htmlspecialchars($r['author_name']) ?></small>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Lembretes Enviados aos Pais -->
    <div class="col-lg-<?= $isStaff ? '6' : '12' ?>">
        <div class="glass-card p-4 h-100">
            <h5 class="fw-bold mb-3 text-primary border-bottom pb-2"><i class="bi bi-whatsapp me-2"></i> Lembretes para Pais</h5>
            <div class="list-group list-group-flush mt-3">
                <?php 
                $externals = array_filter($reminders, fn($r) => $r['type'] === 'external');
                if(empty($externals)): 
                ?>
                    <p class="text-muted small text-center py-4">Sem lembretes enviados aos pais.</p>
                <?php else: ?>
                    <?php foreach($externals as $r): ?>
                    <div class="list-group-item bg-white shadow-sm rounded-3 mb-2 p-3 border-0">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <span class="badge bg-light text-primary border"><?= htmlspecialchars($r['class_name'] ?? ($r['target_name'] ?? 'Geral')) ?></span>
                            <small class="text-muted"><?= date('d/m H:i', strtotime($r['created_at'])) ?></small>
                        </div>
                        <div class="small mb-2"><?= htmlspecialchars($r['content']) ?></div>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted" style="font-size: 0.7rem;">De: <?= htmlspecialchars($r['author_name']) ?></small>
                            <?php if($isStaff): ?>
                            <form method="POST" action="/?action=lembretes&op=delete">
                                <input type="hidden" name="csrf_token" value="<?= \Config\Security::generateCSRFToken() ?>">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger border-0">🗑️</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if($isStaff): ?>
<!-- Modal Novo Lembrete -->
<div class="modal fade" id="modalNovoLembrete" tabindex="-1">
...
</div>

<script>
document.getElementById('select-reminder-type').addEventListener('change', function() {
    document.getElementById('external-fields').style.display = (this.value === 'external') ? 'block' : 'none';
});
</script>
<?php endif; ?>
