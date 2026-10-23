<?php
declare(strict_types=1);

use GymFlow\Bootstrap;
use GymFlow\DomainError;
use GymFlow\GymService;
use GymFlow\ReportCache;
use MongoDB\BSON\ObjectId;

require dirname(__DIR__) . '/src/Bootstrap.php';
require dirname(__DIR__) . '/src/View.php';
if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/src/ReportCache.php';
require dirname(__DIR__) . '/src/GymService.php';
require dirname(__DIR__) . '/src/MailService.php';
require dirname(__DIR__) . '/src/InvitationService.php';
require dirname(__DIR__) . '/src/MachineService.php';

Bootstrap::env(dirname(__DIR__) . '/.env');
date_default_timezone_set('America/Costa_Rica');
$isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name('gymflow_session');
session_set_cookie_params(['httponly'=>true, 'secure'=>$isSecure, 'samesite'=>'Lax', 'path'=>'/']);
session_start();
if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
$csrf = $_SESSION['csrf'];
$error = '';
$service = null;
$invitationService = null;
$machineService = null;
$db = null;
$user = null;
$page = (string)($_GET['page'] ?? 'dashboard');
$allowed = ['dashboard','gimnasios','usuarios','socios','planes','membresias','maquinas','pagos','conexion','activar'];
if (!in_array($page, $allowed, true)) $page = 'dashboard';
$gymList = [];
$gym = null;
$gymId = null;
$items = [];
$members = [];
$plans = [];
$socios = [];
$payments = [];
$machines = [];
$users = [];
$gymName = '';
$selected = null;
$report = [];
$editing = null;
$redisOK = false;
$notice = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

try {
    $db = Bootstrap::db();
    $service = new GymService($db, ReportCache::fromEnvironment());
$invitationService = new \GymFlow\InvitationService($db, new \GymFlow\MailService());
$machineService = new \GymFlow\MachineService($db);
    if (!empty($_SESSION['uid'])) {
        $user = $service->userById((string)$_SESSION['uid']);
        if (!$user) unset($_SESSION['uid']);
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) throw new DomainError('Solicitud invalida. Actualizá la página.');
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'login') {
            $user = $service->login((string)($_POST['correo'] ?? ''), (string)($_POST['password'] ?? ''));
            session_regenerate_id(true);
            $_SESSION['uid'] = (string)$user['_id'];
            $_SESSION['csrf'] = bin2hex(random_bytes(24));
            header('Location: ?page=dashboard', true, 303); exit;
        }
        if ($action === 'activate-account') {
        $invitationService->activate(
            (string)($_POST['token'] ?? ''),
            (string)($_POST['password'] ?? ''),
            (string)($_POST['confirmarPassword'] ?? '')
        );
        $_SESSION['flash'] = 'Cuenta activada correctamente. Ya podés iniciar sesión.';
        header('Location: ?page=dashboard', true, 303); exit;
    }
    if ($action === 'logout') {
            $_SESSION = [];
            session_regenerate_id(true);
            header('Location: ?page=dashboard', true, 303); exit;
        }
        if (!$user) throw new DomainError('Iniciá sesión para continuar.');
        $postGym = $service->gymId($user, (string)($_POST['gym'] ?? ''));
        $withGym = fn(): ObjectId => $service->requireGym($postGym);
        switch ($action) {
            case 'save-gym': $service->saveGym($user, $_POST); $to = 'gimnasios'; break;
            case 'save-user':
                if (empty($_POST['id'])) {
                    $sent = $invitationService->create($user, $_POST);
                    $noticeOverride = $sent
                        ? 'Cuenta pendiente creada. Invitación enviada por correo.'
                        : 'Cuenta pendiente creada, pero falló el envío. Utilizá Reenviar invitación.';
                } else {
                    $service->saveUser($user, $_POST);
                }
                $to = 'usuarios'; break;
            case 'resend-invitation':
                $sent = $invitationService->resend($user, (string)($_POST['id'] ?? ''));
                $noticeOverride = $sent ? 'Invitación reenviada.' : 'No se pudo enviar. Intentá de nuevo y revisá SMTP.';
                $to = 'usuarios'; break;
            case 'save-socio': $service->saveSocio($withGym(), $_POST); $to = 'socios'; break;
            case 'save-plan': $service->savePlan($withGym(), $_POST); $to = 'planes'; break;
            case 'save-machine': $machineService->save($withGym(), $_POST); $to = 'maquinas'; break;
            case 'delete-machine': $machineService->delete($withGym(), (string)($_POST['id'] ?? '')); $to = 'maquinas'; break;
            case 'create-membership': $service->createMembership($withGym(), $_POST); $to = 'membresias'; break;
            case 'cancel-membership': $service->cancelMembership($withGym(), (string)($_POST['id'] ?? '')); $to = 'membresias'; break;
            case 'create-payment': $service->pay($withGym(), $user, $_POST); $to = 'pagos'; break;
            case 'annul-payment': $service->annulPayment($withGym(), $user, $_POST); $to = 'pagos'; break;
            default: throw new DomainError('Acción no reconocida.');
        }
        $_SESSION['flash'] = $noticeOverride ?? ($action === 'delete-machine' ? 'La máquina se eliminó correctamente.' : 'La operación se guardó correctamente en MongoDB.');
        header('Location: '.\GymFlow\href($to, (string)($postGym ?? '')), true, 303); exit;
    }
    if ($user) {
        $gymList = $service->gyms($user);
        $requested = (string)($_GET['gym'] ?? '');
        if ($requested === '' && count($gymList)) $requested = (string)$gymList[0]['_id'];
        $gym = $service->gymId($user, $requested);
        $gymId = $gym ? (string)$gym : null;
        if ($gym) {
            foreach ($gymList as $g) if ((string)$g['_id'] === $gymId) $gymName = $g['nombreComercial'];
            $socios = $service->records('socios', $gym);
            $plans = $service->records('planes', $gym);
            $members = $service->records('membresias', $gym);
            $payments = $service->records('pagos', $gym);
            if ($page === 'maquinas') $machines = $machineService->list($gym, (string)($_GET['q'] ?? ''), (string)($_GET['estado'] ?? ''));
            if ($page === 'dashboard') $report = $service->report($gym);
            $items = match($page) {
                'socios' => $socios, 'planes' => $plans, 'membresias' => $members, 'pagos' => $payments, default => []
            };
        }
        if ($page === 'usuarios' || $page === 'gimnasios') {
            if ($user['rol'] !== 'superadmin') { $page = 'dashboard'; throw new DomainError('Acceso restringido al superadministrador.'); }
            if ($page === 'usuarios') $users = iterator_to_array($db->usuarios->find([], ['sort'=>['nombre'=>1], 'projection'=>['passwordHash'=>0]]), false);
        }
        $id = (string)($_GET['edit'] ?? '');
        if ($id !== '') {
            if ($page === 'socios' || $page === 'planes') $editing = $service->scoped($page, $service->requireGym($gym), $id);
            elseif ($page === 'maquinas') $editing = $machineService->get($service->requireGym($gym), $id);
            elseif ($page === 'gimnasios' && $user['rol'] === 'superadmin') $editing = $db->gimnasios->findOne(['_id'=>Bootstrap::id($id)]);
            elseif ($page === 'usuarios' && $user['rol'] === 'superadmin') $editing = $db->usuarios->findOne(['_id'=>Bootstrap::id($id)], ['projection'=>['passwordHash'=>0]]);
            if (!$editing) throw new DomainError('Registro para editar no encontrado.');
        }
        if ($page === 'conexion') { $service->ping(); $redisOK = $service->redisHealthy(); }
    }
} catch (DomainError $ex) { $error = $ex->getMessage(); }
catch (\MongoDB\Driver\Exception\BulkWriteException $ex) {
    error_log('MongoDB: ' . $ex->getMessage());
    $error = str_contains($ex->getMessage(), 'E11000') ? 'Registro duplicado. Revisá el correo, la identificación, el código de la máquina o un pago existente.' : 'No fue posible guardar en MongoDB.';
}
catch (\Throwable $ex) {
    error_log('GymFlow: ' . $ex);
    $error = 'No se pudo conectar o consultar MongoDB. Revisá la instalación, la URI, el servicio y los permisos de red. ';
    if (!class_exists(\MongoDB\Client::class) || !extension_loaded('mongodb')) $error .= 'Falta Composer o la extensión PHP mongodb.';
}

if ($user && $page === 'activar') $page = 'dashboard';
header('Referrer-Policy: no-referrer');
$nav = [
 'dashboard'=>['icon'=>'◫', 'title'=>'Resumen'],
 'socios'=>['icon'=>'♧', 'title'=>'Socios'],
 'planes'=>['icon'=>'▣', 'title'=>'Planes'],
 'membresias'=>['icon'=>'▤', 'title'=>'Membresías'],
 'maquinas'=>['icon'=>'⚙', 'title'=>'Máquinas'],
 'conexion'=>['icon'=>'◎', 'title'=>'Conexión BD'],
];
if ($user && $user['rol'] === 'superadmin') {
    $nav['gimnasios'] = ['icon'=>'⌂','title'=>'Gimnasios'];
    $nav['usuarios'] = ['icon'=>'♙','title'=>'Usuarios'];
}
$title = $nav[$page]['title'] ?? ($page === 'pagos' ? 'Pagos' : 'GymFlow');
$names = []; foreach ($socios as $s) $names[(string)$s['_id']] = (string)$s['nombreCompleto'];
$planNames = []; foreach ($plans as $p) $planNames[(string)$p['_id']] = (string)$p['nombre'];
$paidMap = []; foreach ($payments as $p) if ($p['estado']==='registrado') $paidMap[(string)$p['membresiaId']] = true;
$membershipNames = []; foreach ($members as $m) $membershipNames[(string)$m['_id']] = ($names[(string)$m['socioId']] ?? 'Socio') . ' · ' . ($m['condicionesContratadas']['nombrePlan'] ?? 'Plan') . ' (' . \GymFlow\shortDate($m['fechaInicio']) . ')';
$gymsNames = []; foreach ($gymList as $g) $gymsNames[(string)$g['_id']] = $g['nombreComercial'];
?>
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="color-scheme" content="light"><title><?= \GymFlow\e($title) ?> · GymFlow</title><link rel="stylesheet" href="/assets/style.css"></head>
<body>
<?php if (!$user && $page === 'activar'): ?>
<div class="login-screen">
  <div class="login-hero"><div class="brand big"><img src="assets/img/logo-gymflow.png" class="gymflow-logo" alt="GymFlow"></div>
    <h1>Activá tu cuenta<br><em>en GymFlow</em></h1><p>Elegí una contraseña segura para acceder a tu gimnasio.</p>
  </div>
  <main class="login-panel"><div class="login-box"><div class="eyebrow">INVITACIÓN PERSONAL</div><h2>Establecé tu contraseña</h2>
    <?php if ($error): ?><div class="alert error"><?= \GymFlow\e($error) ?></div><?php endif; ?>
    <?php $activationToken = (string)($_POST['token'] ?? $_GET['token'] ?? ''); ?>
    <?php if ($invitationService && $invitationService->isValid($activationToken)): ?>
      <p class="muted">El enlace vence en 24 horas y solo puede utilizarse una vez.</p>
      <form method="post" action="?page=activar" class="stack">
        <?php \GymFlow\hiddenAction('activate-account', $csrf); ?>
        <input type="hidden" name="token" value="<?= \GymFlow\e($activationToken) ?>">
        <?php \GymFlow\field('Nueva contraseña (mínimo 12 caracteres)', 'password', '', 'password', 'required minlength="12" autocomplete="new-password"'); ?>
        <?php \GymFlow\field('Confirmar contraseña', 'confirmarPassword', '', 'password', 'required minlength="12" autocomplete="new-password"'); ?>
        <button class="btn primary wide">Activar cuenta →</button>
      </form>
    <?php else: ?><div class="alert error">La invitación es inválida, caducó o ya se utilizó. Solicitá una nueva al administrador.</div><?php endif; ?>
    <p class="login-caption"><a href="?page=dashboard">Volver al inicio de sesión</a></p>
  </div></main>
</div>
<?php elseif (!$user): ?>
<div class="login-screen">
  <div class="login-hero">
    <div class="brand big">
      <img src="assets/img/logo-gymflow.png"
           alt="GymFlow"
           class="gymflow-logo">
    </div>

    <h1>
      La gestión de tu gimnasio,<br>
      <em>en un solo lugar.</em>
    </h1>

    <p>
      Controlá socios, planes, membresías y máquinas.
      Un sistema para administrar múltiples gimnasios de forma segura.
    </p>

    <div class="hero-chips">
      <span>PHP 8.2+</span>
      <span>MongoDB</span>
      <span>Redis</span>
      <span>Multi-gimnasio</span>
    </div>
 </div>
<main class="login-panel">
  <div class="login-box"><div class="eyebrow">PANEL ADMINISTRATIVO</div><h2>Bienvenido de nuevo</h2><p class="muted">Ingresá tus credenciales para continuar.</p>
 <?php if ($error): ?><div class="alert error"><?= \GymFlow\e($error) ?></div><?php endif; ?>
 <?php if ($notice): ?><div class="alert success"><?= \GymFlow\e($notice) ?></div><?php endif; ?>
 <form method="post" class="stack"><?php \GymFlow\hiddenAction('login', $csrf); \GymFlow\field('Correo electrónico', 'correo', '', 'email', 'required autocomplete="username"'); \GymFlow\field('Contraseña', 'password', '', 'password', 'required autocomplete="current-password"'); ?><button class="btn primary wide">Iniciar sesión <span>→</span></button></form>
 <p class="login-caption">¿Primera vez? Ejecutá el script de inicialización incluido en el proyecto. Las cuentas de prueba se crean de forma segura en tu base de datos.</p></div></main>
</div>
<?php else: ?>
<div class="shell">
 <aside class="sidebar"><a class="brand"
   href="<?= \GymFlow\e(\GymFlow\href('dashboard', $gymId)) ?>">
  <img src="assets/img/logo-gymflow.png"
       alt="GymFlow"
       class="gymflow-logo">
</a>
 <div class="side-label">ESPACIO DE TRABAJO</div>
 <nav class="nav"><?php foreach($nav as $key=>$item): ?><a href="<?= \GymFlow\e(\GymFlow\href($key,$gymId)) ?>" class="nav-item <?= $key===$page?'active':'' ?>"><span class="nav-symbol"><?= \GymFlow\e($item['icon']) ?></span><?= \GymFlow\e($item['title']) ?></a><?php endforeach; ?></nav>
 <div class="sidebar-footer"><div class="user-avatar"><?= \GymFlow\e(strtoupper(substr($user['nombre'],0,1))) ?></div><div class="user-desc"><strong><?= \GymFlow\e($user['nombre']) ?></strong><small><?= \GymFlow\e($user['rol']==='superadmin'?'Superadministrador':'Administrador') ?></small></div></div>
 <form method="post" class="logout"><?php \GymFlow\hiddenAction('logout', $csrf, $gymId); ?><button>↪ Cerrar sesión</button></form>
 </aside>
 <main class="content"><header class="topbar"><div><div class="eyebrow">GESTIÓN DE GIMNASIOS · GYMFLOW</div><h1><?= \GymFlow\e($title) ?></h1></div><div class="topright"><span class="live-dot"></span><span>Panel seguro</span>
 <?php if ($user['rol'] === 'superadmin' && count($gymList)): ?><form method="get" class="gym-select"><input type="hidden" name="page" value="<?= \GymFlow\e($page) ?>"><select name="gym" aria-label="Gimnasio activo"><option value="">Elegí gimnasio</option><?php foreach($gymList as $g): ?><option value="<?= \GymFlow\e((string)$g['_id']) ?>" <?= $gymId === (string)$g['_id'] ? 'selected' : '' ?>><?= \GymFlow\e($g['nombreComercial']) ?></option><?php endforeach; ?></select><button class="btn small" type="submit">Cambiar</button></form><?php else: ?><span class="gym-name"><?= \GymFlow\e($gymName) ?></span><?php endif; ?></div></header>
 <?php if ($error): ?><div class="alert error"><?= \GymFlow\e($error) ?></div><?php endif; ?>
 <?php if ($notice): ?><div class="alert success"><?= \GymFlow\e($notice) ?></div><?php endif; ?>
 <?php if (!$gym && !in_array($page,['gimnasios','usuarios'],true)): ?><div class="card"><h3>No hay un gimnasio seleccionado</h3><p>Creá un gimnasio desde el módulo Gimnasios para empezar.</p></div>
 <?php elseif ($page === 'dashboard'): ?>
 <div class="page-intro"><div><h2>Estado general de <?= \GymFlow\e($gymName) ?></h2><p>Indicadores desde MongoDB con caché temporal Redis.</p></div><a class="btn primary" href="<?= \GymFlow\e(\GymFlow\href('socios',$gymId)) ?>">+ Registrar socio</a></div>
 <p class="hint">Origen del resumen: <strong><?= \GymFlow\e($service?->cacheEvent() ?? 'no disponible') ?></strong>. Un HIT indica lectura desde Redis; MISS consulta MongoDB. Crear, modificar o anular registros invalida la caché del gimnasio.</p>
 <div class="metric-grid">
  <?php foreach ([['Socios registrados',$report['sociosRegistrados']??0,'◎'],['Socios habilitados',$report['sociosActivos']??0,'♧'],['Membresías habilitadas',$report['membresiasHabilitadas']??0,'▣'],['Próximas a vencer',$report['porVencer']??0,'◷']] as $metric): ?><div class="metric"><span class="metric-icon"><?= $metric[2] ?></span><small><?= \GymFlow\e($metric[0]) ?></small><strong><?= \GymFlow\e($metric[1]) ?></strong></div><?php endforeach; ?>
 </div>
 <div class="dashboard-grid"><div class="card"><div class="card-heading"><div><h3>Ingresos registrados</h3><p>Pagos efectivos de este gimnasio</p></div><span class="circle-ico">₡</span></div><div class="income"><?= \GymFlow\money($report['ingresos']??0) ?></div><p class="hint">No incluye pagos anulados. No corresponde necesariamente a ingresos bancarios conciliados.</p></div><div class="card"><div class="card-heading"><div><h3>Resumen de membresías</h3><p>Control del período contratado</p></div></div><div class="keyrow"><span>Futuras</span><strong><?= \GymFlow\e($report['futuras']??0) ?></strong></div><div class="keyrow"><span>Vencidas</span><strong><?= \GymFlow\e($report['vencidas']??0) ?></strong></div><div class="keyrow"><span>Canceladas</span><strong><?= \GymFlow\e($report['canceladas']??0) ?></strong></div></div></div>
 <div class="card"><div class="card-heading"><div><h3>Actividad reciente</h3><p>Últimas membresías contratadas</p></div><a href="<?= \GymFlow\e(\GymFlow\href('membresias',$gymId)) ?>">Ver todas →</a></div><div class="tablewrap"><table><thead><tr><th>Socio</th><th>Plan</th><th>Inicio</th><th>Vencimiento</th><th>Estado</th></tr></thead><tbody><?php foreach(array_slice($members,0,6) as $m): ?><tr><td><?= \GymFlow\e($names[(string)$m['socioId']] ?? 'Socio') ?></td><td><?= \GymFlow\e($m['condicionesContratadas']['nombrePlan']) ?></td><td><?= \GymFlow\e(\GymFlow\shortDate($m['fechaInicio'])) ?></td><td><?= \GymFlow\e(\GymFlow\shortDate($m['fechaFin'])) ?></td><td><?= \GymFlow\stateBadge(\GymFlow\memberState($m,isset($paidMap[(string)$m['_id']]))) ?></td></tr><?php endforeach; ?><?php if (!$members): ?><tr><td colspan="5" class="empty">No hay membresías todavía.</td></tr><?php endif; ?></tbody></table></div></div>
 <?php elseif ($page === 'socios'): ?>
 <div class="page-intro"><div><h2>Socios de <?= \GymFlow\e($gymName) ?></h2><p>Identificaciones únicas dentro de cada gimnasio.</p></div><span class="counter"><?= count($socios) ?> registros</span></div>
 <div class="two-col"><section class="card"><div class="card-heading"><h3><?= $editing?'Editar socio':'Registrar socio' ?></h3><?php if($editing): ?><a href="<?= \GymFlow\e(\GymFlow\href('socios',$gymId)) ?>">Nuevo +</a><?php endif; ?></div><form method="post" class="stack"><?php \GymFlow\hiddenAction('save-socio',$csrf,$gymId); ?><input type="hidden" name="id" value="<?= \GymFlow\e((string)($editing['_id']??'')) ?>">
 <?php \GymFlow\field('Identificación *','identificacion',$editing['identificacion']??'','text','required maxlength="40"'); \GymFlow\field('Nombre completo *','nombreCompleto',$editing['nombreCompleto']??'','text','required'); \GymFlow\field('Correo','correo',$editing['contacto']['correo']??'','email'); \GymFlow\field('Teléfono','telefono',$editing['contacto']['telefono']??'','tel'); \GymFlow\field('Fecha de nacimiento','fechaNacimiento',$editing['fechaNacimiento']??'','date'); \GymFlow\choice('Estado','estado',['activo'=>'Activo','inactivo'=>'Inactivo'],$editing['estado']??'activo'); \GymFlow\area('Observaciones','observaciones',$editing['observaciones']??''); ?><button class="btn primary">Guardar socio</button></form></section>
 <section class="card full-table"><div class="card-heading"><h3>Listado de socios</h3></div><div class="tablewrap"><table><thead><tr><th>Identificación</th><th>Nombre</th><th>Contacto</th><th>Estado</th><th>Acción</th></tr></thead><tbody><?php foreach ($socios as $s): ?><tr><td><?= \GymFlow\e($s['identificacion']) ?></td><td><strong><?= \GymFlow\e($s['nombreCompleto']) ?></strong></td><td><?= \GymFlow\e($s['contacto']['telefono']??'—') ?><div class="subtext"><?= \GymFlow\e($s['contacto']['correo']??'') ?></div></td><td><?= \GymFlow\stateBadge($s['estado']) ?></td><td><a class="action-link" href="<?= \GymFlow\e(\GymFlow\href('socios',$gymId,['edit'=>(string)$s['_id']])) ?>">Editar</a></td></tr><?php endforeach; ?><?php if (!$socios): ?><tr><td colspan="5" class="empty">Registrá tu primer socio usando el formulario.</td></tr><?php endif; ?></tbody></table></div></section></div>
 <?php elseif ($page === 'planes'): ?>
 <div class="page-intro"><div><h2>Planes y precios</h2><p>Los precios acordados quedan conservados al contratar una membresía.</p></div><span class="counter"><?= count($plans) ?> planes</span></div>
 <div class="two-col"><section class="card"><div class="card-heading"><h3><?= $editing?'Editar plan':'Nuevo plan' ?></h3><?php if($editing): ?><a href="<?= \GymFlow\e(\GymFlow\href('planes',$gymId)) ?>">Nuevo +</a><?php endif; ?></div><form method="post" class="stack"><?php \GymFlow\hiddenAction('save-plan',$csrf,$gymId); ?><input type="hidden" name="id" value="<?= \GymFlow\e((string)($editing['_id']??'')) ?>"><?php \GymFlow\field('Nombre del plan *','nombre',$editing['nombre']??'','text','required'); \GymFlow\field('Duración en días *','duracionDias',$editing['duracionDias']??30,'number','required min="1" max="3650"'); \GymFlow\field('Precio en colones *','precio',(string)($editing['precio']??'15000.00'),'number','required min="0.01" step="0.01"'); \GymFlow\choice('Estado','estado',['activo'=>'Activo','inactivo'=>'Inactivo'],$editing['estado']??'activo'); \GymFlow\area('Descripción','descripcion',$editing['descripcion']??''); ?><button class="btn primary">Guardar plan</button></form></section><section class="card full-table"><div class="card-heading"><h3>Planes disponibles</h3></div><div class="tablewrap"><table><thead><tr><th>Nombre</th><th>Duración</th><th>Precio</th><th>Estado</th><th></th></tr></thead><tbody><?php foreach($plans as $p): ?><tr><td><strong><?= \GymFlow\e($p['nombre']) ?></strong><div class="subtext"><?= \GymFlow\e($p['descripcion']??'') ?></div></td><td><?= \GymFlow\e($p['duracionDias']) ?> días</td><td><?= \GymFlow\money($p['precio']) ?></td><td><?= \GymFlow\stateBadge($p['estado']) ?></td><td><a class="action-link" href="<?= \GymFlow\e(\GymFlow\href('planes',$gymId,['edit'=>(string)$p['_id']])) ?>">Editar</a></td></tr><?php endforeach; ?><?php if (!$plans): ?><tr><td colspan="5" class="empty">No hay planes todavía.</td></tr><?php endif; ?></tbody></table></div></section></div>
 <?php elseif ($page === 'membresias'): ?>
 <div class="page-intro"><div><h2>Control de membresías</h2><p>Vigencia calculada por fechas y pago registrado.</p></div><div class="machine-actions"><a class="btn" href="<?= \GymFlow\e(\GymFlow\href('pagos', $gymId)) ?>">Ver pagos de membresías</a><span class="counter"><?= count($members) ?> contrataciones</span></div></div><div class="two-col"><section class="card"><div class="card-heading"><h3>Contratar / renovar</h3></div><form method="post" class="stack"><?php \GymFlow\hiddenAction('create-membership',$csrf,$gymId); ?><?php \GymFlow\choice('Socio *','socioId', array_combine(array_map(fn($s)=>(string)$s['_id'],array_filter($socios,fn($s)=>$s['estado']==='activo')),array_map(fn($s)=>$s['nombreCompleto'],array_filter($socios,fn($s)=>$s['estado']==='activo')))?:[]); \GymFlow\choice('Plan *','planId', array_combine(array_map(fn($p)=>(string)$p['_id'],array_filter($plans,fn($p)=>$p['estado']==='activo')),array_map(fn($p)=>$p['nombre'].' — '.\GymFlow\money($p['precio']),array_filter($plans,fn($p)=>$p['estado']==='activo')))?:[]); \GymFlow\field('Fecha inicial *','fechaInicio',date('Y-m-d'),'date','required'); ?><label class="check"><input type="checkbox" name="renovar" value="1"> Renovar después del último período vigente o futuro</label><?php \GymFlow\area('Observaciones','observaciones'); ?><p class="hint">La fecha final se calcula sumando los días del plan; no se permiten períodos superpuestos.</p><button class="btn primary" <?= !$socios||!$plans?'disabled':'' ?>>Crear membresía</button></form></section><section class="card full-table"><div class="card-heading"><h3>Historial</h3></div><div class="tablewrap"><table><thead><tr><th>Socio / Plan</th><th>Inicio</th><th>Fin</th><th>Precio</th><th>Estado</th><th></th></tr></thead><tbody><?php foreach($members as $m): ?><tr><td><strong><?= \GymFlow\e($names[(string)$m['socioId']] ?? 'Socio') ?></strong><div class="subtext"><?= \GymFlow\e($m['condicionesContratadas']['nombrePlan']) ?></div></td><td><?= \GymFlow\e(\GymFlow\shortDate($m['fechaInicio'])) ?></td><td><?= \GymFlow\e(\GymFlow\shortDate($m['fechaFin'])) ?></td><td><?= \GymFlow\money($m['condicionesContratadas']['precio']) ?></td><td><?= \GymFlow\stateBadge(\GymFlow\memberState($m,isset($paidMap[(string)$m['_id']]))) ?></td><td><?php if(!$m['cancelada']): ?><form method="post" class="inline-form"><?php \GymFlow\hiddenAction('cancel-membership',$csrf,$gymId); ?><input type="hidden" name="id" value="<?= \GymFlow\e((string)$m['_id']) ?>"><button class="danger-link" title="Conserva el historial">Cancelar</button></form><?php endif; ?></td></tr><?php endforeach; ?><?php if (!$members): ?><tr><td colspan="6" class="empty">Todavía no hay membresías.</td></tr><?php endif; ?></tbody></table></div></section></div>
   <?php elseif ($page === 'maquinas'): ?>
  <div class="page-intro">
    <div><h2>Máquinas de <?= \GymFlow\e($gymName) ?></h2><p>Inventario propio de cada gimnasio. Registrá, buscá y administrá equipos.</p></div>
    <span class="counter"><?= count($machines) ?> resultados</span>
  </div>
  <form method="get" class="machine-search card">
    <input type="hidden" name="page" value="maquinas">
    <input type="hidden" name="gym" value="<?= \GymFlow\e($gymId) ?>">
    <label class="field"><span>Buscar por código, nombre, tipo, marca, modelo o ubicación</span>
      <input name="q" maxlength="80" value="<?= \GymFlow\e((string)($_GET['q'] ?? '')) ?>" placeholder="Ej. caminadora, cardio, A-01"></label>
    <label class="field"><span>Estado</span><select name="estado">
      <option value="">Todos</option>
      <?php foreach (['operativa'=>'Operativa','mantenimiento'=>'Mantenimiento','fuera_de_servicio'=>'Fuera de servicio'] as $key=>$text): ?>
        <option value="<?= \GymFlow\e($key) ?>" <?= (string)($_GET['estado'] ?? '') === $key ? 'selected' : '' ?>><?= \GymFlow\e($text) ?></option>
      <?php endforeach; ?>
    </select></label>
    <button type="submit" class="btn primary">Buscar</button>
    <a class="btn" href="<?= \GymFlow\e(\GymFlow\href('maquinas', $gymId)) ?>">Limpiar</a>
  </form>
  <div class="two-col">
    <section class="card">
      <div class="card-heading"><h3><?= $editing ? 'Editar máquina' : 'Registrar máquina' ?></h3>
        <?php if ($editing): ?><a href="<?= \GymFlow\e(\GymFlow\href('maquinas', $gymId)) ?>">Nueva +</a><?php endif; ?>
      </div>
      <form method="post" class="stack">
        <?php \GymFlow\hiddenAction('save-machine', $csrf, $gymId); ?>
        <input type="hidden" name="id" value="<?= \GymFlow\e((string)($editing['_id'] ?? '')) ?>">
        <?php
          \GymFlow\field('Código de inventario *', 'codigo', $editing['codigo'] ?? '', 'text', 'required maxlength="40"');
          \GymFlow\field('Nombre de la máquina *', 'nombre', $editing['nombre'] ?? '', 'text', 'required maxlength="150"');
          \GymFlow\choice('Tipo *', 'tipo', ['cardio'=>'Cardio','fuerza'=>'Fuerza','funcional'=>'Funcional','otro'=>'Otro'], $editing['tipo'] ?? 'fuerza');
          \GymFlow\field('Marca', 'marca', $editing['marca'] ?? '', 'text', 'maxlength="100"');
          \GymFlow\field('Modelo', 'modelo', $editing['modelo'] ?? '', 'text', 'maxlength="100"');
          \GymFlow\field('Ubicación dentro del gimnasio', 'ubicacion', $editing['ubicacion'] ?? '', 'text', 'maxlength="150"');
          \GymFlow\choice('Estado', 'estado', ['operativa'=>'Operativa','mantenimiento'=>'Mantenimiento','fuera_de_servicio'=>'Fuera de servicio'], $editing['estado'] ?? 'operativa');
          \GymFlow\area('Observaciones', 'observaciones', $editing['observaciones'] ?? '');
        ?>
        <button class="btn primary">Guardar máquina</button>
      </form>
    </section>
    <section class="card full-table">
      <div class="card-heading"><h3>Inventario de máquinas</h3></div>
      <div class="tablewrap"><table>
        <thead><tr><th>Código</th><th>Máquina</th><th>Ubicación</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
          <?php foreach ($machines as $machine): ?>
            <tr>
              <td><strong><?= \GymFlow\e($machine['codigo']) ?></strong></td>
              <td><strong><?= \GymFlow\e($machine['nombre']) ?></strong>
                <div class="subtext"><?= \GymFlow\e(ucfirst((string)$machine['tipo'])) ?> · <?= \GymFlow\e(trim(($machine['marca'] ?? '') . ' ' . ($machine['modelo'] ?? ''))) ?></div>
              </td>
              <td><?= \GymFlow\e($machine['ubicacion'] ?: '—') ?></td>
              <td><?= \GymFlow\stateBadge((string)$machine['estado']) ?></td>
              <td><div class="machine-actions">
                <a class="action-link" href="<?= \GymFlow\e(\GymFlow\href('maquinas', $gymId, ['edit'=>(string)$machine['_id']])) ?>">Editar</a>
                <form method="post" onsubmit="return confirm('¿Eliminar esta máquina definitivamente?');">
                  <?php \GymFlow\hiddenAction('delete-machine', $csrf, $gymId); ?>
                  <input type="hidden" name="id" value="<?= \GymFlow\e((string)$machine['_id']) ?>">
                  <button class="danger-link" type="submit">Eliminar</button>
                </form>
              </div></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$machines): ?><tr><td colspan="5" class="empty">No hay máquinas que coincidan con la búsqueda.</td></tr><?php endif; ?>
        </tbody>
      </table></div>
    </section>
  </div>

<?php elseif ($page === 'pagos'): ?>
 <div class="page-intro"><div><h2>Registro de pagos</h2><p>Cobros manuales, anulaciones y trazabilidad. No procesa transacciones bancarias.</p></div><span class="counter"><?= count($payments) ?> movimientos</span></div><div class="two-col"><section class="card"><div class="card-heading"><h3>Registrar pago</h3></div><form method="post" class="stack"><?php \GymFlow\hiddenAction('create-payment',$csrf,$gymId); ?><input type="hidden" name="claveOperacion" value="<?= bin2hex(random_bytes(16)) ?>"><?php $unpaid = array_filter($members, fn($m)=>!$m['cancelada']&&!isset($paidMap[(string)$m['_id']])); \GymFlow\choice('Membresía sin pago *','membresiaId',array_combine(array_map(fn($m)=>(string)$m['_id'],$unpaid),array_map(fn($m)=>($names[(string)$m['socioId']]??'Socio').' — '.\GymFlow\money($m['condicionesContratadas']['precio']),$unpaid))?:[]); \GymFlow\choice('Método de pago *','metodo',['sinpe_movil'=>'SINPE Móvil (registro manual)','efectivo'=>'Efectivo','transferencia'=>'Transferencia']); \GymFlow\field('Referencia del comprobante','referencia'); \GymFlow\area('Observaciones','observaciones'); ?><p class="hint">El monto es exactamente el precio contratado. Se registra un único pago efectivo por membresía.</p><button class="btn primary" <?= !$unpaid?'disabled':'' ?>>Confirmar pago</button></form></section><section class="card full-table"><div class="card-heading"><h3>Historial de cobros</h3></div><div class="tablewrap"><table><thead><tr><th>Membresía</th><th>Monto</th><th>Fecha</th><th>Método</th><th>Estado / Anular</th></tr></thead><tbody><?php foreach ($payments as $p): ?><tr><td><strong><?= \GymFlow\e($membershipNames[(string)$p['membresiaId']]??'Membresía histórica') ?></strong><div class="subtext"><?= \GymFlow\e($p['referencia']??'') ?></div></td><td><?= \GymFlow\money($p['monto']) ?></td><td><?= \GymFlow\e(\GymFlow\shortDate($p['fechaPago'])) ?></td><td><?= \GymFlow\e(str_replace('_',' ', $p['metodo'])) ?></td><td><?= \GymFlow\stateBadge($p['estado']) ?><?php if($p['estado']==='registrado'): ?><form method="post" class="annul-form"><?php \GymFlow\hiddenAction('annul-payment',$csrf,$gymId); ?><input type="hidden" name="id" value="<?= \GymFlow\e((string)$p['_id']) ?>"><input name="motivo" placeholder="Motivo de anulación" required maxlength="500"><button class="danger-link">Anular</button></form><?php else: ?><div class="subtext"><?= \GymFlow\e($p['anulacion']['motivo']??'') ?></div><?php endif; ?></td></tr><?php endforeach; ?><?php if (!$payments): ?><tr><td colspan="5" class="empty">No hay cobros registrados.</td></tr><?php endif; ?></tbody></table></div></section></div>
 <?php elseif ($page === 'gimnasios'): ?>
 <div class="page-intro"><div><h2>Gimnasios en la plataforma</h2><p>Administración global y separación de información por gimnasio.</p></div></div><div class="two-col"><section class="card"><div class="card-heading"><h3><?= $editing?'Editar gimnasio':'Registrar gimnasio' ?></h3><?php if($editing): ?><a href="<?= \GymFlow\e(\GymFlow\href('gimnasios',$gymId)) ?>">Nuevo +</a><?php endif; ?></div><form method="post" class="stack"><?php \GymFlow\hiddenAction('save-gym',$csrf,$gymId); ?><input type="hidden" name="id" value="<?= \GymFlow\e((string)($editing['_id']??'')) ?>"><?php \GymFlow\field('Nombre comercial *','nombreComercial',$editing['nombreComercial']??'','text','required'); \GymFlow\field('Razón social','razonSocial',$editing['razonSocial']??''); \GymFlow\field('Correo del gimnasio *','correo',$editing['contacto']['correo']??'','email','required'); \GymFlow\field('Teléfono','telefono',$editing['contacto']['telefono']??''); \GymFlow\field('Provincia','provincia',$editing['ubicacion']['provincia']??''); \GymFlow\field('Cantón','canton',$editing['ubicacion']['canton']??''); \GymFlow\choice('Estado','estado',['activo'=>'Activo','pendiente'=>'Pendiente','inactivo'=>'Inactivo'],$editing['estado']??'activo'); ?><button class="btn primary">Guardar gimnasio</button></form></section><section class="card full-table"><div class="card-heading"><h3>Gimnasios creados</h3></div><div class="tablewrap"><table><thead><tr><th>Gimnasio</th><th>Contacto</th><th>Estado</th><th></th></tr></thead><tbody><?php foreach($gymList as $g): ?><tr><td><strong><?= \GymFlow\e($g['nombreComercial']) ?></strong></td><td><?= \GymFlow\e($g['contacto']['correo']??'') ?></td><td><?= \GymFlow\stateBadge($g['estado']) ?></td><td><a class="action-link" href="<?= \GymFlow\e(\GymFlow\href('gimnasios',$gymId,['edit'=>(string)$g['_id']])) ?>">Editar</a></td></tr><?php endforeach; ?><?php if(!$gymList): ?><tr><td colspan="4" class="empty">Creá tu primer gimnasio.</td></tr><?php endif; ?></tbody></table></div></section></div>
 <?php elseif ($page === 'usuarios'): ?>
 <div class="page-intro"><div><h2>Accesos administrativos</h2><p>Los administradores solo acceden a los datos de su gimnasio. Al crear un usuario se envía una invitación para establecer su contraseña.</p></div></div><div class="two-col"><section class="card"><div class="card-heading"><h3><?= $editing?'Editar usuario':'Nuevo usuario' ?></h3><?php if($editing): ?><a href="<?= \GymFlow\e(\GymFlow\href('usuarios',$gymId)) ?>">Nuevo +</a><?php endif; ?></div><form method="post" class="stack"><?php \GymFlow\hiddenAction('save-user',$csrf,$gymId); ?><input type="hidden" name="id" value="<?= \GymFlow\e((string)($editing['_id']??'')) ?>"><?php \GymFlow\field('Nombre completo *','nombre',$editing['nombre']??'','text','required'); \GymFlow\field('Correo de acceso *','correo',$editing['correo']??'','email','required'); \GymFlow\choice('Rol','rol',['administrador'=>'Administrador','superadmin'=>'Superadministrador'],$editing['rol']??'administrador'); \GymFlow\choice('Gimnasio asignado','gimnasioId',[''=>'Sin gimnasio (solo superadmin)']+$gymsNames,(string)($editing['gimnasioId']??$gymId)); if($editing) \GymFlow\field('Nueva contraseña (opcional)','password','','password','minlength="10" autocomplete="new-password"'); \GymFlow\choice('Estado','estado',['activo'=>'Activo','inactivo'=>'Inactivo'],$editing['estado']??'activo'); ?><button class="btn primary">Guardar usuario</button></form></section><section class="card full-table"><div class="card-heading"><h3>Personal autorizado</h3></div><div class="tablewrap"><table><thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Gimnasio</th><th>Estado</th><th></th></tr></thead><tbody><?php foreach($users as $u): ?><tr><td><strong><?= \GymFlow\e($u['nombre']) ?></strong></td><td><?= \GymFlow\e($u['correo']) ?></td><td><?= \GymFlow\e($u['rol']) ?></td><td><?= \GymFlow\e($gymsNames[(string)($u['gimnasioId']??'')]??'Global') ?></td><td><?= \GymFlow\stateBadge($u['estado']) ?></td><td><?php if(($u['estado']??'') === 'pendiente'): ?><form method="post" class="inline-form"><?php \GymFlow\hiddenAction('resend-invitation',$csrf,$gymId); ?><input type="hidden" name="id" value="<?= \GymFlow\e((string)$u['_id']) ?>"><button class="action-link" title="Reenvía un nuevo enlace">Reenviar invitación</button></form><?php else: ?><a class="action-link" href="<?= \GymFlow\e(\GymFlow\href('usuarios',$gymId,['edit'=>(string)$u['_id']])) ?>">Editar</a><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></section></div>
 <?php elseif ($page === 'conexion'): ?>
 <div class="card connection"><span class="connected">✓ MongoDB respondió al PING</span><h2>Estado real de los dos motores</h2><p>Esta pantalla consulta los servicios desde el servidor PHP. Una falla de Redis no debe impedir operaciones con MongoDB.</p><div class="connection-row"><span>Persistencia</span><strong>MongoDB: conectado ✓</strong></div><div class="connection-row"><span>Base configurada</span><strong><?= \GymFlow\e(getenv('MONGODB_DB')?:'gymflow') ?></strong></div><div class="connection-row"><span>Caché de indicadores</span><strong><?= $redisOK ? 'Redis: conectado ✓' : 'Redis: no disponible (modo directo MongoDB)' ?></strong></div><div class="connection-row"><span>Tiempo de vida de caché</span><strong><?= (int)($service?->cacheTtl() ?? 60) ?> segundos</strong></div><div class="connection-row"><span>Colecciones MongoDB</span><strong>gimnasios · usuarios · socios · planes · membresias · pagos · maquinas</strong></div><p class="hint">En el panel podés verificar HIT y MISS. Redis guarda resultados temporales, no socios ni pagos. Las credenciales permanecen exclusivamente en el servidor.</p></div>
 <?php endif; ?>
 <footer class="app-footer">GymFlow · Proyecto Open Source · Seguimiento 1 · 2026 <span>PHP + MongoDB + Redis</span></footer></main>
</div>
<?php endif; ?>
</body></html>
