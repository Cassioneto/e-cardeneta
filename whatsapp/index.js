const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const express = require('express');
const cors = require('cors');
const fs = require('fs');
const path = require('path');

const app = express();
app.use(express.json());
app.use(cors());

// API Token
const API_TOKEN = process.env.WA_TOKEN || 'e_cardeneta_super_secure_key_32b';

// Estado global
let currentQR  = null;
let isReady    = false;
let lastError  = null;
let client     = null;

// ─── Inicializar cliente ────────────────────────────────────────────────────
function createClient() {
    client = new Client({
        authStrategy: new LocalAuth({ dataPath: path.join(__dirname, '.wwebjs_auth') }),
        puppeteer: {
            headless: true,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--no-first-run',
                '--no-zygote',
                '--disable-gpu',
                '--disable-extensions',
                '--disable-software-rasterizer'
            ]
        }
    });

    client.on('qr', (qr) => {
        currentQR  = qr;
        isReady    = false;
        lastError  = null;
        qrcode.generate(qr, { small: true });
        console.log('[WhatsApp] Novo QR Code gerado. Escaneie com o telemóvel.');
    });

    client.on('authenticated', () => {
        console.log('[WhatsApp] Autenticado com sucesso!');
        lastError = null;
    });

    client.on('ready', () => {
        currentQR = null;
        isReady   = true;
        lastError = null;
        console.log('[WhatsApp] Pronto e conectado!');
    });

    client.on('auth_failure', (msg) => {
        isReady   = false;
        currentQR = null;
        lastError = 'Falha de autenticação: ' + msg;
        console.error('[WhatsApp] Falha de autenticação:', msg);
        console.log('[WhatsApp] A limpar sessão corrompida...');
        clearSession();
        setTimeout(() => {
            console.log('[WhatsApp] A reinicializar cliente...');
            createClient();
        }, 3000);
    });

    client.on('disconnected', (reason) => {
        isReady   = false;
        currentQR = null;
        console.log('[WhatsApp] Desconectado:', reason);
    });

    client.on('error', (err) => {
        console.error('[WhatsApp] Erro interno:', err.message);
        lastError = err.message;
    });

    client.initialize().catch(err => {
        console.error('[WhatsApp] Erro ao inicializar:', err.message);
        lastError = err.message;
    });
}

function clearSession() {
    const sessionPath = path.join(__dirname, '.wwebjs_auth', 'session');
    try {
        if (fs.existsSync(sessionPath)) {
            fs.rmSync(sessionPath, { recursive: true, force: true });
            console.log('[WhatsApp] Sessão removida com sucesso.');
        }
    } catch (e) {
        console.error('[WhatsApp] Erro ao limpar sessão:', e.message);
    }
}

// ─── ENDPOINTS ──────────────────────────────────────────────────────────────

// Estado e QR
app.get('/status', (req, res) => {
    if (req.query.token !== API_TOKEN) {
        return res.status(403).json({ error: 'Unauthorized' });
    }
    res.json({
        ready: isReady,
        qr:    currentQR,
        error: lastError
    });
});

// Resetar sessão (útil quando QR não funciona)
app.post('/reset', (req, res) => {
    if ((req.query.token || req.body?.token) !== API_TOKEN) {
        return res.status(403).json({ error: 'Unauthorized' });
    }
    console.log('[WhatsApp] Reset pedido via API. A reiniciar...');
    isReady   = false;
    currentQR = null;
    lastError = null;

    if (client) {
        client.destroy().catch(() => {}).finally(() => {
            clearSession();
            setTimeout(createClient, 1000);
        });
    } else {
        clearSession();
        setTimeout(createClient, 1000);
    }
    res.json({ success: true, message: 'Sessão resetada. Novo QR em breve.' });
});

// Enviar mensagem
app.post('/send', async (req, res) => {
    const { token, number, message } = req.body;
    if (token !== API_TOKEN) return res.status(403).json({ error: 'Unauthorized' });
    if (!isReady)            return res.status(503).json({ error: 'WhatsApp não está conectado. Escaneie o QR code primeiro.' });
    if (!number || !message) return res.status(400).json({ error: 'Número ou mensagem em falta.' });

    try {
        // Normalizar o número (remover todos os não-dígitos)
        const cleanNumber = number.replace(/\D/g, '');

        // Resolver o chatId correto via API do WhatsApp (evita "No LID for user")
        const numberId = await client.getNumberId(cleanNumber);
        if (!numberId) {
            console.warn(`[WhatsApp] Número ${cleanNumber} não encontrado no WhatsApp.`);
            return res.status(404).json({ error: `Número ${cleanNumber} não está registado no WhatsApp.` });
        }

        await client.sendMessage(numberId._serialized, message);
        console.log(`[WhatsApp] Mensagem enviada para ${cleanNumber} (${numberId._serialized})`);
        res.json({ success: true });
    } catch (error) {
        console.error('[WhatsApp] Erro ao enviar:', error.message);
        res.status(500).json({ error: 'Erro ao enviar mensagem: ' + error.message });
    }
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`[WhatsApp] Microserviço a correr em http://localhost:${PORT}`);
    console.log('[WhatsApp] A inicializar cliente WhatsApp...');
    createClient();
});
