# Presentar SICLIN por roles

## Preparación (antes de la exposición)

Desde la raíz del repositorio:

```bash
./scripts/demo-up.sh
cat .local/presentation-accounts.json
```

Abre http://localhost:8010 y, en otra pestaña, http://localhost:8030 (Mailpit). Todas las cuentas demo preparadas comparten la contraseña **`SiclinDemo2026!`**: `admin@siclin.test`, `coordinador@siclin.test`, `tutor@siclin.test`, `tutor2@siclin.test`, `interno@siclin.test`, `interno2@siclin.test`, `interno3@siclin.test` e `interno.clinico@siclin.test`. El comando de preparación restaura esas credenciales cada vez que se ejecuta y conserva el progreso. `invitado@siclin.test`, creada durante el recorrido, es distinta: su contraseña se establece mediante activación OTP. No muestres el archivo privado de cuentas al proyectar. Los datos son ficticios; los formularios sí guardan cambios y ejecutan los servicios reales.

Usa perfiles de navegador distintos si quieres mantener varios roles abiertos. Varias pestañas del mismo perfil comparten sesión: para cambiar de rol, pulsa **Cerrar sesión** y vuelve a ingresar. La cookie de demo es independiente de la instancia de trabajo.

En cada rol, **Guía de demostración** muestra las instrucciones correspondientes. Un segundo `demo-up.sh` conserva postulaciones, notas y decisiones; restaura las claves de las ocho cuentas originales y conserva las claves de las cuentas adicionales. Para repetir desde cero, o si las fechas ya vencieron:

```bash
./scripts/demo-up.sh --reset
cat .local/presentation-accounts.json
```

Solo se reinicia la base `siclin_demo` del proyecto Docker `siclin-demo`. Se guarda un dump previo en `.local/demo-backups/`. El archivo de cuentas se vuelve a generar con la contraseña común indicada arriba. Los datos del puerto 8000 se conservan. El reinicio no elimina el historial del buzón de Mailpit: usa siempre el correo más reciente y comprueba el destinatario. Los códigos anteriores quedan inválidos al reiniciar la base.

## Datos preparados

| Caso | Estado inicial | Qué demostrar |
|---|---|---|
| Medicina Interna | Abierta, empieza en 7 días, dos hospitales, 3 plazas menos 1 reserva por hospital | Ana postula; Coordinación simula, calcula, revisa y publica |
| Diego y Valeria | Ya postularon; prefieren B y A respectivamente | Coordinación puede calcular incluso antes de que Ana postule |
| Ana | Sin postulación | Postulación en vivo con Hospital A primero y B segundo |
| Camila, Cirugía | Rote publicado desde hace 3 días hasta dentro de 2 días | Asistencia, evaluación, incidencias y libreta |
| Guardia de Camila | Finalizó hace 4 horas al preparar los datos; sin confirmar | Tutor registra cumplimiento |
| Estudio «valores hipotéticos» | Sin política elegida | Explicar a mano los cinco criterios; separado de la simulación calculada |

El rote retrospectivo y la guardia de Camila son datos iniciales de demostración. La asignación se calcula con el motor PHP; el seeder carga su publicación y el turno directamente para poder mostrar seguimiento sin esperar una semana. **La prueba del proceso de aprobación/publicación se hace con Medicina Interna**, usando los botones normales.

## Datos ampliados: apartados listos para mostrar

`demo-up.sh` incorpora esta ampliación una sola vez, sin borrar los casos anteriores ni duplicar registros. Busca el prefijo **DEMO ampliada**. Agrega 20 cuentas, 2 cohortes, 10 requisitos, 3 hospitales, 7 convocatorias, 58 postulaciones, 36 asignaciones publicadas, 60 registros de seguimiento, 4 incidencias, 3 guardias, una permuta consentida y 7 PDF. También calcula cinco propuestas de asignación y tres estudios de nueve combinaciones; incluye un ejercicio manual adicional de costos, identificado como hipotético.

Las fechas son relativas al día de la primera carga. Los expedientes históricos y en curso son ejemplos retrospectivos: sus asignaciones se calculan con el motor PHP y su publicación se carga como dato inicial para poder mostrar seguimiento. La convocatoria futura de Ginecología sí pasa por los servicios normales de aprobación y publicación. Las notas, los escenarios, la planificación de guardias y los PDF utilizan los servicios reales.

| Apartado y rol | Qué abrir | Qué mostrar o hacer en vivo |
|---|---|---|
| Gestión académica · Coordinación | Cohortes A/B, Requisitos, Internos, Hospitales | Trayectoria clínica y planificación pendiente; habilitar a Alex; comparar centros activos con Centro E inactivo y convenio vencido |
| Postulación y rotes · Interno/Coordinación | **Oferta abierta · Pediatría** | Seis postulaciones preparadas. Daniela (`interno.demo07`) puede enviar una nueva y abrir su comprobante |
| Postulación y rotes · Coordinación | **Propuesta para revisar · Medicina Interna** | Doce asignados, cero pendientes. En Propuestas y versiones, abrir asignaciones, aprobar el plan y publicar |
| Postulación y rotes · Coordinación | **Cupos insuficientes · Salud Pública** | Cuatro postulantes, dos cupos utilizables: dos asignados y dos pendientes. No se puede aprobar una propuesta incompleta |
| Escenarios y decisiones · Coordinación | **Escenarios: Propuesta para revisar**, **Cupos insuficientes** y **Rotes publicados** | Abrir resultados y sensibilidad; comparar A1/A2/A3 frente a 100 %, 75 % y 50 % de capacidad; explicar Maximin, Maximax, Laplace, Hurwicz y Savage |
| Seguimiento · Tutor/Coordinación | **Cirugía**, Sofía/Gabriel/Mariana | Tres registros por interno, asistencias y notas; registrar hoy si está libre. Dos incidencias abiertas y dos resueltas |
| Seguimiento · Coordinación | **Permutas consensuadas**, Ginecología | Una solicitud ya consentida por ambos internos; mostrar y aprobar antes de agregar guardias a esos rotes |
| Documentos y reportes · Coordinación | **Archivo · Salud Pública**, **Seguimiento activo · Cirugía**, **Rotes publicados · Ginecología** | Tres actas ya emitidas, ocupación, promedios, descarga de PDF y verificación por QR |
| Mi libreta y documentos · Interno | Sofía o Gabriel | Tres rotes, historial clínico y libreta PDF ya generada; solo se muestra su propio ámbito |
| Disponibilidad y guardias · Tutor/Coordinación | **Cirugía** | Tres turnos calculados y aprobados, dos realizados y uno pendiente de confirmar |
| Gestión académica · Coordinación | **Preparación · Cirugía** | Convocatoria en borrador con plazas; abrir postulaciones durante los primeros diez días desde la carga |

Accesos adicionales, todos con contraseña inicial **`SiclinDemo2026!`**:

| Cuenta | Uso recomendado |
|---|---|
| `interno.demo01@siclin.test` · Sofía | Demo completa de historial, incidencias, tres rotes y PDF propio |
| `interno.demo02@siclin.test` · Gabriel | Segundo expediente completo y contraparte de la permuta preparada |
| `interno.demo07@siclin.test` · Daniela | Postulación nueva a Pediatría; también tiene historial clínico |
| `interno.demo13@siclin.test` a `interno.demo16@siclin.test` | Participantes del caso de escasez de cupos |
| `interno.pendiente@siclin.test` · Alex | Cuenta activa pendiente de cohorte y habilitación académica |
| `tutor.demo1@siclin.test` · Elena | Supervisión del Centro Clínico C |
| `tutor.demo2@siclin.test` y `tutor.demo3@siclin.test` | Rafael e Inés supervisan los rotes futuros de Ginecología |

Las cuentas `interno.demo01` a `interno.demo12` tienen actividad en tres periodos. Lucía (`tutor`) y Mateo (`tutor2`) supervisan el seguimiento ampliado en las dos sedes iniciales, conservando sus casos originales. Los nombres de hospitales que hayas personalizado se mantienen. Las cuentas y claves iniciales están en `.local/presentation-accounts.json`. Si cambiaste una clave adicional por OTP, usa la nueva: la ampliación no la restablece.

Para la exposición, muestra primero los resultados preparados y después ejecuta una acción en vivo. Cambiar cupos, convenios, habilitación o asignaciones puede invalidar una propuesta calculada; en ese caso recalcula antes de publicar. Las actas son copias del momento de emisión: genera otra después de una permuta o de nuevos registros si necesitas un PDF actualizado.

## Recorrido recomendado (15–20 minutos)

### 1. Administrador: cuenta, habilitación y permisos (4 minutos)

Cuenta: `admin@siclin.test`. Tiene acceso completo a todos los módulos.

1. Cuentas de usuario → Nuevo registro. Usa un correo ficticio nuevo, nombre «Invitado Demo», rol Interno y Cuenta activa. Define una contraseña confirmada de al menos doce caracteres con letras y números.
2. Cierra sesión y prueba el nuevo acceso. Aparecerá «Coordinación debe completar tu habilitación».
3. Entra como `coordinador@siclin.test`. En Cohortes crea una cohorte nueva para este ejemplo, de modo que no se alteren las propuestas preparadas.
4. Habilitar cuentas → Completar datos de la cuenta nueva. Asigna cohorte, matrícula, promedio y méritos; marca Habilitado académicamente y guarda.
5. Ingresa con el correo y la contraseña nuevos. Muestra sus menús y su ficha. Para tutores y coordinadores, la pantalla de habilitación permite Autorizar ingreso al portal sin ficha de interno. Los administradores no requieren aprobación.
6. Vuelve como administrador → Cuentas de usuario → Editar. Cambia la contraseña y confirma. Prueba que la anterior no ingresa y la nueva sí. Las otras sesiones se cierran.
7. Para demostrar eliminación, edita la cuenta temporal, escribe el motivo y confirma Eliminar registro. En Bitácora busca `gestion.eliminada`; comprueba que la cuenta ya no ingrese.

La eliminación conserva los registros vinculados como historial. Usa cuentas temporales para este paso. Si prefieres demostrar OTP, deja vacía la contraseña inicial, actívala por correo en Mailpit 8030 y completa igualmente la habilitación antes de ingresar.

El botón Modo oscuro / Modo claro está disponible en todos los roles y en el login. Mostrar / Ocultar alterna la visibilidad de las contraseñas. En móvil, usa Menú para navegar.

### 2. Interno: postulación (2 minutos)

Cuenta: `interno@siclin.test` (Ana).

1. Oferta y postulación → Medicina Interna → Registrar mis preferencias.
2. Hospital A en preferencia 1; Hospital B en preferencia 2. No repitas hospital.
3. Confirma y abre el comprobante QR. El formulario ya no permite reenviar la misma postulación.
4. Muestra que aún no hay asignación: una postulación no equivale a una publicación.

Resultado: preferencias y comprobante privados persistidos. Diego y Valeria ya están listos para la siguiente parte.

### 3. Coordinador: Investigación Operativa y publicación (5 minutos)

Cuenta: `coordinador@siclin.test`.

1. En Postulación y rotes, cierra **Medicina Interna**. Confirma el diálogo.
2. En Escenarios y decisiones, selecciona Medicina Interna, deja α = 0.5 y pulsa **Simular nueve combinaciones**. Para preparar otro estudio de demostración, cambia el campo **Nombre del estudio**, ajusta α o los factores dentro de **Ajustar escenarios** y vuelve a simular. Cada ejecución queda guardada como un estudio independiente; es hipotética y no cambia los cupos reales.
3. Espera a que termine la cola. El estudio nuevo aparece con resultados; abre «Resultados calculados y sensibilidad de cupos». Se ejecutaron tres políticas × tres capacidades.
4. Explica cobertura, primeras preferencias y pendientes. La reducción severa deja cero cupos utilizables: la reserva no se consume para aparentar cobertura.
5. Elige A1 y escribe: «Priorizamos las preferencias y el mérito en esta convocatoria de demostración». Pulsa Aprobar política. Si hay empate entre políticas, explícalo: el caso pequeño no obliga a que sean diferentes.
6. Regresa a rotes. Selecciona ese estudio, pulsa **Calcular propuesta** y espera el resultado del motor PHP.
7. Abre Ver asignaciones y conflictos: con las tres postulaciones se esperan 3 asignados, 0 pendientes, 0 violaciones duras. Ana y Valeria en A; Diego en B con A1.
8. Pulsa **Pasar a revisión → Aprobar plan → Publicar asignaciones**, confirmando cada transición.
9. En Documentos y reportes, genera el acta de **Medicina Interna**. Descarga el PDF y abre Verificar; la página pública acredita el documento sin exponer la nómina.
10. Vuelve como Ana y comprueba su asignación y notificación. Ana no puede leer el acta consolidada de Coordinación.

Si ya cerraste/publicaste, no habrá botón de postulación. Reinicia la demo para repetir el recorrido inicial. Si aparece «datos cambiaron», vuelve a calcular: la publicación compara la entrada actual con la versión calculada.

### 4. Tutor: seguimiento real (3 minutos)

Cuenta: `tutor@siclin.test` (Lucía).

1. Mis internos → Camila Clínica Demo → Registrar asistencia y evaluación.
2. Fecha de hoy, Presente; activa la evaluación por competencias.
3. Escribe: conocimiento 80, procedimientos 60, comunicación 100, profesionalismo 80.
4. Guarda y abre Historial de seguimiento. Con los pesos iniciales iguales, la nota es **80**. La nota se calcula en el servidor.
5. En Cumplimiento de guardias confirma la guardia ya terminada de Camila.
6. En Mis reportes, pulsa Generar libreta PDF. El documento contiene los internos de tu ámbito de supervisión, incluida Camila; la libreta exclusivamente personal se genera desde la cuenta de Camila.
7. `tutor2@siclin.test` no debe ver a Camila: muestra el alcance por tutor si te preguntan por permisos.

Solo hay un registro por asignación y fecha. Si repites el paso 3, usa otra fecha ya transcurrida dentro del rote o consulta el registro existente. Una corrección se documenta como incidencia.

### 5. Interno con actividad: libreta y solicitud (2 minutos)

Cuenta: `interno.clinico@siclin.test` (Camila).

1. Mis rotes y solicitudes → Historial: muestra la nota y la asistencia guardadas por el tutor.
2. Reportar incidencia → Corrección → «Solicito revisar la observación registrada durante la demostración».
3. En Mi libreta y documentos, genera y descarga la libreta propia.
4. Cambia a Coordinación, responde la incidencia con «Se revisó la observación; se conserva el registro original de demostración».
5. Vuelve como Camila y muestra la resolución. El interno puede consultar sus notas; no editarlas.

## Ampliación: guardias y tutores (módulo 7)

Después de publicar Medicina Interna:

1. Como tutor Lucía, Disponibilidad y guardias: registra desde las **08:00** del primer día de Medicina Interna hasta las **20:00** del mismo día. Usa la fecha exacta que aparece en el rote, no la de hoy.
2. Como Coordinación, Guardias y tutores → Disponibilidad y calendario → Proponer un calendario. Nombre «Guardia de Ana», descanso 12 horas, rote de Ana en Hospital A, inicio **10:00**, fin **16:00** de ese día.
3. Genera la propuesta, espera el cálculo, revisa tutor asignado y aprueba. El sistema vuelve a verificar disponibilidad y conflictos.
4. Como Ana, Mis guardias: consulta el turno publicado. No se puede confirmar realizada una guardia futura. Para mostrar cumplimiento usa la guardia retrospectiva de Camila.

El motor elige el tutor; las fechas y el interno del turno los introduce Coordinación. No genera por sí solo un calendario anual.

## Ampliación: permuta

Hazla **antes de crear guardias** de Medicina Interna, porque un rote con guardias o seguimiento no se modifica mediante permuta.

1. Como Ana, consulta su número de Rote. Como Diego (`interno2`), consulta el suyo.
2. Ana → Mis rotes y solicitudes → Solicitar una permuta consensuada: introduce el ID de Diego y un motivo de al menos 10 caracteres.
3. Diego acepta en Permutas consensuadas.
4. Coordinación aprueba. Cambian hospital/tutor de ambas asignaciones y se conserva el historial.

Si haces esta ampliación, Ana pasa a B y la demo de guardias requiere disponibilidad de Mateo (`tutor2`), no de Lucía.

## Recuperación de contraseña y correo institucional

Para recuperar: cierra sesión → Olvidé mi contraseña → correo de una cuenta activada → correo de recuperación en Mailpit 8030 → verificar → contraseña nueva. Comprueba que la anterior ya no permite entrar. El archivo privado conserva la contraseña inicial: si la cambiaste mediante recuperación, usa la nueva.

Para agregar tu correo real, entra como administrador y crea una cuenta con esa dirección. No cambies el email de las cuentas de demo: las guías las identifican por sus correos. En esta instancia el mensaje se captura en **Mailpit**, aunque el destinatario sea institucional. No llega a Internet.

La entrega a tu bandeja institucional se configura en la instancia de trabajo (8000), según [PRUEBA_CORREO_OTP.md](PRUEBA_CORREO_OTP.md). Requiere credenciales SMTP y una comprobación de recepción; queda pendiente sin esas credenciales.

## Si un cálculo o correo no termina

```bash
docker compose -p siclin-demo -f docker-compose.demo.yml ps
docker compose -p siclin-demo -f docker-compose.demo.yml logs --tail=40 queue-optimization queue-mail
docker compose -p siclin-demo -f docker-compose.demo.yml exec -T app php artisan queue:failed
```

Un cálculo en cola necesita el worker. Un OTP vence en 10 minutos; solicita otro si esperaste demasiado. El sistema limita reenvíos: deja al menos 60 segundos entre solicitudes nuevas. Las cuentas, permisos, QR y publicaciones usan el backend real; no se requiere un modo que simule sus respuestas.
