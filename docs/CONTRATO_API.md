# Contratos internos y HTTP

Desde la decisión de usar exclusivamente Laravel no existe una API Laravel–Quarkus ni secreto de servicio requerido. El motor PHP se ejecuta en el worker `optimization`; los datos académicos permanecen en PostgreSQL. Los contratos siguientes son llamadas PHP verificadas por pruebas; no se exponen públicamente.

## Rotes

`RotationOptimizer::solve(array $input): array` recibe:

```json
{
  "plazas": [{"id":"1","capacidad":2,"reserva":0,"tutorId":"4"}],
  "tutores": {"4":2},
  "internos": [{"id":"10","habilitado":true,"merito":85,"preferencias":["1"],"plazaAnterior":null}],
  "politica": {"pesoPreferencias":10,"pesoMerito":8,"pesoEquilibrio":0,"pesoCambios":5},
  "segundos":10
}
```

`RotationService::input()` añade la versión de calendario y requisitos. Identificadores normalizados como strings; no se envían correos/contraseñas al motor. La ejecución registra UUID, SHA-256 de entrada, snapshot, duración, estado, diagnóstico y versión del motor en el resultado.

Respuesta: `asignaciones[{internoId,plazaId,preferencia}]`, `pendientes[{internoId,motivo}]`, `ocupacion`, `hardScore`, `mediumScore`, `softScore`, `engine`, `duration_ms`, `search_complete`, `capacidadUtilizable`, `primerasPreferencias`. `RotationService::validateResult` es independiente del algoritmo.

HTTP de Coordinación:

- POST `/rotes/{cycle}/optimizar`, formulario `study_id`, devuelve redirección con ID de ejecución; no espera al cálculo.
- GET `/propuestas/{run}/estado`: JSON con estado técnico y del plan.
- POST `/propuestas/{run}/revision`, `/aprobar`, `/publicar`: transiciones humanas y auditadas.
- POST `/propuestas/{run}/reintentar` o `/cancelar`: control autorizado de ejecución.

Estados técnicos: `en_cola`, `ejecutando`, `completada`, `fallida`, `cancelada`. Plan: `borrador`, `calculado`, `en_revision`, `aprobado`, `publicado`, `sustituido`. Publicar vuelve a validar snapshot, obligaciones y cupos dentro de una transacción. Un reintento no duplica la persistencia.

## Guardias y escenarios

`ShiftOptimizer::solve`: turnos identificados con interno, inicio/fin Unix, tutores permitidos; carga previa y disponibilidad en minutos; descanso y segundos. Devuelve tutor o pendiente por turno, diagnóstico de búsqueda y puntuaciones. `ShiftPlanner` filtra disponibilidad/periodo y valida nuevamente antes de publicar.

POST `/guardias/generar` registra lote/instantánea y encola. GET `/guardias/lotes/{batch}/estado` consulta progreso. POST `/guardias/lotes/{batch}/aprobar` valida vigencia, cobertura y descansos.

POST `/escenarios/simular` registra estudio/snapshot y encola. GET `/escenarios/{study}/estado` consulta progreso. POST `/escenarios` evalúa una matriz manual pequeña; POST `/escenarios/{study}/elegir` guarda decisión humana. `DecisionAnalysis::calculate(matrix,alpha,orientation)` implementa criterios en PHP, con orientación `benefits` o `costs`.

Todas las mutaciones web usan autenticación, sesión y CSRF excepto inicio/OTP, que siguen siendo formularios protegidos por CSRF. Acceso directo a endpoints con rol incorrecto devuelve 403. `/up` es salud HTTP; `siclin:health` comprueba PostgreSQL y tablas necesarias sin exponer configuración.
