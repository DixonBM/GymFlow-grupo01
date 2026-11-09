<?php
declare(strict_types=1);
namespace GymFlow;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Database;

final class GymService
{
    public function __construct(public readonly Database $db, private readonly ?ReportCache $cache = null) {}

    /** Estado de la ultima consulta del panel, visible como evidencia del HIT/MISS. */
    public function cacheEvent(): string { return $this->cache?->lastEvent() ?? 'sin Redis'; }
    public function redisHealthy(): bool { return $this->cache?->health() ?? false; }
    public function cacheTtl(): int { return $this->cache?->ttl() ?? 0; }
    private function invalidateGym(ObjectId $gym): void { $this->cache?->invalidate((string)$gym); }


    public function ping(): void { $this->db->command(['ping' => 1]); }

    public function userById(string $id): ?array
    {
        $u = $this->db->usuarios->findOne(['_id' => Bootstrap::id($id), 'estado' => 'activo']);
        return $u ? (array) $u : null;
    }
    public function login(string $email, string $password): array
    {
        $u = $this->db->usuarios->findOne(['correo' => Bootstrap::email($email), 'estado' => 'activo']);
        if (!$u || !password_verify($password, (string)$u['passwordHash'])) {
            throw new DomainError('Credenciales incorrectas.');
        }
        if ($u['rol'] === 'administrador') {
            $g = $this->db->gimnasios->findOne(['_id' => $u['gimnasioId'], 'estado' => 'activo']);
            if (!$g) throw new DomainError('Gimnasio sin acceso activo.');
        }
        return (array)$u;
    }
    public function gyms(array $user): array
    {
        if ($user['rol'] === 'superadmin') return iterator_to_array($this->db->gimnasios->find([], ['sort' => ['nombreComercial' => 1]]), false);
        $g = $this->db->gimnasios->findOne(['_id' => $user['gimnasioId']]);
        return $g ? [$g] : [];
    }
    public function gymId(array $user, string $requested): ?ObjectId
    {
        if ($user['rol'] !== 'superadmin') return $user['gimnasioId'];
        if ($requested === '') return null;
        $oid = Bootstrap::id($requested);
        if (!$this->db->gimnasios->findOne(['_id' => $oid])) throw new DomainError('El gimnasio no existe.');
        return $oid;
    }
    public function requireGym(?ObjectId $gym): ObjectId
    {
        if (!$gym) throw new DomainError('Seleccioná un gimnasio para continuar.');
        return $gym;
    }
    public function scoped(string $collection, ObjectId $gym, string $id): array
    {
        $found = $this->db->{$collection}->findOne(['_id' => Bootstrap::id($id), 'gimnasioId' => $gym]);
        if (!$found) throw new DomainError('Registro no encontrado en este gimnasio.');
        return (array)$found;
    }
    public function records(string $collection, ObjectId $gym, int $max = 500): array
    {
        if (!in_array($collection, ['socios','planes','membresias','pagos'], true)) throw new DomainError('Coleccion invalida.');
        return iterator_to_array($this->db->{$collection}->find(['gimnasioId' => $gym], ['sort' => ['_id' => -1], 'limit' => $max]), false);
    }
    private function isSuper(array $u): void
    {
        if ($u['rol'] !== 'superadmin') throw new DomainError('Solo el superadministrador puede realizar esta operacion.');
    }
    public function saveGym(array $u, array $d): void
    {
        $this->isSuper($u);
        $fields = [
            'nombreComercial' => Bootstrap::required($d, 'nombreComercial', 'nombre del gimnasio'),
            'razonSocial' => Bootstrap::optional($d, 'razonSocial'),
            'contacto' => ['correo' => Bootstrap::email(Bootstrap::required($d, 'correo', 'correo')), 'telefono' => Bootstrap::optional($d, 'telefono', 40)],
            'ubicacion' => ['provincia' => Bootstrap::optional($d, 'provincia', 70), 'canton' => Bootstrap::optional($d, 'canton', 70)],
            'estado' => $d['estado'] ?? 'activo',
        ];
        if (!in_array($fields['estado'], ['pendiente','activo','inactivo'], true)) throw new DomainError('Estado invalido.');
        if (!empty($d['id'])) {
            $id = Bootstrap::id((string)$d['id']);
            if (!$this->db->gimnasios->findOne(['_id' => $id])) throw new DomainError('Gimnasio inexistente.');
            $this->db->gimnasios->updateOne(['_id' => $id], ['$set' => $fields]);
        } else {
            $fields['fechaRegistro'] = Bootstrap::now();
            $this->db->gimnasios->insertOne($fields);
        }
    }
    public function saveUser(array $u, array $d): void
    {
        $this->isSuper($u);
        $role = (string)($d['rol'] ?? 'administrador');
        if (!in_array($role, ['superadmin','administrador'], true)) throw new DomainError('Rol invalido.');
        $gym = $role === 'superadmin' ? null : Bootstrap::id(Bootstrap::required($d, 'gimnasioId', 'gimnasio'));
        if ($gym && !$this->db->gimnasios->findOne(['_id' => $gym, 'estado' => 'activo'])) throw new DomainError('El gimnasio debe estar activo.');
        $fields = [
            'gimnasioId' => $gym,
            'nombre' => Bootstrap::required($d, 'nombre', 'nombre'),
            'correo' => Bootstrap::email(Bootstrap::required($d, 'correo', 'correo')),
            'rol' => $role,
            'estado' => (string)($d['estado'] ?? 'activo'),
        ];
        if (!in_array($fields['estado'], ['activo', 'inactivo'], true)) throw new DomainError('Estado de usuario invalido.');
        $pwd = (string)($d['password'] ?? '');
        if ($pwd !== '') {
            if (strlen($pwd) < 10) throw new DomainError('La contraseña debe tener al menos 10 caracteres.');
            $fields['passwordHash'] = password_hash($pwd, PASSWORD_DEFAULT);
        }
        if (!empty($d['id'])) {
            $id = Bootstrap::id((string)$d['id']);
            if (!$this->db->usuarios->findOne(['_id' => $id])) throw new DomainError('Usuario inexistente.');
            if ($id == $u['_id'] && $fields['estado'] !== 'activo') throw new DomainError('No podés desactivar tu propia cuenta.');
            $this->db->usuarios->updateOne(['_id' => $id], ['$set' => $fields]);
        } else {
            if ($pwd === '') throw new DomainError('Asigná una contraseña al nuevo usuario.');
            $this->db->usuarios->insertOne($fields);
        }
    }
    public function saveSocio(ObjectId $gym, array $d): void
    {
        $birthday = Bootstrap::optional($d, 'fechaNacimiento', 10);
        if ($birthday !== '' && ($birthday > date('Y-m-d') || Bootstrap::dateString(Bootstrap::localDate($birthday)) !== $birthday)) {
            throw new DomainError('Fecha de nacimiento invalida.');
        }
        $fields = [
            'identificacion' => Bootstrap::required($d, 'identificacion', 'identificacion', 40),
            'nombreCompleto' => Bootstrap::required($d, 'nombreCompleto', 'nombre completo'),
            'contacto' => [
                'correo' => ($d['correo'] ?? '') !== '' ? Bootstrap::email((string)$d['correo']) : '',
                'telefono' => Bootstrap::optional($d, 'telefono', 40),
            ],
            'fechaNacimiento' => $birthday,
            'observaciones' => Bootstrap::optional($d, 'observaciones', 1000),
            'estado' => (string)($d['estado'] ?? 'activo'),
        ];
        if (!in_array($fields['estado'], ['activo','inactivo'], true)) throw new DomainError('Estado invalido.');
        if (!empty($d['id'])) {
            $record = $this->scoped('socios', $gym, (string)$d['id']);
            $this->db->socios->updateOne(['_id' => $record['_id'], 'gimnasioId' => $gym], ['$set' => $fields]);
        } else {
            $fields['gimnasioId'] = $gym;
            $fields['fechaRegistro'] = Bootstrap::now();
            $this->db->socios->insertOne($fields);
        }
        $this->invalidateGym($gym);
    }
    public function savePlan(ObjectId $gym, array $d): void
    {
        $duration = filter_var($d['duracionDias'] ?? null, FILTER_VALIDATE_INT);
        if ($duration === false || $duration < 1 || $duration > 3650) throw new DomainError('La duracion debe estar entre 1 y 3650 dias.');
        $fields = [
            'nombre' => Bootstrap::required($d, 'nombre', 'nombre del plan'),
            'duracionDias' => $duration,
            'precio' => Bootstrap::decimal((string)($d['precio'] ?? '')),
            'moneda' => 'CRC',
            'descripcion' => Bootstrap::optional($d, 'descripcion', 500),
            'observaciones' => Bootstrap::optional($d, 'observaciones', 500),
            'estado' => (string)($d['estado'] ?? 'activo'),
        ];
        if (!in_array($fields['estado'], ['activo','inactivo'], true)) throw new DomainError('Estado invalido.');
        if (!empty($d['id'])) {
            $record = $this->scoped('planes', $gym, (string)$d['id']);
            $this->db->planes->updateOne(['_id' => $record['_id'], 'gimnasioId' => $gym], ['$set' => $fields]);
        } else {
            $fields['gimnasioId'] = $gym;
            $this->db->planes->insertOne($fields);
        }
        $this->invalidateGym($gym);
    }
    public function createMembership(ObjectId $gym, array $d): void
    {
        $s = $this->scoped('socios', $gym, Bootstrap::required($d, 'socioId', 'socio'));
        $p = $this->scoped('planes', $gym, Bootstrap::required($d, 'planId', 'plan'));
        if ($s['estado'] !== 'activo' || $p['estado'] !== 'activo') throw new DomainError('Socio y plan deben estar activos.');
        $start = Bootstrap::localDate(Bootstrap::required($d, 'fechaInicio', 'fecha de inicio'));
        if (!empty($d['renovar'])) {
            $last = $this->db->membresias->findOne(['gimnasioId'=>$gym,'socioId'=>$s['_id'],'cancelada'=>false], ['sort'=>['fechaFin'=>-1]]);
            if ($last && $last['fechaFin']->toDateTime()->getTimestamp() > $start->toDateTime()->getTimestamp()) $start = $last['fechaFin'];
        }
        $end = Bootstrap::addDays($start, (int)$p['duracionDias']);
        $overlap = $this->db->membresias->findOne([
            'gimnasioId'=>$gym, 'socioId'=>$s['_id'], 'cancelada'=>false,
            'fechaInicio'=>['$lt'=>$end], 'fechaFin'=>['$gt'=>$start],
        ]);
        if ($overlap) throw new DomainError('Ya existe una membresia que se superpone; usá Renovar o cambiá la fecha.');
        $this->db->membresias->insertOne([
            'gimnasioId'=>$gym, 'socioId'=>$s['_id'], 'planId'=>$p['_id'],
            'condicionesContratadas'=>['nombrePlan'=>$p['nombre'], 'duracionDias'=>$p['duracionDias'], 'precio'=>$p['precio'], 'moneda'=>'CRC'],
            'fechaInicio'=>$start, 'fechaFin'=>$end, 'cancelada'=>false,
            'fechaCreacion'=>Bootstrap::now(), 'observaciones'=>Bootstrap::optional($d, 'observaciones', 500),
        ]);
        $this->invalidateGym($gym);
    }

    public function renewMembershipAndPay(
        ObjectId $gym,
        array $u,
        array $d
    ): void {
        $sourceId = Bootstrap::id(
            Bootstrap::required($d, 'membresiaOrigenId', 'membresía anterior')
        );

        $planId = Bootstrap::id(
            Bootstrap::required($d, 'planId', 'plan')
        );

        $method = (string)($d['metodo'] ?? '');

        if (!in_array($method, ['efectivo', 'sinpe_movil', 'transferencia'], true)) {
            throw new DomainError('Método de pago inválido.');
        }

        $key = Bootstrap::required($d, 'claveOperacion', 'clave de operación', 64);

        if (!preg_match('/^[a-f0-9]{32}$/', $key)) {
            throw new DomainError('Clave de operación inválida.');
        }

        $start = Bootstrap::localDate(
            Bootstrap::required($d, 'fechaInicio', 'fecha de inicio')
        );

        $reference = Bootstrap::optional($d, 'referencia', 100);
        $observations = Bootstrap::optional($d, 'observaciones', 500);

        // La sesión debe pertenecer al mismo Manager que utiliza la conexión.
        $session = $this->db->getManager()->startSession();

        try {
            $session->startTransaction();

            // Evitar registrar dos renovaciones por un reenvío del formulario.
            $duplicate = $this->db->pagos->findOne(
                [
                    'gimnasioId' => $gym,
                    'claveOperacion' => $key
                ],
                ['session' => $session]
            );

            if ($duplicate) {
                $existingRenewal = $this->db->membresias->findOne(
                    [
                        '_id' => $duplicate['membresiaId'],
                        'gimnasioId' => $gym,
                        'renovacionDe' => $sourceId
                    ],
                    ['session' => $session]
                );

                $session->abortTransaction();

                if ($existingRenewal) {
                    return;
                }

                throw new DomainError(
                    'La clave de operación ya fue utilizada.'
                );
            }

            // Obtener la membresía anterior.
            $previous = $this->db->membresias->findOne(
                [
                    '_id' => $sourceId,
                    'gimnasioId' => $gym
                ],
                ['session' => $session]
            );

            if (!$previous) {
                throw new DomainError('La membresía anterior no existe.');
            }

            if (!empty($previous['cancelada'])) {
                throw new DomainError('No se puede renovar una membresía cancelada.');
            }

            // Verificar que el socio siga activo.
            $socio = $this->db->socios->findOne(
                [
                    '_id' => $previous['socioId'],
                    'gimnasioId' => $gym,
                    'estado' => 'activo'
                ],
                ['session' => $session]
            );

            if (!$socio) {
                throw new DomainError('El socio no existe o está inactivo.');
            }

            // Obtener el nuevo plan seleccionado.
            $plan = $this->db->planes->findOne(
                [
                    '_id' => $planId,
                    'gimnasioId' => $gym,
                    'estado' => 'activo'
                ],
                ['session' => $session]
            );

            if (!$plan) {
                throw new DomainError('El plan seleccionado no existe o está inactivo.');
            }

            // La membresía anterior debe tener un pago efectivo.
            $previousPayment = $this->db->pagos->findOne(
                [
                    'gimnasioId' => $gym,
                    'membresiaId' => $previous['_id'],
                    'estado' => 'registrado'
                ],
                ['session' => $session]
            );

            if (!$previousPayment) {
                throw new DomainError(
                    'La membresía anterior no tiene un pago registrado.'
                );
            }

            // Solo se permite renovar desde la membresía más reciente.
            $latest = $this->db->membresias->findOne(
                [
                    'gimnasioId' => $gym,
                    'socioId' => $previous['socioId'],
                    'cancelada' => false
                ],
                [
                    'sort' => [
                        'fechaFin' => -1,
                        'fechaCreacion' => -1
                    ],
                    'session' => $session
                ]
            );

            if (
                !$latest ||
                (string)$latest['_id'] !== (string)$previous['_id']
            ) {
                throw new DomainError(
                    'Debés renovar desde la membresía más reciente del socio.'
                );
            }

            // Si todavía está vigente, comenzar al finalizar el período anterior.
            $previousEnd = $latest['fechaFin'];

            if (
                $previousEnd->toDateTime()->getTimestamp() >
                $start->toDateTime()->getTimestamp()
            ) {
                $start = $previousEnd;
            }

            $end = Bootstrap::addDays(
                $start,
                (int)$plan['duracionDias']
            );

            // Evitar períodos superpuestos.
            $overlap = $this->db->membresias->findOne(
                [
                    'gimnasioId' => $gym,
                    'socioId' => $previous['socioId'],
                    'cancelada' => false,
                    'fechaInicio' => ['$lt' => $end],
                    'fechaFin' => ['$gt' => $start]
                ],
                ['session' => $session]
            );

            if ($overlap) {
                throw new DomainError(
                    'El nuevo período se superpone con otra membresía.'
                );
            }

            // 1. Crear una NUEVA membresía.
            $membership = [
                'gimnasioId' => $gym,
                'socioId' => $previous['socioId'],
                'planId' => $plan['_id'],

                'condicionesContratadas' => [
                    'nombrePlan' => $plan['nombre'],
                    'duracionDias' => $plan['duracionDias'],
                    'precio' => $plan['precio'],
                    'moneda' => 'CRC'
                ],

                'fechaInicio' => $start,
                'fechaFin' => $end,
                'cancelada' => false,
                'fechaCreacion' => Bootstrap::now(),
                'observaciones' => $observations,

                // Relación con la membresía anterior.
                'renovacionDe' => $previous['_id']
            ];

            $inserted = $this->db->membresias->insertOne(
                $membership,
                ['session' => $session]
            );

            $newMembershipId = $inserted->getInsertedId();

            // 2. Registrar el pago asociado a la nueva membresía.
            $this->db->pagos->insertOne(
                [
                    'gimnasioId' => $gym,
                    'membresiaId' => $newMembershipId,
                    'fechaPago' => Bootstrap::now(),
                    'monto' => $plan['precio'],
                    'moneda' => 'CRC',
                    'metodo' => $method,
                    'referencia' => $reference,
                    'claveOperacion' => $key,
                    'registradoPor' => $u['_id'],
                    'estado' => 'registrado',
                    'observaciones' => $observations
                ],
                ['session' => $session]
            );

            // Confirmar ambas operaciones juntas.
            $session->commitTransaction();

        } catch (\Throwable $e) {

            // Si algo falla, revertir los cambios.
            try {
                if ($session->isInTransaction()) {
                    $session->abortTransaction();
                }
            } catch (\Throwable $rollbackError) {
                error_log('Error revirtiendo renovación: ' . $rollbackError->getMessage());
            }

            throw $e;

        } finally {
            $session->endSession();
        }

        // Invalidar Redis únicamente después de confirmar la transacción.
        $this->invalidateGym($gym);
    }

    public function cancelMembership(ObjectId $gym, string $id): void
    {
        $m = $this->scoped('membresias', $gym, $id);
        if ($m['cancelada']) throw new DomainError('La membresia ya fue cancelada.');
        $this->db->membresias->updateOne(['_id'=>$m['_id'],'gimnasioId'=>$gym], ['$set'=>['cancelada'=>true]]);
        $this->invalidateGym($gym);
    }
    public function pay(ObjectId $gym, array $u, array $d): void
    {
        $m = $this->scoped('membresias', $gym, Bootstrap::required($d, 'membresiaId', 'membresia'));
        if ($m['cancelada']) throw new DomainError('No se pueden registrar pagos en una membresia cancelada.');
        $method = (string)($d['metodo'] ?? '');
        if (!in_array($method, ['efectivo','sinpe_movil','transferencia'], true)) throw new DomainError('Metodo de pago invalido.');
        $key = Bootstrap::required($d, 'claveOperacion', 'claveOperacion', 64);
        if (!preg_match('/^[a-f0-9]{32}$/', $key)) throw new DomainError('Clave de operacion invalida.');
        $duplicate = $this->db->pagos->findOne(['gimnasioId'=>$gym, 'claveOperacion'=>$key]);
        if ($duplicate) {
            if ((string)$duplicate['membresiaId'] === (string)$m['_id']) return;
            throw new DomainError('La clave de operacion se utilizo en otra membresia.');
        }
        $paid = $this->db->pagos->findOne(['gimnasioId'=>$gym, 'membresiaId'=>$m['_id'], 'estado'=>'registrado']);
        if ($paid) throw new DomainError('Esta membresia ya tiene un pago registrado.');
        $this->db->pagos->insertOne([
            'gimnasioId'=>$gym, 'membresiaId'=>$m['_id'], 'fechaPago'=>Bootstrap::now(),
            'monto'=>$m['condicionesContratadas']['precio'], 'moneda'=>'CRC', 'metodo'=>$method,
            'referencia'=>Bootstrap::optional($d, 'referencia', 100),
            'claveOperacion'=>$key, 'registradoPor'=>$u['_id'], 'estado'=>'registrado',
            'observaciones'=>Bootstrap::optional($d, 'observaciones', 500),
        ]);
        $this->invalidateGym($gym);
    }
    public function annulPayment(ObjectId $gym, array $u, array $d): void
    {
        $p = $this->scoped('pagos', $gym, Bootstrap::required($d, 'id', 'pago'));
        if ($p['estado'] !== 'registrado') throw new DomainError('El pago ya fue anulado.');
        $reason = Bootstrap::required($d, 'motivo', 'motivo de anulacion', 500);
        $this->db->pagos->updateOne(['_id'=>$p['_id'],'gimnasioId'=>$gym,'estado'=>'registrado'], ['$set'=>[
            'estado'=>'anulado', 'anulacion'=>['fecha'=>Bootstrap::now(), 'usuarioId'=>$u['_id'], 'motivo'=>$reason],
        ]]);
        $this->invalidateGym($gym);
    }
    public function report(ObjectId $gym): array
    {
        // Lectura: Redis primero. MISS o fallo: MongoDB sigue disponible.
        $cached = $this->cache?->get((string)$gym);
        if ($cached !== null) return $cached;
        $socios = iterator_to_array($this->db->socios->find(['gimnasioId' => $gym]), false);
        $members = iterator_to_array($this->db->membresias->find(['gimnasioId' => $gym]), false);
        $payments = iterator_to_array($this->db->pagos->find(['gimnasioId' => $gym]), false);
        $effective = [];
        $revenue = 0.0;
        foreach ($payments as $payment) {
            if ($payment['estado'] !== 'registrado') continue;
            $effective[(string)$payment['membresiaId']] = true;
            $revenue += (float)(string)$payment['monto'];
        }
        $now = time(); $enabledMembers = 0; $expiring = 0; $enabledSocios = [];
        $expired = 0; $future = 0; $canceled = 0;
        foreach ($members as $m) {
            $start = $m['fechaInicio']->toDateTime()->getTimestamp();
            $end = $m['fechaFin']->toDateTime()->getTimestamp();
            if ($m['cancelada']) { $canceled++; continue; }
            if ($end <= $now) { $expired++; continue; }
            if ($start > $now) { $future++; continue; }
            if (isset($effective[(string)$m['_id']])) {
                $enabledMembers++;
                $enabledSocios[(string)$m['socioId']] = true;
                if ($end - $now <= 7 * 86400) $expiring++;
            }
        }
        $activeSocios = 0;
        foreach ($socios as $s) if ($s['estado'] === 'activo' && isset($enabledSocios[(string)$s['_id']])) $activeSocios++;
        $result = [
            'sociosRegistrados'=>count($socios), 'sociosActivos'=>$activeSocios,
            'membresiasHabilitadas'=>$enabledMembers, 'porVencer'=>$expiring, 'vencidas'=>$expired,
            'futuras'=>$future, 'canceladas'=>$canceled, 'ingresos'=>$revenue, 'pagosRegistrados'=>count($effective),
        ];
        $this->cache?->put((string)$gym, $result);
        return $result;
    }
}
