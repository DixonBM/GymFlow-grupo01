# Mediciones de rendimiento S3 - GymFlow

## 1. Información de la ejecución

- Fecha: 2026-10-30 14:46:09 -06:00
- Entorno: local
- Sistema: Linux 6.18.54.1-microsoft-standard-WSL2
- PHP: 8.3.36
- MongoDB Server: 7.0.45
- MongoDB PHP Library: 2.5.0
- Predis: v2.4.1
- Base de datos: gymflow
- Redis: Conectado (PONG)
- TTL Redis: 60 segundos
- Gimnasio: Hercules Demo
- ID del gimnasio: `6ad183feffc32261e1096e52`
- Ejecuciones por operación: 30

## 2. Cantidad de datos utilizados

| Colección | Total en MongoDB | Devueltos por records() |
|---|---:|---:|
| socios | 6 | 6 |
| planes | 2 | 2 |
| membresias | 5 | 5 |
| pagos | 3 | 3 |

La consulta records() utiliza un límite de 500 documentos.
El reporte del dashboard procesa las colecciones completas asociadas al gimnasio.

## 3. Metodología

- Se realizaron mediciones mediante `hrtime(true)`.
- Se ejecutaron 30 muestras por operación.
- Se alternó el orden de las mediciones del dashboard.
- Se verificó que los resultados de MongoDB y Redis coincidieran.
- Los tiempos se expresan en milisegundos.
- Las consultas de socios y membresías se realizaron directamente en MongoDB.
- El dashboard reproduce las consultas de socios, planes, membresías, pagos e indicadores.

## 4. Resultados estadísticos

| Operación | Muestras | Mínimo (ms) | Media (ms) | Mediana (ms) | P95 (ms) | Máximo (ms) |
|---|---:|---:|---:|---:|---:|---:|
| Dashboard completo - MongoDB directo | 30 | 1.955 | 2.264 | 2.233 | 2.605 | 3.463 |
| Dashboard completo - Redis HIT | 30 | 1.185 | 1.431 | 1.420 | 1.797 | 1.898 |
| Listado de socios | 30 | 0.265 | 0.331 | 0.323 | 0.407 | 0.424 |
| Consulta de membresias | 30 | 0.271 | 0.331 | 0.321 | 0.384 | 0.416 |

## 5. Comparación de Redis

Redis HIT redujo la mediana del dashboard en 36.40%.

Redis almacena temporalmente los indicadores del dashboard.
Las consultas de listados continúan ejecutándose en MongoDB.

## 6. Tiempos individuales

| Operación | Ejecución | Tiempo (ms) | Registros devueltos |
|---|---:|---:|---|
| Dashboard completo - MongoDB directo | 1 | 2.605 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - Redis HIT | 1 | 1.679 | socios=6; planes=2; membresias=5; pagos=3 |
| Listado de socios | 1 | 0.392 | 6 |
| Consulta de membresias | 1 | 0.416 | 5 |
| Dashboard completo - Redis HIT | 2 | 1.585 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - MongoDB directo | 2 | 2.538 | socios=6; planes=2; membresias=5; pagos=3 |
| Consulta de membresias | 2 | 0.360 | 5 |
| Listado de socios | 2 | 0.354 | 6 |
| Dashboard completo - MongoDB directo | 3 | 2.415 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - Redis HIT | 3 | 1.898 | socios=6; planes=2; membresias=5; pagos=3 |
| Listado de socios | 3 | 0.378 | 6 |
| Consulta de membresias | 3 | 0.333 | 5 |
| Dashboard completo - Redis HIT | 4 | 1.455 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - MongoDB directo | 4 | 2.312 | socios=6; planes=2; membresias=5; pagos=3 |
| Consulta de membresias | 4 | 0.320 | 5 |
| Listado de socios | 4 | 0.329 | 6 |
| Dashboard completo - MongoDB directo | 5 | 2.208 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - Redis HIT | 5 | 1.398 | socios=6; planes=2; membresias=5; pagos=3 |
| Listado de socios | 5 | 0.323 | 6 |
| Consulta de membresias | 5 | 0.318 | 5 |
| Dashboard completo - Redis HIT | 6 | 1.419 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - MongoDB directo | 6 | 2.238 | socios=6; planes=2; membresias=5; pagos=3 |
| Consulta de membresias | 6 | 0.370 | 5 |
| Listado de socios | 6 | 0.407 | 6 |
| Dashboard completo - MongoDB directo | 7 | 2.234 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - Redis HIT | 7 | 1.424 | socios=6; planes=2; membresias=5; pagos=3 |
| Listado de socios | 7 | 0.335 | 6 |
| Consulta de membresias | 7 | 0.321 | 5 |
| Dashboard completo - Redis HIT | 8 | 1.415 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - MongoDB directo | 8 | 2.261 | socios=6; planes=2; membresias=5; pagos=3 |
| Consulta de membresias | 8 | 0.321 | 5 |
| Listado de socios | 8 | 0.315 | 6 |
| Dashboard completo - MongoDB directo | 9 | 2.177 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - Redis HIT | 9 | 1.517 | socios=6; planes=2; membresias=5; pagos=3 |
| Listado de socios | 9 | 0.323 | 6 |
| Consulta de membresias | 9 | 0.318 | 5 |
| Dashboard completo - Redis HIT | 10 | 1.448 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - MongoDB directo | 10 | 2.340 | socios=6; planes=2; membresias=5; pagos=3 |
| Consulta de membresias | 10 | 0.327 | 5 |
| Listado de socios | 10 | 0.291 | 6 |
| Dashboard completo - MongoDB directo | 11 | 2.284 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - Redis HIT | 11 | 1.471 | socios=6; planes=2; membresias=5; pagos=3 |
| Listado de socios | 11 | 0.294 | 6 |
| Consulta de membresias | 11 | 0.271 | 5 |
| Dashboard completo - Redis HIT | 12 | 1.329 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - MongoDB directo | 12 | 2.055 | socios=6; planes=2; membresias=5; pagos=3 |
| Consulta de membresias | 12 | 0.277 | 5 |
| Listado de socios | 12 | 0.265 | 6 |
| Dashboard completo - MongoDB directo | 13 | 2.053 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - Redis HIT | 13 | 1.185 | socios=6; planes=2; membresias=5; pagos=3 |
| Listado de socios | 13 | 0.306 | 6 |
| Consulta de membresias | 13 | 0.369 | 5 |
| Dashboard completo - Redis HIT | 14 | 1.499 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - MongoDB directo | 14 | 2.130 | socios=6; planes=2; membresias=5; pagos=3 |
| Consulta de membresias | 14 | 0.299 | 5 |
| Listado de socios | 14 | 0.280 | 6 |
| Dashboard completo - MongoDB directo | 15 | 2.000 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - Redis HIT | 15 | 1.272 | socios=6; planes=2; membresias=5; pagos=3 |
| Listado de socios | 15 | 0.305 | 6 |
| Consulta de membresias | 15 | 0.330 | 5 |
| Dashboard completo - Redis HIT | 16 | 1.273 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - MongoDB directo | 16 | 1.955 | socios=6; planes=2; membresias=5; pagos=3 |
| Consulta de membresias | 16 | 0.287 | 5 |
| Listado de socios | 16 | 0.279 | 6 |
| Dashboard completo - MongoDB directo | 17 | 1.984 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - Redis HIT | 17 | 1.421 | socios=6; planes=2; membresias=5; pagos=3 |
| Listado de socios | 17 | 0.322 | 6 |
| Consulta de membresias | 17 | 0.346 | 5 |
| Dashboard completo - Redis HIT | 18 | 1.465 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - MongoDB directo | 18 | 2.375 | socios=6; planes=2; membresias=5; pagos=3 |
| Consulta de membresias | 18 | 0.316 | 5 |
| Listado de socios | 18 | 0.314 | 6 |
| Dashboard completo - MongoDB directo | 19 | 1.970 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - Redis HIT | 19 | 1.353 | socios=6; planes=2; membresias=5; pagos=3 |
| Listado de socios | 19 | 0.324 | 6 |
| Consulta de membresias | 19 | 0.336 | 5 |
| Dashboard completo - Redis HIT | 20 | 1.260 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - MongoDB directo | 20 | 2.027 | socios=6; planes=2; membresias=5; pagos=3 |
| Consulta de membresias | 20 | 0.299 | 5 |
| Listado de socios | 20 | 0.286 | 6 |
| Dashboard completo - MongoDB directo | 21 | 2.452 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - Redis HIT | 21 | 1.505 | socios=6; planes=2; membresias=5; pagos=3 |
| Listado de socios | 21 | 0.314 | 6 |
| Consulta de membresias | 21 | 0.303 | 5 |
| Dashboard completo - Redis HIT | 22 | 1.254 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - MongoDB directo | 22 | 2.055 | socios=6; planes=2; membresias=5; pagos=3 |
| Consulta de membresias | 22 | 0.307 | 5 |
| Listado de socios | 22 | 0.303 | 6 |
| Dashboard completo - MongoDB directo | 23 | 2.004 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - Redis HIT | 23 | 1.314 | socios=6; planes=2; membresias=5; pagos=3 |
| Listado de socios | 23 | 0.356 | 6 |
| Consulta de membresias | 23 | 0.321 | 5 |
| Dashboard completo - Redis HIT | 24 | 1.326 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - MongoDB directo | 24 | 2.064 | socios=6; planes=2; membresias=5; pagos=3 |
| Consulta de membresias | 24 | 0.379 | 5 |
| Listado de socios | 24 | 0.391 | 6 |
| Dashboard completo - MongoDB directo | 25 | 2.247 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - Redis HIT | 25 | 1.274 | socios=6; planes=2; membresias=5; pagos=3 |
| Listado de socios | 25 | 0.306 | 6 |
| Consulta de membresias | 25 | 0.318 | 5 |
| Dashboard completo - Redis HIT | 26 | 1.357 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - MongoDB directo | 26 | 2.232 | socios=6; planes=2; membresias=5; pagos=3 |
| Consulta de membresias | 26 | 0.307 | 5 |
| Listado de socios | 26 | 0.311 | 6 |
| Dashboard completo - MongoDB directo | 27 | 2.223 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - Redis HIT | 27 | 1.441 | socios=6; planes=2; membresias=5; pagos=3 |
| Listado de socios | 27 | 0.352 | 6 |
| Consulta de membresias | 27 | 0.328 | 5 |
| Dashboard completo - Redis HIT | 28 | 1.279 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - MongoDB directo | 28 | 3.463 | socios=6; planes=2; membresias=5; pagos=3 |
| Consulta de membresias | 28 | 0.383 | 5 |
| Listado de socios | 28 | 0.347 | 6 |
| Dashboard completo - MongoDB directo | 29 | 2.423 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - Redis HIT | 29 | 1.607 | socios=6; planes=2; membresias=5; pagos=3 |
| Listado de socios | 29 | 0.400 | 6 |
| Consulta de membresias | 29 | 0.384 | 5 |
| Dashboard completo - Redis HIT | 30 | 1.797 | socios=6; planes=2; membresias=5; pagos=3 |
| Dashboard completo - MongoDB directo | 30 | 2.595 | socios=6; planes=2; membresias=5; pagos=3 |
| Consulta de membresias | 30 | 0.369 | 5 |
| Listado de socios | 30 | 0.424 | 6 |

## 7. Análisis de resultados

La mediana representa el valor central de las ejecuciones ordenadas.
Para una cantidad par de muestras se promedian los dos valores centrales.

El P95 se calcula mediante el método de rango más cercano.

Redis HIT redujo la mediana del dashboard en 36.40%.

Las mediciones corresponden a operaciones de la capa de servicios PHP.
No representan el tiempo completo de carga del navegador ni el renderizado HTML.
Los resultados corresponden al volumen de datos registrado en este entorno.

## 8. Evidencias y trazabilidad

- Script: `scripts/benchmark-s3.php`.
- Benchmark anterior: `scripts/cache-benchmark.php`.
- Commit: pendiente de registrar.
- Evidencias adicionales: pendientes de incorporar.

## 9. Conclusión

El benchmark permite comparar el comportamiento del dashboard
con Redis HIT y con consultas directas a MongoDB, además de
medir independientemente los listados de socios y membresías.

No se modificaron ni eliminaron datos durante las pruebas.

