# GymFlow · Gestión de gimnasios · Grupo 01

**Proyecto Final Open Source · Tópicos Avanzados de Bases de Datos · UNA**  
**Seguimiento 1 · Semana 13 · Versión propuesta `v0.1.0`**

**Repositorio previsto:** https://github.com/DixonBM/GymFlow-grupo01

## Problema, usuarios y alcance

Gimnasios pequeños suelen gestionar socios, planes y cobros en registros dispersos. GymFlow centraliza los datos para distintos gimnasios **sin mezclar información entre empresas**. Es un MVP académico abierto que puede ampliarse en futuras etapas.

- **Superadministrador:** administra los gimnasios y sus cuentas administrativas.
- **Administrador de gimnasio:** gestiona únicamente sus socios, planes, membresías y pagos manuales.
- **Socio:** cliente del gimnasio, **sin cuenta propia** en esta versión.

**Incluye en el MVP:** login/logout, gimnasios, usuarios, socios, planes, membresías, renovaciones, cancelaciones, pagos manuales y anulaciones, dashboard y estado de conexión de los dos motores. Conserva historial, crea datos sintéticos y define índices MongoDB.

**Fuera del MVP:** máquinas, rutinas, QR, agenda, pagos bancarios automáticos, facturación electrónica, app móvil. No es software listo para producción sin auditoría y pruebas adicionales.

## Tecnologías y responsabilidad de los datos

| Componente | Tecnología | Uso real |
| --- | --- | --- |
| Interfaz web y backend | **PHP 8.3 + HTML/CSS** | Vistas, sesión, formularios, roles y reglas de negocio (sin React ni Node.js) |
| Persistencia | **MongoDB 7 / Atlas** | Seis colecciones: `gimnasios`, `usuarios`, `socios`, `planes`, `membresias`, `pagos` |
| Segundo motor | **Redis 7** | Caché **temporal** de indicadores del dashboard por gimnasio, TTL de 60 segundos e invalidación tras escrituras |
| Entorno local | **Docker Compose** | Inicia PHP/Apache, MongoDB y Redis juntos |

**SQLite no se usa:** el profesor confirmó que se trabajará con MongoDB + Redis. No se guardan datos de negocios exclusivamente en Redis; si Redis falla, el dashboard consulta MongoDB. El estado de caché `HIT`/`MISS` se muestra en el dashboard para demostrar integración.

Arquitectura: [diagrama de componentes y responsabilidades](docs/arquitectura/arquitectura-v1.md). Modelo persistente: [modelo de datos v1](docs/modelo-datos-v1.md).

## Instalación local en Windows + VS Code (recomendada)

**Requisitos:** Docker Desktop con Docker Compose funcionando, Git y VS Code. Es necesario acceso a Internet la primera vez para obtener imágenes y dependencias. **No necesitás instalar PHP, Composer ni Node.js en Windows para esta opción.**

1. Extraé este ZIP, abrí la carpeta `GymFlow-grupo01` en VS Code y abrí **Terminal → New Terminal**.
2. **No creés un archivo `.env` para la configuración predeterminada de Docker**; Compose usará automáticamente MongoDB y Redis internos. Ejecutá:

   ```bash
   docker compose up --build -d
   ```

3. Inicializá los seis esquemas con índices y generá datos ficticios reproducibles:

   ```bash
   docker compose exec app php scripts/setup.php --seed
   ```

   El comando imprime **contraseñas aleatorias** de demostración. Guardalas en privado; **no las publiques ni subás capturas que las contengan**. Si ejecutás el seed dos veces, no vuelve a mostrar las claves de cuentas preexistentes.

4. Validá que ambos motores estén conectados y que Redis haga `MISS` y después `HIT`:

   ```bash
   docker compose exec app php scripts/smoke.php
   ```

5. Abrí **http://localhost:8000** e ingresá con el correo que corresponda y la contraseña del paso 3:

   | Usuario demo | Permisos |
   | --- | --- |
   | `superadmin@gymflow.test` | Todos los gimnasios |
   | `hercules@gymflow.test` | Solo Hércules Demo |
   | `titan@gymflow.test` | Solo Titan Demo |

6. Abrí **Conexión BD** y comprobá que muestre **MongoDB conectado** y **Redis conectado**. Luego visitá **Resumen** dos veces para observar los indicadores HIT/MISS.
7. Registrá un socio → creá una membresía para un plan → registrá el pago → recargá el sitio → verificá que continúa guardado. Los registros definitivos están en MongoDB, no en Redis.

**Opcional — medición comparativa:**

```bash
docker compose exec app php scripts/cache-benchmark.php
```

Este script compara 30 lecturas del resumen directo de MongoDB con 30 lecturas de caché HIT Redis y muestra los tiempos medidos **en tu computadora**. Anotá el resultado real en la [bitácora S1](docs/seguimientos/S1.md); el ZIP **no incluye resultados inventados**.

**Detener contenedores sin borrar MongoDB:** `docker compose down`.  
**Eliminar intencionalmente toda la información de prueba:** `docker compose down -v` (**irreversible** para el volumen local; solo hacerlo si es descartable).

### Diagnóstico

```bash
docker compose ps
docker compose logs --tail=100 app
docker compose logs --tail=100 mongo
docker compose logs --tail=100 redis
```

- Redis apagado: `docker compose stop redis`; el panel debería continuar leyendo MongoDB. Reiniciá con `docker compose start redis`.
- MongoDB apagado: `docker compose stop mongo`; **no** se debe confirmar ninguna operación fallida. Reiniciá con `docker compose start mongo`.
- Si `localhost:8000` no abre, verificá que Docker Desktop esté iniciado y que el puerto 8000 esté libre.
- Para test aislado de la lógica de caché (sin servicios externos): `docker compose exec app php scripts/unit-cache.php`.

## Instalación manual sin Docker (opcional)

Requiere PHP 8.2+, extensión `mongodb` 2.x, Composer 2, MongoDB y un servidor Redis accesible. Predis es una biblioteca PHP: **no requiere extensión `phpredis`**.

```bash
cp .env.example .env
composer install
php scripts/setup.php --seed
php scripts/smoke.php
php -S 127.0.0.1:8000 -t public public/router.php
```

En PowerShell, reemplazá `cp` por `Copy-Item`. Ajustá `MONGODB_URI` y `REDIS_URL` **dentro de `.env` privado** para apuntar a tus servicios. El puerto de Redis no debe quedar expuesto públicamente.

## Conexión con Atlas, si el grupo decide usarlo

En MongoDB Atlas creá un usuario DB de privilegios mínimos, un clúster en AWS Oregon (según el diseño) y autorizá solamente la IP de salida que conectará; no publiqués `0.0.0.0/0`. Dentro del `.env` de Docker establecé:

```dotenv
MONGODB_URI=mongodb+srv://USUARIO:CLAVE_URL_ENCODED@CLUSTER/gymflow?retryWrites=true&w=majority
MONGODB_DB=gymflow
REDIS_URL=redis://redis:6379
CACHE_TTL_SECONDS=60
```

**Es un ejemplo con marcadores: no hay credenciales reales incluidas.** No lo publiques. Reiniciá los contenedores y repetí `setup.php --seed`, `smoke.php` contra la base Atlas antes de dar la conexión por comprobada. La URI de `grupo1.crnube.org/clientes2/atlas/gymflow.php` es una dirección web, **no** una cadena de conexión a Atlas.

## Estructura de carpetas

```text
GymFlow-grupo01/
├── .github/ISSUE_TEMPLATE/         Plantilla de Issues
├── .github/workflows/              PHP lint + test unitario de caché
├── src/                            Bootstrap, GymService, ReportCache, View
├── public/                         index.php, router.php y CSS
├── scripts/                        Setup, smoke, benchmark y test unitario
├── docs/
│   ├── arquitectura/arquitectura-v1.md
│   ├── modelo-datos-v1.md
│   ├── backlog.md
│   ├── PRUEBAS.md
│   ├── GITHUB-PASO-A-PASO.md
│   ├── seguimientos/S1.md
│   └── evidencias/                 Lugar para capturas reales
├── data/                            Fuente y descripción del seed sintético
├── compose.yaml                    PHP + MongoDB + Redis
├── Dockerfile                      Imagen PHP con extensión MongoDB
├── composer.json                   MongoDB PHP + Predis
├── .env.example                    Solo variables de ejemplo
├── .gitignore                      Exclusión de secretos y vendor
└── LICENSE                         MIT
```

## Verificación del Seguimiento 1 en GitHub

- [ ] Repositorio **público** con licencia MIT, `.gitignore`, estructura `src/`, `docs/`, `data/`, `scripts/`.
- [ ] Todos los integrantes invitados, con **al menos un commit auténtico cada uno**.
- [ ] README con problema, usuarios, MVP, instalación y arquitectura.
- [ ] Diagrama de arquitectura que justifique **MongoDB y Redis**, y modelo de datos v1.
- [ ] GitHub Issues asignados a personas + milestone `Seguimiento 1 - Semana 13`.
- [ ] Fuente de datos sintéticos identificada (`scripts/setup.php --seed`).
- [ ] Hola mundo: **PING real a MongoDB y Redis** más evidencia de HIT/MISS.
- [ ] Tag `v0.1.0` publicado y bitácora `docs/seguimientos/S1.md` rellenada tras probar.

**Guía específica para el repo que ya creaste:** [docs/GITHUB-PASO-A-PASO.md](docs/GITHUB-PASO-A-PASO.md).  
**Casos de prueba:** [docs/PRUEBAS.md](docs/PRUEBAS.md).

## Límites y desarrollo posterior

Este repositorio prepara el **Seguimiento 1**, no significa que los seguimientos 2 y 3 ya estén completados. Para el siguiente avance corresponde aportar pruebas de integración, manejo de fallos y medición; para la entrega final, calidad, instalación reproducible y release `v1.0.0`. La concurrencia extrema de renovaciones y el despliegue definitivo en Virtualmin no han sido verificados.

**Licencia:** MIT. Antes de usar con clientes reales se necesitan HTTPS, controles de seguridad revisados, copias de respaldo y pruebas más exhaustivas.
