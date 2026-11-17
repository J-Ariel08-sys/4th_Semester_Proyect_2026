# Roles y permisos

| Rol | Administración | Datos académicos y publicación | Alcance clínico y documentos |
|---|---|---|---|
| Administrador | Cuentas, roles compatibles, activación, configuración general, auditoría | Sin facultad automática de modificar notas, habilitación, oferta o aprobar | Sin acceso a libretas ni actas académicas |
| Coordinador | No crea roles ni cuentas desde gestión académica | Habilitación, cohortes, oferta, periodos, decisiones, cálculos, revisión, aprobación y publicación | Seguimiento, resolución de solicitudes y reportes de la cohorte |
| Interno | Contacto/contraseña propios | Preferencias en plazo; resultados publicados | Solicitudes, libreta, CSV/PDF y comprobantes propios |
| Tutor | Contacto/contraseña/disponibilidad propios | Sin facultad de publicación | Asistencia, guardias y evaluaciones de internos supervisados; reportes de ese ámbito |

Las rutas tienen middleware de rol; las consultas filtran usuario/tutor. El Gate `download-report` protege los archivos emitidos. Los endpoints de progreso son exclusivos de Coordinación. Ocultar menús no es el control de autorización.

No se elimina el último administrador activo. Los cambios de rol se rechazan si el historial académico resulta incompatible. Desactivar una cuenta o cambiar su correo/rol revoca sesiones; cambiar correo exige activación nueva. Se normaliza el correo y PostgreSQL impide duplicados incluso con diferencias de mayúsculas/espacios.

Importación: Administración crea cuentas sin habilitación; Coordinación actualiza antecedentes de cuentas de interno ya creadas. Vista previa conserva filas válidas, explica errores por fila y exige confirmación antes de persistir. La confirmación revalida concurrencia y es atómica; los errores posteriores a la vista previa requieren revisarla de nuevo.
