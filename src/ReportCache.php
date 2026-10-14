<?php
declare(strict_types=1);
namespace GymFlow;

/** Redis es una cache temporal: MongoDB es siempre la fuente de verdad. */
final class ReportCache
{
    private ?object $client;
    private string $lastEvent = 'sin consulta';
    private int $ttl;
    private string $prefix;

    public function __construct(?object $client, int $ttl = 60, string $prefix = 'gymflow:s1:')
    {
        $this->client = $client;
        $this->ttl = max(1, min(3600, $ttl));
        $this->prefix = $prefix;
    }

    public static function fromEnvironment(): self
    {
        $url = getenv('REDIS_URL') ?: 'redis://127.0.0.1:6379';
        $ttl = (int)(getenv('CACHE_TTL_SECONDS') ?: 60);
        if (!class_exists(\Predis\Client::class)) {
            return new self(null, $ttl);
        }
        // Predis no requiere la extension PHP redis.
        $client = new \Predis\Client($url, [
            'parameters' => ['timeout' => 0.5, 'read_write_timeout' => 0.5],
        ]);
        return new self($client, $ttl);
    }

    private function key(string $gymId): string
    {
        if (!preg_match('/^[a-f0-9]{24}$/i', $gymId)) {
            throw new DomainError('ID de gimnasio incorrecto para la cache.');
        }
        return $this->prefix . 'dashboard:' . strtolower($gymId);
    }

    public function get(string $gymId): ?array
    {
        if (!$this->client) { $this->lastEvent = 'no disponible'; return null; }
        try {
            $json = $this->client->get($this->key($gymId));
            if ($json === null) { $this->lastEvent = 'MISS'; return null; }
            $data = json_decode((string)$json, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data) || !isset($data['sociosRegistrados'], $data['ingresos'])) {
                $this->lastEvent = 'MISS';
                return null;
            }
            $this->lastEvent = 'HIT';
            return $data;
        } catch (\Throwable $e) {
            error_log('Redis (lectura): ' . $e->getMessage());
            $this->lastEvent = 'no disponible';
            return null;
        }
    }

    public function put(string $gymId, array $report): void
    {
        if (!$this->client) return;
        try {
            $this->client->setex($this->key($gymId), $this->ttl, json_encode($report, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION));
        } catch (\Throwable $e) {
            error_log('Redis (escritura): ' . $e->getMessage());
            $this->lastEvent = 'no disponible';
        }
    }

    public function invalidate(string $gymId): void
    {
        if (!$this->client) return;
        try {
            $this->client->del([$this->key($gymId)]);
        } catch (\Throwable $e) {
            // Una escritura confirmada en MongoDB nunca se revierte por falla de cache.
            error_log('Redis (invalidacion): ' . $e->getMessage());
            $this->lastEvent = 'no disponible';
        }
    }

    public function health(): bool
    {
        if (!$this->client) return false;
        try {
            return strtoupper((string)$this->client->ping()) === 'PONG';
        } catch (\Throwable $e) {
            error_log('Redis (PING): ' . $e->getMessage());
            return false;
        }
    }

    public function lastEvent(): string { return $this->lastEvent; }
    public function ttl(): int { return $this->ttl; }
}
