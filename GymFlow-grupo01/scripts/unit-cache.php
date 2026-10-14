<?php
declare(strict_types=1);
/** Prueba unitaria sin MongoDB ni Redis instalados. */
require dirname(__DIR__) . '/src/Bootstrap.php';
require dirname(__DIR__) . '/src/ReportCache.php';
use GymFlow\ReportCache;
class DemoRedis {
    public array $values=[];
    public function ping(): string {return 'PONG';}
    public function get($key): ?string {return $this->values[$key] ?? null;}
    public function setex($key,$ttl,$value): string {$this->values[$key]=$value;return 'OK';}
    public function del($keys): int {$count=0;foreach($keys as $key){if(isset($this->values[$key])){$count++;unset($this->values[$key]);}}return $count;}
}
class FailingRedis {
    public function ping(): never {throw new RuntimeException('sin conexión');}
    public function get($key): never {throw new RuntimeException('sin conexión');}
    public function setex($key,$ttl,$value): never {throw new RuntimeException('sin conexión');}
    public function del($keys): never {throw new RuntimeException('sin conexión');}
}
function check(bool $condition, string $label): void { if (!$condition) throw new RuntimeException($label); }
$id = 'aaaaaaaaaaaaaaaaaaaaaaaa';
$client = new DemoRedis();
$cache = new ReportCache($client, 60);
check($cache->health(), 'PING de fake Redis');
check($cache->get($id) === null && $cache->lastEvent() === 'MISS', 'MISS');
$report = ['sociosRegistrados'=>2,'ingresos'=>25000.0];
$cache->put($id,$report);
check($cache->get($id)===$report && $cache->lastEvent()==='HIT', 'HIT');
$cache->invalidate($id);
check($cache->get($id)===null, 'invalidacion');
$down = new ReportCache(new FailingRedis());
check(!$down->health() && $down->get($id)===null, 'fallo no fatal');
$down->put($id,$report); $down->invalidate($id);
echo "OK: Redis simulado MISS / HIT / invalida / falla sin lanzar excepciones.\n";
