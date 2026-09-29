<?php
$role = $_SESSION['user_role'] ?? '';
$isStaff = in_array($role, ['super_admin', 'admin', 'professor']);
$isAdmin = in_array($role, ['super_admin', 'admin']);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold" style="color:var(--primary-color)">📅 Eventos da Escola</h2>
    <?php if($isAdmin): ?>
    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalNovoEvento">
        + Criar Evento
    </button>
    <?php endif; ?>
</div>

<div class="row g-4">
    <?php if(empty($events)): ?>
        <div class="col-12">
            <div class="glass-card p-5 text-center text-muted">
                <h5>Nenhum evento agendado.</h5>
                <p>Os eventos escolares e de turma aparecerão aqui.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach($events as $e): ?>
        <div class="col-md-6">
            <div class="glass-card h-100 overflow-hidden d-flex">
                <div class="p-4 d-flex flex-column align-items-center justify-content-center bg-primary text-white" style="width: 100px; flex-shrink: 0;">
                    <div class="fs-4 fw-bold mb-0"><?= date('d', strtotime($e['event_date'])) ?></div>
                    <div class="small fw-light"><?= date('M', strtotime($e['event_date'])) ?></div>
                </div>
                <div class="p-4 flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="fw-bold mb-0"><?= htmlspecialchars($e['title']) ?></h5>
                        <?php if($isAdmin): ?>
                        <form method="POST" action="/?action=eventos&op=delete" onsubmit="return confirm('Apagar evento?')">
                            <input type="hidden" name="csrf_token" value="<?= \Config\Security::generateCSRFToken() ?>">
                            <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger border-0 p-0" title="Apagar">🗑️</button>
                        </form>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3 small">
                        <div class="text-muted mb-1"><i class="bi bi-clock me-2"></i><?= date('H:i', strtotime($e['event_time'])) ?></div>
                        <div class="text-muted"><i class="bi bi-geo-alt me-2"></i><?= htmlspecialchars($e['location'] ?: 'Na Escola') ?></div>
                    </div>

                    <p class="text-muted small mb-3"><?= htmlspecialchars($e['description']) ?></p>
                    
                    <div class="d-flex gap-2">
                        <form method="POST" action="/?action=eventos&op=responder">
                            <input type="hidden" name="csrf_token" value="<?= \Config\Security::generateCSRFToken() ?>">
                            <input type="hidden" name="event_id" value="<?= (int)$e['id'] ?>">
                            <input type="hidden" name="status" value="going">
                            <?php if(($e['user_status'] ?? '') === 'going'): ?>
                                <button type="submit" class="btn btn-sm btn-success px-3"><i class="bi bi-check-circle me-1"></i> Confirmado</button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-sm btn-primary-custom px-3">Confirmar Presença</button>
                            <?php endif; ?>
                        </form>
                        <button class="btn btn-sm btn-outline-secondary px-3" data-bs-toggle="modal" data-bs-target="#modalQuemVai<?= $e['id'] ?>">Ver Quem Vai</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Ver Quem Vai (Posicionado fora do card para não ser cortado) -->
        <div class="modal fade" id="modalQuemVai<?= $e['id'] ?>" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card border-0">
              <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Presenças Confirmadas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <ul class="list-group list-group-flush">
                    <?php if(empty($attendees[$e['id']])): ?>
                        <li class="list-group-item bg-transparent text-muted text-center py-4">Ninguém confirmou ainda.</li>
                    <?php else: ?>
                        <?php foreach($attendees[$e['id']] as $attendee): ?>
                            <li class="list-group-item bg-transparent py-3 d-flex align-items-center justify-content-between">
                                <div>
                                    <i class="bi bi-person-check text-success me-2"></i>
                                    <strong><?= htmlspecialchars($attendee['parent']) ?></strong>
                                    <?php if($attendee['children']): ?>
                                        <div class="small text-muted ms-4 mt-1">
                                            <i class="bi bi-emoji-smile me-1"></i> <?= htmlspecialchars($attendee['children']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
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

<?php if($isAdmin): ?>
<!-- Modal Novo Evento -->
<div class="modal fade" id="modalNovoEvento" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-card border-0">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">Novo Evento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="/?action=eventos&op=create">
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= \Config\Security::generateCSRFToken() ?>">
          
          <div class="mb-3">
            <label class="form-label fw-semibold">Título do Evento</label>
            <input type="text" name="title" class="form-control" placeholder="Ex: Festa de Primavera, Reunião de Pais..." required>
          </div>

          <div class="row mb-3">
              <div class="col-md-7">
                  <label class="form-label fw-semibold">Data</label>
                  <input type="date" name="event_date" class="form-control" required>
              </div>
              <div class="col-md-5">
                  <label class="form-label fw-semibold">Hora</label>
                  <input type="time" name="event_time" class="form-control" value="09:00" required>
              </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Local</label>
            <input type="text" name="location" class="form-control" placeholder="Ex: Auditório principal, Sala da Turma A">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Descrição</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
          </div>

          <div class="form-check mt-3">
            <input class="form-check-input" type="checkbox" name="send_whatsapp" id="send_wa_event" value="1" checked>
            <label class="form-check-label" for="send_wa_event">
                <i class="bi bi-whatsapp text-success"></i> Notificar via WhatsApp (Geral)
            </label>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary-custom">Agendar Evento</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>
