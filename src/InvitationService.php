<?php
declare(strict_types=1);
namespace GymFlow;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Database;
use Throwable;

/** Invitaciones con token aleatorio; MongoDB solo guarda SHA-256 del token. */
final class InvitationService
{
    public function __construct(private readonly Database $db, private readonly MailService $mailer) {}

    private function onlySuper(array $admin): void
    {
        if (($admin['rol'] ?? '') !== 'superadmin') throw new DomainError('Solo el superadministrador puede invitar usuarios.');
    }

    private function tokenFields(string $token): array
    {
        return [
            'invitacion.tokenHash' => hash('sha256', $token),
            'invitacion.venceEn' => new UTCDateTime((time() + 24 * 3600) * 1000),
        ];
    }

    private function sendAndMark(ObjectId $userId, string $email, string $name, string $gymName, string $role, string $token): bool
    {
        try {
            $this->mailer->sendInvitation($email, $name, $gymName, $role, $token);
            $this->db->usuarios->updateOne(
                ['_id' => $userId, 'estado' => 'pendiente', 'invitacion.tokenHash' => hash('sha256', $token)],
                ['$set' => ['invitacion.enviadoEn' => Bootstrap::now()]]
            );
            return true;
        } catch (Throwable $exception) {
            // No imprimir tokens ni credenciales SMTP. La cuenta queda pendiente para reenvío.
            error_log('GymFlow: no fue posible enviar invitación SMTP (' . get_class($exception) . ').');
            return false;
        }
    }

    public function create(array $admin, array $form): bool
    {
        $this->onlySuper($admin);
        $name = Bootstrap::required($form, 'nombre', 'nombre');
        $email = Bootstrap::email(Bootstrap::required($form, 'correo', 'correo'));
        $role = (string)($form['rol'] ?? 'administrador');
        if (!in_array($role, ['superadmin','administrador'], true)) throw new DomainError('Rol inválido.');
        if ($this->db->usuarios->findOne(['correo' => $email])) throw new DomainError('Ya existe una cuenta con ese correo.');
        $gym = null;
        $gymName = 'GymFlow';
        if ($role === 'administrador') {
            $gym = Bootstrap::id(Bootstrap::required($form, 'gimnasioId', 'gimnasio'));
            $doc = $this->db->gimnasios->findOne(['_id' => $gym, 'estado' => 'activo']);
            if (!$doc) throw new DomainError('El gimnasio asignado debe estar activo.');
            $gymName = (string)$doc['nombreComercial'];
        }
        $token = bin2hex(random_bytes(32));
        $document = [
            'gimnasioId' => $gym, 'nombre' => $name, 'correo' => $email, 'rol' => $role,
            'estado' => 'pendiente',
            // El validador original requiere passwordHash; no sirve para iniciar sesión.
            'passwordHash' => password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT),
            'fechaCreacion' => Bootstrap::now(),
            'invitacion' => ['tokenHash' => hash('sha256', $token), 'venceEn' => new UTCDateTime((time() + 86400) * 1000)],
        ];
        $userId = $this->db->usuarios->insertOne($document)->getInsertedId();
        return $this->sendAndMark($userId, $email, $name, $gymName, $role, $token);
    }

    public function resend(array $admin, string $id): bool
    {
        $this->onlySuper($admin);
        $user = $this->db->usuarios->findOne(['_id' => Bootstrap::id($id), 'estado' => 'pendiente']);
        if (!$user) throw new DomainError('No se encontró una cuenta pendiente.');
        $last = $user['invitacion']['enviadoEn'] ?? null;
        if ($last instanceof UTCDateTime && $last->toDateTime()->getTimestamp() > time() - 60) {
            throw new DomainError('Esperá un minuto antes de reenviar la invitación.');
        }
        $gymName = 'GymFlow';
        if (($user['rol'] ?? '') === 'administrador') {
            $gym = $this->db->gimnasios->findOne(['_id' => $user['gimnasioId']]);
            $gymName = $gym ? (string)$gym['nombreComercial'] : 'GymFlow';
        }
        $token = bin2hex(random_bytes(32));
        $this->db->usuarios->updateOne(['_id' => $user['_id'], 'estado' => 'pendiente'], [
            '$set' => ['invitacion.tokenHash' => hash('sha256', $token), 'invitacion.venceEn' => new UTCDateTime((time() + 86400) * 1000)],
            '$unset' => ['invitacion.enviadoEn' => ''],
        ]);
        return $this->sendAndMark($user['_id'], (string)$user['correo'], (string)$user['nombre'], $gymName, (string)$user['rol'], $token);
    }

    public function isValid(string $token): bool
    {
        return $this->pendingToken($token) !== null;
    }

    private function pendingToken(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) return null;
        $user = $this->db->usuarios->findOne([
            'estado' => 'pendiente', 'invitacion.tokenHash' => hash('sha256', $token),
            'invitacion.venceEn' => ['$gt' => Bootstrap::now()],
        ]);
        return $user ? (array)$user : null;
    }

    public function activate(string $token, string $password, string $confirmation): void
    {
        if (strlen($password) < 12 || strlen($password) > 1024) {
            throw new DomainError('La contraseña debe tener entre 12 y 1024 caracteres.');
        }
        if (!hash_equals($password, $confirmation)) throw new DomainError('Las contraseñas no coinciden.');
        $user = $this->pendingToken($token);
        if (!$user) throw new DomainError('La invitación no es válida o ya venció. Solicitá un nuevo enlace.');
        $updated = $this->db->usuarios->updateOne([
            '_id' => $user['_id'], 'estado' => 'pendiente',
            'invitacion.tokenHash' => hash('sha256', $token),
            'invitacion.venceEn' => ['$gt' => Bootstrap::now()],
        ], [
            '$set' => ['passwordHash' => password_hash($password, PASSWORD_DEFAULT), 'estado' => 'activo', 'fechaActivacion' => Bootstrap::now()],
            '$unset' => ['invitacion' => ''],
        ]);
        if ($updated->getModifiedCount() !== 1) throw new DomainError('Este enlace ya se utilizó o venció.');
    }
}
