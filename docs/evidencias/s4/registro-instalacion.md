# GymFlow · Gestión de gimnasios · Grupo 01

*Proyecto Final Open Source · Tópicos Avanzados de Bases de Datos · Universidad Nacional*

## Estado del proyecto

# ✅ COMPLETADO

GymFlow es un proyecto académico finalizado que integra una aplicación web para la administración de gimnasios, persistencia en MongoDB, caché en Redis, invitaciones por correo, pruebas, documentación técnica y demostración del funcionamiento.

| Componente de la entrega | Estado |
| --- | --- |
| Implementación de los módulos del sistema | ✅ COMPLETADO |
| Integración con MongoDB | ✅ COMPLETADO |
| Integración con Redis | ✅ COMPLETADO |
| Administración de máquinas | ✅ COMPLETADO |
| Invitaciones y activación de usuarios | ✅ COMPLETADO |
| Renovación de membresías con pago integrado | ✅ COMPLETADO |
| Configuración de instalación y ejecución | ✅ COMPLETADO |
| Pruebas y documentación de resultados | ✅ COMPLETADO |
| Documentación técnica y README | ✅ COMPLETADO |
| Despliegue de la aplicación | ✅ COMPLETADO |
| Video de demostración | ✅ COMPLETADO |
| Entrega académica del proyecto | ✅ COMPLETADO |

## Enlaces del proyecto

- *Repositorio:* [DixonBM/GymFlow-grupo01](https://github.com/DixonBM/GymFlow-grupo01)
- *Aplicación desplegada:* [Abrir GymFlow](https://grupo1.crnube.org/gymflow/)
- *Video de demostración:* [Ver demostración de GymFlow](https://youtu.be/l7KlEOZn9FA)
- *Tablero de trabajo:* [GitHub Projects](https://github.com/users/DixonBM/projects/3)
- *Hitos:* [Milestones](https://github.com/DixonBM/GymFlow-grupo01/milestones)
- *Versiones publicadas:* [Releases](https://github.com/DixonBM/GymFlow-grupo01/releases)
- *Etiquetas:* [Tags](https://github.com/DixonBM/GymFlow-grupo01/tags)
- *Historial de cambios:* [CHANGELOG.md](CHANGELOG.md)
- *Ficha del proyecto:* [portal.yml](portal.yml)

La aplicación desplegada requiere acceso autorizado al servidor y una cuenta del sistema. La instalación local permite ejecutar una demostración propia con datos sintéticos.

## Contenido

1. [Descripción del proyecto](#descripción-del-proyecto)
2. [Problema y objetivos](#problema-y-objetivos)
3. [Usuarios y permisos](#usuarios-y-permisos)
4. [Funcionalidades implementadas](#funcionalidades-implementadas)
5. [Reglas de negocio](#reglas-de-negocio)
6. [Tecnologías utilizadas](#tecnologías-utilizadas)
7. [Arquitectura](#arquitectura)
8. [Almacenamiento y modelo de datos](#almacenamiento-y-modelo-de-datos)
9. [Instalación local con Docker](#instalación-local-con-docker)
10. [Uso de la aplicación](#uso-de-la-aplicación)
11. [Variables de entorno](#variables-de-entorno)
12. [Instalación manual](#instalación-manual)
13. [Pruebas y validación](#pruebas-y-validación)
14. [Reproducción de la instalación desde cero](#reproducción-de-la-instalación-desde-cero)
15. [Mediciones de rendimiento](#mediciones-de-rendimiento)
16. [Operación y mantenimiento local](#operación-y-mantenimiento-local)
17. [Despliegue](#despliegue)
18. [Capturas del sistema](#capturas-del-sistema)
19. [Estructura del repositorio](#estructura-del-repositorio)
20. [Documentación de la entrega](#documentación-de-la-entrega)
21. [Versiones y trazabilidad](#versiones-y-trazabilidad)
22. [Equipo y licencia](#equipo-y-licencia)

## Descripción del proyecto

GymFlow es una aplicación web de código abierto que centraliza la administración de varios gimnasios.

Permite gestionar gimnasios, usuarios administrativos, socios, planes, membresías, pagos manuales e inventario de máquinas desde una misma plataforma, manteniendo separada la información operativa de cada gimnasio.

El proyecto utiliza dos motores de datos con responsabilidades diferentes:

- *MongoDB:* almacenamiento persistente de los datos del negocio.
- *Redis:* caché temporal de indicadores del resumen por gimnasio.

La aplicación está desarrollada con PHP y HTML/CSS. Docker Compose permite ejecutar el entorno local con la aplicación, MongoDB, Redis y Mailpit.

## Problema y objetivos

### Problema abordado

Los gimnasios pequeños suelen administrar socios, planes, vencimientos y cobros mediante hojas de cálculo, documentos separados o registros manuales.

Esta dispersión dificulta:

- Consultar información actualizada.
- Identificar membresías y vencimientos.
- Conservar el historial de pagos.
- Controlar el acceso a los datos.
- Administrar varios gimnasios de manera uniforme.
- Mantener trazabilidad de las operaciones.

### Objetivo general

Centralizar la administración de gimnasios mediante una aplicación web que integre persistencia, caché y reglas de negocio.

### Objetivos específicos

- Administrar gimnasios y sus cuentas de acceso.
- Separar la información de cada gimnasio.
- Gestionar socios y planes.
- Registrar contrataciones, renovaciones y cancelaciones.
- Conservar el historial de pagos y anulaciones.
- Administrar el inventario de máquinas.
- Integrar MongoDB y Redis.
- Proporcionar una instalación reproducible.
- Documentar arquitectura, pruebas y mediciones.
- Presentar una demostración funcional del sistema.

## Usuarios y permisos

| Usuario | Responsabilidades |
| --- | --- |
| Superadministrador | Administra gimnasios y cuentas administrativas. Selecciona el gimnasio cuya información desea gestionar. |
| Administrador de gimnasio | Gestiona los socios, planes, membresías, pagos y máquinas de su gimnasio. |
| Socio | Cliente registrado por el administrador. No tiene cuenta propia de acceso en esta versión. |

La aplicación obtiene el gimnasio del administrador a partir de su cuenta.

Los servicios restringen las consultas y modificaciones al gimnasio correspondiente. La separación de información se aplica en las operaciones del sistema y no solamente en la presentación de la interfaz.

Los módulos globales de gimnasios y usuarios están reservados al superadministrador.

## Funcionalidades implementadas

### Autenticación y cuentas

- Inicio de sesión.
- Cierre de sesión.
- Validación de credenciales.
- Control de acceso por rol.
- Administración de cuentas activas e inactivas.
- Invitaciones por correo.
- Activación de cuentas mediante un enlace.
- Definición de contraseña durante la activación.
- Reenvío de invitaciones.

### Gimnasios

- Registro de gimnasios.
- Consulta de información.
- Modificación de datos comerciales.
- Gestión de información de contacto.
- Administración del estado del gimnasio.
- Selección del gimnasio por parte del superadministrador.

### Socios

- Registro de socios.
- Consulta y modificación de información.
- Activación y desactivación.
- Identificación única dentro del gimnasio.
- Separación de registros por gimnasio.

### Planes

- Registro de planes.
- Modificación de nombre, duración y precio.
- Activación y desactivación.
- Manejo de importes en colones costarricenses.
- Conservación de las condiciones contratadas en las membresías.

### Membresías

- Contratación de planes para un socio.
- Registro de fechas de inicio y finalización.
- Conservación de las condiciones contratadas.
- Cancelación con mantenimiento del historial.
- Renovación de membresías.
- Registro del pago dentro del flujo de renovación.
- Validación de períodos superpuestos.

### Pagos

- Registro manual de pagos en efectivo.
- Registro manual de SINPE Móvil.
- Registro manual de transferencias.
- Conservación de referencia y responsable.
- Control de un único pago efectivo por membresía.
- Anulación con motivo.
- Consulta del historial.

Los pagos representan operaciones realizadas fuera de GymFlow. El sistema registra esas operaciones; no ejecuta transferencias ni cobros bancarios.

### Máquinas

- Registro de máquinas por gimnasio.
- Edición de información.
- Eliminación.
- Búsqueda.
- Filtrado por estado.
- Código único por gimnasio.
- Clasificación por tipo.
- Estados operativa, mantenimiento y fuera de servicio.

### Resumen y conexiones

- Indicadores administrativos por gimnasio.
- Caché temporal del resumen mediante Redis.
- Identificación de lecturas HIT/MISS.
- Pantalla de conexión a MongoDB y Redis.
- Consulta del resumen desde MongoDB cuando Redis no está disponible.

### Delimitación del alcance

La entrega comprende las funciones descritas en este documento.

Las rutinas, asistencia por QR, agenda, facturación electrónica, aplicaciones móviles y autoservicio del socio no forman parte del alcance contratado para este proyecto académico.

## Reglas de negocio

- Los administradores trabajan únicamente con su gimnasio.
- La identificación de un socio es única dentro del gimnasio.
- El código de una máquina es único dentro del gimnasio.
- Los planes requieren duración y precio positivos.
- Una membresía conserva las condiciones del plan al contratar.
- Cambiar posteriormente un plan no modifica las condiciones históricas de las membresías existentes.
- Cancelar una membresía conserva su historial.
- La renovación valida la elegibilidad de la membresía y sus períodos.
- La renovación y su pago se registran dentro de una misma transacción MongoDB.
- Los pagos utilizan el importe contratado.
- La anulación de un pago requiere un motivo.
- Los enlaces de activación vencen en 24 horas.
- Cada enlace de activación tiene un solo uso.
- El reenvío de una invitación requiere esperar un minuto desde el envío anterior.

## Tecnologías utilizadas

| Componente | Tecnología | Responsabilidad |
| --- | --- | --- |
| Aplicación web | PHP y HTML/CSS | Interfaz, formularios, sesiones, permisos y reglas de negocio. |
| Servidor local Docker | PHP 8.3 + Apache | Ejecución de la aplicación. |
| Persistencia | MongoDB 7 | Almacenamiento definitivo, índices, validación y transacciones. |
| Caché | Redis 7.4 | Resumen temporal por gimnasio. |
| Cliente Redis | Predis | Comunicación desde PHP con Redis. |
| Correo | PHPMailer y SMTP | Envío de invitaciones. |
| Correo de desarrollo | Mailpit | Captura local de mensajes. |
| Dependencias | Composer | Instalación de bibliotecas PHP. |
| Entorno local | Docker Compose | Coordinación de los servicios. |
| Validación automatizada | GitHub Actions | Sintaxis PHP y prueba aislada de caché. |
| Control de versiones | Git y GitHub | Historial, colaboración y trazabilidad. |

La ejecución de la aplicación no requiere React ni Node.js.

Las dependencias PHP se declaran en composer.json y sus versiones se fijan en composer.lock.

## Arquitectura

mermaid
flowchart LR
    U[Navegador] --> A[Aplicación PHP y Apache]
    A --> M[MongoDB: persistencia y transacciones]
    A --> R[Redis: caché temporal]
    A --> S[SMTP: invitaciones]
    S --> P[Mailpit en desarrollo]


### Responsabilidades

- El navegador presenta formularios y resultados.
- PHP valida las solicitudes, controla el acceso y ejecuta las reglas de negocio.
- MongoDB conserva la información definitiva.
- Redis acelera la consulta del resumen.
- SMTP permite enviar invitaciones.
- Mailpit captura los mensajes en el entorno local.

La arquitectura detallada se encuentra en [docs/arquitectura/arquitectura-v1.md](docs/arquitectura/arquitectura-v1.md).

## Almacenamiento y modelo de datos

### MongoDB

El sistema utiliza siete colecciones:

| Colección | Información |
| --- | --- |
| gimnasios | Datos comerciales, contacto y estado de cada gimnasio. |
| usuarios | Cuentas administrativas, roles, contraseñas protegidas y activación. |
| socios | Clientes de cada gimnasio. |
| planes | Duración, precio y estado de los planes. |
| membresias | Contrataciones, condiciones, fechas e historial. |
| pagos | Pagos manuales y anulaciones. |
| maquinas | Inventario de máquinas por gimnasio. |

El setup configura validadores e índices, entre ellos:

- Correo único de usuario.
- Identificación única de socio por gimnasio.
- Código único de máquina por gimnasio.
- Índices para consultas de planes, membresías y pagos.
- Control de operaciones de pago.
- Control de un pago efectivo por membresía.

La migración de invitaciones agrega el índice utilizado para localizar los tokens de activación.

Los importes se representan mediante Decimal128.

### Transacciones

La renovación con pago requiere un replica set o un clúster MongoDB compatible con transacciones.

Docker configura el replica set local rs0 y espera a que MongoDB tenga un primary disponible.

El flujo registra la nueva membresía y su pago dentro de la misma transacción.

### Redis

Redis conserva temporalmente el resumen de cada gimnasio.

| Propiedad | Configuración |
| --- | --- |
| Clave | gymflow:s1:dashboard:<gimnasioId> |
| Contenido | Resumen serializado como JSON |
| TTL predeterminado | 60 segundos |
| Rango configurable | Entre 1 y 3600 segundos |
| Lectura | GET |
| Escritura con vencimiento | SETEX |
| Invalidación | DEL |
| Comprobación de conexión | PING |

Si no existe una entrada de caché, el resumen se obtiene de MongoDB.

Si Redis falla, MongoDB continúa siendo la fuente de información.

Redis no conserva la única copia de socios, membresías ni pagos.

### Documentación del modelo

El modelo inicial se encuentra en [docs/modelo-datos-v1.md](docs/modelo-datos-v1.md).

Los servicios, esquemas y migraciones del repositorio reflejan las ampliaciones posteriores.

## Instalación local con Docker

### Requisitos

- Git.
- Docker Desktop iniciado.
- Contenedores Linux habilitados.
- Docker Compose v2 actualizado.
- Internet para descargar imágenes y dependencias.
- Puertos locales 8000 y 8025 disponibles.
- Editor de texto, opcional.

No hace falta instalar PHP, Composer, MongoDB ni Redis directamente en Windows para esta modalidad.

Los comandos siguientes utilizan PowerShell.

### 1. Comprobar herramientas

powershell
git --version
docker version
docker compose version


docker version debe mostrar información del cliente y del servidor.

Si no se conecta al servidor, abrir Docker Desktop y esperar a que termine de iniciar.

### 2. Clonar el repositorio

Desde una carpeta donde todavía no exista una copia con el mismo nombre:

powershell
git clone https://github.com/DixonBM/GymFlow-grupo01.git
Set-Location .\GymFlow-grupo01
git rev-parse HEAD


El último comando identifica la revisión instalada.

### 3. Utilizar la configuración Docker predeterminada

*No crear ni copiar .env para la configuración local predeterminada.*

Compose configura los nombres internos de los servicios:

- MongoDB: mongo.
- Redis: redis.
- Correo: mailpit.

El archivo .env.example debe adaptarse si se utiliza otra configuración. Copiarlo sin modificar puede sobrescribir las direcciones internas con valores incorrectos.

Las variables de la terminal también pueden sobrescribir los valores de Compose.

### 4. Construir e iniciar

powershell
docker compose up --build -d --wait --wait-timeout 240
docker compose ps


La primera construcción descarga imágenes e instala dependencias.

Al finalizar:

- app debe estar iniciado.
- mailpit debe estar iniciado.
- mongo debe estar saludable.
- redis debe estar saludable.

MongoDB inicializa automáticamente el replica set rs0.

Si Compose no reconoce --wait o --wait-timeout, actualizar Docker Desktop o Docker Compose.

### 5. Inicializar esquemas e índices

powershell
docker compose exec app php scripts/setup.php --seed
docker compose exec app php scripts/migrate-invitaciones.php


El setup crea o verifica las siete colecciones y sus índices.

En una base vacía, el seed genera:

- Dos gimnasios.
- Tres cuentas administrativas.
- Diez socios.
- Cuatro planes.
- Diez membresías.
- Seis pagos, incluidos pagos anulados para pruebas.

Las fechas de los datos sintéticos se calculan respecto al día de ejecución.

La colección de máquinas inicialmente queda vacía. Su funcionamiento se comprueba registrando una máquina desde la interfaz.

### 6. Conservar las claves de demostración

El seed imprime contraseñas aleatorias al crear las cuentas.

| Cuenta | Acceso |
| --- | --- |
| superadmin@gymflow.test | Superadministrador |
| hercules@gymflow.test | Administrador de Hércules Demo |
| titan@gymflow.test | Administrador de Titan Demo |

Guardar las contraseñas en privado.

Repetir el seed conserva las cuentas y sus contraseñas; no vuelve a imprimir las claves originales.

No publicar claves en capturas, comentarios o commits.

### 7. Comprobar conexiones y caché

powershell
docker compose exec app php scripts/smoke.php
docker compose exec app php scripts/unit-cache.php
docker compose exec mongo mongosh --quiet --eval 'db.hello().isWritablePrimary'


El smoke debe terminar con:

text
OK: MongoDB+Redis PING, seis colecciones, datos, indices, aislamiento y cache MISS/HIT.


El mensaje menciona seis porque verifica las seis colecciones originales.

El sistema contiene siete colecciones. Máquinas, invitaciones y renovación transaccional se comprueban mediante sus flujos funcionales.

La prueba de caché debe terminar con:

text
OK: Redis simulado MISS / HIT / invalida / falla sin lanzar excepciones.


Los fallos Redis simulados en esa prueba son intencionales.

La consulta de MongoDB debe devolver:

text
true


### 8. Abrir los servicios

- Aplicación: http://localhost:8000
- Mailpit: http://localhost:8025

Ingresar con una cuenta del seed y su contraseña privada.

## Uso de la aplicación

### Seleccionar el gimnasio

El superadministrador selecciona el gimnasio desde el selector de la interfaz.

Un administrador trabaja con el gimnasio asignado a su cuenta.

### Registrar un socio y un plan

1. Abrir *Socios*.
2. Registrar una persona ficticia con identificación nueva.
3. Abrir *Planes*.
4. Crear un plan con precio y duración positivos.
5. Comprobar ambos registros en sus listados.

### Contratar una membresía

1. Abrir *Membresías*.
2. Seleccionar el socio.
3. Seleccionar el plan.
4. Indicar una fecha inicial válida.
5. Confirmar la contratación.
6. Revisar las condiciones y fechas registradas.

### Registrar un pago

1. Entrar a *Membresías → Ver pagos de membresías*.
2. Seleccionar una membresía sin pago.
3. Elegir el método.
4. Registrar la referencia correspondiente.
5. Confirmar.
6. Consultar el historial después de recargar.

### Renovar con pago integrado

1. Abrir *Membresías*.
2. Buscar la membresía elegible más reciente.
3. Pulsar *Renovar*.
4. Revisar plan, fechas y datos del pago.
5. Confirmar la renovación.
6. Comprobar la nueva membresía y su pago.
7. Verificar que el historial anterior se conserva.

### Administrar máquinas

1. Abrir *Máquinas*.
2. Registrar una máquina con código nuevo.
3. Elegir tipo y estado.
4. Guardar.
5. Comprobar su aparición.
6. Probar búsqueda, filtros y edición.

### Crear y activar un usuario

1. Ingresar como superadministrador.
2. Abrir *Usuarios*.
3. Crear una cuenta con correo nuevo y gimnasio activo asignado.
4. Abrir Mailpit.
5. Consultar la invitación.
6. Abrir el enlace en una ventana privada sin una sesión previa.
7. Definir una contraseña de al menos 12 caracteres.
8. Confirmar la activación.
9. Ingresar con la nueva cuenta.

Mailpit captura mensajes locales y no los entrega a buzones externos.

### Consultar motores y caché

- *Conexión BD* muestra el estado de MongoDB y Redis.
- *Resumen* muestra los indicadores del gimnasio.
- Dos visitas seguidas permiten observar el uso de caché.
- El smoke fuerza una lectura MISS seguida de HIT para comprobar ambos caminos.

## Variables de entorno

Valores locales predeterminados de Docker Compose:

| Variable | Valor o función |
| --- | --- |
| MONGODB_URI | mongodb://mongo:27017/?replicaSet=rs0 |
| MONGODB_DB | gymflow |
| REDIS_URL | redis://redis:6379 |
| CACHE_TTL_SECONDS | 60, configurable entre 1 y 3600 |
| APP_ENV | local |
| APP_URL | http://localhost:8000/ |
| SMTP_HOST | mailpit |
| SMTP_PORT | 1025 |
| SMTP_AUTH | false |
| SMTP_ENCRYPTION | none para Mailpit local |
| SMTP_USER | Vacío para Mailpit; privado con proveedor real |
| SMTP_PASS | Vacío para Mailpit; privado con proveedor real |
| MAIL_FROM | no-reply@gymflow.test |
| MAIL_FROM_NAME | GymFlow |

APP_URL se utiliza para construir los enlaces de activación.

El SMTP sin cifrado está permitido únicamente en APP_ENV=local.

Un proveedor real debe utilizar starttls o tls, según sus requisitos.

No publicar .env, credenciales, cookies ni cadenas de conexión privadas.

## Instalación manual

### Requisitos

- PHP 8.2 o superior.
- Extensión PHP mongodb 2.x.
- Composer 2.
- MongoDB configurado como replica set o un clúster compatible.
- Redis accesible.
- SMTP accesible desde PHP.

Predis no requiere la extensión phpredis.

### Preparación

powershell
Copy-Item .env.example .env


Editar .env para los servicios de la computadora:

- Configurar la dirección real de MongoDB.
- Verificar compatibilidad con transacciones.
- Configurar Redis.
- Definir APP_URL.
- Configurar SMTP.

Los nombres mongo, redis y mailpit corresponden a la red interna de Docker.

El Mailpit del Compose publica la bandeja web en 8025, pero no publica SMTP 1025 al host. PHP ejecutado fuera de Docker necesita un SMTP accesible o una publicación local explícita de ese puerto.

### Instalación y ejecución

powershell
composer install
php scripts/setup.php --seed
php scripts/migrate-invitaciones.php
php scripts/smoke.php
php scripts/unit-cache.php
php -S 127.0.0.1:8000 -t public public/router.php


El servidor integrado de PHP se utiliza para desarrollo.

## Pruebas y validación

*Estado de esta parte de la entrega: ✅ COMPLETADO.*

### Integración básica

powershell
docker compose exec app php scripts/smoke.php


La comprobación comprende:

- Conexión real con MongoDB.
- Conexión real con Redis.
- Datos en las seis colecciones originales.
- Índice parcial de pagos.
- Separación de socios por gimnasio.
- Secuencia MISS/HIT.
- Consistencia del resumen desde caché.

### Lógica aislada de caché

powershell
docker compose exec app php scripts/unit-cache.php


La prueba comprende:

- MISS.
- HIT.
- Invalidación.
- Manejo de fallos simulados.

### Pruebas funcionales

La validación funcional del sistema abarca:

- Inicio y cierre de sesión.
- Roles y restricciones.
- Socios y planes.
- Datos inválidos y duplicados.
- Contratación y cancelación.
- Pagos y anulaciones.
- Renovación con pago.
- Máquinas.
- Invitaciones y activación.
- Separación de información.
- Persistencia.
- Caída y recuperación de Redis.

Documentación:

- [Casos de prueba](docs/PRUEBAS.md)
- [Pruebas S2](docs/PRUEBAS-S2.md)
- [Pruebas S3](docs/PRUEBAS-S3.md)

### Validación automatizada

GitHub Actions ejecuta la comprobación de sintaxis PHP y la prueba aislada de caché.

- [Ejecuciones](https://github.com/DixonBM/GymFlow-grupo01/actions)
- [Workflow de validación](.github/workflows/php-lint.yml)

### Repetir la comprobación de caída de Redis

En el entorno local:

powershell
docker compose stop redis


Consultar el resumen y comprobar la lectura desde MongoDB.

Restablecer el servicio:

powershell
docker compose start redis
docker compose exec app php scripts/smoke.php


El smoke completo requiere Redis activo.

## Reproducción de la instalación desde cero

*Estado de esta parte de la entrega: ✅ COMPLETADO.*

El procedimiento de instalación puede repetirse para verificar la reproducibilidad de la entrega.

### Obtener una copia aislada

powershell
git clone https://github.com/DixonBM/GymFlow-grupo01.git GymFlow-S4-limpio
Set-Location .\GymFlow-S4-limpio
git rev-parse HEAD


Utilizar una carpeta nueva y un volumen propio, sin reutilizar la base de una instalación anterior.

Compose utiliza el nombre de la carpeta como nombre de proyecto, salvo que se configure otro mediante COMPOSE_PROJECT_NAME o -p.

### Ejecutar la instalación

Seguir los pasos de construcción, seed, migración y comprobación de este README.

La reproducción debe comprobar:

1. Inicio de los servicios.
2. Inicialización de esquemas e índices.
3. Carga de datos sintéticos.
4. Conexión con MongoDB y Redis.
5. Primary disponible del replica set.
6. Inicio de sesión.
7. Flujo socio, plan, membresía y pago.
8. Renovación con pago.
9. Máquinas.
10. Invitaciones.
11. Aislamiento entre gimnasios.
12. Persistencia después de reiniciar.

### Registrar una ejecución

Cada ejecución se identifica con:

- Persona que instala.
- Fecha y zona horaria.
- Sistema operativo.
- Versiones de herramientas.
- SHA del código probado.
- Entorno y volumen utilizados.
- Resultados.
- Incidencias y correcciones.
- Evidencias.
- Commit que incorpora el registro.

Para conservar salidas:

powershell
New-Item -ItemType Directory -Force .\docs\evidencias\S4

git rev-parse HEAD |
    Out-File -Encoding utf8 .\docs\evidencias\S4\commit-probado.txt

docker compose ps |
    Out-File -Encoding utf8 .\docs\evidencias\S4\servicios.txt

docker compose exec -T app php scripts/smoke.php 2>&1 |
    Tee-Object -FilePath .\docs\evidencias\S4\smoke.txt


Revisar el resultado de cada comando y conservar las evidencias sin claves, tokens ni información privada.

## Mediciones de rendimiento

*Estado de esta parte de la entrega: ✅ COMPLETADO.*

### Comparación del resumen

powershell
docker compose exec app php scripts/cache-benchmark.php


Compara lecturas directas de MongoDB con lecturas del resumen mediante Redis.

### Medición de tres operaciones

powershell
docker compose exec app php scripts/benchmark-s3.php 30


El informe se genera dentro del contenedor.

Para conservarlo en la copia local:

powershell
docker compose cp app:/var/www/html/docs/mediciones-S3.md docs/mediciones-S3.md


Los resultados dependen del equipo, el volumen de datos y las condiciones de ejecución.

Informe de referencia: [docs/mediciones-S3.md](docs/mediciones-S3.md).

## Operación y mantenimiento local

### Consultar servicios

powershell
docker compose ps


### Consultar logs

powershell
docker compose logs --tail=100 app
docker compose logs --tail=100 mongo
docker compose logs --tail=100 redis
docker compose logs --tail=100 mailpit


### Detener conservando MongoDB

powershell
docker compose down


### Volver a iniciar

powershell
docker compose up -d --wait --wait-timeout 240


MongoDB conserva sus datos en el volumen.

Redis puede comenzar vacío y reconstruir la caché. Mailpit no tiene almacenamiento persistente configurado.

### Reconstruir después de editar código

powershell
docker compose up --build -d --wait --wait-timeout 240


Compose copia el código a la imagen. No sincroniza automáticamente los archivos del host.

### Verificar migraciones

powershell
docker compose exec app php scripts/migrate-maquinas.php
docker compose exec app php scripts/migrate-invitaciones.php


### Diagnóstico frecuente

| Situación | Comprobación |
| --- | --- |
| Docker no conecta | Docker Desktop iniciado y contenedores Linux. |
| Compose no reconoce opciones | Actualizar Docker Desktop o Compose. |
| Puerto ocupado | Revisar 8000 y 8025 y detener la instalación propia anterior. |
| MongoDB no saludable | Consultar logs y revisar la inicialización del replica set. |
| Dirección de MongoDB incorrecta | Revisar .env y variables de la terminal. |
| Error de transacciones | Verificar replica set y primary disponible. |
| Redis no disponible | Revisar su servicio y comprobar el resumen desde MongoDB. |
| Clave del seed desconocida | Utilizar la guardada al crear la cuenta; el seed no la restablece. |
| Invitación no visible | Consultar Mailpit y logs de app. |
| Enlace de activación incorrecto | Revisar APP_URL y reenviar. |
| Activación vuelve al resumen | Utilizar una ventana privada sin sesión previa. |
| Ediciones no visibles | Reconstruir la imagen. |
| Construcción interrumpida | Revisar el error, conectividad y espacio de Docker. |

## Despliegue

*Estado de esta parte de la entrega: ✅ COMPLETADO.*

Aplicación:

https://grupo1.crnube.org/gymflow/

Documentación:

[docs/despliegue/virtualmin-atlas.md](docs/despliegue/virtualmin-atlas.md).

Automatización:

[.github/workflows/deploy-virtualmin.yml](.github/workflows/deploy-virtualmin.yml).

El workflow contempla:

- Validación de sintaxis PHP.
- Instalación de dependencias.
- Configuración SSH.
- Ejecución del despliegue remoto, de acuerdo con las autorizaciones del entorno.

El servidor utiliza la configuración correspondiente de:

- PHP y extensiones.
- Composer.
- MongoDB.
- Redis.
- SMTP.
- Variables privadas.
- Dirección pública de la aplicación.

El acceso al despliegue y las cuentas autorizadas son administrados por el grupo.

## Capturas del sistema

Las siguientes imágenes forman parte de las evidencias S3 conservadas en el repositorio.

### Resumen y caché Redis

![Resumen de GymFlow y caché Redis](docs/evidencias/S3/26-redis-dashboard-recuperado-hit.png)

### Registro de socios

![Registro de socio](docs/evidencias/S3/01-socio-registrado.png)

### Inventario de máquinas

![Registro de máquina](docs/evidencias/S3/09-maquina-registrada.exitosa.png)

### Administración de gimnasios

![Administración de gimnasios](docs/evidencias/S3/13-gym-creados-exitosamente.png)

## Estructura del repositorio

text
GymFlow-grupo01/
├── .github/
│   ├── ISSUE_TEMPLATE/
│   └── workflows/
│       ├── php-lint.yml
│       └── deploy-virtualmin.yml
├── src/
│   ├── Bootstrap.php
│   ├── GymService.php
│   ├── ReportCache.php
│   ├── MachineSchema.php
│   ├── MachineService.php
│   ├── InvitationService.php
│   ├── MailService.php
│   └── View.php
├── public/
│   ├── index.php
│   ├── router.php
│   ├── .htaccess
│   └── assets/
├── scripts/
│   ├── setup.php
│   ├── smoke.php
│   ├── unit-cache.php
│   ├── cache-benchmark.php
│   ├── benchmark-s3.php
│   ├── migrate-maquinas.php
│   └── migrate-invitaciones.php
├── docs/
│   ├── arquitectura/
│   ├── despliegue/
│   ├── evidencias/
│   ├── seguimientos/
│   ├── modelo-datos-v1.md
│   ├── PRUEBAS.md
│   ├── PRUEBAS-S2.md
│   ├── PRUEBAS-S3.md
│   └── mediciones-S3.md
├── data/
│   └── README.md
├── compose.yaml
├── Dockerfile
├── composer.json
├── composer.lock
├── .env.example
├── .gitignore
├── .dockerignore
├── portal.yml
├── CHANGELOG.md
├── README.md
└── LICENSE


## Documentación de la entrega

*Estado de esta parte de la entrega: ✅ COMPLETADO.*

| Documento | Contenido |
| --- | --- |
| [Arquitectura](docs/arquitectura/arquitectura-v1.md) | Componentes y responsabilidades. |
| [Modelo de datos](docs/modelo-datos-v1.md) | Diseño inicial de persistencia. |
| [Backlog](docs/backlog.md) | Organización funcional del trabajo. |
| [Pruebas](docs/PRUEBAS.md) | Casos de prueba. |
| [Pruebas S2](docs/PRUEBAS-S2.md) | Validaciones del segundo seguimiento. |
| [Pruebas S3](docs/PRUEBAS-S3.md) | Pruebas funcionales y recuperación. |
| [Mediciones S3](docs/mediciones-S3.md) | Resultados de rendimiento. |
| [Seguimiento 1](docs/seguimientos/S1.md) | Bitácora S1. |
| [Seguimiento 2](docs/seguimientos/S2.md) | Bitácora S2. |
| [Seguimiento 3](docs/seguimientos/S3.md) | Bitácora S3. |
| [Seguimiento 4](docs/seguimientos/S4.md) | Bitácora de entrega. |
| [Despliegue](docs/despliegue/virtualmin-atlas.md) | Virtualmin y Atlas. |
| [Datos sintéticos](data/README.md) | Descripción de los datos de demostración. |
| [CHANGELOG](CHANGELOG.md) | Historial de cambios. |
| [Ficha del portal](portal.yml) | Presentación pública del proyecto. |

Las bitácoras conservan el contexto de cada seguimiento. El código, el historial y esta documentación describen la entrega final.

## Versiones y trazabilidad

*Estado de esta parte de la entrega: ✅ COMPLETADO.*

La evolución del proyecto se conserva mediante:

- Commits.
- Ramas.
- Pull requests.
- Issues.
- Milestones.
- GitHub Projects.
- Etiquetas.
- Publicaciones.
- Bitácoras.
- Evidencias.

Referencias:

- [Historial de commits](https://github.com/DixonBM/GymFlow-grupo01/commits/main/)
- [Pull requests](https://github.com/DixonBM/GymFlow-grupo01/pulls)
- [Issues](https://github.com/DixonBM/GymFlow-grupo01/issues)
- [Tags](https://github.com/DixonBM/GymFlow-grupo01/tags)
- [Releases](https://github.com/DixonBM/GymFlow-grupo01/releases)
- [CHANGELOG](CHANGELOG.md)

La rama main contiene la integración del sistema.

## Equipo y licencia

### Equipo

Grupo 01:

- Dixon Bustos.
- María Fernanda Cruz Campos.
- José Sequeira.

### Contexto académico

Proyecto Final Open Source desarrollado para Tópicos Avanzados de Bases de Datos, Universidad Nacional.

### Licencia

Distribuido bajo la [licencia MIT](LICENSE).

### Estado final

*✅ PROYECTO COMPLETADO.*
