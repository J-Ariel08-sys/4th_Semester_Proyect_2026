# Modelo de datos

Las migraciones son incrementales; no se recrea la base de trabajo.

| Área | Tablas | Relaciones e invariantes principales |
|---|---|---|
| Seguridad | users, sessions, otp_challenges, otp_limits | Correo normalizado único, matrícula única, rol/estado persistidos, OTP ligado a usuario/propósito/desafío, verificador HMAC y grant temporal |
| Académica | cohorts, academic_requirements, specialties, cycles | Usuarios/convocatorias pueden pertenecer a cohorte de La Paz; requisito único cohorte/especialidad; fechas y mínimos configurables |
| Oferta | hospitals, places, availabilities | Cupo por convocatoria/hospital, convenio hasta fin de bloque, tutor y límites; reserva ≤ capacidad |
| Postulación | applications, application_versions | Una postulación por usuario/convocatoria; preferencias y comprobante UUID; estructura de historial para modificaciones excepcionales |
| Rotes | optimization_runs, assignments, assignment_history | Snapshot/huella/UUID/estados separados, asignación única por interno/convocatoria; historial previo a cambios |
| Seguimiento | clinical_records, incidents, swap_requests | Registro único por día/asignación; valores/pesos de rúbrica; resolución; consentimiento y plazas esperadas de permuta |
| Guardias | shift_batches, shifts | Snapshot/estado técnico del cálculo; única fuente de planificación y cumplimiento; fecha inicio<fin |
| Decisiones | scenario_studies | Matriz, orientación/conversión, resultados, snapshot, parámetros, política y justificación |
| Documentos | reports | Snapshot y PDF privado, SHA-256, token QR, versión de ejecución y destinatario opcional; vigente/histórico |
| Operación | notifications, audit_logs, settings, jobs, failed_jobs, job_batches, cache, cache_locks | Notificaciones con enlace autorizado, auditoría con actor/fecha, colas separadas y payloads sensibles cifrados |

PostgreSQL aplica FKs, unicidades, validación de rol, capacidad/reserva, escalas de nota/promedio/mérito y orden de fechas. Restricciones sobre varias filas (supervisión, solapamientos, completitud) se verifican en servicios y transacciones con bloqueos de filas. No se afirma que un CHECK aislado pueda sustituirlas.

Los documentos congelan su versión; un cambio de asignación conserva la anterior en `assignment_history` y marca documentos previos históricos. Las propuestas y simulaciones nunca sobrescriben un plan publicado por el mero hecho de calcularse.
