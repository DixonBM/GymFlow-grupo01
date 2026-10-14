<?php
declare(strict_types=1);
use GymFlow\Bootstrap;
use GymFlow\GymService;
use GymFlow\ReportCache;
require dirname(__DIR__) . '/src/Bootstrap.php';
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/src/ReportCache.php';
require dirname(__DIR__) . '/src/GymService.php';
Bootstrap::env(dirname(__DIR__) . '/.env');
$db = Bootstrap::db();
$cache = ReportCache::fromEnvironment();
$s = new GymService($db, $cache);
$s->ping();
echo "MongoDB PING: OK\n";
if (!$cache->health()) throw new RuntimeException('Redis PING fallo: revisa docker compose logs redis');
echo "Redis PING: OK\n";
$expected = ['gimnasios','usuarios','socios','planes','membresias','pagos'];
foreach ($expected as $col) {
 $num = $db->{$col}->countDocuments([]);
 echo "$col : $num documentos\n";
 if ($num < 1) throw new RuntimeException("Faltan datos de prueba en $col");
}
$gyms = iterator_to_array($db->gimnasios->find([],['limit'=>2]),false);
if(count($gyms)<2) throw new RuntimeException('Se requieren 2 gimnasios de prueba');
foreach($gyms as $g) {
 $id=$g['_id'];
 $scoped=$s->records('socios',$id);
 foreach ($scoped as $doc) if((string)$doc['gimnasioId']!==(string)$id) throw new RuntimeException('Fallo aislamiento de gimnasio');
 // Obligamos MISS -> lectura de MongoDB -> HIT Redis para evidenciar ambos caminos.
 $cache->invalidate((string)$id);
 $r=$s->report($id);
 if ($cache->lastEvent() !== 'MISS') throw new RuntimeException('Fallo prueba de MISS en Redis');
 $rCached=$s->report($id);
 if ($cache->lastEvent() !== 'HIT' || $rCached !== $r) throw new RuntimeException('Fallo HIT Redis: datos inconsistentes');
 echo "Redis cache para $id: MISS seguido de HIT OK\n";
 echo $g['nombreComercial'].': '.count($scoped).' socios, '. $r['membresiasHabilitadas'].' membresias habilitadas, ingresos ₡'.$r['ingresos'].PHP_EOL;
}
$payIdx = iterator_to_array($db->pagos->listIndexes(),false);
if (!in_array('uniq_pago_efectivo',array_map(fn($x)=>$x->getName(),$payIdx),true)) throw new RuntimeException('Falta indice parcial de pagos');
echo "OK: MongoDB+Redis PING, seis colecciones, datos, indices, aislamiento y cache MISS/HIT.\n";
