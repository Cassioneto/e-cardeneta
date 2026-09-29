<!-- VIEW: Mensagens -->
<?php
use Config\Database;
$db = Database::getConnection();
$userId = $_SESSION['user_id'];

// Mensagens recebidas e enviadas
$mensagens = $db->prepare("
    SELECT c.*, u.name as sender_name, u2.name as receiver_name
    FROM communications c
    JOIN users u ON u.id = c.sender_id
    LEFT JOIN users u2 ON u2.id = c.receiver_id
    WHERE c.type = 'message' AND (c.sender_id = ? OR c.receiver_id = ?)
    ORDER BY c.created_at DESC LIMIT 60
");
$mensagens->execute([$userId, $userId]);
$mensagens = $mensagens->fetchAll();

// Destinatários disponíveis
$destinatarios = $db->query("
    SELECT id, name, role FROM users WHERE id != {$userId} ORDER BY name
")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold" style="color:var(--primary-color)">💬 Mensagens</h2>
    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalNovaMensagem">
        + Nova Mensagem
    </button>
</div>

<div class="glass-card p-4">
    <?php if(empty($mensagens)): ?>
        <div class="text-center text-muted py-5">
            <div style="font-size:3rem">💬</div>
            <h5 class="mt-3">Nenhuma mensagem ainda.</h5>
        </div>
    <?php else: ?>
    <div class="list-group list-group-flush">
    <?php foreach($mensagens as $m): ?>
    <?php $isOwn = (int)$m['sender_id'] === (int)$userId; ?>
    <div class="list-group-item border-0 py-3 <?= $isOwn ? 'bg-light' : '' ?>">
        <div class="d-flex gap-3 align-items-start <?= $isOwn ? 'flex-row-reverse' : '' ?>">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
                 style="width:42px;height:42px;background:var(--<?= $isOwn ? 'secondary' : 'primary' ?>-color)">
                <?= mb_strtoupper(mb_substr($isOwn ? ($_SESSION['user_name']??'?') : $m['sender_name'], 0, 1)) ?>
            </div>
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 <?= $isOwn ? 'justify-content-end' : '' ?> mb-1">
                    <strong class="small"><?= htmlspecialchars($isOwn ? 'Eu' : $m['sender_name']) ?></strong>
                    <?php if($m['sent_via_whatsapp']): ?>
                        <span class="badge bg-success" style="font-size:.65rem">WA ✔</span>
                    <?php endif; ?>
                    <small class="text-muted"><?= date('d/m H:i', strtotime($m['created_at'])) ?></small>
                </div>
                <div class="p-3 rounded-3 <?= $isOwn ? 'bg-primary text-white ms-auto' : 'bg-white' ?>"
                     style="max-width:520px;<?= $isOwn ? 'margin-left:auto' : '' ?>">
                    <?= nl2br(htmlspecialchars($m['content'])) ?>
                </div>
                <?php if(!$isOwn && !empty($m['receiver_name'])): ?>
                <small class="text-muted mt-1">Para: <?= htmlspecialchars($m['receiver_name']) ?></small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Nova Mensagem -->
<div class="modal fade" id="modalNovaMensagem" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-card border-0">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">Nova Mensagem</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="/?action=messages&op=send">
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= \Config\Security::generateCSRFToken() ?>">
          <input type="hidden" name="type" value="message">
          <div class="mb-3">
            <label class="form-label fw-semibold">Para</label>
            <select name="receiver_id" class="form-select" required>
              <option value="">-- Selecionar destinatário --</option>
              <?php foreach($destinatarios as $d): ?>
              <?php
              $roleLabel = match($d['role']) {
                'super_admin'=> 'Super Admin', 'admin'=>'Admin', 'professor'=>'Professor(a)',
                'parent'=>'Pai/Mãe', 'baba'=>'Babá', default=>$d['role']
              };
              ?>
              <option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['name']) ?> (<?= $roleLabel ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Mensagem</label>
            <textarea name="message" class="form-control" rows="4" required placeholder="Escreva a sua mensagem..."></textarea>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="send_whatsapp" id="msg_wa" value="1" checked>
            <label class="form-check-label" for="msg_wa"><i class="bi bi-whatsapp text-success"></i> Enviar via WhatsApp</label>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary-custom">Enviar</button>
        </div>
      </form>
    </div>
  </div>
</div>
