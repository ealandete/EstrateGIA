<?php
$totalPeligros = count($peligros);
$totalIncidentes = count($incidentes);
$peligrosCriticos = count(array_filter($peligros, fn($p) => ($p['peligro_nivel'] ?? '') === 'inaceptable'));
$accidentes = count(array_filter($incidentes, fn($i) => ($i['inc_tipo'] ?? '') === 'accidente'));
$diasPerdidos = array_sum(array_column($incidentes, 'inc_dias_incapacidad') ?? [0]);
$costoTotal = array_sum(array_column($incidentes, 'inc_costo') ?? [0]);
?>
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-label">Incidentes <?= $anio ?></div>
            <div class="stat-value"><?= $totalIncidentes ?></div>
            <small class="text-muted">Accidentes: <?= $accidentes ?></small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-label">D&iacute;as Perdidos</div>
            <div class="stat-value"><?= $diasPerdidos ?></div>
            <small class="text-muted">Costo: $<?= number_format($costoTotal, 0, ',', '.') ?></small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-label">Peligros Identificados</div>
            <div class="stat-value"><?= $totalPeligros ?></div>
            <small class="text-muted">Cr&iacute;ticos: <?= $peligrosCriticos ?></small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-label">Capacitaciones <?= $anio ?></div>
            <div class="stat-value">0</div>
            <small class="text-muted">Ex&aacute;menes: 0</small>
        </div>
    </div>
</div>

<?php if (!empty($indicadores)): ?>
<h6 class="mb-2"><i class="fas fa-chart-simple me-2" style="color:#ffc107"></i>Indicadores de Gesti&oacute;n</h6>
<div class="row g-3 mb-3">
    <?php foreach ($indicadores as $ind): $nombre = $ind['sind_nombre'] ?? ''; $valor = (float)($ind['sind_valor'] ?? 0); $unidad = $ind['sind_unidad'] ?? ''; $meta = (float)($ind['sind_meta'] ?? 1); ?>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-label"><?= htmlspecialchars($nombre) ?: '—' ?></div>
            <div class="stat-value"><?= $valor ? number_format($valor, 0) : '—' ?><?= $unidad ? ' ' . htmlspecialchars($unidad) : '' ?></div>
            <div class="progress mt-1" style="height:5px">
                <?php $pct = $meta > 0 ? min(100, ($valor / $meta) * 100) : 0; ?>
                <div class="progress-bar bg-<?= $pct >= 90 ? 'success' : ($pct >= 50 ? 'warning' : 'danger') ?>" style="width:<?= $pct ?>%"></div>
            </div>
            <small class="text-muted">Meta: <?= $meta > 0 ? number_format($meta, 0) : '—' ?><?= $unidad ? ' ' . htmlspecialchars($unidad) : '' ?></small>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-md-9">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="stat-card text-center">
                    <button class="btn btn-sm btn-outline-warning mb-2" data-bs-toggle="modal" data-bs-target="#modalIndicador"><i class="fas fa-plus me-1"></i>A&ntilde;adir KPI</button>
                    <small class="d-block text-muted">Indicador de gesti&oacute;n SST</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card text-center">
                    <a href="?seccion=reportes&anio=<?= $anio ?>" class="btn btn-sm btn-outline-primary mb-2"><i class="fas fa-file-pdf me-1"></i>Generar Reporte</a>
                    <small class="d-block text-muted">Informes normativos</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card text-center">
                    <a href="?seccion=inspecciones&anio=<?= $anio ?>" class="btn btn-sm btn-outline-success mb-2"><i class="fas fa-magnifying-glass me-1"></i>Nueva Inspecci&oacute;n</a>
                    <small class="d-block text-muted">Registrar inspecci&oacute;n</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card-box">
            <div class="card-box-header"><i class="fas fa-triangle-exclamation me-2"></i>Matriz de Peligros (<?= $totalPeligros ?>)</div>
            <div class="card-box-body p-0">
                <?php if (empty($peligros)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-triangle-exclamation" style="font-size:2rem;display:block;margin-bottom:8px;color:#ccc"></i>
                    No hay peligros registrados.
                </div>
                <?php else: ?>
                <table class="table-box small mb-0">
                    <thead><tr><th>C&oacute;digo</th><th>Peligro</th><th>Tipo</th><th>Nivel</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($peligros, 0, 8) as $p): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($p['peligro_codigo']) ?></strong></td>
                        <td><?= htmlspecialchars(mb_substr($p['peligro_descripcion'] ?? '', 0, 50)) ?></td>
                        <td><?= htmlspecialchars($p['peligro_tipo'] ?? '') ?></td>
                        <td><span class="badge bg-<?= ($p['peligro_nivel'] ?? '') === 'inaceptable' ? 'danger' : (($p['peligro_nivel'] ?? '') === 'importante' ? 'warning' : 'info') ?>"><?= htmlspecialchars($p['peligro_nivel'] ?? '') ?></span></td>
                        <td><span class="badge bg-<?= ($p['peligro_estado'] ?? '') === 'controlado' ? 'success' : 'warning' ?>"><?= htmlspecialchars($p['peligro_estado'] ?? '') ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($totalPeligros > 8): ?>
                <div class="text-center py-2"><a href="?seccion=peligros&anio=<?= $anio ?>" class="small">Ver todos (<?= $totalPeligros ?>)</a></div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card-box">
            <div class="card-box-header"><i class="fas fa-clipboard-list me-2"></i>&Uacute;ltimos Incidentes (<?= $totalIncidentes ?>)</div>
            <div class="card-box-body p-0">
                <?php if (empty($incidentes)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-clipboard-list" style="font-size:2rem;display:block;margin-bottom:8px;color:#ccc"></i>
                    No hay incidentes registrados.
                </div>
                <?php else: ?>
                <table class="table-box small mb-0">
                    <thead><tr><th>C&oacute;digo</th><th>Fecha</th><th>Tipo</th><th>Gravedad</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($incidentes, 0, 8) as $inc): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($inc['inc_codigo'] ?? '') ?></strong></td>
                        <td><?= date('d/m/Y', strtotime($inc['inc_fecha'] ?? '')) ?></td>
                        <td><?= htmlspecialchars($inc['inc_tipo'] ?? '') ?></td>
                        <td><span class="badge bg-<?= ($inc['inc_gravedad'] ?? '') === 'grave' ? 'danger' : (($inc['inc_gravedad'] ?? '') === 'moderado' ? 'warning' : 'info') ?>"><?= htmlspecialchars($inc['inc_gravedad'] ?? '') ?></span></td>
                        <td><span class="badge bg-<?= ($inc['inc_estado'] ?? '') === 'cerrado' ? 'success' : 'warning' ?>"><?= htmlspecialchars($inc['inc_estado'] ?? '') ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($totalIncidentes > 8): ?>
                <div class="text-center py-2"><a href="?seccion=incidentes&anio=<?= $anio ?>" class="small">Ver todos (<?= $totalIncidentes ?>)</a></div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalIndicador">
    <div class="modal-dialog">
        <form method="POST" action="/sst/indicador/crear" class="modal-content">
            <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
            <div class="modal-header"><h5>A&ntilde;adir Indicador SST</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><input type="text" name="nombre" class="form-control form-control-sm" placeholder="Nombre del indicador *" required></div>
                <div class="row g-2 mb-2">
                    <div class="col-6"><input type="text" name="formula" class="form-control form-control-sm" placeholder="F&oacute;rmula"></div>
                    <div class="col-6"><input type="text" name="unidad" class="form-control form-control-sm" placeholder="Unidad (%)"></div>
                </div>
                <div class="row g-2">
                    <div class="col-6"><input type="number" name="meta" class="form-control form-control-sm" placeholder="Meta" step="0.01"></div>
                    <div class="col-6"><input type="number" name="valor" class="form-control form-control-sm" placeholder="Valor actual" step="0.01"></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-warning">Guardar</button></div>
        </form>
    </div>
</div>
