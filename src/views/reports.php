<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold" style="color:var(--primary-color)">📊 Relatórios e Gestão</h2>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary btn-sm" onclick="window.print()">🖨️ Imprimir Página</button>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Ocupação por Turma -->
    <div class="col-lg-4">
        <div class="glass-card p-4 h-100">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Ocupação por Turma</h5>
            <?php foreach($classStats as $s): ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span><?= htmlspecialchars($s['name']) ?></span>
                        <span class="fw-bold"><?= $s['total'] ?> crianças</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?= min(100, $s['total'] * 5) ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Medicamentos -->
    <div class="col-lg-4">
        <div class="glass-card p-4 h-100">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Medicamentos (30 dias)</h5>
            <div class="d-flex flex-column gap-3 mt-4">
            <?php 
            $totalMed = array_sum(array_column($medStats, 'total')) ?: 1;
            foreach($medStats as $s): 
                $label = $s['administered'] ? '✅ Administrados' : '⏳ Pendentes/Falhados';
                $color = $s['administered'] ? 'success' : 'warning';
                $pct = round(($s['total'] / $totalMed) * 100);
            ?>
                <div>
                    <h6 class="small fw-bold text-<?= $color ?> mb-0"><?= $label ?></h6>
                    <div class="d-flex align-items-center gap-2">
                        <div class="flex-grow-1 progress" style="height: 6px;">
                            <div class="progress-bar bg-<?= $color ?>" style="width: <?= $pct ?>%"></div>
                        </div>
                        <span class="small text-muted"><?= $pct ?>%</span>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Saúde Geral -->
    <div class="col-lg-4">
        <div class="glass-card p-4 h-100">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Registos de Saúde</h5>
            <ul class="list-unstyled mt-3">
            <?php foreach($healthStats as $h): ?>
                <li class="d-flex justify-content-between py-2 border-bottom border-light">
                    <span class="small"><?= ucfirst(str_replace('_', ' ', $h['record_type'])) ?></span>
                    <span class="badge bg-light text-dark rounded-pill"><?= $h['total'] ?></span>
                </li>
            <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="glass-card p-4">
    <h5 class="fw-bold mb-4">Exportação de Dados (LGPD)</h5>
    <p class="text-muted small">De acordo com a LGPD, o encarregado de educação tem direito à portabilidade dos dados. Utilize os filtros abaixo para gerar um PDF com o historial completo da criança.</p>
    
    <div class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label small fw-bold">Selecionar Criança</label>
            <select class="form-select custom-select-lg">
                <option value="">-- Selecione --</option>
                <!-- Preenchido via PHP ou JS -->
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-bold">Período</label>
            <select class="form-select">
                <option>Último Mês</option>
                <option>Ano Letivo</option>
                <option>Sempre</option>
            </select>
        </div>
        <div class="col-md-5">
            <button class="btn btn-primary-custom w-100" onclick="alert('Funcionalidade de PDF em desenvolvimento com DomPDF/mpdf.')">
                📥 Gerar Relatório Individual (PDF)
            </button>
        </div>
    </div>
</div>
