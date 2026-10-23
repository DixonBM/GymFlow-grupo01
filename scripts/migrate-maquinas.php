<?php
declare(strict_types=1);

use GymFlow\Bootstrap;
use GymFlow\MachineSchema;

require dirname(__DIR__) . '/src/Bootstrap.php';
require dirname(__DIR__) . '/src/MachineSchema.php';
require dirname(__DIR__) . '/vendor/autoload.php';
Bootstrap::env(dirname(__DIR__) . '/.env');

$db = Bootstrap::db();
$db->command(['ping' => 1]);
MachineSchema::ensure($db);
echo "Máquinas: colección, validador e índices creados/verificados. No se alteraron socios, membresías ni pagos.\n";
