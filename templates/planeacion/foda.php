<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/planeacion">Planes</a></li>
        <li class="breadcrumb-item"><a href="/planeacion/<?= $id ?>"><?= htmlspecialchars($plan['plan_nombre'] ?? 'Plan ' . $id) ?></a></li>
        <li class="breadcrumb-item active">FODA</li>
    </ol>
</nav>

<h5 class="mb-4"><i class="fas fa-chess-board me-2"></i>Análisis FODA</h5>

<?php if ($foda): ?>
    <?php $matriz = json_decode($foda['analisis_contenido'] ?? '{}', true); ?>
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card-box border-start border-4 border-success">
                <div class="card-box-header text-success"><i class="fas fa-plus-circle me-2"></i>Fortalezas</div>
                <div class="card-box-body">
                    <ul class="mb-0">
                    <?php foreach (($matriz['fortalezas'] ?? []) as $f): ?>
                        <li><?= htmlspecialchars($f) ?></li>
                    <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-box border-start border-4 border-danger">
                <div class="card-box-header text-danger"><i class="fas fa-minus-circle me-2"></i>Debilidades</div>
                <div class="card-box-body">
                    <ul class="mb-0">
                    <?php foreach (($matriz['debilidades'] ?? []) as $d): ?>
                        <li><?= htmlspecialchars($d) ?></li>
                    <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-box border-start border-4 border-primary">
                <div class="card-box-header text-primary"><i class="fas fa-lightbulb me-2"></i>Oportunidades</div>
                <div class="card-box-body">
                    <ul class="mb-0">
                    <?php foreach (($matriz['oportunidades'] ?? []) as $o): ?>
                        <li><?= htmlspecialchars($o) ?></li>
                    <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-box border-start border-4 border-warning">
                <div class="card-box-header text-warning"><i class="fas fa-triangle-exclamation me-2"></i>Amenazas</div>
                <div class="card-box-body">
                    <ul class="mb-0">
                    <?php foreach (($matriz['amenazas'] ?? []) as $a): ?>
                        <li><?= htmlspecialchars($a) ?></li>
                    <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="card-box">
        <div class="card-box-body text-center py-5 text-muted">
            <i class="fas fa-chess-board" style="font-size:4rem;color:#ccc;margin-bottom:16px;display:block;"></i>
            <h5>No hay análisis FODA registrado</h5>
            <p>Utiliza el asistente IA para generar un análisis FODA automáticamente.</p>
        </div>
    </div>
<?php endif; ?>
