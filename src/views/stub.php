<div class="glass-card p-5 text-center">
    <h2 class="text-primary mb-3"><i class="bi bi-tools"></i> Módulo em Desenvolvimento</h2>
    <h4 class="text-secondary"><?= htmlspecialchars(ucfirst($action ?? 'Módulo')) ?></h4>
    <p class="text-muted mt-4">
        Esta tela está mapeada e pronta para receber as regras de negócio de <strong><?= htmlspecialchars($action ?? 'este recurso') ?></strong>. <br>
        A rota e as permissões de acesso já estão garantidas por segurança.
    </p>
    <a href="/?action=dashboard" class="btn btn-outline-primary mt-3">Voltar ao Dashboard</a>
</div>
