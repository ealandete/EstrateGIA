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

echo "\n🔀 Router\n";
check('Router get', function() { (new Router())->get('/ut',function(){return 'x';}); ok(true); });
check('Router 404', function() { $r=new Router(); $out=$r->dispatch('GET','/noexiste_ut_'.time()); ok(strpos($out,'404')!==false); });

$total = $passed + $failed;
$pct = $total > 0 ? round($passed/$total*100) : 0;
echo "\n═════════════════════════════\n  $passed/$total TESTS ($pct%)\n  ".($failed===0?'✅ TODOS OK':'❌ HAY FALLOS')."\n═════════════════════════════\n\n";
exit($failed > 0 ? 1 : 0);
