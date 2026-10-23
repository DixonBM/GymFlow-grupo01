<?php
declare(strict_types=1);
namespace GymFlow;

use MongoDB\Database;

/** Migración idempotente: NO borra colecciones ni inserta datos de demostración. */
final class MachineSchema
{
    public static function ensure(Database $db): void
    {
        $json = [
            'bsonType' => 'object',
            'required' => ['gimnasioId','codigo','nombre','tipo','estado','fechaRegistro','fechaActualizacion'],
            'properties' => [
                'gimnasioId' => ['bsonType' => 'objectId'],
                'codigo' => ['bsonType' => 'string'],
                'nombre' => ['bsonType' => 'string'],
                'tipo' => ['enum' => ['cardio','fuerza','funcional','otro']],
                'marca' => ['bsonType' => 'string'],
                'modelo' => ['bsonType' => 'string'],
                'ubicacion' => ['bsonType' => 'string'],
                'estado' => ['enum' => ['operativa','mantenimiento','fuera_de_servicio']],
                'observaciones' => ['bsonType' => 'string'],
                'fechaRegistro' => ['bsonType' => 'date'],
                'fechaActualizacion' => ['bsonType' => 'date'],
            ],
        ];
        $names = array_map(static fn($c) => $c->getName(), iterator_to_array($db->listCollections()));
        if (!in_array('maquinas', $names, true)) {
            $db->createCollection('maquinas', [
                'validator' => ['$jsonSchema' => $json],
                'validationLevel' => 'strict', 'validationAction' => 'error',
            ]);
        } else {
            $db->command([
                'collMod' => 'maquinas', 'validator' => ['$jsonSchema' => $json],
                'validationLevel' => 'strict', 'validationAction' => 'error',
            ]);
        }
        $db->maquinas->createIndex(['gimnasioId' => 1, 'codigo' => 1], [
            'unique' => true, 'name' => 'uniq_codigo_maquina_por_gimnasio',
        ]);
        $db->maquinas->createIndex(['gimnasioId' => 1, 'nombre' => 1], [
            'name' => 'idx_maquinas_gimnasio_nombre',
        ]);
    }
}
