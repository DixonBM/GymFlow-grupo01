<?php
declare(strict_types=1);
namespace GymFlow;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use MongoDB\Database;

/** Inventario de máquinas: todas las operaciones se limitan a gimnasioId. */
final class MachineService
{
    private const TIPOS = ['cardio', 'fuerza', 'funcional', 'otro'];
    private const ESTADOS = ['operativa', 'mantenimiento', 'fuera_de_servicio'];

    public function __construct(private readonly Database $db) {}

    public function list(ObjectId $gym, string $search = '', string $status = ''): array
    {
        $search = trim($search);
        if (strlen($search) > 80) throw new DomainError('La búsqueda no puede superar 80 caracteres.');
        if ($status !== '' && !in_array($status, self::ESTADOS, true)) throw new DomainError('Filtro de estado inválido.');
        $filter = ['gimnasioId' => $gym];
        if ($search !== '') {
            $term = new Regex(preg_quote($search, '/'), 'i');
            $filter['$or'] = [
                ['codigo' => $term], ['nombre' => $term], ['tipo' => $term],
                ['marca' => $term], ['modelo' => $term], ['ubicacion' => $term],
            ];
        }
        if ($status !== '') $filter['estado'] = $status;
        return iterator_to_array($this->db->maquinas->find($filter, [
            'sort' => ['nombre' => 1, '_id' => 1], 'limit' => 500,
        ]), false);
    }

    public function get(ObjectId $gym, string $id): array
    {
        $machine = $this->db->maquinas->findOne([
            '_id' => Bootstrap::id($id), 'gimnasioId' => $gym,
        ]);
        if (!$machine) throw new DomainError('Máquina no encontrada en este gimnasio.');
        return (array) $machine;
    }

    public function save(ObjectId $gym, array $input): void
    {
        $code = Bootstrap::required($input, 'codigo', 'código de inventario', 40);
        if (!preg_match('/^[\p{L}\p{N}_.-]+$/u', $code)) {
            throw new DomainError('El código solo admite letras, números, puntos, guiones y guion bajo.');
        }
        $type = (string)($input['tipo'] ?? '');
        $status = (string)($input['estado'] ?? 'operativa');
        if (!in_array($type, self::TIPOS, true)) throw new DomainError('Tipo de máquina inválido.');
        if (!in_array($status, self::ESTADOS, true)) throw new DomainError('Estado de máquina inválido.');
        $fields = [
            'codigo' => $code,
            'nombre' => Bootstrap::required($input, 'nombre', 'nombre de la máquina', 150),
            'tipo' => $type,
            'marca' => Bootstrap::optional($input, 'marca', 100),
            'modelo' => Bootstrap::optional($input, 'modelo', 100),
            'ubicacion' => Bootstrap::optional($input, 'ubicacion', 150),
            'estado' => $status,
            'observaciones' => Bootstrap::optional($input, 'observaciones', 1000),
            'fechaActualizacion' => Bootstrap::now(),
        ];
        if (!empty($input['id'])) {
            $machine = $this->get($gym, (string)$input['id']);
            $this->db->maquinas->updateOne(
                ['_id' => $machine['_id'], 'gimnasioId' => $gym],
                ['$set' => $fields]
            );
        } else {
            $fields['gimnasioId'] = $gym;
            $fields['fechaRegistro'] = Bootstrap::now();
            $this->db->maquinas->insertOne($fields);
        }
    }

    public function delete(ObjectId $gym, string $id): void
    {
        $machine = $this->get($gym, $id);
        $result = $this->db->maquinas->deleteOne([
            '_id' => $machine['_id'], 'gimnasioId' => $gym,
        ]);
        if ($result->getDeletedCount() !== 1) throw new DomainError('No se pudo eliminar la máquina.');
    }
}
