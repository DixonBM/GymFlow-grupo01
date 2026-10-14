# Fuente de datos · GymFlow

Para cumplir el requerimiento de **fuente de datos identificada**, se eligió un **generador sintético reproducible**, incluido en `scripts/setup.php` con la opción `--seed`.

Datos creados:

- 2 gimnasios: **Hercules Demo** y **Titan Demo**.
- 1 superadministrador y 1 administrador para cada gimnasio.
- 5 socios por gimnasio; identificaciones ficticias `SYN101` etc.
- 2 planes por gimnasio (Mensual y Trimestral) y 5 membresías de ejemplo por gimnasio.
- Membresías vigentes pagadas, futuras, vencidas, canceladas y con pagos anulados.

No se utilizan identidades ni números reales. El script es idempotente: al repetirlo evita duplicar los registros generados y no cambia contraseñas existentes. Genera claves aleatorias al crear cada cuenta y las muestra únicamente por terminal.

**Comando:** `docker compose exec app php scripts/setup.php --seed`

**Validación:** `docker compose exec app php scripts/smoke.php`
