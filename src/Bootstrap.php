<?php
declare(strict_types=1);

namespace GymFlow;

use DateTimeImmutable;
use DateTimeZone;
use MongoDB\BSON\Decimal128;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use MongoDB\Database;
use RuntimeException;

final class Bootstrap
{
    public static function env(string $filename): void
    {
        if (!is_file($filename)) return;
        foreach (file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            if (getenv($key) === false) putenv($key . '=' . trim($val, " \t\n\r\0\x0B\"'"));
        }
    }
    public static function db(): Database
    {
        if (!class_exists(Client::class) || !extension_loaded('mongodb')) {
            throw new RuntimeException('Falta instalar Composer o la extension PHP mongodb.');
        }
        $uri = getenv('MONGODB_URI') ?: 'mongodb://127.0.0.1:27017';
        $name = getenv('MONGODB_DB') ?: 'gymflow';
        if (!preg_match('/^[a-zA-Z0-9_-]{1,60}$/', $name)) throw new RuntimeException('Nombre de base de datos invalido');
        return (new Client($uri, ['serverSelectionTimeoutMS' => 4500, 'connectTimeoutMS' => 4500]))->selectDatabase($name);
    }
    public static function now(): UTCDateTime
    {
        return new UTCDateTime((int) round(microtime(true) * 1000));
    }
    public static function localDate(string $s): UTCDateTime
    {
        $tz = new DateTimeZone('America/Costa_Rica');
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $s, $tz);
        if (!$d || $d->format('Y-m-d') !== $s) throw new DomainError('Fecha invalida (YYYY-MM-DD).');
        return new UTCDateTime($d->getTimestamp() * 1000);
    }
    public static function dateString(?UTCDateTime $date): string
    {
        return $date ? $date->toDateTime()->setTimezone(new DateTimeZone('America/Costa_Rica'))->format('Y-m-d') : '';
    }
    public static function addDays(UTCDateTime $start, int $days): UTCDateTime
    {
        $dt = $start->toDateTime()->setTimezone(new DateTimeZone('America/Costa_Rica'))->modify('+' . $days . ' days');
        return new UTCDateTime($dt->getTimestamp() * 1000);
    }
    public static function decimal(string $value): Decimal128
    {
        $value = trim($value);
        if (!preg_match('/^(?:0|[1-9][0-9]{0,11})(?:\.[0-9]{1,2})?$/', $value) || (float) $value <= 0) {
            throw new DomainError('El precio debe ser positivo y tener maximo dos decimales (ej. 15000.00).');
        }
        [$whole, $cents] = array_pad(explode('.', $value, 2), 2, '');
        return new Decimal128($whole . '.' . str_pad($cents, 2, '0'));
    }
    public static function id(string $id): ObjectId
    {
        if (!preg_match('/^[a-f\d]{24}$/i', $id)) throw new DomainError('Identificador invalido.');
        return new ObjectId($id);
    }
    public static function required(array $d, string $key, string $label, int $max = 200): string
    {
        $value = trim((string) ($d[$key] ?? ''));
        if ($value === '' || strlen($value) > $max) throw new DomainError("Revisá el campo $label (obligatorio, máximo $max caracteres).");
        return $value;
    }
    public static function optional(array $d, string $key, int $max = 500): string
    {
        $value = trim((string) ($d[$key] ?? ''));
        if (strlen($value) > $max) throw new DomainError("El campo $key supera $max caracteres.");
        return $value;
    }
    public static function email(string $value): string
    {
        $value = strtolower(trim($value));
        if (strlen($value) > 254 || !filter_var($value, FILTER_VALIDATE_EMAIL)) throw new DomainError('Correo electronico invalido.');
        return $value;
    }
}

final class DomainError extends \RuntimeException {}
