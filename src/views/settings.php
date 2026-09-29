<!-- VIEW: Configurações do Sistema -->
<div class="row g-4">
  <!-- Coluna Configurações -->
  <div class="col-lg-7">
    <div class="glass-card p-4">
        <h3 class="fw-bold mb-4">Configurações da Instituição</h3>
        <form action="/?action=settings" method="POST">
            <input type="hidden" name="csrf_token" value="<?= \Config\Security::generateCSRFToken() ?>">

            <h6 class="text-uppercase text-muted fw-bold mt-3 mb-3">🎨 Identidade Visual</h6>
            <div class="row mb-3">
                <div class="col-6">
                    <label class="form-label fw-semibold">Cor Primária</label>
                    <input type="color" name="color_primary" class="form-control form-control-color w-100"
                           style="height:50px"
                           value="<?= htmlspecialchars($systemSettings['color_primary'] ?? '#6C5CE7') ?>">
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">Cor Secundária</label>
                    <input type="color" name="color_secondary" class="form-control form-control-color w-100"
                           style="height:50px"
                           value="<?= htmlspecialchars($systemSettings['color_secondary'] ?? '#FD79A8') ?>">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">URL do Logo da Instituição</label>
                <input type="url" name="logo_path" class="form-control"
                       value="<?= htmlspecialchars($systemSettings['logo_path'] ?? '') ?>"
                       placeholder="https://exemplo.com/logo.png">
            </div>

            <hr>
            <h6 class="text-uppercase text-muted fw-bold mt-3 mb-3">📡 API WhatsApp (Microserviço Node.js)</h6>
            <div class="mb-3">
                <label class="form-label fw-semibold">URL do Endpoint de Envio</label>
                <input type="url" name="whatsapp_api_url" class="form-control"
                       value="<?= htmlspecialchars($systemSettings['whatsapp_api_url'] ?? 'http://127.0.0.1:3000/send') ?>">
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Token Secreto da API</label>
                <input type="password" name="whatsapp_api_token" class="form-control"
                       value="<?= htmlspecialchars($systemSettings['whatsapp_api_token'] ?? '') ?>">
            </div>
            
            <hr>
            <h6 class="text-uppercase text-muted fw-bold mt-3 mb-3">🕒 Horário de Funcionamento (Notificações)</h6>
            <div class="row mb-4">
                <div class="col-6">
                    <label class="form-label fw-semibold">Hora de Início</label>
                    <input type="time" name="business_hour_start" class="form-control"
                           value="<?= htmlspecialchars($systemSettings['business_hour_start'] ?? '08:00') ?>">
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">Hora de Fim</label>
                    <input type="time" name="business_hour_end" class="form-control"
                           value="<?= htmlspecialchars($systemSettings['business_hour_end'] ?? '18:00') ?>">
                </div>
                <div class="col-12 mt-2">
                    <small class="text-muted">Notificações automáticas (WhatsApp) serão suspensas fora deste intervalo.</small>
                </div>
            </div>

            <button type="submit" class="btn btn-primary-custom w-100">💾 Guardar Configurações</button>
        </form>
    </div>
  </div>

  <!-- Coluna QR Code WhatsApp -->
  <div class="col-lg-5">
    <div class="glass-card p-4 text-center">
        <h5 class="fw-bold mb-3">📱 Ligação WhatsApp</h5>
        <div id="wa-status-badge" class="mb-3">
            <span class="badge bg-secondary fs-6">A verificar...</span>
        </div>
        <div id="qr-wrapper">
            <p class="text-muted small">A carregar estado do microserviço...</p>
        </div>
        <div class="d-flex gap-2 justify-content-center mt-3">
            <button id="btn-refresh-qr" class="btn btn-outline-secondary btn-sm" onclick="checkWAStatus()">
                🔄 Atualizar Estado
            </button>
            <button class="btn btn-outline-danger btn-sm" onclick="resetSession()" title="Limpar sessão e gerar novo QR">
                🗑️ Resetar Sessão
            </button>
        </div>
        <div class="alert alert-info mt-4 text-start small">
            <strong>Como conectar:</strong><br>
            1. Inicie o microserviço: <code>cd whatsapp && npm install && node index.js</code><br>
            2. O QR code aparecerá aqui automaticamente.<br>
            3. Abra o WhatsApp no telemóvel → Dispositivos Ligados → Ligar Dispositivo → Escaneie.
            <hr class="my-2">
            <strong>⚠️ Se o QR não funcionar:</strong> clique em <em>Resetar Sessão</em> para limpar a sessão corrompida e gerar um novo QR.
        </div>
    </div>

    <!-- Preview de cores -->
    <div class="glass-card p-4 mt-4">
        <h6 class="fw-bold mb-3">🖌️ Pré-visualização de Cores</h6>
        <div class="d-flex gap-3 align-items-center mb-2">
            <div id="preview-primary" style="width:48px;height:48px;border-radius:12px;background:<?= htmlspecialchars($systemSettings['color_primary'] ?? '#6C5CE7') ?>"></div>
            <div>
                <small class="text-muted d-block">Cor Primária</small>
                <strong id="preview-primary-hex"><?= htmlspecialchars($systemSettings['color_primary'] ?? '#6C5CE7') ?></strong>
            </div>
        </div>
        <div class="d-flex gap-3 align-items-center">
            <div id="preview-secondary" style="width:48px;height:48px;border-radius:12px;background:<?= htmlspecialchars($systemSettings['color_secondary'] ?? '#FD79A8') ?>"></div>
            <div>
                <small class="text-muted d-block">Cor Secundária</small>
                <strong id="preview-secondary-hex"><?= htmlspecialchars($systemSettings['color_secondary'] ?? '#FD79A8') ?></strong>
            </div>
        </div>
    </div>
  </div>
</div>

<!-- qrcode.js Local -->
<script src="/js/qrcode.js"></script>
<script>
const WA_API = '<?= htmlspecialchars($systemSettings['whatsapp_api_url'] ?? 'http://127.0.0.1:3000') ?>'.replace('/send', '');
const WA_TOKEN = '<?= htmlspecialchars($systemSettings['whatsapp_api_token'] ?? 'e_cardeneta_super_secure_key_32b') ?>';

let qrInstance = null;

async function checkWAStatus() {
    const statusBadge = document.getElementById('wa-status-badge');
    const qrWrapper = document.getElementById('qr-wrapper');
    
    try {
        const res = await fetch(`${WA_API}/status?token=${encodeURIComponent(WA_TOKEN)}`);
        if (!res.ok) throw new Error('Microserviço não acessível.');
        const data = await res.json();
        
        if (data.ready) {
            statusBadge.innerHTML = '<span class="badge bg-success fs-6">✅ Conectado ao WhatsApp</span>';
            qrWrapper.innerHTML = '<p class="text-success fw-bold mt-2">WhatsApp ligado e pronto para enviar mensagens!</p>';
        } else if (data.qr) {
            statusBadge.innerHTML = '<span class="badge bg-warning text-dark fs-6">⏳ Aguarda scan do QR</span>';
            
            if (typeof QRCode !== 'undefined') {
                qrWrapper.innerHTML = '';
                new QRCode(qrWrapper, {
                    text: data.qr,
                    width: 220,
                    height: 220,
                    colorDark : "#000000",
                    colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.H
                });
            } else {
                qrWrapper.innerHTML = '<div class="alert alert-danger px-2 mt-2">Erro: Biblioteca QRCode.js não encontrada em /js/qrcode.js</div>';
            }

            if (data.error) {
                qrWrapper.innerHTML += `<div class="alert alert-warning text-start small mt-2"><strong>Aviso:</strong> ${data.error}</div>`;
            }
        } else if (data.error) {
            statusBadge.innerHTML = '<span class="badge bg-danger fs-6">❌ Erro</span>';
            qrWrapper.innerHTML = `<div class="alert alert-danger text-start small"><strong>Erro:</strong> ${data.error}<br><br>Tente clicar em <strong>Resetar Sessão</strong>.</div>`;
        } else {
            statusBadge.innerHTML = '<span class="badge bg-secondary fs-6">🔄 A iniciar...</span>';
            qrWrapper.innerHTML = '<p class="text-muted">Microserviço a arrancar, aguarde...</p>';
        }
    } catch(e) {
        statusBadge.innerHTML = '<span class="badge bg-danger fs-6">❌ Microserviço offline</span>';
        qrWrapper.innerHTML = `<p class="text-danger small"><strong>Erro:</strong> ${e.message}<br><br>Certifique-se que o microserviço Node.js está a correr (<code>cd whatsapp &amp;&amp; node index.js</code> na pasta do projeto).</p>`;
    }
}

async function resetSession() {
    if (!confirm('Tem a certeza? Isto irá apagar a sessão actual e gerar um novo QR code.')) return;
    const statusBadge = document.getElementById('wa-status-badge');
    const qrWrapper   = document.getElementById('qr-wrapper');
    statusBadge.innerHTML = '<span class="badge bg-warning text-dark fs-6">⏳ A resetar...</span>';
    qrWrapper.innerHTML   = '<p class="text-muted">A limpar sessão, aguarde...</p>';
    try {
        const res = await fetch(`${WA_API}/reset?token=${encodeURIComponent(WA_TOKEN)}`, { method: 'POST' });
        const data = await res.json();
        qrWrapper.innerHTML = '<p class="text-info">Sessão limpa! Novo QR em breve...</p>';
        setTimeout(checkWAStatus, 4000);
    } catch(e) {
        qrWrapper.innerHTML = `<p class="text-danger small">Erro ao resetar: ${e.message}</p>`;
    }
}

// Actualizar QR a cada 15 segundos
checkWAStatus();
setInterval(checkWAStatus, 15000);

// Preview de cores em tempo real
document.querySelector('[name="color_primary"]')?.addEventListener('input', function() {
    document.getElementById('preview-primary').style.background = this.value;
    document.getElementById('preview-primary-hex').textContent = this.value;
});
document.querySelector('[name="color_secondary"]')?.addEventListener('input', function() {
    document.getElementById('preview-secondary').style.background = this.value;
    document.getElementById('preview-secondary-hex').textContent = this.value;
});
</script>
