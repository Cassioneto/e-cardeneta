<div class="row justify-content-center align-items-center" style="min-height: 70vh;">
    <div class="col-md-5">
        <div class="glass-card p-5 text-center">
            <h2 class="mb-4 text-primary" style="font-weight: 700;">Acesso e-Cardeneta</h2>
            <p class="text-muted mb-4">Entre com suas credenciais de segurança para continuar.</p>
            <form action="/?action=login" method="POST">
                <!-- OWASP A01/05: Token CSRF para todas as submissões de POST -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email" placeholder="nome@exemplo.com" required autocomplete="email">
                    <label for="email">E-mail</label>
                </div>
                <div class="form-floating mb-4">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Senha" required autocomplete="current-password">
                    <label for="password">Senha</label>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary-custom btn-lg">Entrar no Sistema</button>
                </div>
            </form>
            <div class="mt-4">
                <small class="text-muted"><i class="bi bi-shield-lock"></i> Seus dados estão seguros e criptografados (LGPD / OWASP Compliance).</small>
            </div>
        </div>
    </div>
</div>
