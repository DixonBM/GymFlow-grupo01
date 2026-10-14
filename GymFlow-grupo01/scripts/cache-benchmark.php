<?php
declare(strict_types=1);
/** Medición opcional (no reemplaza la evidencia de pruebas reales). */
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
if (!$cache->health()) throw new RuntimeException('Redis no responde. Iniciar ambos motores primero.');
$gym = $db->gimnasios->findOne([]);
if (!$gym) throw new RuntimeException('Ejecuta php scripts/setup.php --seed primero.');
$id = (string)$gym['_id'];
$without = new GymService($db, null);
$with = new GymService($db, $cache);
$cache->invalidate($id);
$first = $with->report($gym['_id']);
$cached = $with->report($gym['_id']);
if ($first !== $cached || $cache->lastEvent() !== 'HIT') throw new RuntimeException('La cache no coincidió con MongoDB');
$rounds = 30;
$timings = ['MongoDB directo' => [], 'Redis HIT' => []];
for ($i=0; $i<$rounds; $i++) {
    // Orden alternado para mitigar el sesgo por calentamiento.
    $order = ($i % 2 === 0) ? ['MongoDB directo','Redis HIT'] : ['Redis HIT','MongoDB directo'];
    foreach ($order as $which) {
        $start = hrtime(true);
        $out = ($which === 'Redis HIT') ? $with->report($gym['_id']) : $without->report($gym['_id']);
        $timings[$which][] = (hrtime(true)-$start) / 1e6;
        if ($out !== $first) throw new RuntimeException('Diferencia en indicadores.');
    }
}
echo "GymFlow · Medición en entorno REAL · ".date('c')." · gimnasios: 1 · repeticiones: $rounds\n";
foreach ($timings as $label=>$t) {
    sort($t);
    printf("%-18s media: %.3f ms; mediana: %.3f ms; p95: %.3f ms\n", $label, array_sum($t)/count($t), $t[(int)floor(count($t)/2)], $t[(int)ceil(0.95*count($t))-1]);
}
echo "Anotar hardware, versiones, cantidad de documentos y fecha en docs/seguimientos/S1.md.\n";
echo "No extrapolar un dataset pequeno a produccion.\n";
