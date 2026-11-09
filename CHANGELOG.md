# Historial de cambios de GymFlow

## v0.9.0 — Versión candidata de S4

### Agregado

- Renovación de membresía y registro de su pago manual en una sola transacción de MongoDB. La nueva membresía queda vinculada a la anterior. [PR #23][pr23]
- Script de medición del tablero con y sin caché Redis, del listado de socios y de la consulta de membresías. [Mediciones S3](docs/mediciones-S3.md)
- Evidencias de la prueba de caída y recuperación de Redis en Docker local. [Evidencias S3](docs/evidencias/S3/)
- Ficha del proyecto para el portal del curso y bitácora de entrega. [Ficha](portal.yml) · [Bitácora S4](docs/seguimientos/S4.md)

### Cambiado

- El entorno Docker configura MongoDB como conjunto de réplicas `rs0` para admitir transacciones. [PR #23][pr23]
- La bitácora S3 incorpora los resultados de las pruebas funcionales, la recuperación de Redis y las mediciones. [Commit `27c39b8`][s3-cierre]

### Corregido

- El flujo de renovación protege contra reenvíos del formulario y rechaza períodos superpuestos. La caché se invalida al confirmar la transacción. [PR #23][pr23]

## v0.3.0 — Seguimiento 3

### Agregado

- Inventario de máquinas por gimnasio e invitaciones por correo para activar cuentas administrativas. [PR #22][pr22]
- Configuración local de Mailpit para probar el envío de invitaciones. [PR #22][pr22]
- Matriz y capturas de pruebas funcionales de S3. [Commit `b7ab614`][s3]

### Cambiado

- Se incorporó `composer.lock` y la imagen Docker instala las dependencias fijadas para el despliegue. [Commit `3ab95b2`][composer]

## v0.2.0 — Seguimiento 2

### Agregado

- Pruebas y evidencias de socios, planes, membresías, pagos y administración de gimnasios. [Commit `1ea963b`][s2-pruebas]
- Bitácora S2 y automatización del despliegue controlado a Virtualmin. [Commit `1e24073`][s2] · [Commit `8de8c05`][virtualmin]

### Cambiado

- Se actualizó la interfaz con el logotipo de GymFlow y ajustes de estilo. [Commit `f6264ef`][estilos]

### Corregido

- Se reorganizó la estructura de la pantalla de inicio de sesión. [Commit `4f94a23`][login]

## v0.1.0 — Seguimiento 1

### Agregado

- Primera versión del sistema web multigimnasio con PHP, autenticación y gestión de gimnasios, usuarios, socios, planes, membresías y pagos manuales. [Commit `38e9b98`][s1-base]
- Persistencia en MongoDB, caché Redis para el tablero y entorno local con Docker Compose, datos sintéticos y scripts de verificación. [Commit `38e9b98`][s1-base]
- Documentación inicial de arquitectura, modelo de datos y bitácora con evidencias. [Commit `67a5fb1`][s1]

[pr23]: https://github.com/DixonBM/GymFlow-grupo01/pull/23
[s3-cierre]: https://github.com/DixonBM/GymFlow-grupo01/commit/27c39b8
[pr22]: https://github.com/DixonBM/GymFlow-grupo01/pull/22
[s3]: https://github.com/DixonBM/GymFlow-grupo01/commit/b7ab614
[composer]: https://github.com/DixonBM/GymFlow-grupo01/commit/3ab95b2
[s2-pruebas]: https://github.com/DixonBM/GymFlow-grupo01/commit/1ea963b
[s2]: https://github.com/DixonBM/GymFlow-grupo01/commit/1e24073
[virtualmin]: https://github.com/DixonBM/GymFlow-grupo01/commit/8de8c05
[estilos]: https://github.com/DixonBM/GymFlow-grupo01/commit/f6264ef
[login]: https://github.com/DixonBM/GymFlow-grupo01/commit/4f94a23
[s1-base]: https://github.com/DixonBM/GymFlow-grupo01/commit/38e9b98
[s1]: https://github.com/DixonBM/GymFlow-grupo01/commit/67a5fb1
