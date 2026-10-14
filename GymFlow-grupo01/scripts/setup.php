<?php
declare(strict_types=1);

use GymFlow\Bootstrap;
use MongoDB\BSON\ObjectId;

require dirname(__DIR__).'/src/Bootstrap.php';
require dirname(__DIR__).'/vendor/autoload.php';
Bootstrap::env(dirname(__DIR__).'/.env');
date_default_timezone_set('America/Costa_Rica');
$db = Bootstrap::db();
$db->command(['ping'=>1]);
echo "GymFlow · MongoDB OK · base: ".(getenv('MONGODB_DB')?:'gymflow').PHP_EOL;

$schemas = [
 'gimnasios' => ['req'=>['nombreComercial','contacto','estado','fechaRegistro'], 'props'=>['nombreComercial'=>['bsonType'=>'string'], 'contacto'=>['bsonType'=>'object'], 'estado'=>['enum'=>['pendiente','activo','inactivo']], 'fechaRegistro'=>['bsonType'=>'date']]],
 'usuarios' => ['req'=>['gimnasioId','nombre','correo','passwordHash','rol','estado'], 'props'=>['gimnasioId'=>['bsonType'=>['objectId','null']], 'nombre'=>['bsonType'=>'string'],'correo'=>['bsonType'=>'string'],'passwordHash'=>['bsonType'=>'string'],'rol'=>['enum'=>['superadmin','administrador']], 'estado'=>['enum'=>['activo','inactivo']]]],
 'socios' => ['req'=>['gimnasioId','identificacion','nombreCompleto','fechaRegistro','estado'], 'props'=>['gimnasioId'=>['bsonType'=>'objectId'],'identificacion'=>['bsonType'=>'string'],'nombreCompleto'=>['bsonType'=>'string'], 'fechaNacimiento'=>['bsonType'=>'string'], 'fechaRegistro'=>['bsonType'=>'date'], 'estado'=>['enum'=>['activo','inactivo']]]],
 'planes' => ['req'=>['gimnasioId','nombre','duracionDias','precio','moneda','estado'], 'props'=>['gimnasioId'=>['bsonType'=>'objectId'], 'nombre'=>['bsonType'=>'string'], 'duracionDias'=>['bsonType'=>'int'], 'precio'=>['bsonType'=>'decimal'], 'moneda'=>['enum'=>['CRC']], 'estado'=>['enum'=>['activo','inactivo']]]],
 'membresias' => ['req'=>['gimnasioId','socioId','planId','condicionesContratadas','fechaInicio','fechaFin','cancelada','fechaCreacion'], 'props'=>['gimnasioId'=>['bsonType'=>'objectId'],'socioId'=>['bsonType'=>'objectId'],'planId'=>['bsonType'=>'objectId'], 'condicionesContratadas'=>['bsonType'=>'object'], 'fechaInicio'=>['bsonType'=>'date'],'fechaFin'=>['bsonType'=>'date'], 'cancelada'=>['bsonType'=>'bool'], 'fechaCreacion'=>['bsonType'=>'date']]],
 'pagos' => ['req'=>['gimnasioId','membresiaId','fechaPago','monto','moneda','metodo','claveOperacion','registradoPor','estado'], 'props'=>['gimnasioId'=>['bsonType'=>'objectId'],'membresiaId'=>['bsonType'=>'objectId'],'fechaPago'=>['bsonType'=>'date'], 'monto'=>['bsonType'=>'decimal'], 'moneda'=>['enum'=>['CRC']], 'metodo'=>['enum'=>['efectivo','sinpe_movil','transferencia']], 'claveOperacion'=>['bsonType'=>'string'], 'registradoPor'=>['bsonType'=>'objectId'], 'estado'=>['enum'=>['registrado','anulado']]]],
];
$existing = array_map(fn($c)=>$c->getName(),iterator_to_array($db->listCollections()));
foreach($schemas as $name=>$schema) {
    $json = ['bsonType'=>'object','required'=>$schema['req'],'properties'=>$schema['props']];
    if (!in_array($name,$existing,true)) {
        $db->createCollection($name, ['validator'=>['$jsonSchema'=>$json], 'validationLevel'=>'strict', 'validationAction'=>'error']);
    } else {
        $db->command(['collMod'=>$name,'validator'=>['$jsonSchema'=>$json],'validationLevel'=>'strict','validationAction'=>'error']);
    }
    echo "Colección/validador: $name\n";
}
$db->usuarios->createIndex(['correo'=>1],['unique'=>true,'name'=>'uniq_correo']);
$db->socios->createIndex(['gimnasioId'=>1,'identificacion'=>1],['unique'=>true,'name'=>'uniq_identificacion_gimnasio']);
$db->planes->createIndex(['gimnasioId'=>1,'estado'=>1],['name'=>'planes_estado']);
$db->membresias->createIndex(['gimnasioId'=>1,'socioId'=>1],['name'=>'membresias_socio']);
$db->membresias->createIndex(['gimnasioId'=>1,'fechaFin'=>1],['name'=>'membresias_vencimiento']);
$db->pagos->createIndex(['gimnasioId'=>1,'fechaPago'=>1],['name'=>'pagos_fecha']);
$db->pagos->createIndex(['gimnasioId'=>1,'membresiaId'=>1],['name'=>'pagos_historial']);
$db->pagos->createIndex(['gimnasioId'=>1,'claveOperacion'=>1],['unique'=>true,'name'=>'uniq_operacion']);
$db->pagos->createIndex(['gimnasioId'=>1,'membresiaId'=>1],['unique'=>true,'partialFilterExpression'=>['estado'=>'registrado'],'name'=>'uniq_pago_efectivo']);
echo "Indices creados/verificados.\n";

if (!in_array('--seed',$argv,true)) {
    echo "Para cargar datos sintéticos: php scripts/setup.php --seed\n";
    exit;
}

function demoUser($db, string $name, string $email, string $role, ?ObjectId $gym): ObjectId
{
    $u = $db->usuarios->findOne(['correo'=>$email]);
    if ($u) { echo "Cuenta existente: $email (se conserva la contraseña)\n"; return $u['_id']; }
    $password = 'Demo-' . bin2hex(random_bytes(8));
    $id = $db->usuarios->insertOne([
        'gimnasioId'=>$gym,'nombre'=>$name,'correo'=>$email,'passwordHash'=>password_hash($password,PASSWORD_DEFAULT),'rol'=>$role,'estado'=>'activo',
    ])->getInsertedId();
    echo "USUARIO: $email | CLAVE: $password\n";
    return $id;
}
function demoGym($db, string $name, string $email): ObjectId
{
    $found = $db->gimnasios->findOne(['nombreComercial'=>$name]);
    if ($found) return $found['_id'];
    return $db->gimnasios->insertOne(['nombreComercial'=>$name,'razonSocial'=>'Empresa de demostracion','contacto'=>['correo'=>$email,'telefono'=>'8888-0000'],'ubicacion'=>['provincia'=>'Heredia','canton'=>'Central'],'estado'=>'activo','fechaRegistro'=>Bootstrap::now()])->getInsertedId();
}
function demoSocio($db, ObjectId $gym, string $name, string $id): ObjectId
{
    $found = $db->socios->findOne(['gimnasioId'=>$gym,'identificacion'=>$id]);
    if ($found) return $found['_id'];
    return $db->socios->insertOne(['gimnasioId'=>$gym,'identificacion'=>$id,'nombreCompleto'=>$name,'contacto'=>['correo'=>'socio'.$id.'@example.test','telefono'=>'8888-1234'],'fechaNacimiento'=>'1999-04-12','fechaRegistro'=>Bootstrap::now(),'estado'=>'activo','observaciones'=>'Dato sintetico para clases'])->getInsertedId();
}
function demoPlan($db, ObjectId $gym, string $name, int $days, string $price): array
{
    $found = $db->planes->findOne(['gimnasioId'=>$gym,'nombre'=>$name]);
    if ($found) return (array)$found;
    $doc = ['gimnasioId'=>$gym,'nombre'=>$name,'duracionDias'=>$days,'precio'=>Bootstrap::decimal($price),'moneda'=>'CRC','descripcion'=>'Plan academico de demostracion','estado'=>'activo'];
    $doc['_id'] = $db->planes->insertOne($doc)->getInsertedId();
    return $doc;
}
function demoMembership($db, ObjectId $gym, ObjectId $socio, array $plan, string $startDate, bool $cancelled, ?ObjectId $admin, bool $pay, bool $annul = false): void
{
    if ($db->membresias->findOne(['gimnasioId'=>$gym,'socioId'=>$socio])) return;
    $start = Bootstrap::localDate($startDate);
    $end = Bootstrap::addDays($start,(int)$plan['duracionDias']);
    $memberId = $db->membresias->insertOne([
        'gimnasioId'=>$gym,'socioId'=>$socio,'planId'=>$plan['_id'],
        'condicionesContratadas'=>['nombrePlan'=>$plan['nombre'],'duracionDias'=>$plan['duracionDias'],'precio'=>$plan['precio'],'moneda'=>'CRC'],
        'fechaInicio'=>$start,'fechaFin'=>$end,'cancelada'=>$cancelled,'fechaCreacion'=>Bootstrap::now(),
        'observaciones'=>'Muestra sintetica',
    ])->getInsertedId();
    if ($pay && $admin) {
        $payment = ['gimnasioId'=>$gym,'membresiaId'=>$memberId,'fechaPago'=>Bootstrap::now(), 'monto'=>$plan['precio'],'moneda'=>'CRC','metodo'=>'efectivo','referencia'=>'DEMO','claveOperacion'=>bin2hex(random_bytes(16)), 'registradoPor'=>$admin,'estado'=>$annul?'anulado':'registrado','observaciones'=>'Pago de prueba'];
        if ($annul) $payment['anulacion'] = ['fecha'=>Bootstrap::now(),'usuarioId'=>$admin,'motivo'=>'Anulacion sintetica para pruebas'];
        $db->pagos->insertOne($payment);
    }
}
$g1 = demoGym($db,'Hercules Demo','hercules@example.test');
$g2 = demoGym($db,'Titan Demo','titan@example.test');
demoUser($db,'Superadministrador Demo','superadmin@gymflow.test','superadmin',null);
$a1 = demoUser($db,'Administrador Hercules','hercules@gymflow.test','administrador',$g1);
$a2 = demoUser($db,'Administrador Titan','titan@gymflow.test','administrador',$g2);
foreach ([[$g1,'Hércules',$a1,100],[$g2,'Titan',$a2,200]] as [$g,$name,$admin,$n]) {
    $monthly = demoPlan($db,$g,'Mensual',30,'20000.00');
    $quarter = demoPlan($db,$g,'Trimestral',90,'55000.00');
    $s1 = demoSocio($db,$g,'Andrea '.$name,'SYN'.($n+1));
    $s2 = demoSocio($db,$g,'Carlos '.$name,'SYN'.($n+2));
    $s3 = demoSocio($db,$g,'Paola '.$name,'SYN'.($n+3));
    $s4 = demoSocio($db,$g,'Mario '.$name,'SYN'.($n+4));
    $s5 = demoSocio($db,$g,'Lucía '.$name,'SYN'.($n+5));
    demoMembership($db,$g,$s1,$monthly,date('Y-m-d',strtotime('-5 days')),false,$admin,true);
    demoMembership($db,$g,$s2,$quarter,date('Y-m-d',strtotime('+5 days')),false,$admin,false);
    demoMembership($db,$g,$s3,$monthly,date('Y-m-d',strtotime('-40 days')),false,$admin,true);
    demoMembership($db,$g,$s4,$monthly,date('Y-m-d',strtotime('-5 days')),true,$admin,false);
    demoMembership($db,$g,$s5,$monthly,date('Y-m-d',strtotime('-4 days')),false,$admin,true,true);
}
echo "\nDatos sinteticos cargados. Guardá las claves mostradas: solo se imprimen al crear las cuentas.\n";
echo "Abrí: http://localhost:8000\n";
