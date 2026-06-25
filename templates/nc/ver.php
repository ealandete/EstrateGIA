<?php $updated = $_GET['updated'] ?? null; ?>
<?php if ($updated): ?><div class="alert alert-success">NC actualizada</div><?php endif; ?>

<nav class="mb-3"><ol class="breadcrumb small"><li class="breadcrumb-item"><a href="/nc">No Conformidades</a></li><li class="breadcrumb-item active"><?= htmlspecialchars($nc['nc_codigo']) ?></li></ol></nav>

<div class="row g-4">
    <div class="col-md-8">
        <!-- Descripción del hallazgo -->
        <div class="card-box mb-3">
            <div class="card-box-header d-flex justify-content-between">
                <span><i class="fas fa-triangle-exclamation me-2" style="color:#dc3545"></i><?= htmlspecialchars($nc['nc_codigo']) ?></span>
                <span class="badge bg-<?= ['abierta'=>'danger','analisis'=>'warning','plan_accion'=>'info','implementacion'=>'primary','verificacion'=>'secondary','cerrada'=>'success'][$nc['nc_estado']] ?>"><?= $nc['nc_estado'] ?></span>
            </div>
            <div class="card-box-body">
                <h6>Descripción del Hallazgo</h6>
                <p><?= nl2br(htmlspecialchars($nc['nc_descripcion'])) ?></p>
                <div class="row small text-muted mt-2">
                    <div class="col-3"><strong>Origen:</strong> <?= str_replace('_',' ',$nc['nc_origen']) ?></div>
                    <div class="col-3"><strong>Tipo:</strong> <?= str_replace('_',' ',$nc['nc_tipo']) ?></div>
                    <div class="col-3"><strong>Gravedad:</strong> <span class="badge bg-<?= $nc['nc_gravedad']==='mayor'?'danger':'warning' ?>"><?= $nc['nc_gravedad'] ?></span></div>
                    <div class="col-3"><strong>Requisito:</strong> <?= htmlspecialchars($nc['nc_requisito_iso']??'-') ?></div>
                </div>
            </div>
        </div>

        <!-- METODOLOGÍA DE RESOLUCIÓN -->
        <div class="card-box mb-3">
            <div class="card-box-header"><i class="fas fa-magnifying-glass me-2"></i>Análisis y Resolución</div>
            <div class="card-box-body">
                <form method="POST" action="/nc/actualizar/<?= $nc['nc_id'] ?>">
                    
                    <!-- Selección de metodología -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Metodología de Resolución</label>
                        <div class="row g-2" id="metodologiaSelector">
                            <?php 
                            $metodos = [
                                '5w' => ['5 Porqués', 'Análisis iterativo de causa preguntando ¿por qué? 5 veces hasta llegar a la causa raíz', 'question'],
                                'ishikawa' => ['Espina de Pescado', 'Diagrama causa-efecto con 6M: Mano de obra, Método, Máquina, Material, Medio, Medición', 'fish'],
                                '8d' => ['8 Disciplinas (8D)', 'Metodología estructurada en 8 pasos para resolución de problemas complejos', 'list-ol'],
                                'phva' => ['Ciclo PHVA', 'Planear-Hacer-Verificar-Actuar para mejora continua', 'arrows-spin'],
                            ];
                            $metSel = $nc['nc_analisis_causa'] ? (json_decode($nc['nc_analisis_causa'], true)['metodo'] ?? '5w') : '5w';
                            $analisisData = json_decode($nc['nc_analisis_causa'] ?? '{}', true) ?: [];
                            foreach ($metodos as $mk => $mm):
                            ?>
                            <div class="col-md-3">
                                <label class="card p-2 text-center metodologia-card <?= $metSel===$mk?'border-primary bg-primary bg-opacity-10':'' ?>" style="cursor:pointer">
                                    <input type="radio" name="metodo" value="<?= $mk ?>" <?= $metSel===$mk?'checked':'' ?> class="d-none" onchange="cambiarMetodo('<?= $mk ?>')">
                                    <i class="fas fa-<?= $mm[2] ?> fs-4 mb-1" style="color:#1a73e8"></i>
                                    <strong class="d-block small"><?= $mm[0] ?></strong>
                                    <small class="text-muted"><?= $mm[1] ?></small>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Campos por metodología -->
                    <div id="metodoFields">
                        <!-- 5 Porqués -->
                        <div class="metodo-panel" id="panel_5w" style="<?= $metSel==='5w'?'':'display:none' ?>">
                            <h6 class="mb-2">Análisis de 5 Porqués</h6>
                            <p class="small text-muted mb-2">Pregunta ¿por qué? iterativamente hasta encontrar la causa raíz del problema.</p>
                            <div class="mb-2"><label class="form-label small">Problema (¿Qué ocurrió?)</label><input type="text" name="w_problema" class="form-control form-control-sm" value="<?= htmlspecialchars($analisisData['problema']??'') ?>"></div>
                            <?php for($i=1;$i<=5;$i++): ?>
                            <div class="mb-1"><label class="form-label small">¿Por qué? #<?= $i ?></label><input type="text" name="w_porque<?= $i ?>" class="form-control form-control-sm" value="<?= htmlspecialchars($analisisData["porque$i"]??'') ?>" placeholder="Porque..."></div>
                            <?php endfor; ?>
                            <div class="mb-2"><label class="form-label small">Causa Raíz Identificada</label><input type="text" name="w_causa_raiz" class="form-control form-control-sm" value="<?= htmlspecialchars($analisisData['causa_raiz']??'') ?>"></div>
                        </div>

                        <!-- Ishikawa -->
                        <div class="metodo-panel" id="panel_ishikawa" style="<?= $metSel==='ishikawa'?'':'display:none' ?>">
                            <h6 class="mb-2">Diagrama de Ishikawa (6M)</h6>
                            <div class="row g-2">
                                <?php 
                                $ms = ['Mano de obra / Personal','Método / Procedimiento','Máquina / Equipo','Material / Insumo','Medio ambiente / Entorno','Medición / Indicador'];
                                foreach ($ms as $i => $m): 
                                ?>
                                <div class="col-md-6"><label class="form-label small"><?= $m ?></label><input type="text" name="m<?= $i+1 ?>" class="form-control form-control-sm" value="<?= htmlspecialchars($analisisData["m".($i+1)]??'') ?>" placeholder="Causas..."></div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- 8D -->
                        <div class="metodo-panel" id="panel_8d" style="<?= $metSel==='8d'?'':'display:none' ?>">
                            <?php 
                            $dsteps = ['D1: Formar equipo','D2: Describir el problema','D3: Acción de contención','D4: Causa raíz','D5: Acción correctiva permanente','D6: Implementar y validar','D7: Prevenir recurrencia','D8: Reconocer al equipo'];
                            foreach ($dsteps as $i => $ds): 
                            ?>
                            <div class="mb-1"><label class="form-label small"><?= $ds ?></label><input type="text" name="d<?= $i+1 ?>" class="form-control form-control-sm" value="<?= htmlspecialchars($analisisData["d".($i+1)]??'') ?>" placeholder="<?= $ds ?>..."></div>
                            <?php endforeach; ?>
                        </div>

                        <!-- PHVA -->
                        <div class="metodo-panel" id="panel_phva" style="<?= $metSel==='phva'?'':'display:none' ?>">
                            <?php 
                            $psteps = ['P: Planificar (¿Qué se va a hacer? ¿Cómo? ¿Cuándo?)','H: Hacer (Ejecutar lo planificado)','V: Verificar (¿Se logró el resultado esperado?)','A: Actuar (Estandarizar o ajustar)'];
                            foreach ($psteps as $i => $ps): 
                            ?>
                            <div class="mb-1"><label class="form-label small"><?= $ps ?></label><textarea name="phva_<?= $i+1 ?>" class="form-control form-control-sm" rows="2" placeholder="<?= $ps ?>..."><?= htmlspecialchars($analisisData["phva_".($i+1)]??'') ?></textarea></div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <hr>
                    <!-- Plan de acción y estado -->
                    <div class="row g-2 mb-2">
                        <div class="col-4">
                            <label class="form-label small">Nuevo Estado</label>
                            <select name="estado" class="form-select form-select-sm">
                                <option value="abierta" <?= $nc['nc_estado']=='abierta'?'selected':'' ?>>Abierta</option>
                                <option value="analisis" <?= $nc['nc_estado']=='analisis'?'selected':'' ?>>En Análisis</option>
                                <option value="plan_accion" <?= $nc['nc_estado']=='plan_accion'?'selected':'' ?>>Plan de Acción</option>
                                <option value="implementacion" <?= $nc['nc_estado']=='implementacion'?'selected':'' ?>>Implementación</option>
                                <option value="verificacion" <?= $nc['nc_estado']=='verificacion'?'selected':'' ?>>Verificación</option>
                                <option value="cerrada">Cerrar NC</option>
                            </select>
                        </div>
                        <div class="col-8">
                            <label class="form-label small">Nota de seguimiento</label>
                            <input type="text" name="nota_seguimiento" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="mb-2"><label class="form-label small">Plan de Acción Correctiva</label><textarea name="plan_accion" class="form-control" rows="3"><?= htmlspecialchars($nc['nc_plan_accion']??'') ?></textarea></div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Actualizar NC</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card-box mb-3"><div class="card-box-header">Detalles</div><div class="card-box-body small">
            <div><strong>Proceso:</strong> <?= htmlspecialchars($nc['proceso_nombre']??'-') ?></div>
            <div><strong>Responsable:</strong> <?= htmlspecialchars($nc['responsable_nombre']??'No asignado') ?></div>
            <div><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($nc['nc_fecha_deteccion'])) ?></div>
            <?php if ($nc['nc_fecha_cierre']): ?><div><strong>Cierre:</strong> <?= date('d/m/Y', strtotime($nc['nc_fecha_cierre'])) ?></div><?php endif; ?>
        </div></div>

        <?php if (!empty($seguimiento)): ?>
        <div class="card-box"><div class="card-box-header"><i class="fas fa-clock-rotate-left me-2"></i>Seguimiento</div>
        <div class="card-box-body p-0">
            <?php foreach (array_reverse($seguimiento) as $seg): ?>
            <div class="p-2 px-3 border-bottom small"><strong><?= $seg['estado'] ?></strong> <small class="text-muted"><?= $seg['fecha'] ?></small><?php if ($seg['nota']): ?><div class="text-muted"><?= htmlspecialchars($seg['nota']) ?></div><?php endif; ?></div>
            <?php endforeach; ?>
        </div></div>
        <?php endif; ?>
    </div>
</div>

<script>
function cambiarMetodo(metodo) {
    document.querySelectorAll('.metodo-panel').forEach(p => p.style.display = 'none');
    document.getElementById('panel_' + metodo).style.display = 'block';
    document.querySelectorAll('.metodologia-card').forEach(c => c.classList.remove('border-primary','bg-primary','bg-opacity-10'));
    event.target.closest('.metodologia-card').classList.add('border-primary','bg-primary','bg-opacity-10');
}
</script>
