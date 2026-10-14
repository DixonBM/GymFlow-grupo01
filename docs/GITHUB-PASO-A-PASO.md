# Guía GitHub · El repositorio YA ESTÁ CREADO

**URL exacta:** https://github.com/DixonBM/GymFlow-grupo01

## 1. En la pantalla actual: invitar colaboradores

En la página **Code** del repositorio vacío aparece un botón **Invite collaborators** en una tarjeta derecha. Hacé clic, escribí los nombres de usuario de cada compañero y enviá las invitaciones. También podés hacerlo desde **Settings → Collaborators → Add people**.

Cada compañero debe **aceptar** su invitación y crear por lo menos **un commit suyo con una contribución real**. No basta que una sola cuenta suba todos los archivos.

## 2. Subir el proyecto desde VS Code (recomendado)

1. Descargá y **descomprimí** el ZIP. En VS Code seleccioná **File → Open Folder** y abrí `GymFlow-grupo01` (la carpeta donde está README.md).
2. Abrí Terminal → New Terminal. Si no reconociera `git`, instalá Git for Windows.
3. Ejecutá, en esa carpeta:

   ```bash
   git init
   git add .
   git status
   git commit -m "feat: GymFlow PHP con MongoDB Redis y documentos S1"
   git branch -M main
   git remote add origin https://github.com/DixonBM/GymFlow-grupo01.git
   git push -u origin main
   ```

4. Si Git solicita nombre/correo de autor, configurá los tuyos con `git config --global user.name "Tu nombre"` y `git config --global user.email "Tu correo verificado en GitHub"`; repetí el commit y push. Autenticá desde el navegador/Git Credential Manager si Git te lo pide. **No usés la contraseña de la cuenta en el terminal**.
5. Recargá la página de GitHub. Deberías ver `README.md`, `LICENSE`, `.gitignore`, `public/`, `src/`, `scripts/`, `docs/` y `compose.yaml`.

**No uses** los comandos de "create a new repository" mostrados por GitHub para generar un `README.md` nuevo: el ZIP ya trae el contenido. **No subás únicamente el `.zip` al repositorio** ni `.env`, `vendor/` o contraseñas.

### Alternativa con navegador

En el repositorio vacío hacé clic en **uploading an existing file** (debajo de **Quick setup**) y arrastrá **los archivos y carpetas extraídos**, no el ZIP. Confirmá que también quedaron `.github/` y `.gitignore`, que pueden ocultarse en Windows. Finalmente usá **Commit changes**. VS Code + Git es más confiable para conservar archivos ocultos y commits futuros.

## 3. Colaboradores y commits

Cada compañero puede clonar con:

```bash
git clone https://github.com/DixonBM/GymFlow-grupo01.git
cd GymFlow-grupo01
```

Luego edita o agrega una mejora, verifica su propio usuario de Git, y ejecuta `git add .`, `git commit -m "docs: contribución propia"` y `git push`. Si varias personas trabajan a la vez, usar ramas y pull requests para evitar conflictos. Confirmar contribuciones en la pestaña **Commits**.

## 4. Crear Milestone e Issues

1. Repo → **Issues** → **Milestones** → **New milestone**.
2. Título: `Seguimiento 1 - Semana 13`; descripción: `Problema, arquitectura, datos, repositorio y PING MongoDB/Redis`; **Create milestone**.
3. Volver a **Issues** → **New issue** y crear las tareas 1 a 8 de [`docs/backlog.md`](backlog.md).
4. En cada Issue, seleccionar **Assignees** con un compañero y **Milestone** con `Seguimiento 1 - Semana 13`. La plantilla `.github/ISSUE_TEMPLATE/tarea.md` ya está incluida.

## 5. Probar la aplicación y documentar evidencias

En VS Code y con Docker Desktop instalado:

```bash
docker compose up --build -d
docker compose exec app php scripts/setup.php --seed
docker compose exec app php scripts/smoke.php
```

- Accedé a `http://localhost:8000`, registrá datos ficticios y comprobá que persisten en MongoDB.
- Abrí **Conexión BD**: deben responder MongoDB y Redis.
- Abrí dos veces el dashboard y observá `MISS` y `HIT`. Probá detener Redis y volver a abrir: MongoDB mantiene los datos.
- Guardá capturas reales SIN credenciales en `docs/evidencias/`, actualizá `docs/seguimientos/S1.md` y subí esos archivos con un nuevo commit.

## 6. Crear etiqueta del Seguimiento 1

Solo después de que el grupo haya probado y actualizado la bitácora:

```bash
git tag -a v0.1.0 -m "Seguimiento 1 - GymFlow Grupo 01"
git push origin v0.1.0
```

En GitHub se puede verificar desde **Tags**. El profesor pide tag `v0.1.0`; el release de versión final es otro entregable posterior.

## 7. Lo que debe estar listo para entregar

- [ ] Repo público y licencia MIT.
- [ ] Collaborators aceptados y al menos un commit por integrante.
- [ ] README, arquitectura, modelo v1, código y datos sintéticos.
- [ ] Issues reales con responsables y un milestone S1.
- [ ] PING real a **MongoDB y Redis**, más prueba funcional de persistencia.
- [ ] Tag `v0.1.0` y bitácora S1 con evidencia auténtica.

**Importante:** no se ha realizado desde ChatGPT el push, la asignación de personas ni la conexión real a Atlas. Se deben realizar y verificar en el equipo de ustedes.
