# Roles y permisos

| Rol | Administración | Datos académicos y publicación | Alcance clínico y documentos |
|---|---|---|---|
| Administrador | Todos los módulos, cuentas, contraseñas, roles, configuración y auditoría | Habilitación y toda la gestión académica; calcula, aprueba y publica | Todos los rotes, notas, actas y libretas |
| Coordinador | Autoriza el acceso de todas las cuentas no administrativas; no crea cuentas ni cambia contraseñas o roles ajenos | Completa fichas de internos, cohortes, oferta, periodos, decisiones y publicación | Seguimiento, solicitudes y reportes académicos |
| Interno | Contacto y contraseña propios | Preferencias en plazo; resultados publicados | Solicitudes, libreta, CSV/PDF y comprobantes propios |
| Tutor | Contacto, contraseña y disponibilidad propios | Sin facultad de publicación | Asistencia, guardias y evaluaciones de internos supervisados; documentos de su ámbito |

Las rutas verifican el rol y el estado de acceso en el servidor. El administrador accede a todos los módulos; los demás roles conservan los filtros por usuario/tutor. El Gate `download-report` permite al administrador consultar cualquier documento. Las validaciones de cupos, fechas, consentimiento e historial siguen aplicándose.

## Creación y habilitación

1. Administración crea la cuenta con nombre, correo, rol y estado activo. Puede definir una contraseña confirmada de al menos doce caracteres con letras y números. Si la deja vacía, el usuario la establece mediante activación OTP.
2. Coordinación o Administración abre **Gestión académica y hospitalaria → Habilitar cuentas → Completar datos**. Para internos completa cohorte, matrícula, promedio y méritos y marca **Habilitado académicamente**. Para tutores y coordinadores marca **Autorizar ingreso al portal**.
3. El usuario ingresa con su correo y contraseña. Todas las cuentas nuevas necesitan la aprobación, salvo administradores. La contraseña, la cuenta activa y la habilitación son condiciones independientes.

La importación de nóminas CSV se retiró, incluidas sus rutas. Los formularios reemplazan ese flujo. Las cuentas activas anteriores a la migración conservan su acceso; los internos que estaban pendientes de habilitación continúan pendientes.

## Contraseñas y sesiones

Administración cambia contraseñas desde la edición de Cuentas de usuario. Dejar los campos vacíos conserva la contraseña. Una clave nueva se almacena con hash, revoca sesiones y códigos OTP anteriores y registra el evento sin escribir la clave ni su hash en bitácora. Cada usuario también puede cambiar su propia clave desde Mi cuenta, verificando la actual.

Cambiar correo requiere una nueva contraseña definida por Administración o activación OTP del correo nuevo. Cambiar de rol revoca la habilitación anterior; el rol de administrador está exento de aprobación. Desactivar, eliminar o revocar la habilitación de una cuenta bloquea el ingreso y las sesiones existentes. El último administrador activo está protegido; no se permite eliminar la cuenta de la sesión actual.

## Eliminación e historial

Las ediciones de cuentas, habilitaciones, internos, tutores, cohortes, requisitos, hospitales, especialidades, convocatorias y plazas incluyen **Eliminar registro**, motivo obligatorio y confirmación. La eliminación es lógica: retira el registro de la gestión sin romper las referencias históricas. La bitácora conserva autor, fecha, recurso, ID, motivo y datos del registro, excluyendo contraseña y token de sesión. Los identificadores únicos de registros eliminados se conservan reservados.

Los registros eliminados no se pueden volver a editar; las cuentas quedan inactivas. Las propuestas deben recalcularse cuando cambien sus datos. Los rotes publicados y documentos históricos conservan sus referencias.

## Apariencia

Todos los roles y el login tienen selector claro/oscuro. La preferencia se guarda en el navegador; inicialmente respeta el tema del dispositivo. Los formularios se adaptan a pantallas pequeñas y las tablas extensas tienen desplazamiento horizontal dentro de su contenedor.
