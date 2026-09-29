<!-- VIEW: Utilizadores -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold" style="color:var(--primary-color)">Utilizadores do Sistema</h2>
    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalNovoUser">
        + Novo Utilizador
    </button>
</div>

<div class="glass-card p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Telefone</th>
                    <th>Perfil</th>
                    <th>Criado em</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $u): ?>
                <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($u['name']) ?></td>
                    <td class="text-muted"><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                    <td>
                        <?php
                        $badge = match($u['role']) {
                            'super_admin' => 'bg-danger',
                            'admin'       => 'bg-warning text-dark',
                            'professor'   => 'bg-primary',
                            'parent'      => 'bg-success',
                            'baba'        => 'bg-secondary',
                            default       => 'bg-secondary'
                        };
                        $labels = [
                            'super_admin' => 'Super Admin',
                            'admin'       => 'Admin',
                            'professor'   => 'Professor(a)',
                            'parent'      => 'Pai/Mãe',
                            'baba'        => 'Babá',
                        ];
                        ?>
                        <span class="badge <?= $badge ?>"><?= $labels[$u['role']] ?? $u['role'] ?></span>
                    </td>
                    <td class="text-muted small"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <?php if($u['id'] !== $_SESSION['user_id']): ?>
                        <form method="POST" action="/?action=users&op=delete" class="d-inline"
                              onsubmit="return confirm('Apagar o utilizador <?= htmlspecialchars($u['name'], ENT_QUOTES) ?>?')">
                            <input type="hidden" name="csrf_token" value="<?= \Config\Security::generateCSRFToken() ?>">
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Apagar</button>
                        </form>
                        <?php else: ?>
                            <span class="text-muted small">(você)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Novo Utilizador -->
<div class="modal fade" id="modalNovoUser" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-card border-0">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">Novo Utilizador</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="/?action=users&op=create" id="formNovoUser" novalidate>
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= \Config\Security::generateCSRFToken() ?>">
          <div class="mb-3">
            <label class="form-label fw-semibold">Nome Completo</label>
            <input type="text" name="name" class="form-control" required placeholder="Ex: Maria da Silva">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">E-mail</label>
            <input type="email" name="email" class="form-control" required placeholder="email@exemplo.com">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Telefone (WhatsApp)</label>
            <input type="text" name="phone" class="form-control" placeholder="351912345678 (sem + ou espaços)">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Perfil</label>
            <select name="role" class="form-select" required>
              <option value="parent">Pai / Mãe / Responsável</option>
              <option value="baba">Babá</option>
              <option value="professor">Professor(a)</option>
              <option value="admin">Administrador</option>
              <option value="super_admin">Super Admin</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Senha Inicial</label>
            <input type="password" name="password" class="form-control" required minlength="8"
                   placeholder="Mínimo 8 caracteres">
            <div class="form-text">O utilizador deve alterar na primeira sessão.</div>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary-custom">Criar Utilizador</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Validação client-side
document.getElementById('formNovoUser').addEventListener('submit', function(e) {
    const senha = this.querySelector('[name="password"]').value;
    if (senha.length < 8) {
        e.preventDefault();
        alert('A senha deve ter no mínimo 8 caracteres.');
    }
});
</script>
