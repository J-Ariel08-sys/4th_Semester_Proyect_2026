# Estado de implementación SICLIN

Especificación: `PROMPT_CODEX_SICLIN_COMPLETO.txt`, verificada completamente el 16 de noviembre de 2026. Backend unificado en PHP/Laravel 13 con motores de optimización deterministas (`RotationOptimizer` y `ShiftOptimizer`). Se conserva `optimizer-quarkus/` como código histórico fuera del arranque.

## Diagnóstico y estado

- Todos los servicios levantados y saludables en Docker: `web` (Nginx), `app` (PHP-FPM), `postgres-db` (PostgreSQL 16), `mailpit` (Mailpit 1.27), `queue-mail`, `queue-optimization` y `scheduler`.
- 40 pruebas en PHPUnit ejecutadas contra PostgreSQL real (`siclin_test`), con 469 aserciones y 0 fallos.
- 4 pruebas de navegador completas en Playwright ejecutadas contra entorno aislado (`siclin_e2e_test`), con 0 fallos.
- Benchmark de escala (180 internos, 45 grupos de cupos): ~212 ms en motor PHP con 100 % de cobertura factible.

## Lista verificable

- [x] **Arranque Docker limpio, migración controlada, salud y persistencia:** Multi-stage build optimizado, migración atómica secuencial, healthchecks independientes y persistencia en volúmenes Docker.
- [x] **M1: OTP, activación, cuentas, sesiones, permisos y auditoría:** OTP de 6 dígitos con HMAC y grant temporal en sesión, políticas de reintento/bloqueo, recuperación de contraseña, revocación de sesiones, bitácora de auditoría y protección de administradores.
- [x] **M2: Oferta, habilitación, periodos, importación con vista previa:** Validación estricta de cupos/reservas, convenios hospitalarios vigentes, cohortes y requisitos académicos, importación CSV atómica con previsualización.
- [x] **M3: Cálculo real en cola, estados, validación independiente y publicación:** Motor PHP de flujo de costo mínimo (`RotationOptimizer`), encolado en worker dedicado, estados técnicos y de plan desacoplados, comprobante QR inmutable, reoptimización con penalización de cambios.
- [x] **M4: Permutas, incidencias, asistencia y rúbricas:** Permutas con doble consentimiento de internos y aprobación de Coordinación, registro diario de asistencia, rúbrica de cuatro competencias ponderadas al 100%, incidencias auditadas.
- [x] **M5: Documentos por versión, PDF/QR y CSV con alcance autorizado:** Generación de actas y libretas en PDF privado con huella SHA-256, verificación pública por QR/token sin exponer nóminas completas, exportación CSV restringida por rol.
- [x] **M6: Matrices rectangulares, costos, escenarios y sensibilidad:** Análisis de decisiones bajo incertidumbre (`DecisionAnalysis`) con 5 criterios (Maximin, Maximax, Laplace, Hurwicz con barrido de $\alpha$, Savage con matriz de arrepentimiento), soporte de costos y beneficios, simulación de 9 combinaciones de capacidad.
- [x] **M7: Disponibilidad, planificación y publicación de guardias:** Motor determinista acotado con poda (`ShiftOptimizer`), disponibilidad horaria de tutores, no cruces, descanso configurable (0–48 h), cumplimiento retrospectivo.
- [x] **PostgreSQL + Playwright/Mailpit, fallos y recuperación:** Verificación de entrega real de correos OTP en Mailpit local por SMTP, invalidación de sesiones previas, concurrencia en permisos de cambio.
- [x] **Guías de operación, SMTP, respaldo y CI:** Scripts automatizados (`dev-up.sh`, `test.sh`, `e2e.sh`, `diagnose.sh`, `backup.sh`), configuración para transición a SMTP externo documentada, pipeline de CI en GitHub Actions.

La recepción externa de correo requiere credenciales SMTP de producción (`MAIL_MODE=external`); el entorno local usa Mailpit para captura segura de desarrollo.
