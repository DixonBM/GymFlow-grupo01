# Pruebas funcionales — Seguimiento 2

**Proyecto:** GymFlow · Grupo 01  
**Responsable:** María Fernanda Cruz Campos  
**Fecha del reporte y revisión de evidencias:** 16 de octubre de 2026  
**Entorno:** aplicación web del servidor universitario: https://grupo1.crnube.org/gymflow/  
**Versión o commit desplegado:** pendiente de identificar.

## 1. Alcance

Se realizaron pruebas manuales de gimnasios, socios, planes, membresías y pagos. Los resultados se documentan a partir del reporte de la responsable y de diez capturas revisadas. La responsable también indicó que salió y volvió a ingresar, y los datos continuaban disponibles.

«Aprobada» se refiere al caso descrito, no a la validación completa del módulo. Los registros de pagos son manuales: no representan una integración ni una transferencia bancaria real. No se ejecutaron desde esta revisión consultas directas a MongoDB, pruebas de Redis ni mediciones de rendimiento.

## 2. Resultados

| ID | Prueba | Resultado esperado | Resultado observado | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- |
| PF-01 | Registrar gimnasio | Guardar y mostrar el gimnasio | Aparece George Angulo en el listado, con estado Activo, junto a Hércules Demo y Titan Demo. La responsable reportó que la operación funcionó. | Aprobada | [09](evidencias/S2/09-gimnasio-registrado.png) |
| PF-02 | Registrar socio | Guardar y mostrar el socio | El socio aparece Activo en Hércules Demo; se muestran siete registros. La responsable confirmó su creación. | Aprobada | [01](evidencias/S2/01-socio-registrado.png) |
| PF-03 | Editar socio | Guardar cambios sin crear un registro adicional | Se agregó el segundo nombre al socio, se mantuvo su identificación y el total de siete registros. Aparece mensaje de guardado correcto. | Aprobada | [01](evidencias/S2/01-socio-registrado.png), [02](evidencias/S2/02-socio-editado.png) |
| PF-04 | Crear plan | Guardar las condiciones del plan | Plan Anual, 365 días, ₡300.000,00, Activo; listado con tres planes. La responsable confirmó su creación. | Aprobada | [03](evidencias/S2/03-plan-registrado.png) |
| PF-05 | Editar precio del plan | Actualizar el precio sin duplicar el plan | El precio del Anual cambió a ₡400.000,00; conserva duración, estado y total de tres planes. Se muestra confirmación de guardado. | Aprobada | [03](evidencias/S2/03-plan-registrado.png), [04](evidencias/S2/04-plan-monto-editado.png) |
| PF-06 | Consultar membresía anual creada | Mostrar socio, plan, período, precio y estado | Se observa la Anual del 16/10/2026 al 16/10/2027 por ₡300.000,00, Vigente. | Aprobada | [05](evidencias/S2/05-membresia-anual-vigente.png) |
| PF-07 | Cancelar membresía | Conservar historial con estado Cancelada | La Anual pasa de Vigente a Cancelada, conservando sus fechas y precio. | Aprobada | [05](evidencias/S2/05-membresia-anual-vigente.png), [06](evidencias/S2/06-membresia-cancelada-y-nueva.png) |
| PF-08 | Crear membresía mensual | Guardar un nuevo período con su precio | Aparece una Mensual del 16/10/2026 al 15/11/2026 por ₡20.000,00, Pendiente de pago. El total pasa de seis a siete contrataciones; se muestra confirmación de guardado. | Aprobada | [06](evidencias/S2/06-membresia-cancelada-y-nueva.png) |
| PF-09 | Registrar pago manual | Mostrar pago asociado a la membresía | Pago Anual por ₡300.000,00, del 16/10/2026, método sinpe_movil y estado Registrado. La responsable confirmó el registro. | Aprobada | [07](evidencias/S2/07-pago-registrado.png) |
| PF-10 | Anular pago con motivo | Cambiar estado y conservar historial y motivo | El pago cambia a Anulado y muestra el motivo «no llego simpe movil». Se mantienen cuatro movimientos y aparece confirmación de guardado. | Aprobada | [07](evidencias/S2/07-pago-registrado.png), [08](evidencias/S2/08-pago-anulado.png) |
| PF-11 | Salir y volver a ingresar | Mantener disponibles los datos guardados | La responsable indicó que los datos continuaban disponibles al volver a ingresar. | Aprobada según reporte manual | Sin secuencia visual específica |

| PF-12 | Crear administrador para el nuevo gimnasio | Guardar usuario con rol administrador, gimnasio asignado y estado Activo | Aparece Administrador George Angulo, con rol administrador, asignado a George Angulo y estado Activo. La interfaz muestra confirmación de guardado correcto. | Aprobada | [10](evidencias/S2/10-administrador-nuevo-gimnasio.png) |

La responsable no reportó errores durante estas operaciones. Los mensajes de guardado corresponden a lo mostrado por la interfaz y no sustituyen la inspección directa de documentos en MongoDB.

## 3. Observaciones

- La captura 05 muestra una membresía contratada por ₡300.000,00 mientras el selector ofrece el plan Anual a ₡400.000,00. Esto es consistente con conservar las condiciones contratadas del registro existente.
- Las capturas 05 y 06 respaldan cancelación y creación de una membresía distinta; no demuestran el uso de la opción de renovación automática.
- La membresía mensual sigue apareciendo en el selector de membresías sin pago en las capturas 07 y 08. No se acredita su pago.
- La captura 09 muestra un gimnasio registrado y la opción Editar; por sí sola no acredita una edición realizada. Falta precisar qué campo del gimnasio se editó y su resultado.
- No se comprobó con estas capturas el ajuste de ingresos del dashboard después de anular el pago.

- La captura 10 acredita la creación del administrador y su asignación al gimnasio George Angulo. No demuestra todavía el inicio de sesión con esa cuenta ni la restricción efectiva de acceso a otros gimnasios. El selector del formulario vacío muestra Hércules Demo, pero la fila guardada identifica George Angulo como gimnasio asignado al nuevo usuario.

## 4. Evidencias

Guardar el documento en `docs/PRUEBAS-S2.md` y las diez capturas en `docs/evidencias/S2/`, usando exactamente estos nombres para que funcionen los enlaces:

| Captura | Nombre de archivo |
| --- | --- |
| Socio registrado | `01-socio-registrado.png` |
| Socio editado | `02-socio-editado.png` |
| Plan registrado | `03-plan-registrado.png` |
| Monto del plan editado | `04-plan-monto-editado.png` |
| Membresía anual vigente | `05-membresia-anual-vigente.png` |
| Membresía cancelada y nueva mensual | `06-membresia-cancelada-y-nueva.png` |
| Pago registrado | `07-pago-registrado.png` |
| Pago anulado | `08-pago-anulado.png` |
| Gimnasio registrado | `09-gimnasio-registrado.png` |
| Administrador del nuevo gimnasio | `10-administrador-nuevo-gimnasio.png` |

Antes de publicar las capturas, ocultar identificaciones, teléfonos, correos y referencias de pago si corresponden a datos reales, incluidos los de otras personas visibles. Las imágenes se agregan por separado; este archivo contiene enlaces relativos, no las imágenes originales.

## 5. Pruebas pendientes

| Caso | Resultado esperado | Resultado obtenido | Estado |
| --- | --- | --- | --- |
| Editar gimnasio | Guardar y mostrar el campo modificado | | Pendiente de precisar y documentar |
| Dar de baja lógicamente un socio | Conservar historial y mostrar estado Inactivo | | Pendiente |
| Registrar identificación duplicada en el mismo gimnasio | Rechazar el duplicado | | Pendiente |
| Enviar campos vacíos o valores inválidos | Mostrar validación sin guardar datos inválidos | | Pendiente |
| Iniciar sesión con el nuevo administrador | Acceder con la nueva cuenta al gimnasio George Angulo | | Pendiente |
| Iniciar sesión con contraseña incorrecta | Rechazar el acceso | | Pendiente |
| Registrar segundo pago efectivo de una misma membresía | Rechazar el duplicado | | Pendiente |
| Verificar ingresos del dashboard tras anulación | Excluir el pago anulado | | Pendiente |
| Usar renovación automática | Crear período correcto sin superposición | | Pendiente |
| Probar acceso cruzado entre gimnasios con rol administrador | Impedir consultas y modificaciones no autorizadas | | Pendiente |
| Verificar Redis MISS, HIT e invalidación | Usar caché y actualizar indicadores tras escrituras | | Pendiente |
| Simular caída de Redis en entorno local | Continuar consulta desde MongoDB | | Pendiente |
| Simular caída de MongoDB en entorno local | Mostrar error sin confirmar guardado fallido | | Pendiente |
| Ejecutar carga sintética y prueba negativa de JSON Schema | Crear datos reproducibles y rechazar documentos inválidos | | Pendiente |

Las pruebas de fallos se realizarán en entorno local de pruebas, sin detener servicios compartidos del curso. Parte de estos casos prepara el Seguimiento 3.

## 6. Trazabilidad y cierre

- [ ] Identificar versión o commit desplegado durante las pruebas.
- [ ] Subir las diez capturas revisadas a la ruta indicada y comprobar los enlaces.
- [ ] Agregar enlace al Issue y al commit de este aporte después de publicarlos.
- [ ] Completar las pruebas pendientes con resultados reales.

Este registro es un aporte de pruebas funcionales; no reemplaza la bitácora grupal `docs/seguimientos/S2.md` ni acredita por sí solo todos los requisitos de S2.

Se utilizó IA para organizar la documentación y revisar las capturas. La ejecución manual y los resultados reportados corresponden a la responsable indicada. No se presentan pruebas pendientes como ejecutadas.
