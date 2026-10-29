# Pruebas S3 — Casos normales, conexión y datos inválidos

**Proyecto:** GymFlow — Grupo 01  
**Responsable de pruebas funcionales:** María Fernanda Cruz Campos  
**Fecha de documentación:** 29 de octubre de 2026  
**Issues:** [#10](https://github.com/DixonBM/GymFlow-grupo01/issues/10) y [#11](https://github.com/DixonBM/GymFlow-grupo01/issues/11)  
**Aplicación de referencia:** https://grupo1.crnube.org/gymflow/  
**Fecha de ejecución y commit desplegado:** pendientes de consignar por la responsable.

## 1. Alcance y criterio de evidencia

Se documentan 16 casos funcionales: 15 documentados en el inventario de capturas (siete casos normales y ocho validaciones) y una comprobación de conservación de datos tras recargar, confirmada verbalmente por la responsable. Se trabaja desde la interfaz; no se cuenta con acceso directo a MongoDB ni al control de Redis del servidor. Un compañero administra Redis en Docker y el profesor administra el servicio del servidor.

Este documento se preparó con la vista de carpeta compartida, no con las 15 imágenes originales a resolución completa. Los resultados se registran como **reportados, pendientes de cotejo de evidencia**, sin inventar valores ni transcribir mensajes ilegibles. Antes de cerrar el issue, la responsable debe abrir cada original y confirmar que sustenta el resultado indicado. Los pasos describen el caso que debe corresponder a cada evidencia; deben ajustarse si la ejecución real fue distinta.

La aceptación de un caso no acredita todo el módulo. Si el navegador bloqueó el envío, debe registrarse como validación del formulario; no demuestra por sí sola una validación del servidor. Ver un registro en pantalla tampoco equivale a una consulta directa de MongoDB.

## 2. Casos documentados

| ID | Caso | Procedimiento a cotejar | Resultado esperado | Resultado reportado | Evidencia |
| --- | --- | --- | --- | --- | --- |
| S3-N01 | Registrar socio | Completar los campos del socio y guardar. | El socio aparece en el listado. | Registro exitoso reportado. | [01-socio-registrado](evidencias/S3/01-socio-registrado.png) |
| S3-N02 | Modificar socio | Abrir el socio registrado, modificar un campo y guardar. | El mismo socio muestra el cambio. | Modificación exitosa reportada. | [02-socio-registrado-modificado](evidencias/S3/02-socio-registrado-modificado.png) |
| S3-N03 | Registrar plan | Completar nombre, duración y precio válidos; guardar. | El plan aparece con sus condiciones. | Registro exitoso reportado. | [03-plan-registrado-exitoso](evidencias/S3/03-plan-registrado-exitoso.png) |
| S3-I01 | Socio duplicado | Intentar registrar otra vez la identificación en el mismo gimnasio. | Se rechaza el duplicado. | Rechazo reportado. | [03-socio-duplicado-rechazado](evidencias/S3/03-socio-duplicado-rechazado.png) |
| S3-I02 | Duración inválida de plan | Ingresar una duración no permitida e intentar guardar. | No permite guardar una duración fuera de 1 a 3650 días. | Validación de duración reportada; valor exacto pendiente de transcribir. | [05-plan-dias-invalido](evidencias/S3/05-plan-dias-invalido.png) |
| S3-I03 | Precio inválido de plan | Ingresar un precio no permitido e intentar guardar. | Rechaza el precio inválido. | Validación de precio reportada; valor exacto pendiente de transcribir. | [06-plan-precio-invalido](evidencias/S3/06-plan-precio-invalido.png) |
| S3-I04 | Membresía superpuesta | Intentar crear una membresía para un socio con un período coincidente, sin renovación. | Rechaza la superposición. | Rechazo de superposición reportado. | [07-membresia-superpuesta](evidencias/S3/07-membresia-superpuesta.png) |
| S3-N04 | Registrar membresía | Seleccionar socio y plan activos, indicar fecha válida y guardar. | La membresía aparece en el historial con sus condiciones. | Registro exitoso reportado. | [08-membresia-registrada-exitosa](evidencias/S3/08-membresia-registrada-exitosa.png) |
| S3-N05 | Registrar máquina | Completar código y nombre válidos y guardar. | La máquina aparece en el listado. | Registro exitoso reportado. | [09-maquina-registrada.exitosa](evidencias/S3/09-maquina-registrada.exitosa.png) |
| S3-I05 | Código de máquina inválido | Intentar guardar una máquina con código de formato no permitido. | Rechaza el código inválido. | Validación de código reportada; valor exacto pendiente de transcribir. | [10-maquina-codigo-invalido](evidencias/S3/10-maquina-codigo-invalido.png) |
| S3-N06 | Registrar usuario | Completar el formulario de usuario con datos válidos y guardar. | El usuario aparece en el listado. | Registro exitoso reportado; no acredita activación ni acceso con esa cuenta. | [11-usuario-registrado-exitoso](evidencias/S3/11-usuario-registrado-exitoso.png) |
| S3-I06 | Usuario duplicado | Intentar crear un usuario con correo ya registrado. | Rechaza el duplicado. | Rechazo reportado. | [12-usuario-duplicado-rechazado](evidencias/S3/12-usuario-duplicado-rechazado.png) |
| S3-N07 | Registrar gimnasio | Completar nombre, correo y demás campos necesarios; guardar. | El gimnasio aparece en el listado. | Registro exitoso reportado. | [13-gym-creados-exitosamente](evidencias/S3/13-gym-creados-exitosamente.png) |
| S3-I07 | Gimnasio sin nombre | Dejar el nombre comercial vacío e intentar guardar. | Exige completar el nombre y no guarda el registro. | Caso de campo obligatorio reportado; confirmar el mensaje en la captura original. | [14-crear-gym-sin-nombre](evidencias/S3/14-crear-gym-sin-nombre.png) |
| S3-I08 | Credenciales incorrectas | Intentar iniciar sesión con credenciales incorrectas. | Rechaza el acceso. | Rechazo reportado. | [15-credenciales-incorrectas](evidencias/S3/15-credenciales-incorrectas.png) |

**Estado de las 15 filas con captura:** reportado; pendiente de cotejo con captura original. Se mantienen los nombres del inventario, incluido el doble prefijo `03`. Los enlaces asumen extensión `.png`: comprobar nombres, puntos y extensión real al subir los archivos.

### Comprobación adicional confirmada por la responsable

| ID | Prueba | Procedimiento realizado | Resultado esperado | Resultado obtenido | Estado / evidencia |
| --- | --- | --- | --- | --- | --- |
| S3-N08 | Conservación de datos tras recargar | Guardar los datos y recargar la página | Los datos permanecen visibles al consultar nuevamente | La responsable confirmó que recargó la página y los datos se conservaron correctamente | Aprobada según confirmación de la responsable; sin captura adicional |

Esta comprobación acredita conservación y recuperación de datos desde la aplicación, cuyo almacenamiento principal es MongoDB. No se efectuó una inspección directa de documentos en la base de datos.

## 3. Conexión, caché y recuperación

**Funcionamiento actual:** la responsable reporta que MongoDB y Redis funcionan correctamente. La aplicación conserva los datos después de recargar. Redis está administrado por el profesor; su funcionamiento se registra como reportado, sin afirmar que se ejecutaron comandos de diagnóstico o pruebas de caída.

| ID | Prueba | Resultado esperado | Resultado obtenido | Estado / responsable por coordinar |
| --- | --- | --- | --- | --- |
| S3-C01 | Consultar Conexión BD | Mostrar estado real de MongoDB y Redis | Funcionamiento correcto reportado por la responsable; conservación de datos comprobada desde la interfaz | Reportado; sin diagnóstico directo ni captura específica de Conexión BD |
| S3-C02 | Consultar dashboard tras expiración y recargar dentro del TTL | Observar MISS y luego HIT | Sin evidencia S3 aportada | Pendiente de ejecución/documentación |
| S3-C03 | Crear socio y volver al dashboard | Actualizar el resumen por invalidación de caché | Sin evidencia S3 aportada | Pendiente de ejecución/documentación |
| S3-F01 | Interrumpir Redis con MongoDB disponible | Mantener consultas del dashboard y guardado de datos | No ejecutada por la responsable | Coordinar con compañero en Docker o profesor en servidor |
| S3-F02 | Restaurar Redis | Recuperar conexión/caché y comprobar resumen actualizado | No ejecutada por la responsable | Coordinar con quien ejecute S3-F01 |
| S3-F03 | Interrumpir MongoDB y luego restaurar | Mostrar error sin confirmación falsa; recuperar funcionamiento | No ejecutada por la responsable | Requiere control del ambiente de pruebas |

Las evidencias históricas de S1/S2 son antecedentes y no sustituyen una ejecución de S3. Las pruebas en Docker deben identificar ese ambiente; no se presentan como comprobaciones del servidor universitario.

## 4. Registro y cierre

Para cada captura confirmar: fecha, ambiente, dato utilizado, mensaje o resultado real y estado final (aprobada, fallida o pendiente). Si una validación permitió guardar datos incorrectos, registrar el hallazgo como fallo y enlazar su issue; no cambiar el resultado para ajustarlo al esperado.

- [ ] Cotejar los 15 originales y completar valores exactos de los casos inválidos.
- [ ] Confirmar el ambiente, la fecha de ejecución y el commit desplegado.
- [ ] Subir los originales a `docs/evidencias/S3/` y comprobar todos los enlaces.
- [ ] Si se requiere una comprobación técnica de conexión, anexar captura de Conexión BD o diagnóstico del administrador. El estado actual se registra según reporte de la responsable.
- [ ] Incorporar caída y recuperación de Redis para cerrar el issue #11.

No se añaden pagos, anulaciones ni persistencia tras reiniciar sesión como pruebas de S3 porque no aparecen en el inventario entregado. Sí se incorpora la conservación de datos tras recargar, confirmada por la responsable. Las pruebas de S2 conservan su propio registro.

Se utilizó IA para organizar la documentación a partir del inventario aportado. La ejecución y validación de resultados corresponden a los integrantes del grupo.
