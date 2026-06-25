<div class="mb-3"><a href="/indicadores" class="btn btn-sm btn-light"><i class="fas fa-arrow-left me-1"></i>Volver</a></div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card-box">
            <div class="card-box-header"><?= htmlspecialchars($indicador['indicador_nombre'] ?? 'Indicador') ?></div>
            <div class="card-box-body">
                <p><strong>Tipo:</strong> <span class="badge-status badge-<?= $indicador['categoria_tipo'] ?>"><?= $indicador['categoria_nombre'] ?></span></p>
                <p><strong>Fórmula:</strong> <code><?= htmlspecialchars($indicador['indicador_formula'] ?? 'No definida') ?></code></p>
                <p><strong>Unidad:</strong> <?= htmlspecialchars($indicador['indicador_unidad_medida'] ?? '-') ?></p>
                <p><strong>Frecuencia:</strong> <?= $indicador['indicador_frecuencia_medicion'] ?? '-' ?></p>
                <p><strong>Tendencia esperada:</strong> <?= $indicador['indicador_tendencia_esperada'] ?? '-' ?></p>
                <p><strong>Fuente de datos:</strong> <?= htmlspecialchars($indicador['indicador_fuente_datos'] ?? 'Manual') ?></p>
                <p><strong>Responsable:</strong> <?= htmlspecialchars($indicador['responsable_nombre'] ?? 'Sin asignar') ?></p>
                <?php if (!empty($indicador['indicador_sistemas_json'])): ?>
                <p><strong>Sistemas:</strong> <?php foreach (json_decode($indicador['indicador_sistemas_json'],true)?:[] as $s): ?><span class="badge bg-secondary me-1"><?= $s ?></span><?php endforeach; ?></p>
                <?php endif; ?>
                <?php if (!empty($indicador['indicador_descripcion'])): ?>
                <p class="small text-muted mt-2 border-top pt-2"><?= htmlspecialchars($indicador['indicador_descripcion']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card-box">
            <div class="card-box-header">Metas</div>
            <div class="card-box-body">
                <?php foreach ($metas as $meta): ?>
                <div class="mb-2 p-2 border rounded">
                    <strong><?= $meta['meta_periodo'] ?>:</strong> <?= number_format($meta['meta_valor'], 2) ?> <?= $indicador['indicador_unidad_medida'] ?? '' ?>
                    <div class="progress mt-1" style="height:6px"><div class="progress-bar bg-<?= ($meta['ultimo_cumplimiento']??0)>=90?'success':(($meta['ultimo_cumplimiento']??0)>=70?'warning':'danger') ?>" style="width:<?= min($meta['ultimo_cumplimiento']??0,100) ?>%"></div></div>
                    <small class="text-muted">Cumplimiento: <?= number_format($meta['ultimo_cumplimiento']??0,1) ?>% | Rango: <?= $meta['meta_valor_minimo']??0 ?> - <?= $meta['meta_valor_maximo']??0 ?></small>
                </div>
                <?php endforeach; ?>
                <?php if (empty($metas)): ?><div class="text-muted small text-center py-2">Sin metas definidas. <a href="#" data-bs-toggle="modal" data-bs-target="#modalMeta">Crear meta</a></div><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card-box mt-4">
    <div class="card-box-header d-flex justify-content-between">
        <span><i class="fas fa-history me-2"></i>Histórico de Mediciones</span>
        <small class="text-muted">Última actualización: <?= $mediciones[0]['medicion_fecha'] ?? 'Nunca' ?> · <?= count($mediciones) ?> registros</small>
    </div>
    <div class="card-box-body">
        <?php if (!empty($mediciones)): ?>
        <canvas id="chartTendencia" height="80" class="mb-3"></canvas>
        <?php endif; ?>
        <table class="table-box">
            <thead><tr><th>Período</th><th>Valor</th><th>Meta</th><th>% Cumpl.</th><th>Semáforo</th><th>Origen</th><th>Registrado por</th></tr></thead>
            <tbody>
            <?php foreach ($mediciones as $m): ?>
            <tr>
                <td><?= $m['medicion_periodo'] ?? $m['medicion_fecha'] ?></td>
                <td><strong><?= number_format($m['medicion_valor'] ?? $m['medicion_resultado'], 2) ?></strong> <?= $indicador['indicador_unidad_medida'] ?? '' ?></td>
                <td><?= number_format($m['medicion_meta'] ?? 0, 2) ?></td>
                <td><?= number_format($m['medicion_cumplimiento_porcentaje']??0, 1) ?>%</td>
                <td><span class="semaforo-dot d-inline-block" style="background:<?= ($m['medicion_semaforo']??'')==='verde'?'#28a745':(($m['medicion_semaforo']??'')==='amarillo'?'#ffc107':'#dc3545') ?>;width:16px;height:16px;border-radius:50%"></span></td>
                <td><small><?= htmlspecialchars($m['medicion_origen'] ?? 'Manual') ?></small></td>
                <td><small><?= htmlspecialchars($m['registrado_por'] ?? '—') ?></small></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal crear Meta -->
<div class="modal fade" id="modalMeta"><div class="modal-dialog"><form method="POST" action="/indicadores/meta/crear" class="modal-content">
    <input type="hidden" name="indicador_id" value="<?= $indicador['indicador_id'] ?>">
    <div class="modal-header"><h5>Nueva Meta</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2 mb-2"><div class="col-6"><input type="text" name="periodo" class="form-control form-control-sm" placeholder="Período (YYYY-MM)" required></div><div class="col-6"><input type="number" name="valor" class="form-control form-control-sm" placeholder="Valor meta" step="0.01" required></div></div>
        <div class="row g-2"><div class="col-6"><input type="number" name="valor_minimo" class="form-control form-control-sm" placeholder="Mínimo" step="0.01"></div><div class="col-6"><input type="number" name="valor_maximo" class="form-control form-control-sm" placeholder="Máximo" step="0.01"></div></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar Meta</button></div>
</form></div></div>

<?php if (!empty($mediciones)): ?>
<script>
var labels = <?= json_encode(array_column(array_reverse($mediciones), 'medicion_periodo')) ?>;
var values = <?= json_encode(array_column(array_reverse($mediciones), 'medicion_resultado')) ?>;
var meta = <?= json_encode($metas[0]['meta_valor'] ?? 0) ?>;
new Chart(document.getElementById('chartTendencia'), {
    type:'line',
    data:{
        labels:labels,
        datasets:[
            {label:'Mediciones',data:values,borderColor:'#007bff',tension:0.3},
            {label:'Meta',data:Array(labels.length).fill(meta),borderColor:'#28a745',borderDash:[5,5]}
        ]
    },
    options:{responsive:true,plugins:{legend:{display:true}}}
});
</script>
<?php endif; ?>
