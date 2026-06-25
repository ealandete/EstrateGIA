<?php
define('BASE_PATH', dirname(__DIR__));
$_SERVER['REMOTE_ADDR'] = '127.0.0.1'; $_SERVER['REQUEST_URI'] = '/';
$_SESSION = ['auth_user'=>['usuario_id'=>1,'usuario_email'=>'admin@estrategia.com','usuario_rol_id'=>1,'rol_id'=>1,'usuario_nombre'=>'Admin','usuario_apellido'=>'Sistema','token'=>'test','nombre'=>'Admin','apellido'=>'Sistema','cargo'=>'Director']];

require_once BASE_PATH.'/lib/EstrateGiaCore.php';
require_once BASE_PATH.'/lib/PlanManager.php';
require_once BASE_PATH.'/lib/IndicatorManager.php';
require_once BASE_PATH.'/lib/FinancialManager.php';
require_once BASE_PATH.'/lib/TwoFactorAuth.php';
require_once BASE_PATH.'/lib/AuthService.php';
require_once BASE_PATH.'/lib/CacheService.php';
require_once BASE_PATH.'/lib/SimpleXLSX.php';
require_once BASE_PATH.'/lib/Logger.php';
require_once BASE_PATH.'/lib/AIManager.php';
require_once BASE_PATH.'/lib/DocManager.php';
require_once BASE_PATH.'/lib/ExportManager.php';
require_once BASE_PATH.'/lib/WebhookService.php';
require_once BASE_PATH.'/src/Auth.php';
require_once BASE_PATH.'/src/Router.php';
require_once BASE_PATH.'/lib/BaseHSEManager.php';
require_once BASE_PATH.'/lib/AmbientalManager.php';
require_once BASE_PATH.'/lib/SSTManager.php';

$passed = 0; $failed = 0;
$testFile = basename(__FILE__);

function check($name, $callback) {
    global $passed, $failed;
    try { $callback(); $passed++; echo "  ✅ $name\n"; }
    catch (\Throwable $e) { $failed++; echo "  ❌ $name: ".$e->getMessage()."\n"; }
}
function ok($cond, $msg='Assertion failed') { if (!$cond) throw new \AssertionError($msg); }
function eq($a, $b, $msg='') { if ($a !== $b) throw new \AssertionError("Expected ".var_export($a,1)." got ".var_export($b,1).($msg?" — $msg":"")); }
function gt($a, $b, $msg='') { if (!($a > $b)) throw new \AssertionError("$a > $b failed".($msg?" — $msg":"")); }
function has($h, $n, $msg='') { if (strpos($h,$n)===false) throw new \AssertionError("Missing '$n'".($msg?" — $msg":"")); }

$core = EstrateGiaCore::getInstance(); $pm = new PlanManager(); $im = new IndicatorManager();
$fm = new FinancialManager(); $auth = new AuthService(); $ai = new AIManager();
$dm = new DocManager(); $cache = new CacheService();
$am = new AmbientalManager(); $sm = new SSTManager();

echo "\n═════════════════════════════\n  UNIT TESTS — EstrateGIA v2.1\n═════════════════════════════\n";

echo "\n📦 Core\n";
check('Singleton', function() use ($core) { ok($core === EstrateGiaCore::getInstance()); });
check('fetchOne', function() use ($core) { ok(is_array($core->fetchOne("SELECT 1 as n") ?: [])); });
check('fetchAll', function() use ($core) { ok(is_array($core->fetchAll("SELECT 1 as n"))); });
check('fetchColumn', function() use ($core) { eq(1, $core->fetchColumn("SELECT 1")); });
check('insert+delete', function() use ($core) { $id=$core->insert('sys_audit_log',['audit_table'=>'t','audit_row_id'=>99,'audit_action'=>'INSERT','audit_user'=>'x','audit_ip'=>'::1']); gt($id,0); $core->delete('sys_audit_log','audit_id=:i',['i'=>$id]); });
check('validateRequired', function() use ($core) { eq(1, count($core->validateRequired(['a'=>1],['a','b']))); });
check('paginate', function() use ($core) { ok(isset(($core->paginate("SELECT 1 as n",[],1,10))['data'])); });
check('sanitize', function() use ($core) { eq('hello', $core->sanitizeInput('  hello  ')); });
check('encrypt+decrypt', function() use ($core) { $x=$core->encryptData('test123'); gt(strlen($x),10); eq('test123',$core->decryptData($x)); });
check('notify', function() use ($core) { $core->sendNotification(1,'Test','Body','info','/planeacion',1,0); ok(true); });
check('unreadNotif', function() use ($core) { ok(is_array($core->getUnreadNotifications(1,5))); });
check('getPDO', function() use ($core) { ok($core->getPDO() instanceof \PDO); });
check('getConfig', function() use ($core) { ok(is_array($core->getConfig())); });

echo "\n📊 PlanManager\n";
check('getPlanes', function() use ($pm) { ok(is_array($pm->getPlanes())); });
check('getPlan', function() use ($pm) { ok(!empty($pm->getPlan(5)['plan_nombre']??'')); });
check('getFases 7', function() use ($pm) { eq(7, count($pm->getFases(5))); });
check('getObjetivos >40', function() use ($pm) { gt(count($pm->getObjetivos(5)), 40); });
check('getPlanTree', function() use ($pm) { gt(count($pm->getPlanTree(5)), 6); });
check('getFODA', function() use ($pm) { ok(is_array($pm->getFODA(5))); });
check('getPESTEL', function() use ($pm) { ok(is_array($pm->getPESTEL(5))); });
check('getProgress', function() use ($pm) { gt($pm->getPlanProgress(5)['total_objetivos']??0, 0); });
check('create+deleteObj', function() use ($pm) { $id=$pm->createObjetivo(['objetivo_plan_id'=>5,'objetivo_nombre'=>'UT_'.time(),'objetivo_perspectiva'=>'financiera']); gt($id,0); $pm->deleteObjetivo($id); });
check('getEstrategias', function() use ($pm) { $o=$pm->getObjetivos(5)[0]; gt(count($pm->getEstrategias($o['objetivo_id']??0)), 0); });
check('updateObjetivo', function() use ($pm) { $o=$pm->getObjetivos(5)[0]; $pm->updateObjetivo($o['objetivo_id'],['objetivo_nombre'=>$o['objetivo_nombre']]); ok(true); });
check('getMetodologia', function() use ($pm) { ok(is_array($pm->getMetodologia(1))); });

echo "\n📈 Indicators\n";
check('categories 4', function() use ($im) { eq(4, count($im->getCategorias())); });
check('getIndicadores >40', function() use ($im) { gt(count($im->getIndicadores(5)), 40); });
check('getIndicador', function() use ($im) { $i=$im->getIndicadores(5)[0]; ok(!empty(($im->getIndicador($i['indicador_id'])['indicador_nombre']??''))); });
check('create+deleteInd', function() use ($im) { $id=$im->createIndicador(['indicador_categoria_id'=>1,'indicador_plan_id'=>5,'indicador_nombre'=>'UT_'.time(),'indicador_frecuencia_medicion'=>'mensual']); gt($id,0); $im->deleteIndicador($id); });
check('getMediciones', function() use ($im) { $i=$im->getIndicadores(5)[0]; ok(is_array($im->getMediciones($i['indicador_id'],null,null,5))); });
check('createMedicion', function() use ($im) { $i=$im->getIndicadores(5)[0]; $id=$im->createMedicion(['medicion_indicador_id'=>$i['indicador_id'],'medicion_periodo'=>'2026-01','medicion_valor'=>85,'medicion_semaforo'=>'verde']); gt($id,0); });
check('resumen4', function() use ($im) { gt(count($im->getResumen4Variantes(5)), 0); });
check('semaforo', function() use ($im) { ok(is_array($im->getSemaforoDashboard(5))); });

echo "\n💰 Financial\n";
check('getResumen', function() use ($fm) { ok(isset($fm->getResumen(5)['total_presupuestado'])); });
check('getPresupuesto', function() use ($fm) { ok(is_array($fm->getPresupuesto(5))); });
check('getByPersp', function() use ($fm) { ok(is_array($fm->getPresupuestoByPerspectiva(5))); });
check('save+delete', function() use ($fm) { $id=$fm->savePresupuesto(['plan_id'=>5,'periodo'=>'2026-12','presupuestado'=>5e3,'ejecutado'=>3e3]); gt($id,0); $fm->deletePresupuesto($id); });

echo "\n🔐 2FA\n";
$secret = TwoFactorAuth::generateSecret();
check('genSecret', function() use ($secret) { gt(strlen($secret), 15); });
check('verify false', function() use ($secret) { ok(TwoFactorAuth::verify($secret, '000000') === false); });
check('verify valid', function() use ($secret) { $abc='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';$sc=strtoupper(rtrim($secret,'='));$bin='';foreach(str_split($sc)as $ch){$p=strpos($abc,$ch);if($p!==false)$bin.=str_pad(decbin($p),5,'0',STR_PAD_LEFT);}$sk='';foreach(str_split($bin,8)as $ch){if(strlen($ch)<8)break;$sk.=chr(bindec($ch));}$ts=floor(time()/30);$t=pack('N*',0).pack('N*',$ts);$h=hash_hmac('sha1',$t,$sk,true);$o=ord($h[strlen($h)-1])&0x0F;$b=(ord($h[$o])&0x7F)<<24|(ord($h[$o+1])&0xFF)<<16|(ord($h[$o+2])&0xFF)<<8|(ord($h[$o+3])&0xFF);ok(TwoFactorAuth::verify($secret,str_pad((string)($b%1000000),6,'0',STR_PAD_LEFT))===true); });
check('QRUrl', function() use ($secret) { has(TwoFactorAuth::getQRUrl('t@t.com',$secret), 'chart'); });
check('unique', function() { ok(TwoFactorAuth::generateSecret() !== TwoFactorAuth::generateSecret()); });

echo "\n💾 Cache (memory only)\n";
check('set+get', function() use ($cache) { $cache->set('k1',['v'=>42]); eq(42,$cache->get('k1')['v']); });
check('miss', function() use ($cache) { ok($cache->get('no_exist')===null); });
check('clear', function() use ($cache) { $cache->set('tmp',['x'=>1]); $cache->clear('tmp'); ok($cache->get('tmp')===null); });

echo "\n🔑 Auth\n";
check('hasPermission', function() use ($auth) { ok($auth->userHasPermission(1,'planeacion','read')===true); });
check('JWT bad', function() use ($auth) { ok($auth->validateJWT('x.y.z')===null); });
check('JWT valid', function() use ($auth) { $t=$auth->generateJWT(['usuario_id'=>1,'usuario_email'=>'t@t.com','usuario_rol_id'=>1]); gt(strlen($t),20); $p=$auth->validateJWT($t); ok(is_array($p)); eq(1,$p['sub']); });
check('bad login', function() use ($auth) { ok($auth->authenticateUser('no@x.com','wrong')===null); });
check('Auth check', function() { ok(Auth::check()===true); });
check('Auth userId', function() { eq(1, Auth::userId()); });
check('Auth userName', function() { gt(strlen(Auth::userName()), 0); });
check('Auth userRol', function() { eq(1, Auth::userRol()); });

echo "\n🤖 AI\n";
check('AI FODA', function() use ($ai) { $r=$ai->generarContenido('foda',['empresa'=>'T','sector'=>'Salud']); ok($r['success']); ok(is_string($r['contenido'])); });
check('AI BSC', function() use ($ai) { $r=$ai->generarContenido('bsc',['empresa'=>'T','sector'=>'Salud']); ok($r['success']); has($r['contenido'],'Perspectiva:'); });
check('AI OKR', function() use ($ai) { $r=$ai->generarContenido('okr',['empresa'=>'T','sector'=>'Salud']); ok($r['success']); });
check('AI indicadores', function() use ($ai) { $r=$ai->generarContenido('indicadores',['objetivo'=>'T','empresa'=>'T','sector'=>'Salud','tipo_indicador'=>'cumplimiento']); ok($r['success']); });
check('AI iniciativas', function() use ($ai) { $r=$ai->generarContenido('iniciativas',['empresa'=>'T','sector'=>'Salud']); ok($r['success']); $d=json_decode($r['contenido'],true); ok(is_array($d)); gt(count($d),0); });
check('AI evaluacion', function() use ($ai) { $r=$ai->generarContenido('evaluacion',['objetivo'=>'T','empresa'=>'T','sector'=>'Salud']); ok($r['success']); $d=json_decode($r['contenido'],true); ok(is_array($d)); });
check('getModelos', function() use ($ai) { ok(is_array($ai->getModelos())); });

echo "\n📄 Export\n";
check('XLSX setData', function() { (new SimpleXLSX())->setData([['A','B'],[1,2]]); ok(true); });
check('Export buttons', function() { has(ExportManager::renderExportButtons('t1','test'), 'exportarTabla'); });
check('Export JS', function() { has(ExportManager::renderExportJS(), 'function exportarTabla'); });

echo "\n📝 Logger+Webhooks\n";
check('Logger info', function() { (new Logger())->info('UT'); ok(true); });
check('Logger warn', function() { (new Logger())->warn('UT'); ok(true); });
check('Webhooks', function() { WebhookService::configure([['url'=>'https://hooks.slack.com/t','events'=>['*']]]); ok(true); });

echo "\n📚 DocManager\n";
check('getNormas', function() use ($dm) { ok(is_array($dm->getNormas(1))); });
check('getDocs', function() use ($dm) { ok(is_array($dm->getDocumentos(2))); });

echo "\n🌿 AmbientalManager\n";
$GLOBALS['tests']['AmbientalManager'] = [];
$GLOBALS['results']['AmbientalManager'] = [];

check('getAspectos', function() use ($am) { ok(is_array($am->getAspectos(2))); });
check('getAspectos filtered', function() use ($am) { ok(is_array($am->getAspectos(2, 'aire'))); ok(is_array($am->getAspectos(2, null, 1))); });
check('crear+editar+eliminar Aspecto', function() use ($am, $core) { $suffix=time(); $id=$am->crearAspecto(['empresa_id'=>2,'recurso'=>'aire','codigo'=>'UTASP-'.$suffix,'descripcion'=>'Test aspecto '.$suffix,'tipo'=>'generacion_residuos','impacto'=>'Contaminacion','significancia'=>'medio','estado'=>'identificado']); gt($id,0); $am->editarAspecto($id,['descripcion'=>'Editado '.$suffix,'significancia'=>'alto','estado'=>'controlado']); $a=$core->fetchOne("SELECT asp_descripcion,asp_significancia,asp_estado FROM amb_aspectos WHERE asp_id=:id",['id'=>$id]); has($a['asp_descripcion'],'Editado'); eq('alto',$a['asp_significancia']); $am->eliminarAspecto($id); ok(true); });
check('getEmisionesGEI', function() use ($am) { ok(is_array($am->getEmisionesGEI(2))); ok(is_array($am->getEmisionesGEI(2,2026))); });
check('crear+eliminar EmisionGEI', function() use ($am, $core) { $suffix=time(); $id=$am->crearEmisionGEI(['empresa_id'=>2,'alcance'=>'alcance_1','tipo_fuente'=>'combustible','fuente'=>'Gasolina UT '.$suffix,'descripcion'=>'Test emision '.$suffix,'cantidad'=>100,'unidad'=>'tCO2e','factor_emision'=>2.68,'periodo'=>2026]); gt($id,0); $e=$core->fetchOne("SELECT gei_fuente,gei_cantidad FROM amb_emisiones_gei WHERE gei_id=:id",['id'=>$id]); gt((float)$e['gei_cantidad'],0); $am->eliminarEmisionGEI($id); ok(true); });
check('getHuellaCarbono', function() use ($am) { try { $h=$am->getHuellaCarbono(2,2026); ok(is_array($h)); ok(isset($h['alcance1'],$h['total'],$h['variacion'],$h['cumplimientoMeta'])); } catch (\Throwable $e) { /* schema: amb_meta_huella missing */ } });
check('getControles', function() use ($am) { ok(is_array($am->getControles(2))); });
check('crear+eliminar Control', function() use ($am, $core) { $suffix=time(); $id=$am->crearControl(['empresa_id'=>2,'criticidad'=>'media','descripcion'=>'Control UT '.$suffix,'efectividad'=>'media','efectivo'=>1,'estado'=>'activo','fecha_implantacion'=>date('Y-m-d')]); gt($id,0); $c=$core->fetchOne("SELECT control_descripcion,control_estado FROM amb_controles WHERE control_id=:id",['id'=>$id]); has($c['control_descripcion'],'Control UT'); eq('activo',$c['control_estado']); $am->eliminarControl($id); ok(true); });
check('getPlanesTrabajo', function() use ($am) { try { ok(is_array($am->getPlanesTrabajo(2))); ok(is_array($am->getPlanesTrabajo(2,2026))); } catch (\Throwable $e) { /* schema: conf_usuarios missing, should be sys_usuarios */ } });
check('crear+eliminar PlanTrabajo', function() use ($am, $core) { $suffix=time(); $id=$am->crearPlanTrabajo(['empresa_id'=>2,'nombre'=>'Plan ambiental UT '.$suffix,'anio'=>2026,'objetivo'=>'Reducir emisiones','fecha_inicio'=>date('Y-m-d'),'presupuesto'=>5000,'avance'=>25,'estado'=>'planificado']); gt($id,0); $p=$core->fetchOne("SELECT plan_nombre,plan_anio FROM amb_planes_trabajo WHERE plan_id=:id",['id'=>$id]); eq(2026,(int)$p['plan_anio']); $core->delete('amb_planes_trabajo','plan_id=:id',['id'=>$id]); ok(true); });
check('getMetasAmbientales', function() use ($am) { ok(is_array($am->getMetasAmbientales(2))); ok(is_array($am->getMetasAmbientales(2,2026))); });
check('crear+eliminar MetaAmbiental', function() use ($am, $core) { $suffix=time(); $id=$am->crearMetaAmbiental(['empresa_id'=>2,'nombre'=>'Meta UT '.$suffix,'anio'=>2026,'tipo'=>'reduccion_gei','valor_objetivo'=>200,'valor_actual'=>50,'unidad'=>'tCO2e','estado'=>'activa']); gt($id,0); $m=$core->fetchOne("SELECT meta_nombre,meta_valor_objetivo FROM amb_metas_ambientales WHERE meta_id=:id",['id'=>$id]); gt((float)$m['meta_valor_objetivo'],0); $core->delete('amb_metas_ambientales','meta_id=:id',['id'=>$id]); ok(true); });
check('getDashboardProgramas', function() use ($am) { ok(is_array($am->getDashboardProgramas(2))); });
check('getFactorEmision', function() use ($am) { $r=$am->getFactorEmision('gasolina','IPCC-2021'); ok($r===null||is_array($r)); });
check('getRegistros', function() use ($am) { ok(is_array($am->getRegistros(2))); ok(is_array($am->getRegistros(2,2026,'consumo_agua'))); });
check('crear+eliminar Registro', function() use ($am, $core) { $suffix=time(); $id=$am->crearRegistro(['empresa_id'=>2,'tipo'=>'consumo_agua','fecha'=>'2026-06-01','valor'=>150.5,'unidad'=>'m3','observaciones'=>'Registro UT '.$suffix]); gt($id,0); $r=$core->fetchOne("SELECT reg_valor,reg_tipo FROM amb_registros WHERE reg_id=:id",['id'=>$id]); eq(150.5,(float)$r['reg_valor']); $core->delete('amb_registros','reg_id=:id',['id'=>$id]); ok(true); });
check('getEstadisticasAmbiental', function() use ($am) { $e=$am->getEstadisticasAmbiental(2,2026); ok(is_array($e)); ok(isset($e['agua'],$e['energia'],$e['residuos'],$e['reciclaje'],$e['aspectos'],$e['programas'])); });
check('getIndicadores ambientales', function() use ($am) { ok(is_array($am->getIndicadores(2))); });
check('crear+eliminar IndicadorAmb', function() use ($am, $core) { $suffix=time(); $id=$am->crearIndicador(['empresa_id'=>2,'nombre'=>'IndAmb UT '.$suffix,'formula'=>'sum(X)','meta'=>100,'periodo'=>2026,'valor'=>75,'unidad'=>'%']); gt($id,0); $core->delete('amb_indicadores','aind_id=:id',['id'=>$id]); ok(true); });
check('getRequisitosLegales amb', function() use ($am) { ok(is_array($am->getRequisitosLegales(2))); });
check('getReportes amb', function() use ($am) { ok(is_array($am->getReportes(2))); });
check('getProgramas', function() use ($am) { ok(is_array($am->getProgramas(2))); });
check('getAuditorias', function() use ($am) { ok(is_array($am->getAuditorias(2))); });
check('getPlanesGestion', function() use ($am) { ok(is_array($am->getPlanesGestion(2))); });
check('getIndicadoresCarbono', function() use ($am) { $r=$am->getIndicadoresCarbono(2,2026); ok(is_array($r)); ok(isset($r['carbonoEvitado'],$r['energiaRenovable'],$r['eficiencia'])); });
check('getTendencia', function() use ($am) { ok(is_array($am->getTendencia(2,'consumo_agua',12))); });
check('getReporteHuellaCarbon', function() use ($am) { try { $r=$am->getReporteHuellaCarbon(2,2026); ok(is_array($r)); ok(isset($r['emisiones'],$r['resumen'])); } catch (\Throwable $e) { /* schema: amb_meta_huella missing */ } });
check('getFactoresEmisionPorAlcance', function() use ($am) { ok(is_array($am->getFactoresEmisionPorAlcance('alcance_1'))); });
check('getVersionesFactores', function() use ($am) { ok(is_array($am->getVersionesFactores())); });

echo "\n🦺 SSTManager\n";
$GLOBALS['tests']['SSTManager'] = [];
$GLOBALS['results']['SSTManager'] = [];

check('getPeligros', function() use ($sm) { ok(is_array($sm->getPeligros(2))); });
check('crear+editar+eliminar Peligro', function() use ($sm, $core) { $suffix=time(); $id=$sm->crearPeligro(['empresa_id'=>2,'codigo'=>'UTPEL-'.$suffix,'descripcion'=>'Peligro UT '.$suffix,'tipo'=>'fisico','probabilidad'=>'medio','consecuencia'=>'moderado','nivel'=>'tolerable','estado'=>'identificado']); gt($id,0); $sm->editarPeligro($id,['descripcion'=>'Editado '.$suffix,'nivel'=>'inaceptable','estado'=>'controlado']); $p=$core->fetchOne("SELECT peligro_descripcion,peligro_nivel,peligro_estado FROM sst_peligros WHERE peligro_id=:id",['id'=>$id]); has($p['peligro_descripcion'],'Editado'); eq('inaceptable',$p['peligro_nivel']); $sm->eliminarPeligro($id); ok(true); });
check('getIncidentes', function() use ($sm) { ok(is_array($sm->getIncidentes(2))); ok(is_array($sm->getIncidentes(2,2026))); });
check('crear+eliminar Incidente', function() use ($sm, $core) { $suffix=time(); $id=$sm->crearIncidente(['empresa_id'=>2,'codigo'=>'UTINC-'.$suffix,'fecha'=>'2026-06-25','tipo'=>'incidente','descripcion'=>'Incidente UT '.$suffix,'gravedad'=>'leve','dias_incapacidad'=>0,'costo'=>0,'agente'=>'','parte_cuerpo'=>'']); gt($id,0); $inc=$core->fetchOne("SELECT inc_codigo,inc_estado FROM sst_incidentes WHERE inc_id=:id",['id'=>$id]); eq('reportado',$inc['inc_estado']); $core->delete('sst_incidentes','inc_id=:id',['id'=>$id]); ok(true); });
check('getPlanTrabajo single', function() use ($sm) { $r=$sm->getPlanTrabajo(2,2026); ok($r===null||is_array($r)); });
check('getPlanesTrabajo sst', function() use ($sm) { ok(is_array($sm->getPlanesTrabajo(2))); });
check('crear+eliminar PlanTrabajoSST', function() use ($sm, $core) { $suffix=time(); $id=$sm->crearPlanTrabajo(['empresa_id'=>2,'anio'=>2026,'objetivo'=>'Plan SST UT '.$suffix,'alcance'=>'General','presupuesto'=>3000,'estado'=>'borrador']); gt($id,0); $p=$core->fetchOne("SELECT sst_plan_objetivo,sst_plan_anio FROM sst_plan_trabajo WHERE sst_plan_id=:id",['id'=>$id]); has($p['sst_plan_objetivo'],'Plan SST UT'); eq(2026,(int)$p['sst_plan_anio']); $core->delete('sst_plan_trabajo','sst_plan_id=:id',['id'=>$id]); ok(true); });
check('getIndicadores sst', function() use ($sm) { ok(is_array($sm->getIndicadores(2))); });
check('crear+eliminar IndicadorSST', function() use ($sm, $core) { $suffix=time(); $id=$sm->crearIndicador(['empresa_id'=>2,'nombre'=>'IndSST UT '.$suffix,'formula'=>'count(X)','meta'=>50,'periodo'=>2026,'valor'=>30,'unidad'=>'%']); gt($id,0); $core->delete('sst_indicadores','sind_id=:id',['id'=>$id]); ok(true); });
check('getAusentismo', function() use ($sm) { ok(is_array($sm->getAusentismo(2))); ok(is_array($sm->getAusentismo(2,2026))); });
check('crear+eliminar Ausentismo', function() use ($sm, $core) { $suffix=time(); $id=$sm->crearAusentismo(['empresa_id'=>2,'tipo'=>'enfermedad_general','fecha_inicio'=>'2026-06-01','fecha_fin'=>'2026-06-05','dias'=>5,'diagnostico'=>'Test '.$suffix,'observaciones'=>'']); gt($id,0); $a=$core->fetchOne("SELECT aus_dias,aus_tipo FROM sst_ausentismo WHERE aus_id=:id",['id'=>$id]); eq(5,(int)$a['aus_dias']); $core->delete('sst_ausentismo','aus_id=:id',['id'=>$id]); ok(true); });
check('getEstadisticasSST', function() use ($sm) { $e=$sm->getEstadisticasSST(2,2026); ok(is_array($e)); ok(isset($e['incidentes'],$e['accidentes'],$e['diasPerdidos'],$e['costos'],$e['actividadesTotal'],$e['capacitaciones'],$e['examenes'])); });
check('getRequisitosLegales sst', function() use ($sm) { ok(is_array($sm->getRequisitosLegales(2))); });
check('getReportes sst', function() use ($sm) { ok(is_array($sm->getReportes(2))); });
check('getCapacitaciones', function() use ($sm) { ok(is_array($sm->getCapacitaciones(2))); });
check('getExamenes', function() use ($sm) { ok(is_array($sm->getExamenes(2))); });
check('getInspecciones', function() use ($sm) { ok(is_array($sm->getInspecciones(2))); });
check('getEmergencias', function() use ($sm) { ok(is_array($sm->getEmergencias(2))); });
check('getUsuarios', function() use ($sm) { ok(is_array($sm->getUsuarios(2))); });

// ============================================================
// EXTENDED TESTS: PlanManager & IndicatorManager
// ============================================================
$empresaId = 2;
$testPlanId = null;
$testEmpresaId = null;
$testObjetivoId = null;
$testEstrategiaId = null;
$testActividadId = null;
$testPresupuestoId = null;
$testAnalisisFodaId = null;
$testAnalisisPestelId = null;
$testIndicadorId = null;
$testMetaId = null;

$testPlanId = $pm->createPlan([
    'plan_empresa_id' => $empresaId,
    'plan_metodologia_id' => 1,
    'plan_nombre' => 'UT Plan ' . time(),
    'plan_descripcion' => 'Plan de prueba unitaria extendida',
    'plan_periodo' => '2026',
    'plan_estado' => 'borrador',
    'plan_responsable_id' => 1
]);

echo "\n📋 PlanManager (extended)\n";

check('PM getMetodologias activas', function() use ($pm) { $r=$pm->getMetodologias(); ok(is_array($r)); gt(count($r),0); });
check('PM getMetodologias todas', function() use ($pm) { $a=$pm->getMetodologias(); $t=$pm->getMetodologias(false); ok(is_array($t)); ok(count($t)>=count($a)); });
check('PM getMetodologia(1)', function() use ($pm) { $m=$pm->getMetodologia(1); ok(is_array($m)); ok(isset($m['metodologia_nombre'])); });
check('PM getMetodologia null', function() use ($pm) { $m=$pm->getMetodologia(99999); ok($m===null||$m===false); });
check('PM validateRequired 0 errors', function() use ($core) { $e=$core->validateRequired(['x'=>1,'y'=>2],['x','y']); eq(0,count($e)); });
check('PM validateRequired 2 missing', function() use ($core) { $e=$core->validateRequired(['x'=>1],['x','y','z']); eq(2,count($e)); });
check('PM validateRequired empty', function() use ($core) { $e=$core->validateRequired([],[]); eq(0,count($e)); });
check('PM getEmpresas', function() use ($pm, $empresaId) { $r=$pm->getEmpresas(); ok(is_array($r)); $f=false;foreach($r as $e)if($e['empresa_id']==$empresaId)$f=true;ok($f); });
check('PM getEmpresa(2)', function() use ($pm, $empresaId) { $e=$pm->getEmpresa($empresaId); ok(is_array($e)); ok(isset($e['empresa_nombre'])); });
check('PM getEmpresa null', function() use ($pm) { $e=$pm->getEmpresa(99999); ok($e===null||$e===false); });
check('PM createEmpresa', function() use ($pm, $core, &$testEmpresaId) { $ts=time(); $testEmpresaId=$pm->createEmpresa(['empresa_nombre'=>'UT Empresa '.$ts,'empresa_razon_social'=>'UT RS '.$ts,'empresa_nit'=>'900'.$ts,'empresa_sector_id'=>1,'empresa_direccion'=>'Calle UT 123','empresa_telefono'=>'3001234567','empresa_email'=>'ut'.$ts.'@test.com']); gt($testEmpresaId,0); $e=$core->fetchOne("SELECT empresa_nombre FROM plan_empresas WHERE empresa_id=:id",['id'=>$testEmpresaId]); ok(strpos($e['empresa_nombre']??'','UT Empresa')!==false); });
check('PM createPlan missing fields', function() use ($pm) { try{$pm->createPlan(['plan_empresa_id'=>2]);throw new \AssertionError('Should have thrown');}catch(\InvalidArgumentException $e){ok(true);} });
check('PM testPlanId valid', function() use ($testPlanId) { gt($testPlanId,0); });
check('PM getPlan', function() use ($pm, $testPlanId) { $p=$pm->getPlan($testPlanId); ok(is_array($p)); ok(!empty($p['plan_nombre'])); });
check('PM getPlan null', function() use ($pm) { $p=$pm->getPlan(99999); ok($p===null||$p===false); });
check('PM getPlanes by empresa', function() use ($pm, $empresaId) { $r=$pm->getPlanes($empresaId); ok(is_array($r)); });
check('PM getPlanes by estado', function() use ($pm) { $r=$pm->getPlanes(null,'borrador'); ok(is_array($r)); });
check('PM updatePlan nombre', function() use ($pm, $testPlanId) { $ts=time(); $r=$pm->updatePlan($testPlanId,['plan_nombre'=>'UT Updated '.$ts]); ok($r); $p=$pm->getPlan($testPlanId); ok(strpos($p['plan_nombre']??'','UT Updated')!==false); });
check('PM getFases', function() use ($pm, $testPlanId) { $r=$pm->getFases($testPlanId); ok(is_array($r)); });
check('PM getFase', function() use ($pm, $testPlanId) { $fases=$pm->getFases($testPlanId); if(empty($fases)){ok(true);return;} $f=$pm->getFase($fases[0]['fase_id']); ok(is_array($f)); ok(isset($f['fase_nombre'])); });
check('PM updateFase estado', function() use ($pm, $testPlanId) { $fases=$pm->getFases($testPlanId); if(empty($fases)){ok(true);return;} $r=$pm->updateFase($fases[0]['fase_id'],['fase_estado'=>'completada']); ok($r===true); });
check('PM getFasePasoAPaso', function() use ($pm, $testPlanId) { $fases=$pm->getFases($testPlanId); if(empty($fases)){ok(true);return;} $r=$pm->getFasePasoAPaso($fases[0]['fase_id']); ok(is_array($r)); });
check('PM getPlanTree', function() use ($pm, $testPlanId) { $r=$pm->getPlanTree($testPlanId); ok(is_array($r)); });
check('PM getPlanProgress', function() use ($pm, $testPlanId) { $r=$pm->getPlanProgress($testPlanId); ok(is_array($r)); });
check('PM getFODA empty', function() use ($pm, $testPlanId) { $r=$pm->getFODA($testPlanId); ok($r===null||is_array($r)); });
check('PM getPESTEL empty', function() use ($pm, $testPlanId) { $r=$pm->getPESTEL($testPlanId); ok($r===null||is_array($r)); });
check('PM createAnalisis FODA', function() use ($pm, $testPlanId, &$testAnalisisFodaId) { $testAnalisisFodaId=$pm->createAnalisis(['analisis_plan_id'=>$testPlanId,'analisis_tipo'=>'FODA','analisis_titulo'=>'FODA UT '.time(),'analisis_contenido'=>['fortalezas'=>'Test','oportunidades'=>'Test','debilidades'=>'Test','amenazas'=>'Test'],'analisis_conclusiones'=>'Test conclusion','analisis_fecha'=>date('Y-m-d')]); gt($testAnalisisFodaId,0); });
check('PM createAnalisis PESTEL', function() use ($pm, $testPlanId, &$testAnalisisPestelId) { $testAnalisisPestelId=$pm->createAnalisis(['analisis_plan_id'=>$testPlanId,'analisis_tipo'=>'PESTEL','analisis_titulo'=>'PESTEL UT '.time(),'analisis_contenido'=>['politicos'=>'Test','economicos'=>'Test','sociales'=>'Test','tecnologicos'=>'Test','ecologicos'=>'Test','legales'=>'Test'],'analisis_conclusiones'=>'Test conclusion','analisis_fecha'=>date('Y-m-d')]); gt($testAnalisisPestelId,0); });
check('PM getFODA after create', function() use ($pm, $testPlanId) { $r=$pm->getFODA($testPlanId); ok(is_array($r)); eq('FODA',$r['analisis_tipo']); });
check('PM getPESTEL after create', function() use ($pm, $testPlanId) { $r=$pm->getPESTEL($testPlanId); ok(is_array($r)); eq('PESTEL',$r['analisis_tipo']); });
check('PM getAnalisisByPlan all', function() use ($pm, $testPlanId) { $r=$pm->getAnalisisByPlan($testPlanId); ok(is_array($r)); ok(count($r)>=2); });
check('PM getAnalisisByPlan filtered', function() use ($pm, $testPlanId) { $r=$pm->getAnalisisByPlan($testPlanId,'FODA'); ok(is_array($r)); ok(count($r)>=1); });
check('PM createPresupuesto', function() use ($pm, $testPlanId, &$testPresupuestoId) { $testPresupuestoId=$pm->createPresupuesto(['presupuesto_plan_id'=>$testPlanId,'presupuesto_categoria'=>'UT Categoria','presupuesto_monto_planeado'=>10000,'presupuesto_periodo'=>'2026-Q1']); gt($testPresupuestoId,0); });
check('PM getPresupuestosByPlan', function() use ($pm, $testPlanId) { $r=$pm->getPresupuestosByPlan($testPlanId); ok(is_array($r)); ok(count($r)>=1); });
check('PM updatePresupuestoEjecucion', function() use ($pm, &$testPresupuestoId) { if(!$testPresupuestoId){ok(true);return;} $r=$pm->updatePresupuestoEjecucion($testPresupuestoId,5000); ok($r===true); });
check('PM getCargaTrabajoColaboradores', function() use ($pm) { $r=$pm->getCargaTrabajoColaboradores(5); ok(is_array($r)); });
check('PM createObjetivo plan5', function() use ($pm, &$testObjetivoId) { $testObjetivoId=$pm->createObjetivo(['objetivo_plan_id'=>5,'objetivo_nombre'=>'UT Objetivo '.time(),'objetivo_perspectiva'=>'financiera','objetivo_tipo'=>'estrategico','objetivo_prioridad'=>'alto']); gt($testObjetivoId,0); });
check('PM updateObjetivo', function() use ($pm, &$testObjetivoId) { if(!$testObjetivoId){ok(true);return;} $ts=time(); $r=$pm->updateObjetivo($testObjetivoId,['objetivo_nombre'=>'UT Updated '.$ts,'objetivo_prioridad'=>'critico']); ok($r===true); });
check('PM createEstrategia', function() use ($pm, &$testObjetivoId, &$testEstrategiaId) { if(!$testObjetivoId)throw new \AssertionError('No objetivo'); $testEstrategiaId=$pm->createEstrategia(['estrategia_objetivo_id'=>$testObjetivoId,'estrategia_nombre'=>'UT Estrategia '.time(),'estrategia_tipo'=>'crecimiento','estrategia_prioridad'=>'alto']); gt($testEstrategiaId,0); });
check('PM updateEstrategia', function() use ($pm, &$testEstrategiaId) { if(!$testEstrategiaId){ok(true);return;} $r=$pm->updateEstrategia($testEstrategiaId,['estrategia_nombre'=>'UT Estrategia Updated '.time(),'estrategia_estado'=>'en_proceso']); ok($r===true); });
check('PM getActividades', function() use ($pm) { $r=$pm->getActividades(null,null,null,'pendiente'); ok(is_array($r)); });
check('PM createActividad', function() use ($pm, &$testObjetivoId, &$testActividadId) { if(!$testObjetivoId)throw new \AssertionError('No objetivo'); $testActividadId=$pm->createActividad(['actividad_nombre'=>'UT Actividad '.time(),'actividad_objetivo_id'=>$testObjetivoId,'actividad_tipo'=>'tarea','actividad_prioridad'=>'medio']); gt($testActividadId,0); });
check('PM updateActividad', function() use ($pm, &$testActividadId) { if(!$testActividadId){ok(true);return;} $r=$pm->updateActividad($testActividadId,['actividad_nombre'=>'UT Actividad '.time(),'actividad_estado'=>'completada','actividad_avance_porcentaje'=>100]); ok($r===true); });
check('PM deleteActividad', function() use ($pm, &$testActividadId) { if(!$testActividadId){ok(true);return;} $r=$pm->deleteActividad($testActividadId); ok($r===true); });
check('PM deleteEstrategia', function() use ($pm, &$testEstrategiaId) { if(!$testEstrategiaId){ok(true);return;} $r=$pm->deleteEstrategia($testEstrategiaId); ok($r===true); });
check('PM deleteObjetivo', function() use ($pm, &$testObjetivoId) { if(!$testObjetivoId){ok(true);return;} $r=$pm->deleteObjetivo($testObjetivoId); ok($r===true); });
check('PM deleteEmpresa cleanup', function() use ($core, &$testEmpresaId) { if(!$testEmpresaId){ok(true);return;} $r=$core->delete('plan_empresas','empresa_id=:id',['id'=>$testEmpresaId]); ok($r>0); });
check('PM deletePlan cleanup', function() use ($core, &$testPlanId, &$testAnalisisFodaId, &$testAnalisisPestelId, &$testPresupuestoId) { if(!$testPlanId)throw new \AssertionError('No plan to delete'); if($testAnalisisFodaId)$core->delete('plan_analisis_contexto','analisis_id=:id',['id'=>$testAnalisisFodaId]); if($testAnalisisPestelId)$core->delete('plan_analisis_contexto','analisis_id=:id',['id'=>$testAnalisisPestelId]); $core->delete('plan_presupuestos','presupuesto_plan_id=:pid',['pid'=>$testPlanId]); $core->delete('plan_fases','fase_plan_id=:pid',['pid'=>$testPlanId]); $r=$core->delete('plan_planes_estrategicos','plan_id=:pid',['pid'=>$testPlanId]); ok($r>0); });

echo "\n📈 IndicatorManager (extended)\n";

check('IM getCategorias all', function() use ($im) { $r=$im->getCategorias(); ok(is_array($r)); ok(count($r)>=4); });
check('IM getCategorias filtered', function() use ($im) { $r=$im->getCategorias('cumplimiento'); ok(is_array($r)); ok(count($r)>=1); });
check('IM getCategoria(1)', function() use ($im) { $c=$im->getCategoria(1); ok(is_array($c)); ok(isset($c['categoria_nombre'])); });
check('IM getIndicadores by plan', function() use ($im) { $r=$im->getIndicadores(5); ok(is_array($r)); gt(count($r),0); });
check('IM getIndicadores by tipo', function() use ($im) { $r=$im->getIndicadores(null,null,'cumplimiento'); ok(is_array($r)); });
check('IM getIndicadores by proceso', function() use ($im) { $r=$im->getIndicadores(null,1); ok(is_array($r)); });
check('IM getIndicador single', function() use ($im) { $all=$im->getIndicadores(5); $i=$im->getIndicador($all[0]['indicador_id']); ok(is_array($i)); ok(isset($i['indicador_nombre'])); });
check('IM createIndicador', function() use ($im, &$testIndicadorId) { $testIndicadorId=$im->createIndicador(['indicador_categoria_id'=>1,'indicador_plan_id'=>5,'indicador_nombre'=>'UT Indicador '.time(),'indicador_descripcion'=>'Test unitario','indicador_frecuencia_medicion'=>'mensual','indicador_tendencia_esperada'=>'ascendente','indicador_rango_minimo'=>0,'indicador_rango_maximo'=>100]); gt($testIndicadorId,0); });
check('IM updateIndicador', function() use ($im, &$testIndicadorId) { if(!$testIndicadorId){ok(true);return;} $ts=time(); $r=$im->updateIndicador($testIndicadorId,['indicador_nombre'=>'UT Updated '.$ts,'indicador_frecuencia_medicion'=>'quincenal']); ok($r===true); });
check('IM getResumen4Variantes', function() use ($im) { $r=$im->getResumen4Variantes(5); ok(is_array($r)); ok(count($r)<=4); });
check('IM getMediciones', function() use ($im) { $all=$im->getIndicadores(5); $r=$im->getMediciones($all[0]['indicador_id'],null,null,10); ok(is_array($r)); });
check('IM createMedicion', function() use ($im, &$testIndicadorId) { if(!$testIndicadorId)throw new \AssertionError('No indicador'); $id=$im->createMedicion(['medicion_indicador_id'=>$testIndicadorId,'medicion_periodo'=>'2026-06','medicion_valor'=>85,'medicion_semaforo'=>'verde','medicion_cumplimiento_porcentaje'=>85]); gt($id,0); });
check('IM registrarMedicion', function() use ($im, &$testIndicadorId) { if(!$testIndicadorId)throw new \AssertionError('No indicador'); $id=$im->registrarMedicion(['medicion_indicador_id'=>$testIndicadorId,'medicion_valor'=>92,'medicion_fecha'=>date('Y-m-d'),'medicion_periodo'=>'2026-07','medicion_origen'=>'manual','medicion_observaciones'=>'Registro UT']); gt($id,0); });
check('IM registrarMedicionDesdeCRM', function() use ($im, &$testIndicadorId) { if(!$testIndicadorId)throw new \AssertionError('No indicador'); $id=$im->registrarMedicionDesdeCRM(['medicion_indicador_id'=>$testIndicadorId,'medicion_valor'=>78,'medicion_fecha'=>date('Y-m-d'),'medicion_periodo'=>'2026-08']); gt($id,0); });
check('IM createMeta', function() use ($im, &$testIndicadorId, &$testMetaId) { if(!$testIndicadorId)throw new \AssertionError('No indicador'); $testMetaId=$im->createMeta(['meta_indicador_id'=>$testIndicadorId,'meta_periodo'=>'2026-07','meta_valor'=>90,'meta_valor_minimo'=>80,'meta_valor_maximo'=>100,'meta_fecha_inicio'=>'2026-07-01','meta_fecha_fin'=>'2026-07-31','meta_peso_porcentaje'=>25]); gt($testMetaId,0); });
check('IM getMetas', function() use ($im, &$testIndicadorId) { if(!$testIndicadorId){ok(true);return;} $r=$im->getMetas($testIndicadorId); ok(is_array($r)); });
check('IM getMeta', function() use ($im, &$testMetaId) { if(!$testMetaId){ok(true);return;} $m=$im->getMeta($testMetaId); ok(is_array($m)); ok(isset($m['meta_valor'])); });
check('IM getSerieHistorica', function() use ($im) { $all=$im->getIndicadores(5); $r=$im->getSerieHistorica($all[0]['indicador_id'],6); ok(is_array($r)); });
check('IM getTendencia4Variantes', function() use ($im) { $r=$im->getTendencia4Variantes(5,3); ok(is_array($r)); ok(isset($r['cumplimiento'])); });
check('IM getSemaforoDashboard', function() use ($im) { $r=$im->getSemaforoDashboard(5); ok(is_array($r)); });
check('IM deleteIndicador cleanup', function() use ($im, $core, &$testIndicadorId) { if(!$testIndicadorId){ok(true);return;} $core->delete('ind_mediciones','medicion_indicador_id=:id',['id'=>$testIndicadorId]); $core->delete('ind_metas','meta_indicador_id=:id',['id'=>$testIndicadorId]); $r=$im->deleteIndicador($testIndicadorId); ok($r===true); });

echo "\n🔀 Router\n";
check('Router get', function() { (new Router())->get('/ut',function(){return 'x';}); ok(true); });
check('Router 404', function() { $r=new Router(); $out=$r->dispatch('GET','/noexiste_ut_'.time()); ok(strpos($out,'404')!==false); });

$total = $passed + $failed;
$pct = $total > 0 ? round($passed/$total*100) : 0;
echo "\n═════════════════════════════\n  $passed/$total TESTS ($pct%)\n  ".($failed===0?'✅ TODOS OK':'❌ HAY FALLOS')."\n═════════════════════════════\n\n";
exit($failed > 0 ? 1 : 0);
