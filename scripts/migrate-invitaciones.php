<?php
declare(strict_types=1);

use GymFlow\Bootstrap;

require dirname(__DIR__) . '/src/Bootstrap.php';
require dirname(__DIR__) . '/vendor/autoload.php';
Bootstrap::env(dirname(__DIR__) . '/.env');
$db = Bootstrap::db();
$info = $db->listCollections(['filter' => ['name' => 'usuarios']]);
$options = null;
foreach ($info as $collection) { $options = $collection->getOptions(); break; }
if ($options === null) throw new RuntimeException('Colección usuarios no existe. Inicializá la base antes de migrar.');
$validator = isset($options['validator']) ? json_decode(json_encode($options['validator'], JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR) : null;
if (!$validator || !isset($validator['$jsonSchema'])) throw new RuntimeException('No hay un esquema existente que pueda ampliarse de forma segura.');
$json = (array)$validator['$jsonSchema'];
$properties = (array)($json['properties'] ?? []);
$state = (array)($properties['estado'] ?? []);
$values = array_values((array)($state['enum'] ?? []));
if (!in_array('pendiente', $values, true)) $values[] = 'pendiente';
$state['enum'] = $values;
$properties['estado'] = $state;
$json['properties'] = $properties;
$validator['$jsonSchema'] = $json;
$db->command(['collMod' => 'usuarios', 'validator' => $validator, 'validationAction' => 'error', 'validationLevel' => 'strict']);
$db->usuarios->createIndex(['invitacion.tokenHash' => 1], ['name' => 'invitacion_token_hash', 'sparse' => true]);
echo "GymFlow: usuarios permite estado pendiente e índice de invitaciones listo. No se modificaron usuarios existentes.\n";
