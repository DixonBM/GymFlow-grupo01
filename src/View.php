<?php
declare(strict_types=1);
namespace GymFlow;

use MongoDB\BSON\Decimal128;
use MongoDB\BSON\UTCDateTime;

function e(mixed $value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function money(mixed $m): string { return '₡' . number_format((float)(string)($m ?? 0), 2, ',', '.'); }
function shortDate(mixed $d): string { return $d instanceof UTCDateTime ? Bootstrap::dateString($d) : ''; }
function stateBadge(string $s): string {
    $name = match($s) { 'activo','registrado','vigente','pagada','operativa' => 'good', 'inactivo','vencida','anulado','cancelada','fuera_de_servicio' => 'bad', default => 'warn' };
    return '<span class="badge ' . $name . '">' . e(ucfirst(str_replace('_', ' ', $s))) . '</span>';
}
function field(string $label, string $key, mixed $value = '', string $type = 'text', string $other = ''): void {
    echo '<label class="field"><span>' . e($label) . '</span><input name="' . e($key) . '" type="' . e($type) . '" value="' . e($value) . '" ' . $other . '></label>';
}
function area(string $label, string $key, mixed $value = ''): void {
    echo '<label class="field"><span>' . e($label) . '</span><textarea name="' . e($key) . '" rows="3">' . e($value) . '</textarea></label>';
}
function choice(string $label, string $key, array $options, mixed $selected = null): void {
    echo '<label class="field"><span>' . e($label) . '</span><select name="' . e($key) . '">';
    foreach ($options as $v => $t) echo '<option value="' . e($v) . '" ' . ((string)$selected === (string)$v ? 'selected' : '') . '>' . e($t) . '</option>';
    echo '</select></label>';
}
function href(string $page, ?string $gym = null, array $more = []): string {
    return '?'.http_build_query(['page'=>$page] + ($gym ? ['gym'=>$gym] : []) + $more);
}
function hiddenAction(string $action, string $csrf, ?string $gym = null): void {
    echo '<input type="hidden" name="action" value="' . e($action) . '">';
    echo '<input type="hidden" name="csrf" value="' . e($csrf) . '">';
    echo '<input type="hidden" name="gym" value="' . e($gym) . '">';
}
function memberState(mixed $m, bool $paid): string {
    if ($m['cancelada']) return 'cancelada';
    $now = time(); $start = $m['fechaInicio']->toDateTime()->getTimestamp(); $end = $m['fechaFin']->toDateTime()->getTimestamp();
    if ($now < $start) return 'futura';
    if ($now >= $end) return 'vencida';
    if (!$paid) return 'pendiente de pago';
    if ($end - $now <= 7 * 86400) return 'por vencer';
    return 'vigente';
}
