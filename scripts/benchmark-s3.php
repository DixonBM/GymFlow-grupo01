<?php
declare(strict_types=1);

use GymFlow\Bootstrap;
use GymFlow\GymService;
use GymFlow\ReportCache;
use MongoDB\BSON\ObjectId;

$root = dirname(__DIR__);

require $root . '/src/Bootstrap.php';
require $root . '/vendor/autoload.php';
require $root . '/src/ReportCache.php';
require $root . '/src/GymService.php';

Bootstrap::env($root . '/.env');
date_default_timezone_set('America/Costa_Rica');

/**
 * Calcula la mediana correctamente para muestras pares e impares.
 */
function s3Median(array $values): float
{
    if (count($values) === 0) {
        throw new RuntimeException('No hay muestras para calcular la mediana.');
    }

    sort($values, SORT_NUMERIC);

    $n = count($values);
    $middle = intdiv($n, 2);

    if ($n % 2 === 1) {
        return (float) $values[$middle];
    }

    return (
        (float) $values[$middle - 1] +
        (float) $values[$middle]
    ) / 2;
}

/**
 * Percentil 95 mediante rango más cercano.
 */
function s3P95(array $values): float
{
    sort($values, SORT_NUMERIC);

    $index = max(
        0,
        (int) ceil(0.95 * count($values)) - 1
    );

    return (float) $values[$index];
}

/**
 * Mide una operación usando nanosegundos
 * y devuelve el tiempo en milisegundos.
 */
function s3Measure(callable $operation): array
{
    $start = hrtime(true);

    $result = $operation();

    $elapsed = (hrtime(true) - $start) / 1_000_000;

    return [$elapsed, $result];
}

/**
 * Obtiene los identificadores de los documentos.
 */
function s3DocumentIds(array $documents): array
{
    $ids = [];

    foreach ($documents as $document) {
        $ids[] = (string) $document['_id'];
    }

    return $ids;
}

/**
 * Reproduce las consultas de datos principales del dashboard.
 */
function s3DashboardData(
    GymService $service,
    ObjectId $gym
): array {
    return [
        'socios' => $service->records('socios', $gym),
        'planes' => $service->records('planes', $gym),
        'membresias' => $service->records('membresias', $gym),
        'pagos' => $service->records('pagos', $gym),
        'report' => $service->report($gym),
    ];
}

/**
 * Normaliza los resultados para comprobar que coincidan.
 */
function s3NormalizeDashboard(array $data): array
{
    return [
        'socios' => s3DocumentIds($data['socios']),
        'planes' => s3DocumentIds($data['planes']),
        'membresias' => s3DocumentIds($data['membresias']),
        'pagos' => s3DocumentIds($data['pagos']),
        'report' => $data['report'],
    ];
}

function s3AssertSame(
    mixed $expected,
    mixed $actual,
    string $message
): void {
    if ($expected !== $actual) {
        throw new RuntimeException($message);
    }
}

function s3PackageVersion(string $package): string
{
    if (
        !class_exists(\Composer\InstalledVersions::class) ||
        !\Composer\InstalledVersions::isInstalled($package)
    ) {
        return 'No disponible';
    }

    return \Composer\InstalledVersions::getPrettyVersion($package)
        ?: 'No disponible';
}

function s3Ms(float $value): string
{
    return number_format($value, 3, '.', '');
}

try {

    // Número de ejecuciones. Mínimo permitido: 3.
    $rounds = isset($argv[1])
        ? filter_var($argv[1], FILTER_VALIDATE_INT)
        : 30;

    if ($rounds === false || $rounds < 3 || $rounds > 200) {
        throw new RuntimeException(
            'Las ejecuciones deben estar entre 3 y 200.'
        );
    }

    // Conexión con MongoDB.
    $db = Bootstrap::db();
    $db->command(['ping' => 1]);

    // Conexión con Redis.
    $cache = ReportCache::fromEnvironment();

    if (!$cache->health()) {
        throw new RuntimeException(
            'Redis no responde. Iniciá Docker Compose antes del benchmark.'
        );
    }

    // Selección del gimnasio.
    $requestedGymId = trim(
        (string) (getenv('BENCHMARK_GYM_ID') ?: '')
    );

    $gym = $requestedGymId !== ''
        ? $db->gimnasios->findOne([
            '_id' => Bootstrap::id($requestedGymId)
        ])
        : $db->gimnasios->findOne([]);

    if (!$gym) {
        throw new RuntimeException(
            'No se encontró el gimnasio para realizar las mediciones.'
        );
    }

    $gymId = $gym['_id'];
    $gymIdString = (string) $gymId;

    $gymName = (string) (
        $gym['nombreComercial'] ?? 'Sin nombre'
    );

    $without = new GymService($db, null);
    $with = new GymService($db, $cache);

    // Cantidad total de documentos por colección.
    $counts = [];

    foreach (
        ['socios', 'planes', 'membresias', 'pagos'] as $collection
    ) {
        $counts[$collection] = $db->{$collection}->countDocuments([
            'gimnasioId' => $gymId
        ]);
    }

    // Comprobar que MongoDB y Redis entreguen los mismos indicadores.
    $cache->invalidate($gymIdString);

    $missReport = $with->report($gymId);
    $missEvent = $cache->lastEvent();

    $hitReport = $with->report($gymId);
    $hitEvent = $cache->lastEvent();

    $directReport = $without->report($gymId);

    if ($missEvent !== 'MISS' || $hitEvent !== 'HIT') {
        throw new RuntimeException(
            'No se pudo comprobar la secuencia MISS / HIT de Redis.'
        );
    }

    s3AssertSame(
        $directReport,
        $missReport,
        'Los indicadores de MongoDB y el primer resultado no coinciden.'
    );

    s3AssertSame(
        $directReport,
        $hitReport,
        'Los indicadores de MongoDB y Redis no coinciden.'
    );

    // Resultados de referencia.
    $dashboardReference = s3DashboardData($without, $gymId);

    $cachedReference = s3DashboardData($with, $gymId);

    s3AssertSame(
        s3NormalizeDashboard($dashboardReference),
        s3NormalizeDashboard($cachedReference),
        'Los datos del dashboard no coinciden entre ambos motores.'
    );

    // Calentamiento previo de las consultas.
    s3DashboardData($without, $gymId);

    $with->report($gymId);
    $with->report($gymId);

    // Operaciones que se van a medir.
    $timings = [
        'Dashboard completo - MongoDB directo' => [],
        'Dashboard completo - Redis HIT' => [],
        'Listado de socios' => [],
        'Consulta de membresias' => [],
    ];

    $details = [];

    $expectedDashboard = s3NormalizeDashboard(
        $dashboardReference
    );

    $expectedIds = [
        'socios' => s3DocumentIds(
            $dashboardReference['socios']
        ),
        'membresias' => s3DocumentIds(
            $dashboardReference['membresias']
        ),
    ];

    // Ejecutar las mediciones.
    for ($i = 1; $i <= $rounds; $i++) {

        // Alternar el orden para reducir sesgos.
        $dashboardOrder = ($i % 2 === 1)
            ? ['direct', 'redis']
            : ['redis', 'direct'];

        foreach ($dashboardOrder as $mode) {

            if ($mode === 'redis') {

                // Asegurar que la medición sea realmente un HIT.
                $with->report($gymId);

                if ($cache->lastEvent() !== 'HIT') {
                    $with->report($gymId);
                }

                if ($cache->lastEvent() !== 'HIT') {
                    throw new RuntimeException(
                        'Redis dejó de entregar HIT durante la medición.'
                    );
                }

                $service = $with;
                $label = 'Dashboard completo - Redis HIT';

            } else {

                $service = $without;
                $label = 'Dashboard completo - MongoDB directo';
            }

            [$elapsed, $result] = s3Measure(
                fn() => s3DashboardData($service, $gymId)
            );

            if (
                $mode === 'redis' &&
                $cache->lastEvent() !== 'HIT'
            ) {
                throw new RuntimeException(
                    'La consulta medida no utilizó Redis HIT.'
                );
            }

            s3AssertSame(
                $expectedDashboard,
                s3NormalizeDashboard($result),
                'Los resultados del dashboard cambiaron durante la prueba.'
            );

            $timings[$label][] = $elapsed;

            $details[] = [
                'operation' => $label,
                'sample' => $i,
                'ms' => $elapsed,
                'rows' => sprintf(
                    'socios=%d; planes=%d; membresias=%d; pagos=%d',
                    count($result['socios']),
                    count($result['planes']),
                    count($result['membresias']),
                    count($result['pagos'])
                ),
            ];
        }

        // Alternar también el orden de las consultas independientes.
        $listOrder = ($i % 2 === 1)
            ? ['socios', 'membresias']
            : ['membresias', 'socios'];

        foreach ($listOrder as $collection) {

            [$elapsed, $result] = s3Measure(
                fn() => $without->records($collection, $gymId)
            );

            s3AssertSame(
                $expectedIds[$collection],
                s3DocumentIds($result),
                "Los resultados de $collection cambiaron durante la prueba."
            );

            $label = $collection === 'socios'
                ? 'Listado de socios'
                : 'Consulta de membresias';

            $timings[$label][] = $elapsed;

            $details[] = [
                'operation' => $label,
                'sample' => $i,
                'ms' => $elapsed,
                'rows' => count($result),
            ];
        }
    }

    // Calcular estadísticas.
    $summary = [];

    foreach ($timings as $label => $values) {

        $sorted = $values;
        sort($sorted, SORT_NUMERIC);

        $summary[$label] = [
            'count' => count($values),
            'min' => min($sorted),
            'max' => max($sorted),
            'mean' => array_sum($values) / count($values),
            'median' => s3Median($values),
            'p95' => s3P95($values),
        ];
    }

    // Comparación porcentual del dashboard.
    $directMedian = $summary[
        'Dashboard completo - MongoDB directo'
    ]['median'];

    $redisMedian = $summary[
        'Dashboard completo - Redis HIT'
    ]['median'];

    $difference = $directMedian > 0
        ? (($directMedian - $redisMedian) / $directMedian) * 100
        : 0;

    if ($difference > 0) {
        $comparison = sprintf(
            'Redis HIT redujo la mediana del dashboard en %.2f%%.',
            $difference
        );
    } elseif ($difference < 0) {
        $comparison = sprintf(
            'Redis HIT aumentó la mediana del dashboard en %.2f%%.',
            abs($difference)
        );
    } else {
        $comparison = 'Ambas modalidades obtuvieron la misma mediana.';
    }

    // Información del entorno.
    $mongoVersion = 'No disponible';

    try {
        $info = $db->command(['buildInfo' => 1])->toArray();

        if (isset($info[0]->version)) {
            $mongoVersion = (string) $info[0]->version;
        }
    } catch (Throwable $ignored) {
        // La versión no es indispensable para ejecutar el benchmark.
    }

    $date = date('Y-m-d H:i:s P');

    $appEnv = getenv('APP_ENV') ?: 'No definido';
    $dbName = getenv('MONGODB_DB') ?: 'gymflow';

    // Construcción del reporte Markdown.
    $lines = [];

    $lines[] = '# Mediciones de rendimiento S3 - GymFlow';
    $lines[] = '';
    $lines[] = '## 1. Información de la ejecución';
    $lines[] = '';
    $lines[] = "- Fecha: $date";
    $lines[] = "- Entorno: $appEnv";
    $lines[] = "- Sistema: " . php_uname('s') . ' ' . php_uname('r');
    $lines[] = "- PHP: " . PHP_VERSION;
    $lines[] = "- MongoDB Server: $mongoVersion";
    $lines[] = "- MongoDB PHP Library: " . s3PackageVersion('mongodb/mongodb');
    $lines[] = "- Predis: " . s3PackageVersion('predis/predis');
    $lines[] = "- Base de datos: $dbName";
    $lines[] = "- Redis: Conectado (PONG)";
    $lines[] = "- TTL Redis: " . $cache->ttl() . ' segundos';
    $lines[] = "- Gimnasio: $gymName";
    $lines[] = "- ID del gimnasio: `$gymIdString`";
    $lines[] = "- Ejecuciones por operación: $rounds";
    $lines[] = '';

    $lines[] = '## 2. Cantidad de datos utilizados';
    $lines[] = '';
    $lines[] = '| Colección | Total en MongoDB | Devueltos por records() |';
    $lines[] = '|---|---:|---:|';

    foreach (['socios', 'planes', 'membresias', 'pagos'] as $collection) {
        $returned = count($dashboardReference[$collection]);

        $lines[] = sprintf(
            '| %s | %d | %d |',
            $collection,
            $counts[$collection],
            $returned
        );
    }

    $lines[] = '';
    $lines[] = 'La consulta records() utiliza un límite de 500 documentos.';
    $lines[] = 'El reporte del dashboard procesa las colecciones completas asociadas al gimnasio.';
    $lines[] = '';

    $lines[] = '## 3. Metodología';
    $lines[] = '';
    $lines[] = '- Se realizaron mediciones mediante `hrtime(true)`.';
    $lines[] = "- Se ejecutaron $rounds muestras por operación.";
    $lines[] = '- Se alternó el orden de las mediciones del dashboard.';
    $lines[] = '- Se verificó que los resultados de MongoDB y Redis coincidieran.';
    $lines[] = '- Los tiempos se expresan en milisegundos.';
    $lines[] = '- Las consultas de socios y membresías se realizaron directamente en MongoDB.';
    $lines[] = '- El dashboard reproduce las consultas de socios, planes, membresías, pagos e indicadores.';
    $lines[] = '';

    $lines[] = '## 4. Resultados estadísticos';
    $lines[] = '';
    $lines[] = '| Operación | Muestras | Mínimo (ms) | Media (ms) | Mediana (ms) | P95 (ms) | Máximo (ms) |';
    $lines[] = '|---|---:|---:|---:|---:|---:|---:|';

    foreach ($summary as $label => $stats) {

        $lines[] = sprintf(
            '| %s | %d | %s | %s | %s | %s | %s |',
            $label,
            $stats['count'],
            s3Ms($stats['min']),
            s3Ms($stats['mean']),
            s3Ms($stats['median']),
            s3Ms($stats['p95']),
            s3Ms($stats['max'])
        );
    }

    $lines[] = '';
    $lines[] = '## 5. Comparación de Redis';
    $lines[] = '';
    $lines[] = $comparison;
    $lines[] = '';
    $lines[] = 'Redis almacena temporalmente los indicadores del dashboard.';
    $lines[] = 'Las consultas de listados continúan ejecutándose en MongoDB.';
    $lines[] = '';

    $lines[] = '## 6. Tiempos individuales';
    $lines[] = '';
    $lines[] = '| Operación | Ejecución | Tiempo (ms) | Registros devueltos |';
    $lines[] = '|---|---:|---:|---|';

    foreach ($details as $detail) {

        $lines[] = sprintf(
            '| %s | %d | %s | %s |',
            $detail['operation'],
            $detail['sample'],
            s3Ms($detail['ms']),
            $detail['rows']
        );
    }

    $lines[] = '';
    $lines[] = '## 7. Análisis de resultados';
    $lines[] = '';
    $lines[] = 'La mediana representa el valor central de las ejecuciones ordenadas.';
    $lines[] = 'Para una cantidad par de muestras se promedian los dos valores centrales.';
    $lines[] = '';
    $lines[] = 'El P95 se calcula mediante el método de rango más cercano.';
    $lines[] = '';
    $lines[] = $comparison;
    $lines[] = '';
    $lines[] = 'Las mediciones corresponden a operaciones de la capa de servicios PHP.';
    $lines[] = 'No representan el tiempo completo de carga del navegador ni el renderizado HTML.';
    $lines[] = 'Los resultados corresponden al volumen de datos registrado en este entorno.';
    $lines[] = '';
    $lines[] = '## 8. Evidencias y trazabilidad';
    $lines[] = '';
    $lines[] = '- Script: `scripts/benchmark-s3.php`.';
    $lines[] = '- Benchmark anterior: `scripts/cache-benchmark.php`.';
    $lines[] = '- Commit: pendiente de registrar.';
    $lines[] = '- Evidencias adicionales: pendientes de incorporar.';
    $lines[] = '';
    $lines[] = '## 9. Conclusión';
    $lines[] = '';
    $lines[] = 'El benchmark permite comparar el comportamiento del dashboard';
    $lines[] = 'con Redis HIT y con consultas directas a MongoDB, además de';
    $lines[] = 'medir independientemente los listados de socios y membresías.';
    $lines[] = '';
    $lines[] = 'No se modificaron ni eliminaron datos durante las pruebas.';
    $lines[] = '';

    $reportPath = getenv('BENCHMARK_REPORT_PATH')
    ?: $root . '/docs/mediciones-S3.md';

    if (!is_dir(dirname($reportPath))) {
        mkdir(dirname($reportPath), 0775, true);
    }

    $written = file_put_contents(
        $reportPath,
        implode(PHP_EOL, $lines) . PHP_EOL,
        LOCK_EX
    );

    if ($written === false) {
        throw new RuntimeException(
            'No se pudo escribir docs/mediciones-S3.md.'
        );
    }

    // Resumen en consola.
    echo PHP_EOL;
    echo "GYMFLOW - BENCHMARK S3" . PHP_EOL;
    echo "======================" . PHP_EOL;
    echo "Gimnasio: $gymName" . PHP_EOL;
    echo "MongoDB: CONECTADO" . PHP_EOL;
    echo "Redis: CONECTADO" . PHP_EOL;
    echo "Ejecuciones: $rounds" . PHP_EOL . PHP_EOL;

    foreach ($summary as $label => $stats) {
        printf(
            "%s | Mediana: %.3f ms | Media: %.3f ms | P95: %.3f ms\n",
            $label,
            $stats['median'],
            $stats['mean'],
            $stats['p95']
        );
    }

    echo PHP_EOL;
    echo $comparison . PHP_EOL;
    echo "REPORTE GENERADO: docs/mediciones-S3.md" . PHP_EOL;

} catch (Throwable $e) {

    fwrite(
        STDERR,
        PHP_EOL . 'ERROR BENCHMARK S3: ' . $e->getMessage() . PHP_EOL
    );

    exit(1);
}
