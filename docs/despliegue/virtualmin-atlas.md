# GymFlow — Despliegue en Webmin/Virtualmin con MongoDB Atlas y Redis

**Proyecto:** GymFlow — Grupo 01  
**Curso:** Tópicos Avanzados de Bases de Datos — Universidad Nacional de Costa Rica  
**Fecha de documentación:** 17 de octubre de 2026  
**Repositorio:** https://github.com/DixonBM/GymFlow-grupo01  
**Sitio de pruebas:** https://grupo1.crnube.org/gymflow/  
**Entorno de GitHub:** `gymflow-production`  
**Versión desplegada verificada:** commit `8de8c05` de `main`

> **Alcance de este documento.** Describe la instalación manual que se realizó, la conexión a los dos motores, las pruebas verificadas y el despliegue controlado desde GitHub Actions. Es documentación de un entorno académico, **no** una certificación de seguridad o disponibilidad para operar con clientes reales. Las contraseñas y claves privadas no se incluyen.

## 1. Objetivo y resultado

El objetivo fue desplegar GymFlow en el servidor universitario administrado con Webmin/Virtualmin y comprobar que la aplicación PHP usa **MongoDB Atlas para persistencia** y **Redis para caché temporal**. También se incorporó un workflow para que, tras un `push` a `main`, GitHub Actions valide PHP, espere una aprobación y actualice el código del servidor mediante una clave SSH restringida.

**Resultados comprobados:**

- La aplicación carga y permite iniciar sesión en `https://grupo1.crnube.org/gymflow/`.
- El formulario de socios escribió un registro real en MongoDB Atlas: la colección pasó de **10 a 11 documentos** en la prueba inicial, con verificación posterior desde Atlas.
- MongoDB y Redis responden correctamente desde el servidor PHP (`PING: OK`).
- La prueba `scripts/smoke.php` verifica seis colecciones y la secuencia de caché **MISS → HIT**.
- GitHub Actions desplegó el commit `8de8c05` correctamente y el servidor confirmó ese mismo commit después de la ejecución.

## 2. Arquitectura implementada

```text
Desarrolladores (VS Code / Git)
           │ git push a main
           ▼
Repositorio público GitHub: DixonBM/GymFlow-grupo01
           │
           ▼
GitHub Actions: deploy-virtualmin.yml
  ├─ Validación de PHP y Composer
  ├─ Entorno gymflow-production: aprobación de revisor
  └─ Acceso SSH autenticado y verificado
           │
           ▼
Servidor Ubuntu 24.04 LTS — Webmin/Virtualmin
  ├─ /home/grupo1/apps/GymFlow-grupo01/  [código y .env privados]
  ├─ /home/grupo1/public_html/gymflow/   [entrada web y assets]
  ├─ PHP 8.3 en modo PHP-FPM
  ├─ MongoDB Atlas (remoto) → gymflow_pruebas
  └─ Redis (servidor universitario, 127.0.0.1:6379)
```

| Elemento | Ubicación o función |
| --- | --- |
| Dominio web | `https://grupo1.crnube.org/gymflow/` |
| Usuario de Linux | `grupo1` |
| Home | `/home/grupo1` |
| Repositorio clonado | `/home/grupo1/apps/GymFlow-grupo01` |
| Directorio expuesto por Apache | `/home/grupo1/public_html/gymflow` |
| Archivo de configuración de esta aplicación | `/home/grupo1/apps/GymFlow-grupo01/.env` (privado, ignorado por Git) |
| Script privado de despliegue | `/home/grupo1/bin/deploy-gymflow.sh` |
| Archivo SSH autorizado | `/home/grupo1/.ssh/authorized_keys` |
| Persistencia definitiva | MongoDB Atlas, base `gymflow_pruebas` |
| Caché | Redis, TTL `60` segundos |

**Separación de responsabilidades.** MongoDB conserva gimnasios, usuarios, socios, planes, membresías y pagos. Redis almacena únicamente indicadores temporales del dashboard; la pérdida de Redis no debería impedir las operaciones sobre MongoDB.

## 3. Requisitos del servidor

Se comprobó el siguiente entorno de ejecución:

- Ubuntu **24.04 LTS**; servidor virtual `grupo1.crnube.org`, administrado por Virtualmin.
- PHP **8.3** con modo web **PHP-FPM** y extensión `mongodb` disponible.
- **Composer 2** y dependencias del proyecto (`mongodb/mongodb`, `predis/predis`).
- Git y acceso a GitHub; acceso de salida a MongoDB Atlas.
- Redis Server y Redis CLI disponibles en el servidor; `redis-cli ping` respondió `PONG`.
- Cuenta `grupo1` con permisos sobre su directorio y sin necesitar `sudo` para el despliegue de la aplicación.

Comandos de consulta para comprobar el ambiente:

```bash
whoami
pwd
php -v
php -m | grep -i mongodb
command -v composer
command -v redis-server
command -v redis-cli
redis-cli ping
```

No instalar paquetes del sistema ni cambiar el servicio SSH global sin aprobación del administrador universitario.

## 4. Preparar el código privado (instalación inicial)

Estos comandos documentan la preparación inicial. **Si el repositorio ya existe y funciona, no volver a clonarlo.**

```bash
cd /home/grupo1
mkdir -p apps
cd apps
git clone https://github.com/DixonBM/GymFlow-grupo01.git
cd GymFlow-grupo01
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
php -r 'echo "Extensión MongoDB: ", phpversion("mongodb"), PHP_EOL;'
```

Conservar la configuración y el código fuera de la carpeta web: **no copiar `.env`, `vendor/`, `src/` ni `.git/` a `public_html`**.

### 4.1. Configuración de conexión

El archivo `.env` se mantiene en la raíz privada del proyecto y **no se versiona**. Ejemplo **sin credenciales reales**:

```dotenv
MONGODB_URI=mongodb+srv://USUARIO:CLAVE_URL_CODIFICADA@CLUSTER.mongodb.net/?retryWrites=true&w=majority
MONGODB_DB=gymflow_pruebas
REDIS_URL=redis://127.0.0.1:6379
CACHE_TTL_SECONDS=60
APP_ENV=production
```

La dirección de MongoDB, el usuario y la contraseña se sustituyen por los datos reales autorizados. No agregar espacios indebidos a la URI; si la contraseña contiene caracteres especiales, aplicar codificación URL. Confirmar en Atlas que la IP de salida del servidor está permitida y que el usuario tiene solamente los privilegios necesarios. **No utilizar apertura global `0.0.0.0/0`** para esta aplicación.

Proteger la configuración sin exponer su contenido:

```bash
cd /home/grupo1/apps/GymFlow-grupo01
chmod 600 .env
php -r 'require "vendor/autoload.php"; \GymFlow\Bootstrap::env(getcwd()."/.env"); \GymFlow\Bootstrap::db()->command(["ping"=>1]); echo "MongoDB PING: OK\n";'
```

> Existe además `/home/grupo1/config/conexiones.php` por trabajos académicos anteriores. **Este manual no asume que GymFlow lea ese archivo**: el proyecto utiliza las variables `MONGODB_URI`, `MONGODB_DB` y `REDIS_URL` de su `.env`. No incluir otras credenciales ni publicarlas.

### 4.2. Inicialización de datos de demostración

La base usada en la prueba fue `gymflow_pruebas`. Solo en una **base de prueba correctamente seleccionada** se ejecutó:

```bash
cd /home/grupo1/apps/GymFlow-grupo01
php scripts/setup.php --seed
```

El script verificó los índices y las seis colecciones: `gimnasios`, `usuarios`, `socios`, `planes`, `membresias`, `pagos`. Los usuarios de demostración ya existentes conservaron su contraseña; **no** se vuelve a mostrar una contraseña creada en ejecuciones anteriores. Ejecutar `--seed` únicamente cuando corresponda inicializar datos de prueba; no utilizarlo como parte de un despliegue rutinario.

## 5. Publicación web mediante Virtualmin

La aplicación se publicó bajo el dominio existente, **sin crear un subservidor independiente**, en:

```text
/home/grupo1/public_html/gymflow/
```

La implementación observada expone los recursos de `public/assets/` y un archivo `index.php` adaptador; el resto de la aplicación permanece en el repositorio privado.

### 5.1. Preparar los recursos públicos

```bash
mkdir -p /home/grupo1/public_html/gymflow/assets
cp -a /home/grupo1/apps/GymFlow-grupo01/public/assets/. \
      /home/grupo1/public_html/gymflow/assets/
```

### 5.2. Archivo adaptador para `/gymflow/`

El adaptador instalado en `/home/grupo1/public_html/gymflow/index.php` utiliza el PHP del repositorio privado. Corrige la ruta absoluta de la hoja de estilos para que funcione en esta subcarpeta:

```php
<?php
declare(strict_types=1);

ob_start(static function (string $html): string {
    return str_replace(
        'href="/assets/style.css"',
        'href="/gymflow/assets/style.css"',
        $html
    );
});

require '/home/grupo1/apps/GymFlow-grupo01/public/index.php';
```

Esta adaptación fue probada con la aplicación actual. Si las versiones posteriores introducen nuevas rutas absolutas u otros recursos, se deberán revisar; la alternativa de largo plazo es soportar explícitamente un prefijo de URL desde la aplicación.

Comprobación de sintaxis:

```bash
php -l /home/grupo1/public_html/gymflow/index.php
```

### 5.3. Control de acceso web

Antes de habilitar el sitio se comprobó una restricción `.htaccess` mediante `HTTP 403`. Posteriormente se configuró acceso autenticado con usuario y contraseña para pruebas del grupo (HTTP Basic Auth, con archivo de contraseñas fuera de `public_html`). **No publicar el archivo de contraseñas ni sustituir el `.htaccess` existente durante despliegues.**

Además de la protección HTTP, GymFlow requiere sus propias credenciales de inicio de sesión. Verificar HTTPS, configuración de sesiones, control de permisos y protección de las credenciales antes de permitir usuarios ajenos al equipo.

## 6. Redis en el servidor Virtualmin

La disponibilidad de Redis se comprobó con:

```bash
command -v redis-server
command -v redis-cli
redis-cli ping
```

**Resultado observado:** ambos ejecutables estaban disponibles y `redis-cli ping` devolvió `PONG`. Desde PHP, el archivo `.env` apunta a `redis://127.0.0.1:6379`.

Ejecutar la prueba de integración de ambos motores:

```bash
cd /home/grupo1/apps/GymFlow-grupo01
php scripts/smoke.php
```

La prueba verificó los `PING` de MongoDB y Redis, las seis colecciones, el aislamiento por gimnasio y la secuencia `MISS seguido de HIT OK` en la caché.

## 7. Automatización del despliegue desde GitHub Actions

### 7.1. Archivo versionado

El repositorio contiene `.github/workflows/deploy-virtualmin.yml`. Su configuración verificada usa dos eventos:

```yaml
on:
  push:
    branches:
      - main
  workflow_dispatch:
```

Los jobs son:

1. **Validar PHP:** realiza checkout del repositorio, configura PHP 8.3 con extensión MongoDB, valida sintaxis PHP e instala dependencias para la comprobación.
2. **Desplegar en Virtualmin:** solicita el entorno `gymflow-production`; después de su aprobación configura SSH y ejecuta la conexión remota, donde `authorized_keys` impone el script `deploy-gymflow.sh`.

El entorno está restringido a `main` y tiene como revisores configurados a `Jose0696` y `MariaCruzC`. La aprobación es **manual**, aunque el workflow se **dispara automáticamente** con cada `push`. `Prevent self-review` no se ha documentado como activo.

### 7.2. Secretos y variable necesarios

| Ámbito | Nombre | Contenido (no publicar) |
| --- | --- | --- |
| Repository secret | `SSH_HOST` | Dominio SSH `viernes.crnube.com` |
| Repository secret | `SSH_USER` | Usuario de Linux `grupo1` |
| Environment secret (`gymflow-production`) | `SSH_PRIVATE_KEY` | Clave privada de despliegue completa, en formato OpenSSH |
| Environment variable (`gymflow-production`) | `SSH_KNOWN_HOSTS` | Clave pública **verificada** del host SSH, con su nombre de host |

La clave privada de automatización **no** es la clave personal de Windows. La clave pública correspondiente se autorizó en `/home/grupo1/.ssh/authorized_keys` con un comando forzado y restricciones:

```text
restrict,command="/home/grupo1/bin/deploy-gymflow.sh" ssh-ed25519 CLAVE_PUBLICA_DE_ACTIONS github-actions-gymflow
```

Esto es una **plantilla**: no reemplazar las entradas reales existentes ni subir `authorized_keys` a GitHub. Se mantuvo separada la clave SSH personal. La clave de host guardada en `SSH_KNOWN_HOSTS` se obtuvo del servidor autorizado y se verificó antes de confiar en ella.

### 7.3. Script privado en Virtualmin

El script `/home/grupo1/bin/deploy-gymflow.sh` (permisos `700`) realiza, según la configuración instalada:

```text
Comprobar existencia de .env
→ Rechazar cambios locales en archivos versionados
→ git fetch / git pull --ff-only origin main
→ composer install sin dependencias de desarrollo
→ php -l sobre scripts, src y public
→ Sincronizar public/assets/ con public_html/gymflow/assets/
→ Mostrar el commit desplegado
```

No sobrescribe el adaptador web ni la configuración `.env`, no ejecuta el generador de datos y no modifica directamente las colecciones de Atlas. **No hay mecanismo de rollback automático confirmado**. Cada cambio de código debe revisarse antes de aprobarse.

**Limitación a tener en cuenta:** el script hace `git pull` de la punta de `main` en el momento de ejecutarse, y no fija necesariamente el SHA del workflow aprobado. Si llegan más commits mientras un despliegue espera aprobación, conviene verificar el SHA finalmente publicado; como mejora futura, desplegar exactamente el commit aprobado.

### 7.4. Resultado del despliegue

Se observó primero una ejecución manual (`workflow_dispatch`) exitosa. Luego se activó el disparador por `push` y se obtuvo un despliegue aprobado exitoso:

- **Commit verificado:** `8de8c05` — `ci: automatizar despliegue GymFlow desde main`.
- **Validación PHP:** exitosa.
- **Despliegue hacia Virtualmin:** exitoso.
- **Verificación posterior en el servidor:** `git log -1 --oneline` devolvió `8de8c05` y `php scripts/smoke.php` terminó correctamente.

Por tanto, el flujo **push → validación → aprobación → SSH → despliegue** ha quedado probado. No equivale a un despliegue sin intervención humana: la aprobación de producción sigue siendo obligatoria.

## 8. Pruebas reales y resultados observados

### 8.1. Persistencia del formulario web

En una prueba inicial se registró un socio ficticio desde el navegador, bajo **Hércules Demo**, y se observó:

- Mensaje web de guardado correcto.
- Incremento del gimnasio de **5 a 6 socios**.
- Conteo global de Atlas de **10 a 11 socios**.
- Nuevo documento visible en MongoDB Atlas, con identificador y `gimnasioId`.

Esta prueba confirmó que los formularios de la instancia publicada escriben información persistente en MongoDB Atlas, sin depender de la base local de Docker.

### 8.2. Verificación final posterior al despliegue automático

**Comandos ejecutados:**

```bash
cd /home/grupo1/apps/GymFlow-grupo01
git log -1 --oneline
php scripts/smoke.php
```

**Resultado observado el 17/10/2026:**

| Comprobación | Resultado |
| --- | --- |
| Commit de `main` | `8de8c05` |
| MongoDB Atlas | `PING: OK` |
| Redis | `PING: OK` |
| Gimnasios | 3 documentos |
| Usuarios | 4 documentos |
| Socios | 12 documentos |
| Planes | 5 documentos |
| Membresías | 12 documentos |
| Pagos | 8 documentos |
| **Total de documentos en las seis colecciones** | **44** |
| Hércules Demo | 7 socios; 2 membresías habilitadas; ₡60 000 de ingresos registrados |
| Titan Demo | 5 socios; 1 membresía habilitada; ₡40 000 de ingresos registrados |
| Caché de ambos gimnasios | `MISS seguido de HIT OK` |
| Resultado final de smoke | `OK: MongoDB+Redis PING, seis colecciones, datos, indices, aislamiento y cache MISS/HIT.` |

Los conteos reflejan el **momento de la captura**, no cantidades fijas que deban permanecer iguales después de nuevas pruebas.

**Evidencia del resultado final:**

![Validación final de Git y smoke test en Virtualmin](../evidencias/15-verificacion-final-virtualmin-atlas-redis.png)

### 8.3. Otras evidencias del repositorio

- [Pantalla de login publicada en Virtualmin](../evidencias/08-gymflow-virtualmin-login.png).
- [Dashboard de GymFlow publicado](../evidencias/09-gymflow-virtualmin-dashboard.png).
- [Registro de un socio desde Virtualmin](../evidencias/10-gymflow-socio-registrado-virtualmin.png).
- [Conteo de persistencia desde terminal](../evidencias/11-gymflow-persistencia-terminal.png).
- [Documento consultado en Atlas](../evidencias/12-gymflow-documento-mongodb-atlas.png).
- [Workflow de despliegue](../../.github/workflows/deploy-virtualmin.yml).
- [Bitácora del Seguimiento 1](../seguimientos/S1.md).

**Evidencia adicional sugerida:** guardar una captura de GitHub Actions mostrando el despliegue `push` exitoso y otra de **Conexión BD** con ambos motores conectados; omitir secretos, tokens y datos personales en las imágenes públicas.

## 9. Operación diaria y solución de problemas

### 9.1. Actualizar GymFlow

El método habitual es: desarrollo y commit desde VS Code → `git push origin main` → controles de GitHub Actions → aprobación de José o María → despliegue al servidor. **No editar directamente los archivos versionados en Virtualmin** porque las modificaciones locales pueden bloquear el despliegue.

Para verificar el estado sin cambiar datos:

```bash
cd /home/grupo1/apps/GymFlow-grupo01
git status
git log -1 --oneline
php scripts/smoke.php
```

El `composer.lock` generado localmente apareció como *untracked* en alguna verificación del servidor. No se debe borrar ni versionar a ciegas; revisar primero si el proyecto decide incorporar un lockfile reproducible.

### 9.2. Problemas conocidos y diagnóstico

| Síntoma | Comprobación / acción segura |
| --- | --- |
| El workflow espera indefinidamente | Revisar aprobación pendiente del entorno `gymflow-production` |
| `SSH_KNOWN_HOSTS` vacío | Configurar la **environment variable** `SSH_KNOWN_HOSTS` y verificar la huella del servidor |
| `Load key ... error in libcrypto` | Revisar contenido y saltos de línea del secreto `SSH_PRIVATE_KEY`; no publicar la clave |
| `Permission denied (publickey)` | Confirmar par de claves, restricciones `authorized_keys` y usuario SSH |
| `git pull` falla | Revisar `git status` y archivos locales antes de resolver conflictos; no usar `git reset --hard` sin evaluar consecuencias |
| Redis no responde | `redis-cli ping`; comprobar `REDIS_URL` y permisos; la persistencia reside en Atlas |
| Fallo de MongoDB | Verificar la conectividad, IP permitida en Atlas, usuario y URI privada |
| El panel muestra cero socios | Confirmar primero cuál gimnasio está seleccionado; el sistema separa datos por gimnasio |
| CSS no carga en `/gymflow/` | Verificar que `assets/` se copió y que el adaptador conserva la ruta `/gymflow/assets/style.css` |

### 9.3. Recuperación y respaldos

El flujo comprobado **no** cuenta con un rollback automático documentado. Antes de despliegues significativos conviene conservar el commit anterior, comprobar cómo restaurarlo, contar con respaldos de Atlas y preparar una reversión ensayada. No confundir el repositorio Git con un respaldo de la base de datos.

## 10. Seguridad y trabajos posteriores

- **Rotar inmediatamente** las contraseñas de MongoDB Atlas y las cuentas de demostración que fueron compartidas durante la preparación. No usar esas credenciales expuestas para usuarios reales.
- Revisar los permisos de `.env`, `authorized_keys`, el script de despliegue, la autenticación HTTP y el acceso SSH. No revelar ni subir claves privadas.
- Mantener Redis accesible solo desde el entorno autorizado; no exponer el puerto `6379` al público.
- Confirmar y conservar autorización del administrador universitario para despliegues automáticos en el servidor compartido.
- Como mejora futura: desplegar el commit exacto aprobado, bloquear despliegues si fallan pruebas funcionales, añadir monitoreo y preparar recuperación/rollback.
- Revisar que las reglas de aprobación permanezcan activas; `Prevent self-review` puede activarse si se desea impedir la autoaprobación.
- Actualizar `docs/seguimientos/S1.md` con la conexión final de Redis y el despliegue GitHub Actions; conservar el tag histórico `v0.1.0` sin moverlo.

## 11. Conclusión

Se cumplió la integración técnica solicitada: GymFlow funciona desde el servidor de Virtualmin con PHP 8.3, persiste sus datos en MongoDB Atlas y utiliza Redis para la caché de indicadores. Se comprobó la creación de registros desde el frontend, la lectura posterior desde Atlas, el estado conectado de ambos motores, las pruebas `MISS/HIT` y el despliegue controlado desde GitHub Actions. Quedan como actividades complementarias de cierre la revisión de seguridad, la documentación de respaldos/recuperación y la actualización final de evidencias en el repositorio.
