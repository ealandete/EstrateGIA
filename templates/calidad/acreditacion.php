<?php $evaluado = $_GET['evaluado'] ?? null; ?>
<?php if ($evaluado): ?><div class="alert alert-success">Estandar evaluado correctamente</div><?php endif; ?>
<?php if (isset($_GET['visita_creada'])): ?><div class="alert alert-success">Visita creada correctamente</div><?php endif; ?>
<?php if (isset($_GET['plan_creado'])): ?><div class="alert alert-success">Plan de mejora creado correctamente</div><?php endif; ?>
<?php if (isset($_GET['plan_cerrado'])): ?><div class="alert alert-success">Plan de mejora cerrado</div><?php endif; ?>
<?php if (isset($_GET['seguimiento_creado'])): ?><div class="alert alert-success">Seguimiento registrado</div><?php endif; ?>
<?php if (isset($_GET['fase_cambiada'])): ?><div class="alert alert-success">Fase del ciclo actualizada</div><?php endif; ?>
<?php if (isset($_GET['sua_cargados'])): ?><div class="alert alert-info"><?= (int)$_GET['sua_cargados'] ?> estandares SUA cargados</div><?php endif; ?>
<?php if (isset($_GET['fase_error'])): ?><div class="alert alert-danger">Transicion de fase no permitida</div><?php endif; ?>

<nav class="mb-3"><ol class="breadcrumb small"><li class="breadcrumb-item"><a href="/calidad">Calidad</a></li><li class="breadcrumb-item active">Acreditacion</li></ol></nav>

<div class="d-flex justify-content-between mb-3">
    <div>
        <h5><i class="fas fa-certificate me-2" style="color:#ffc107"></i>Acreditacion en Salud · <?= htmlspecialchars($empresa['empresa_nombre']) ?></h5>
        <small class="text-muted">Ministerio de Salud y Proteccion Social · Resolucion 5095/2018 · JCI 8th Edition</small>
    </div>
    <div>
        <a href="/calidad/autoevaluacion?empresa_id=<?= $empresaId ?>" class="btn btn-outline-success btn-sm me-1"><i class="fas fa-clipboard-check me-1"></i>Autoevaluacion</a>
        <a href="/acreditacion/reporte?empresa_id=<?= $empresaId ?>" class="btn btn-outline-primary btn-sm" target="_blank"><i class="fas fa-file-pdf me-1"></i>Informe</a>
    </div>
</div>

<!-- TABS -->
<ul class="nav nav-tabs mb-3" id="acreditacionTabs" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#estandares"><i class="fas fa-list-check me-1"></i>Estandares</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#visitas"><i class="fas fa-building me-1"></i>Visitas</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#planes-mejora"><i class="fas fa-clipboard me-1"></i>Planes de Mejora</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#seguimiento"><i class="fas fa-chart-line me-1"></i>Seguimiento</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#ciclo"><i class="fas fa-sync-alt me-1"></i>Ciclo</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#sua"><i class="fas fa-database me-1"></i>SUA Oficial</a></li>
</ul>

<div class="tab-content">
    <!-- ===== TAB 1: ESTANDARES ===== -->
    <div class="tab-pane fade show active" id="estandares">
        <!-- KPIs General -->
        <div class="row g-3 mb-4">
            <div class="col-md-2">
                <div class="card-box text-center"><div class="card-box-body">
                    <h3 class="text-primary mb-0"><?= $total ?></h3><small class="text-muted">Estandares Totales</small>
                </div></div>
            </div>
            <div class="col-md-2">
                <div class="card-box text-center"><div class="card-box-body">
                    <h3 class="text-<?= $pctCumplimiento>=90?'success':($pctCumplimiento>=60?'warning':'danger') ?> mb-0"><?= $pctCumplimiento ?>%</h3><small class="text-muted">Cumplimiento</small>
                </div></div>
            </div>
            <div class="col-md-2">
                <div class="card-box text-center"><div class="card-box-body">
                    <h3 class="text-success mb-0"><?= $cumplen ?></h3><small class="text-muted">Cumplen</small>
                </div></div>
            </div>
            <div class="col-md-2">
                <div class="card-box text-center"><div class="card-box-body">
                    <h3 class="text-warning mb-0"><?= $parcial ?></h3><small class="text-muted">Parcial</small>
                </div></div>
            </div>
            <div class="col-md-2">
                <div class="card-box text-center"><div class="card-box-body">
                    <h3 class="text-danger mb-0"><?= $noCumplen ?></h3><small class="text-muted">No Cumplen</small>
                </div></div>
            </div>
            <div class="col-md-2">
                <div class="card-box text-center"><div class="card-box-body">
                    <h3 class="mb-0"><?= $total - $cumplen - $parcial - $noCumplen ?></h3><small class="text-muted">Sin Evaluar</small>
                </div></div>
            </div>
        </div>

        <!-- Avance por Tipo de Estandar -->
        <h5 class="mb-3"><i class="fas fa-chart-bar me-2"></i>Avance por Estandar de Acreditacion</h5>
        <div class="row g-3 mb-4">
        <?php foreach ($porTipo as $tipo => $datos):
            $pctTipo = $datos['total'] > 0 ? round(($datos['cumple'] / $datos['total']) * 100, 1) : 0;
            $colorBar = $pctTipo >= 90 ? 'success' : ($pctTipo >= 60 ? 'primary' : 'warning');
            $labels = ['SUA'=>'Sist. Unico Acreditacion','ISO7101'=>'ISO 7101:2023','Habilitacion'=>'Habilitacion Res.3100'];
        ?>
        <div class="col-md-4">
        <div class="card-box h-100">
            <div class="card-box-header d-flex justify-content-between">
                <strong><?= $labels[$tipo] ?? $tipo ?></strong>
                <span class="badge bg-<?= $colorBar ?>"><?= $pctTipo ?>%</span>
            </div>
            <div class="card-box-body">
                <div class="progress mb-2" style="height:12px"><div class="progress-bar bg-<?= $colorBar ?>" style="width:<?= $pctTipo ?>%"><?= $pctTipo ?>%</div></div>
                <div class="d-flex justify-content-between small">
                    <span class="text-success"><?= $datos['cumple'] ?> Cumplen</span>
                    <span class="text-warning"><?= $datos['parcial'] ?> Parcial</span>
                    <span class="text-danger"><?= $datos['no_cumple'] ?> No Cumplen</span>
                    <span class="text-muted">/ <?= $datos['total'] ?></span>
                </div>
            </div>
        </div>
        </div>
        <?php endforeach; ?>
        </div>

        <!-- Cumplimiento por Grupo -->
        <div class="row g-4 mb-4">
            <div class="col-md-8">
                <h6><i class="fas fa-table me-2"></i>Estandares por Grupo</h6>
                <div class="card-box"><div class="card-box-body p-0" style="max-height:400px;overflow-y:auto">
                <table class="table-box small">
                    <thead><tr><th>Grupo</th><th>Estandar</th><th>Nivel</th><th>Puntaje</th><th>Cumplimiento</th><th>Evidencia</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($estandares as $est): $c = $est['ultimo_cumplimiento'] ?? 'no_evaluado'; ?>
                    <tr>
                        <td><small><?= htmlspecialchars($est['estandar_grupo']) ?></small></td>
                        <td><strong><?= htmlspecialchars($est['estandar_codigo']) ?></strong><br><small><?= htmlspecialchars(substr($est['estandar_nombre'], 0, 50)) ?></small></td>
                        <td><?= $est['estandar_nivel'] ?></td>
                        <td><span class="badge bg-<?= ($est['ultimo_puntaje']??0)>=90?'success':(($est['ultimo_puntaje']??0)>=70?'warning':'danger') ?>"><?= $est['ultimo_puntaje'] ?? 0 ?>%</span></td>
                        <td><span class="badge bg-<?= $c==='cumple'?'success':($c==='cumple_parcial'?'warning':($c==='no_cumple'?'danger':'secondary')) ?>"><?= str_replace('_',' ',$c) ?></span></td>
                        <td><small><?= htmlspecialchars(substr($est['evidencia_descripcion']??'', 0, 40)) ?></small></td>
                        <td><button class="btn btn-sm btn-outline-primary" onclick="abrirEvaluacion(<?= $est['estandar_id'] ?>,'<?= addslashes($est['estandar_codigo']) ?> - <?= addslashes($est['estandar_nombre']) ?>')"><i class="fas fa-edit"></i></button></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div></div>
            </div>
            <div class="col-md-4">
                <h6><i class="fas fa-tasks me-2"></i>Actividades de Acreditacion</h6>
                <?php if (empty($actividades)): ?>
                <div class="card-box"><div class="card-box-body text-center py-3 text-muted small">Sin actividades programadas</div></div>
                <?php else: ?>
                <div class="card-box"><div class="card-box-body p-0" style="max-height:400px;overflow-y:auto">
                <?php foreach ($actividades as $act): ?>
                <div class="p-2 border-bottom small">
                    <div class="d-flex justify-content-between">
                        <strong><?= htmlspecialchars($act['act_descripcion']) ?></strong>
                        <span class="badge bg-<?= $act['act_estado']==='completada'?'success':($act['act_estado']==='en_proceso'?'primary':'warning') ?>"><?= $act['act_estado'] ?></span>
                    </div>
                    <div class="text-muted"><?= $act['act_estandar_tipo'] ?> · Fin: <?= $act['act_fecha_fin'] ?> · <?= htmlspecialchars($act['responsable_nombre']??'-') ?></div>
                    <div class="progress mt-1" style="height:5px"><div class="progress-bar bg-<?= $act['act_avance']>=80?'success':'primary' ?>" style="width:<?= $act['act_avance'] ?? 0 ?>%"></div></div>
                </div>
                <?php endforeach; ?>
                </div></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ciclos de Acreditacion -->
        <?php if (!empty($ciclos)): ?>
        <h6 class="mb-3"><i class="fas fa-sync-alt me-2"></i>Ciclos de Acreditacion</h6>
        <div class="row g-3 mb-4">
        <?php foreach ($ciclos as $ciclo): $pct = $ciclo['nivel_puntaje_actual']; ?>
        <div class="col-md-4">
        <div class="card p-3 h-100" style="border-left:4px solid <?= $ciclo['nivel_estandar_tipo']==='SUA'?'#28a745':($ciclo['nivel_estandar_tipo']==='ISO7101'?'#007bff':'#ffc107') ?>">
            <div class="d-flex justify-content-between mb-2">
                <strong><?= $ciclo['nivel_estandar_tipo'] ?></strong>
                <span class="badge bg-<?= $pct>=90?'success':($pct>=60?'warning':'danger') ?>"><?= $pct ?>%</span>
            </div>
            <div class="progress mb-2" style="height:10px"><div class="progress-bar bg-<?= $pct>=90?'success':($pct>=60?'primary':'warning') ?>" style="width:<?= $pct ?>%"></div></div>
            <div class="d-flex justify-content-between small text-muted">
                <span>Meta: <?= $ciclo['nivel_puntaje_objetivo'] ?>%</span>
                <span>Fase: <?= $ciclo['nivel_fase'] ?></span>
            </div>
        </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ===== TAB 2: VISITAS ===== -->
    <div class="tab-pane fade" id="visitas">
        <div class="d-flex justify-content-between mb-3">
            <h6><i class="fas fa-building me-2"></i>Visitas de Acreditacion</h6>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalVisita"><i class="fas fa-plus me-1"></i>Nueva Visita</button>
        </div>

        <?php if (empty($visitas)): ?>
        <div class="card-box"><div class="card-box-body text-center py-4 text-muted">No hay visitas registradas. Cree la primera visita de acreditacion.</div></div>
        <?php else: ?>
        <div class="card-box"><div class="card-box-body p-0">
        <table class="table-box small">
            <thead><tr><th>Tipo</th><th>F. Programada</th><th>F. Real</th><th>Evaluador Lider</th><th>Hallazgos</th><th>NC</th><th>Observ.</th><th>Estado</th></tr></thead>
            <tbody>
            <?php foreach ($visitas as $v): ?>
            <tr>
                <td><span class="badge bg-<?= $v['visita_tipo']==='visita_evaluacion'?'primary':($v['visita_tipo']==='seguimiento'?'warning':($v['visita_tipo']==='reacreditacion'?'success':'info')) ?>"><?= str_replace('_',' ',ucfirst($v['visita_tipo'])) ?></span></td>
                <td><?= $v['visita_fecha_programada'] ? date('d/m/Y', strtotime($v['visita_fecha_programada'])) : '-' ?></td>
                <td><?= $v['visita_fecha_real'] ? date('d/m/Y', strtotime($v['visita_fecha_real'])) : '-' ?></td>
                <td><?= htmlspecialchars($v['visita_evaluador_lider'] ?? '-') ?></td>
                <td><?= $v['visita_hallazgos'] ?></td>
                <td><?= $v['visita_no_conformidades'] ?></td>
                <td><?= $v['visita_observaciones'] ?></td>
                <td><span class="badge bg-<?= $v['visita_estado']==='completada'?'success':($v['visita_estado']==='en_curso'?'primary':($v['visita_estado']==='cancelada'?'danger':'warning')) ?>"><?= $v['visita_estado'] ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div></div>
        <?php endif; ?>
    </div>

    <!-- ===== TAB 3: PLANES DE MEJORA ===== -->
    <div class="tab-pane fade" id="planes-mejora">
        <div class="d-flex justify-content-between mb-3">
            <h6><i class="fas fa-clipboard me-2"></i>Planes de Mejora Estructurados</h6>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalPlanMejora"><i class="fas fa-plus me-1"></i>Nuevo Plan</button>
        </div>

        <?php if (empty($planesMejora)): ?>
        <div class="card-box"><div class="card-box-body text-center py-4 text-muted">No hay planes de mejora. Cree el primer plan para abordar los estandares no cumplidos.</div></div>
        <?php else: ?>
        <div class="card-box"><div class="card-box-body p-0">
        <table class="table-box small">
            <thead><tr><th>Estandar</th><th>Accion</th><th>Responsable</th><th>F. Compromiso</th><th>F. Cierre</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($planesMejora as $pm): ?>
            <tr>
                <td><strong><?= htmlspecialchars($pm['estandar_codigo'] ?? '-') ?></strong><br><small><?= htmlspecialchars(substr($pm['estandar_nombre']??'', 0, 30)) ?></small></td>
                <td><small><?= htmlspecialchars(substr($pm['plan_accion'], 0, 60)) ?></small></td>
                <td><?= htmlspecialchars($pm['responsable_nombre'] ?? '-') ?></td>
                <td><?= $pm['plan_fecha_compromiso'] ? date('d/m/Y', strtotime($pm['plan_fecha_compromiso'])) : '-' ?></td>
                <td><?= $pm['plan_fecha_cierre'] ? date('d/m/Y', strtotime($pm['plan_fecha_cierre'])) : '-' ?></td>
                <td><span class="badge bg-<?= $pm['plan_estado']==='cerrado'?'success':($pm['plan_estado']==='en_progreso'?'primary':($pm['plan_estado']==='vencido'?'danger':'warning')) ?>"><?= $pm['plan_estado'] ?></span></td>
                <td>
                    <?php if ($pm['plan_estado'] !== 'cerrado'): ?>
                    <button class="btn btn-outline-warning btn-sm" onclick="document.getElementById('seguimientoPlanId').value='<?= $pm['plan_id'] ?>';new bootstrap.Modal(document.getElementById('modalSeguimiento')).show()"><i class="fas fa-plus"></i> Seg.</button>
                    <form method="POST" action="/acreditacion/plan-mejora/<?= $pm['plan_id'] ?>/cerrar" class="d-inline">
                        <button type="submit" class="btn btn-outline-success btn-sm" onclick="return confirm('Cerrar este plan de mejora?')"><i class="fas fa-check"></i></button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div></div>
        <?php endif; ?>
    </div>

    <!-- ===== TAB 4: SEGUIMIENTO ===== -->
    <div class="tab-pane fade" id="seguimiento">
        <h6 class="mb-3"><i class="fas fa-chart-line me-2"></i>Seguimiento de Planes de Mejora</h6>

        <?php if (empty($seguimientos)): ?>
        <div class="card-box"><div class="card-box-body text-center py-4 text-muted">No hay seguimientos registrados. Use el boton de seguimiento en la pestaña de Planes de Mejora.</div></div>
        <?php else: ?>
        <div class="card-box"><div class="card-box-body p-0">
        <table class="table-box small">
            <thead><tr><th>Plan ID</th><th>Fecha</th><th>Avance</th><th>Observaciones</th><th>Evidencia</th><th>Usuario</th></tr></thead>
            <tbody>
            <?php foreach ($seguimientos as $s): ?>
            <tr>
                <td>#<?= $s['plan_id'] ?></td>
                <td><?= date('d/m/Y', strtotime($s['seguimiento_fecha'])) ?></td>
                <td>
                    <div class="progress" style="height:8px;width:80px"><div class="progress-bar bg-<?= $s['seguimiento_avance']>=80?'success':($s['seguimiento_avance']>=40?'warning':'danger') ?>" style="width:<?= $s['seguimiento_avance'] ?>%"></div></div>
                    <?= $s['seguimiento_avance'] ?>%
                </td>
                <td><small><?= htmlspecialchars(substr($s['seguimiento_observaciones'] ?? '', 0, 60)) ?></small></td>
                <td><?= $s['seguimiento_evidencia_url'] ? '<a href="'.$s['seguimiento_evidencia_url'].'" target="_blank"><i class="fas fa-file"></i></a>' : '-' ?></td>
                <td><?= $s['seguimiento_usuario_id'] ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div></div>
        <?php endif; ?>
    </div>

    <!-- ===== TAB 5: CICLO (STATE MACHINE) ===== -->
    <div class="tab-pane fade" id="ciclo">
        <h6 class="mb-3"><i class="fas fa-sync-alt me-2"></i>State Machine del Ciclo de Acreditacion</h6>

        <div class="card-box mb-3">
            <div class="card-box-body">
                <p class="small text-muted mb-2">La maquina de estados controla las transiciones validas entre fases del ciclo de acreditacion. Seleccione el tipo de estandar y la nueva fase.</p>
                <form method="POST" action="/acreditacion/ciclo/cambiar-fase" class="row g-2">
                    <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
                    <div class="col-md-3">
                        <select name="nivel_estandar_tipo" class="form-select form-select-sm">
                            <option value="SUA">SUA</option>
                            <option value="ISO7101">ISO 7101</option>
                            <option value="Habilitacion">Habilitacion</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="nivel_fase" class="form-select form-select-sm">
                            <option value="preparacion">Preparacion</option>
                            <option value="autoevaluacion">Autoevaluacion</option>
                            <option value="plan_mejora">Plan de Mejora</option>
                            <option value="implementacion">Implementacion</option>
                            <option value="visita_simulacro">Visita Simulacro</option>
                            <option value="pre_visita">Pre-Visita</option>
                            <option value="visita_evaluacion">Visita Evaluacion</option>
                            <option value="informe_resultados">Informe de Resultados</option>
                            <option value="seguimiento">Seguimiento</option>
                            <option value="reacreditacion">Reacreditacion</option>
                            <option value="acreditado">Acreditado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="nivel_puntaje_objetivo" class="form-control form-control-sm" value="90" min="0" max="100" placeholder="Puntaje Objetivo">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-sm">Cambiar Fase</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- State Machine Visualization -->
        <div class="card-box"><div class="card-box-body">
            <h6>Transiciones Permitidas</h6>
            <div class="row g-2 small">
            <?php
            $transitions = [
                ['preparacion','autoevaluacion'],['preparacion','diagnostico'],
                ['autoevaluacion','plan_mejora'],['autoevaluacion','preparacion'],
                ['plan_mejora','visita_simulacro'],['plan_mejora','implementacion'],
                ['implementacion','visita_simulacro'],['implementacion','plan_mejora'],
                ['visita_simulacro','pre_visita'],['visita_simulacro','plan_mejora'],
                ['pre_visita','visita_evaluacion'],['pre_visita','visita_simulacro'],
                ['visita_evaluacion','informe_resultados'],['visita_evaluacion','plan_mejora'],
                ['informe_resultados','seguimiento'],['informe_resultados','acreditado'],
                ['seguimiento','reacreditacion'],['seguimiento','acreditado'],
                ['reacreditacion','preparacion'],['reacreditacion','acreditado'],
            ];
            foreach ($transitions as $t):
            ?>
                <div class="col-md-4">
                    <span class="badge bg-secondary"><?= str_replace('_',' ',ucfirst($t[0])) ?></span>
                    <i class="fas fa-arrow-right mx-2 text-muted"></i>
                    <span class="badge bg-primary"><?= str_replace('_',' ',ucfirst($t[1])) ?></span>
                </div>
            <?php endforeach; ?>
            </div>
        </div></div>

        <!-- Current Cycles -->
        <?php if (!empty($ciclos)): ?>
        <h6 class="mt-4 mb-3">Ciclos Actuales</h6>
        <div class="row g-3">
        <?php foreach ($ciclos as $ciclo): $pct = $ciclo['nivel_puntaje_actual']; ?>
        <div class="col-md-4">
        <div class="card p-3 h-100" style="border-left:4px solid <?= $ciclo['nivel_estandar_tipo']==='SUA'?'#28a745':($ciclo['nivel_estandar_tipo']==='ISO7101'?'#007bff':'#ffc107') ?>">
            <div class="d-flex justify-content-between mb-2">
                <strong><?= $ciclo['nivel_estandar_tipo'] ?></strong>
                <span class="badge bg-<?= $pct>=90?'success':($pct>=60?'warning':'danger') ?>"><?= $pct ?>%</span>
            </div>
            <div class="progress mb-2" style="height:10px"><div class="progress-bar bg-<?= $pct>=90?'success':($pct>=60?'primary':'warning') ?>" style="width:<?= $pct ?>%"></div></div>
            <div class="d-flex justify-content-between small text-muted">
                <span>Meta: <?= $ciclo['nivel_puntaje_objetivo'] ?>%</span>
                <span>Fase: <?= $ciclo['nivel_fase'] ?></span>
            </div>
        </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ===== TAB 6: SUA OFICIAL ===== -->
    <div class="tab-pane fade" id="sua">
        <div class="d-flex justify-content-between mb-3">
            <h6><i class="fas fa-database me-2"></i>Estandares SUA Resolucion 5095/2018</h6>
            <form method="POST" action="/acreditacion/cargar-estandares-sua" id="formCargarSUA" class="d-flex gap-2">
                <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
                <select name="sua_eje" class="form-select form-select-sm" style="width:200px">
                    <option value="todos">Todos los Ejes</option>
                    <option value="seguridad_paciente">Seguridad del Paciente</option>
                    <option value="humanizacion">Humanizacion</option>
                    <option value="gestion_tecnologia">Gestion de Tecnologia</option>
                    <option value="enfoque_riesgo">Enfoque de Riesgo</option>
                </select>
                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Esto importara los estandares SUA oficiales al modulo de acreditacion. Continuar?')"><i class="fas fa-download me-1"></i>Cargar Estandares</button>
            </form>
        </div>

        <p class="small text-muted">Sistema Unico de Acreditacion (SUA) segun Resolucion 5095/2018 del Ministerio de Salud y Proteccion Social de Colombia. Incluye 165+ estandares oficiales con cobertura JCI 8th Edition.</p>

        <?php if (empty($suaEstandares)): ?>
        <div class="card-box"><div class="card-box-body text-center py-4 text-muted">No hay estandares SUA cargados en el sistema. Ejecute la migracion SQL primero o haga clic en "Cargar Estandares".</div></div>
        <?php else: ?>
        <div class="row g-3 mb-3">
        <?php
        $ejes = [];
        foreach ($suaEstandares as $s) {
            $eje = $s['sua_eje'];
            if (!isset($ejes[$eje])) $ejes[$eje] = ['total' => 0, 'indispensable' => 0, 'complementario' => 0];
            $ejes[$eje]['total']++;
            if ($s['sua_tipo'] === 'indispensable') $ejes[$eje]['indispensable']++;
            else $ejes[$eje]['complementario']++;
        }
        $ejeLabels = ['seguridad_paciente'=>'Seguridad del Paciente','humanizacion'=>'Humanizacion','gestion_tecnologia'=>'Gestion Tecnologia','enfoque_riesgo'=>'Enfoque Riesgo'];
        $ejeColors = ['seguridad_paciente'=>'success','humanizacion'=>'primary','gestion_tecnologia'=>'info','enfoque_riesgo'=>'warning'];
        foreach ($ejes as $eje => $d):
        ?>
        <div class="col-md-3">
            <div class="card p-3 h-100" style="border-left:4px solid #<?= ['success'=>'28a745','primary'=>'007bff','info'=>'17a2b8','warning'=>'ffc107'][$ejeColors[$eje]] ?>">
                <strong><?= $ejeLabels[$eje] ?></strong>
                <div class="d-flex justify-content-between small text-muted">
                    <span><?= $d['total'] ?> estandares</span>
                    <span class="text-success"><?= $d['indispensable'] ?> indispensables</span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>

        <div class="card-box"><div class="card-box-body p-0" style="max-height:500px;overflow-y:auto">
        <table class="table-box small">
            <thead><tr><th>Codigo</th><th>Grupo</th><th>Subgrupo</th><th>#</th><th>Descripcion</th><th>Tipo</th><th>Eje</th></tr></thead>
            <tbody>
            <?php foreach ($suaEstandares as $s): ?>
            <tr>
                <td><strong><?= htmlspecialchars($s['sua_codigo']) ?></strong></td>
                <td><?= htmlspecialchars($s['sua_grupo']) ?></td>
                <td><?= htmlspecialchars($s['sua_subgrupo'] ?? '') ?></td>
                <td><?= $s['sua_numero'] ?></td>
                <td><small><?= htmlspecialchars(substr($s['sua_descripcion'], 0, 80)) ?></small></td>
                <td><span class="badge bg-<?= $s['sua_tipo']==='indispensable'?'danger':'secondary' ?>"><?= $s['sua_tipo'] ?></span></td>
                <td><span class="badge bg-<?= $ejeColors[$s['sua_eje']] ?>"><?= $ejeLabels[$s['sua_eje']] ?? $s['sua_eje'] ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div></div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL Evaluar Estandar (Enhanced with SUA 1-5 scale) -->
<div class="modal fade" id="modalEvaluar"><div class="modal-dialog"><form method="POST" action="/acreditacion/evaluar" class="modal-content" enctype="multipart/form-data">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
    <input type="hidden" name="estandar_id" id="evalEstandarId">
    <input type="hidden" name="escala" id="evalEscala" value="0-100">
    <div class="modal-header"><h5>Evaluar Estandar</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div id="evalTitulo" class="fw-bold mb-3"></div>
        <div class="row g-2 mb-2">
            <div class="col-6"><label class="form-label small">Escala</label><select name="escala_selector" class="form-select form-select-sm" onchange="cambiarEscala(this.value)"><option value="0-100">0-100%</option><option value="1-5">SUA 1-5</option></select></div>
            <div class="col-6" id="divCumplimiento"><label class="form-label small">Cumplimiento</label><select name="cumplimiento" class="form-select form-select-sm"><option value="no_cumple">No Cumple</option><option value="cumple_parcial">Cumple Parcial</option><option value="cumple">Cumple</option></select></div>
            <div class="col-6 d-none" id="divNivelSua"><label class="form-label small">Nivel SUA (1-5)</label><select name="nivel_sua" class="form-select form-select-sm"><option value="1">1 - No implementado</option><option value="2">2 - Parcialmente implementado</option><option value="3">3 - Implementado con brechas</option><option value="4">4 - Implementado</option><option value="5">5 - Implementado y sostenido</option></select></div>
        </div>
        <div class="mb-2" id="divPuntaje"><label class="form-label small">Puntaje (0-100)</label><input type="number" name="puntaje" class="form-control form-control-sm" min="0" max="100" value="50"></div>
        <div class="mb-2"><label class="form-label small">Evidencia</label><textarea name="evidencia" class="form-control form-control-sm" rows="2" placeholder="Describa la evidencia encontrada"></textarea></div>
        <div class="mb-2"><label class="form-label small">Plan de Mejora</label><textarea name="plan_mejora" class="form-control form-control-sm" rows="2" placeholder="Acciones correctivas si aplica"></textarea></div>
        <div class="mb-2"><label class="form-label small">Archivo de Evidencia</label><input type="file" name="evidencia_archivo" class="form-control form-control-sm" accept="image/*,.pdf"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Evaluar</button></div>
</form></div></div>

<!-- MODAL Nueva Visita -->
<div class="modal fade" id="modalVisita"><div class="modal-dialog"><form method="POST" action="/acreditacion/visita/crear" class="modal-content" enctype="multipart/form-data">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
    <input type="hidden" name="ciclo_id" value="<?= $ciclos[0]['nivel_id'] ?? '' ?>">
    <div class="modal-header"><h5>Nueva Visita de Acreditacion</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2 mb-2">
            <div class="col-6"><label class="form-label small">Tipo</label><select name="visita_tipo" class="form-select form-select-sm"><option value="visita_evaluacion">Visita de Evaluacion</option><option value="autoevaluacion">Autoevaluacion</option><option value="seguimiento">Seguimiento</option><option value="reacreditacion">Reacreditacion</option></select></div>
            <div class="col-6"><label class="form-label small">Estado</label><select name="visita_estado" class="form-select form-select-sm"><option value="programada">Programada</option><option value="en_curso">En Curso</option><option value="completada">Completada</option></select></div>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-6"><label class="form-label small">Fecha Programada</label><input type="date" name="visita_fecha_programada" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"></div>
            <div class="col-6"><label class="form-label small">Fecha Real</label><input type="date" name="visita_fecha_real" class="form-control form-control-sm"></div>
        </div>
        <div class="mb-2"><label class="form-label small">Evaluador Lider</label><input type="text" name="visita_evaluador_lider" class="form-control form-control-sm" placeholder="Nombre del evaluador lider"></div>
        <div class="mb-2"><label class="form-label small">Equipo Evaluador (JSON)</label><input type="text" name="visita_evaluadores" class="form-control form-control-sm" placeholder='["Evaluador 1","Evaluador 2"]'></div>
        <div class="row g-2 mb-2">
            <div class="col-4"><label class="form-label small">Hallazgos</label><input type="number" name="visita_hallazgos" class="form-control form-control-sm" value="0" min="0"></div>
            <div class="col-4"><label class="form-label small">No Conformidades</label><input type="number" name="visita_no_conformidades" class="form-control form-control-sm" value="0" min="0"></div>
            <div class="col-4"><label class="form-label small">Observaciones</label><input type="number" name="visita_observaciones" class="form-control form-control-sm" value="0" min="0"></div>
        </div>
        <div class="mb-2"><label class="form-label small">Informe (PDF)</label><input type="file" name="visita_informe" class="form-control form-control-sm" accept=".pdf"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Crear Visita</button></div>
</form></div></div>

<!-- MODAL Nuevo Plan de Mejora -->
<div class="modal fade" id="modalPlanMejora"><div class="modal-dialog"><form method="POST" action="/acreditacion/plan-mejora/crear" class="modal-content">
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
    <div class="modal-header"><h5>Nuevo Plan de Mejora</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2 mb-2">
            <div class="col-6"><label class="form-label small">Estandar</label><select name="estandar_id" class="form-select form-select-sm"><?php foreach ($estandares as $e): ?><option value="<?= $e['estandar_id'] ?>"><?= htmlspecialchars($e['estandar_codigo']) ?> - <?= htmlspecialchars(substr($e['estandar_nombre'],0,40)) ?></option><?php endforeach; ?></select></div>
            <div class="col-6"><label class="form-label small">Visita (opcional)</label><select name="visita_id" class="form-select form-select-sm"><option value="">Sin visita asociada</option><?php foreach ($visitas as $v): ?><option value="<?= $v['visita_id'] ?>">Visita <?= $v['visita_id'] ?> - <?= $v['visita_tipo'] ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="mb-2"><label class="form-label small">Accion</label><textarea name="plan_accion" class="form-control form-control-sm" rows="3" placeholder="Describa la accion correctiva o de mejora" required></textarea></div>
        <div class="row g-2 mb-2">
            <div class="col-6"><label class="form-label small">Responsable</label><select name="plan_responsable_id" class="form-select form-select-sm"><?php foreach ($usuarios ?? [] as $u): ?><option value="<?= $u['usuario_id'] ?>"><?= htmlspecialchars($u['usuario_nombre'].' '.$u['usuario_apellido']) ?></option><?php endforeach; ?></select></div>
            <div class="col-6"><label class="form-label small">Fecha Compromiso</label><input type="date" name="plan_fecha_compromiso" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('+30 days')) ?>"></div>
        </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Crear Plan</button></div>
</form></div></div>

<!-- MODAL Seguimiento -->
<div class="modal fade" id="modalSeguimiento"><div class="modal-dialog"><form method="POST" action="/acreditacion/seguimiento/crear" class="modal-content" enctype="multipart/form-data">
    <input type="hidden" name="plan_id" id="seguimientoPlanId">
    <div class="modal-header"><h5>Registrar Seguimiento</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2 mb-2">
            <div class="col-6"><label class="form-label small">Fecha</label><input type="date" name="seguimiento_fecha" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"></div>
            <div class="col-6"><label class="form-label small">Avance (%)</label><input type="number" name="seguimiento_avance" class="form-control form-control-sm" min="0" max="100" step="0.1" value="0"></div>
        </div>
        <div class="mb-2"><label class="form-label small">Observaciones</label><textarea name="seguimiento_observaciones" class="form-control form-control-sm" rows="3" placeholder="Detalle el avance y observaciones del seguimiento"></textarea></div>
        <div class="mb-2"><label class="form-label small">Evidencia</label><input type="file" name="seguimiento_evidencia" class="form-control form-control-sm" accept="image/*,.pdf"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Registrar Seguimiento</button></div>
</form></div></div>

<script>
function abrirEvaluacion(id, titulo) {
    document.getElementById('evalEstandarId').value = id;
    document.getElementById('evalTitulo').textContent = titulo;
    document.getElementById('evalEscala').value = '0-100';
    cambiarEscala('0-100');
    new bootstrap.Modal(document.getElementById('modalEvaluar')).show();
}

function cambiarEscala(escala) {
    document.getElementById('evalEscala').value = escala;
    if (escala === '1-5') {
        document.getElementById('divCumplimiento').classList.add('d-none');
        document.getElementById('divNivelSua').classList.remove('d-none');
        document.getElementById('divPuntaje').classList.add('d-none');
    } else {
        document.getElementById('divCumplimiento').classList.remove('d-none');
        document.getElementById('divNivelSua').classList.add('d-none');
        document.getElementById('divPuntaje').classList.remove('d-none');
    }
}

// Activate tab from hash
document.addEventListener('DOMContentLoaded', function() {
    var hash = window.location.hash;
    if (hash) {
        var tab = document.querySelector('[data-bs-toggle="tab"][href="' + hash + '"]');
        if (tab) { var bsTab = new bootstrap.Tab(tab); bsTab.show(); }
    }
});
</script>
<?php $moduloContexto = 'acreditacion en salud'; require BASE_PATH . '/templates/hse/ia_panel.php'; ?>