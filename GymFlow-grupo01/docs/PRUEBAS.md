# Plan de pruebas · Seguimiento 1 (MongoDB + Redis)

> Marcar como **aprobada** una prueba únicamente tras ejecutarla en el equipo o servidor del grupo. Las capturas no se incluyen de forma ficticia.

## Prueba de inicio (dos motores)

1. `docker compose up --build -d` y `docker compose ps`: PHP, MongoDB y Redis deben reportarse activos.
2. `docker compose exec app php scripts/setup.php --seed`: crear seis colecciones, índices, dos gimnasios y usuarios/contraseñas sintéticos. Guardar contraseñas fuera de Git.
3. `docker compose exec app php scripts/smoke.php`: debe indicar `MongoDB PING: OK`, `Redis PING: OK` y un ciclo `MISS` → `HIT` para cada gimnasio. Si falla, no presentar como aprobada.
4. `http://localhost:8000/?page=conexion` después del login: deben aparecer los dos motores conectados.
5. `http://localhost:8000/?page=dashboard`: dos accesos consecutivos deben mostrar `MISS` (tras invalidación/expiración) y `HIT` (dentro del TTL); el estado aparece en la pantalla.

## Persistencia y aislamiento

6. Iniciar sesión como `hercules@gymflow.test`, registrar un socio ficticio `SYN999`, guardarlo y verificarlo en `gymflow.socios` con Compass, Atlas o `mongosh` dentro de Docker.
7. Crear un plan, contratar una membresía y registrar un pago manual. Verificar los tres documentos persistidos y recargar el navegador.
8. Volver al panel y comprobar que los indicadores cambiaron, sin esperar 60 segundos (invalidados por escritura).
9. Entrar como `titan@gymflow.test`: `SYN999` no debe ser visible ni editable en Titan.
10. Como superadmin, alternar Hércules y Titan; los resultados y el caché deben permanecer separados.
11. Intentar guardar una identificación duplicada y registrar dos pagos efectivos en una membresía; el sistema debe rechazar ambas operaciones.
12. Anular un pago con motivo, verificar historial y que el resumen excluye ese pago.

## Fallos y recuperación

13. Con el navegador conectado y Mongo activo, ejecutar `docker compose stop redis`; recargar dashboard: el resumen debe seguir funcionando con MongoDB. `Conexión BD` mostrará Redis como no disponible. `docker compose start redis` para recuperarlo.
14. Ejecutar `docker compose stop mongo`; intentar guardar un socio: debe verse error y no confirmación falsa. `docker compose start mongo` para recuperar MongoDB.
15. Ejecutar `docker compose exec app php scripts/cache-benchmark.php` con los motores activos. Registrar tiempos reales de consultas MongoDB vs HIT Redis, tamaño de muestra, equipo y fecha. **30 mediciones en dataset sintético no prueban escalabilidad de producción.**

## Evidencias de GitHub

- Guardar capturas auténticas sin claves ni datos personales en `docs/evidencias/` y escribir su significado en `docs/seguimientos/S1.md`.
- Ejecutar `php scripts/unit-cache.php` para prueba local de la lógica de caché sin infraestructura (prueba simulada, **no reemplaza** el PING real).
- GitHub Actions verifica sintaxis PHP en cada push; las pruebas de integración deben ejecutarse en el equipo del grupo.
