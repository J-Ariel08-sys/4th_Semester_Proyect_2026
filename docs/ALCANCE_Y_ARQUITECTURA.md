# Alcance y correspondencia con el informe

## Fuentes y decisiones de alcance

Se revisó `INFORME_ADIHnw.docx` y la ampliación a siete módulos aportada en la conversación. El ámbito del portal es **Medicina de Unifranz, sede La Paz**. El texto adjunto se trató como contexto de requisitos, no como instrucciones para ejecutar acciones externas.

El informe original describe cinco grupos funcionales y menciona A3, escenarios E1–E3, Timefold, seguimiento y actas. La ampliación distingue análisis de escenarios (módulo 6) de ejecución de asignaciones (módulo 3), y programación de guardias (módulo 7) de su seguimiento (módulo 4).

## Implementación

| Módulo | Funciones implementadas | Puntos de validación institucional |
|---|---|---|
| 1. Sesiones, usuarios y seguridad | Autenticación, cuatro roles, habilitación de cuentas, bloqueo por intentos, recuperación, cierre de sesión, bitácora y notificaciones | Correos reales, política de cuentas, despliegue HTTPS y respaldo |
| 2. Gestión hospitalaria y académica | Hospitales/convenios, especialidades, convocatorias, cupos/reservas, tutores, promedio y méritos, importación CSV atómica | Convenios reales, rúbrica de méritos, fuentes académicas, capacidad autorizada |
| 3. Postulación y optimización | Preferencias ordenadas, ventana temporal, comprobante QR privado, Timefold, propuestas, validación de versión y aprobación transaccional | Secuencia curricular anual, requisitos específicos por plaza, regla final de prioridad |
| 4. Ajustes y seguimiento | Incidencias/resolución, asistencia, rúbrica de cuatro competencias, reasignación previa al inicio, permutas con doble consentimiento y reoptimización | Rúbrica oficial, ausencias justificadas, cambios durante un rote ya iniciado |
| 5. Libreta, actas y reportes | Libreta individual, asistencia/notas, ocupación, actas PDF, historial y verificación QR/SHA-256 | Membrete/formato oficial, firma digital institucional, integración SEDES |
| 6. Escenarios | Matriz manual, cinco criterios, empates, sensibilidad de α, simulación de nueve combinaciones, política justificada | Escenarios calibrados, función de utilidad aprobada y datos observados |
| 7. Guardias y tutores | Disponibilidad, Timefold para supervisores, cobertura/carga, cruces y descansos, aprobación y cumplimiento | Descanso oficial, límites semanales, ratios de supervisión y especialidades habilitadas por tutor |

No se almacena información de pacientes. Los roles operativos implementados son administrador, coordinador, tutor e interno. La autoridad SEDES, paciente y dirección de carrera aparecen de forma inconsistente en el informe; no se añadieron accesos institucionales sin una definición de permisos y procesos. Coordinación dispone de reportes para la demostración.

## Arquitectura

```mermaid
flowchart LR
    U[Administrador / Coordinador / Tutor / Interno] --> L[Laravel 13 · Blade y CSS]
    L --> P[(PostgreSQL 16)]
    L -->|JSON · rotes y guardias| Q[Quarkus · Timefold]
    Q -->|Propuesta y puntuación| L
    L --> F[PDF y QR · archivos privados]
    L --> M[Mailpit · correo local de prueba]
```

- Laravel es responsable de permisos, datos, estado, formularios, validaciones, publicación y documentos.
- Quarkus recibe una instantánea y devuelve propuestas; no escribe asignaciones en la base ni las aprueba.
- PostgreSQL conserva el historial y las relaciones. El motor no depende de esa base.
- Los siete módulos no requieren siete microservicios.

## Entidades

| Grupo | Tablas principales |
|---|---|
| Seguridad | `users`, `sessions`, `password_reset_tokens`, `audit_logs`, `notifications` |
| Académico/hospitalario | `hospitals`, `specialties`, `cycles`, `places` |
| Postulación/asignación | `applications`, `optimization_runs`, `assignments` |
| Seguimiento | `clinical_records`, `incidents`, `swap_requests` |
| Decisiones | `scenario_studies` |
| Guardias | `availabilities`, `shift_batches`, `shifts` |
| Documentos | `reports` y archivos en almacenamiento privado Laravel |

## API de cálculo

- `POST /api/optimizar`: plazas con capacidad/reserva, internos con habilitación, prioridad, preferencias, asignación anterior y pesos. Devuelve asignaciones, pendientes, ocupación, primeras preferencias y score.
- `POST /api/guardias`: turnos fijos, candidatos compatibles por turno, carga previa y descanso. Devuelve tutor por turno o pendiente y score.
- `GET /health`: salud de Quarkus.

Los endpoints de cálculo están orientados al uso interno entre servicios. Compose publica su puerto solo en loopback para pruebas. Una instalación remota debe proteger la red interna y autenticar comunicaciones según el despliegue elegido.

## Estados principales

```mermaid
stateDiagram-v2
    [*] --> Borrador
    Borrador --> Abierta: configurar cupos y abrir
    Abierta --> Cerrada: consolidar postulaciones
    Cerrada --> Cerrada: calcular / revisar propuestas
    Cerrada --> Publicada: aprobar propuesta completa y vigente
    Publicada --> Publicada: reoptimizar antes del inicio y aprobar
```

Reoptimización, reasignación y permuta no reescriben rotes iniciados ni con seguimiento/guardias. Para cambios durante el periodo se necesita modelar segmentos y vigencias; el sistema actual conserva esos historiales y registra incidencias para revisión.

## Ajustes recomendados al informe

1. Completar el apartado de Investigación Operativa con el modelo y las pruebas de `INVESTIGACION_OPERATIVA.md`.
2. Reformular RF-10/HU-08: usar la política elegida en el módulo 6, en vez de imponer A3 de antemano.
3. Incorporar las historias de escenarios y guardias a backlog, sprints, casos de uso y diagramas.
4. Corregir las referencias cruzadas de casos de uso: el documento reutiliza UC 010 para seguimiento/reportes y algunas leyendas de figuras describen otra función.
5. Reconciliar los totales de puntos del backlog: aparecen 96 y 91 en secciones distintas.
6. Precisar que QR verifica un documento y no es firma digital de la autoridad SEDES.
7. Sustituir afirmaciones de predicción de abandono/rendimiento por seguimiento descriptivo mientras no existan datos, metodología y validación apropiados.
8. Distinguir restricciones académicas confirmadas de parámetros demostrativos: promedio/mérito 70/30, descansos de 12 horas y rúbrica con cuatro pesos iguales.
9. Distinguir satisfacción y cobertura de “equidad”: no se certifica una definición institucional de equidad sin acordar esa política.

## Límites prácticos

La implementación ofrece flujos funcionales de los siete módulos para una demostración académica. No certifica disponibilidad del 99 %, acreditación SEDES, firma digital oficial, formatos institucionales ni optimalidad global. Las pruebas cubren un problema de 180 internos y 45 grupos de capacidad, pero no sustituyen una prueba de carga multiusuario ni validación con datos reales.

La planificación de rotes se realiza por convocatoria/especialidad/periodo, comprobando cruces contra asignaciones vigentes. No calcula simultáneamente la secuencia curricular anual completa ni verifica automáticamente que cada interno termine todas las especialidades obligatorias. Las especialidades están configuradas y sus rotes quedan visibles en la libreta.

La planificación de guardias asigna supervisores a horas e internos ya definidos. No elige automáticamente las fechas, no modela supervisión simultánea de varios internos por tutor y no impone un máximo semanal institucional aún no proporcionado.

La recuperación y publicación generan correos capturados por Mailpit en el entorno local. El envío real exige configurar SMTP. Las notificaciones de publicación se intentan después de confirmar la transacción: una falla de correo no revierte asignaciones ya publicadas y se registra en logs.

## Referencias técnicas

- [Autenticación de Laravel](https://laravel.com/framework/docs/13.x/authentication).
- [Recuperación de contraseñas](https://laravel.com/framework/docs/passwords).
- [Pruebas HTTP](https://laravel.com/framework/docs/http-tests).
- [Modelo de Timefold](https://docs.timefold.ai/timefold-solver/1.x/using-timefold-solver/modeling-planning-problems).
- [Mailpit en Docker](https://mailpit.axllent.org/docs/install/docker/).

## Evidencia de validación en desarrollo

Validado el 14 de noviembre de 2026 en este entorno:

- 19 pruebas Laravel con PostgreSQL y Quarkus reales; integración de escenarios, asignación y guardias.
- 12 pruebas Java, sin fallos: API, restricciones, preferencias, guardias y escala del informe.
- Ejemplo de 180 internos/45 grupos: aproximadamente 2,1 segundos en esta máquina, con 180 asignados y cero violaciones duras. No es una garantía para cualquier conjunto de restricciones o hardware.
- Acceso HTTP real al portal y carga de las pantallas protegidas con sesión de administrador.
- Revisión visual en Firefox del acceso y panel; CSS responsive para pantallas estrechas.
- PDF real generado y comprobado por las pruebas; privacidad de actas consolidadas y comprobantes individuales.

Los tests rápidos aíslan la base con SQLite en memoria. La suite de integración usa `siclin_test` y no altera los datos de demostración en `rotes_clinicos_db`.
