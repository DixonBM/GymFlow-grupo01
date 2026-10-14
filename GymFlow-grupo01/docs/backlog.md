# Backlog de GymFlow-grupo01 · Issues para GitHub

Crear **Issues → Milestones → New milestone** llamado `Seguimiento 1 - Semana 13`, asignar cada Issue a un miembro real y vincularlo al hito. Después de validar S1 crear el tag `v0.1.0`.

| # | Nombre recomendado del Issue | Criterio de aceptación verificable | Hito |
| --- | --- | --- | --- |
| 1 | `chore: publicar repositorio, MIT y gitignore` | Repo público, `.gitignore`, licencia y cero secretos | S1 |
| 2 | `docs: problema, usuarios, alcance MVP y README` | README presenta qué entra y qué no | S1 |
| 3 | `docs: arquitectura MongoDB y Redis y modelo de datos v1` | Diagrama explica persistencia, caché, fallos y seis colecciones | S1 |
| 4 | `feat: entorno PHP, MongoDB y Redis con Docker` | `docker compose up --build -d` inicia tres contenedores saludables | S1 |
| 5 | `data: ejecutar carga sintética e índices MongoDB` | Dos gimnasios, registros de prueba, índices comprobados | S1 |
| 6 | `feat: demostrar Redis HIT MISS e invalidación` | `scripts/smoke.php` y pantalla de conexión a ambos motores | S1 |
| 7 | `test: flujo socio membresía pago y separación de gimnasios` | Datos persisten tras recarga; un gimnasio no ve al otro | S1 |
| 8 | `docs: bitácora evidencias y tag v0.1.0` | Capturas reales, bitácora actualizada, tag publicado | S1 |
| 9 | `test: medir índices MongoDB y rendimiento Redis` | Comparar consultas con y sin índice; resultados reproducibles | S2 |
| 10 | `feat: endurecer concurrencia de renovaciones` | No crear membresías superpuestas aun con operaciones concurrentes | S2 |
| 11 | `release: QA final, demo y v1.0.0` | Instalación desde cero, documentación final, release | S3 |

## Reparto sugerido (editar con nombres/cuentas reales)

- Integrante A: Issues 1, 2, 8 (GitHub y documentación).
- Integrante B: Issues 3, 5, 9 (datos, MongoDB, medición).
- Integrante C: Issues 4, 6, 7 (PHP, Redis, integración).

La presencia de una tarea en este archivo **no crea un Issue real**. Cada integrante debe aceptar la invitación y hacer por lo menos un commit **propio** en GitHub.
