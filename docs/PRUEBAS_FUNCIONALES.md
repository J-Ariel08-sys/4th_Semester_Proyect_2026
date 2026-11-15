# Guía funcional de SICLIN

## 1. Arranque desde cero, sin borrar datos

Necesitas Docker con el servicio activo y Docker Compose. Abre una terminal en el repositorio:

```bash
cd /home/ariel/4th_Semester_Proyect_2026
./scripts/dev-up.sh
```

La primera construcción descarga PHP, Java, PostgreSQL y dependencias; puede tardar varios minutos. No requiere instalar PHP, Composer, Java o Node en el equipo anfitrión.

El script:

1. Copia `portal/.env.example` a `portal/.env` si no existe.
2. Construye e inicia PostgreSQL, Quarkus, Laravel y Mailpit.
3. Genera `APP_KEY` si falta.
4. Aplica migraciones sin eliminar registros.
5. Carga la demostración si la tabla de usuarios está vacía.

Abre **http://localhost:8000**. Usa siempre este mismo host para no mezclar cookies de `localhost` y `127.0.0.1`.

```bash
docker compose ps
curl http://localhost:8081/health
```

Esperado: contenedores activos y salud `UP`. Para consultar problemas:

```bash
docker compose logs --tail=100 portal optimizer postgres-db
```

Si los puertos 8000, 8081, 5432 o 8025 están ocupados, cambia su lado izquierdo en `docker-compose.yml`. Si cambias el puerto del portal, actualiza también `APP_URL` en Compose. No publiques estos puertos a Internet para la prueba.

## 2. Cuentas de demostración

Todas usan **`SiclinDemo2026!`**. Son cuentas ficticias y no correos institucionales.

| Rol | Cuenta | Qué probar |
|---|---|---|
| Administrador | `admin@siclin.test` | Usuarios, nómina, configuración y todos los flujos de coordinación |
| Coordinador | `coordinador@siclin.test` | Hospitales, convocatorias, cupos, escenarios, aprobación y reportes |
| Tutor A | `tutor@siclin.test` | Seguimiento de sus internos y disponibilidad/guardias asignadas |
| Tutor B | `tutor2@siclin.test` | Comprobar aislamiento respecto al tutor A |
| Interno A | `interno@siclin.test` | Preferencias e historial propio |
| Interno B | `interno2@siclin.test` | Preferencias distintas y consentimiento de permuta |
| Interno C | `interno3@siclin.test` | Escasez de cupos y resultados |

Para cambiar de rol, cierra sesión. También puedes usar ventanas privadas independientes. No se comparte la sesión entre roles dentro de una misma ventana.

## 3. Módulo 1: acceso y seguridad

1. Entra como administrador. Revisa el menú y la bitácora.
2. En **Usuarios y seguridad**, crea una cuenta de interno: nombre, correo, matrícula, promedio y méritos 0–100, habilitación y cuenta activa. La contraseña inicial debe tener al menos 12 caracteres.
3. Cierra sesión e ingresa como interno. No debe aparecer el menú de administración ni escenarios.
4. Accede manualmente a `http://localhost:8000/gestion/usuarios`: debe mostrar acceso restringido.
5. Para probar bloqueo, cierra sesión e introduce una contraseña errónea cinco veces con el mismo correo. El siguiente intento queda bloqueado temporalmente durante cinco minutos. No uses la cuenta principal si necesitas seguir trabajando inmediatamente.
6. Prueba **Olvidé mi contraseña**. Introduce un correo demo y abre **http://localhost:8025**. El mensaje y su enlace quedan en Mailpit; no se envían a Internet.
7. Abre el enlace y establece una contraseña con al menos 12 caracteres, letras y números. Comprueba el nuevo acceso. El enlace es de un solo uso y caduca según la configuración Laravel.
8. Desde administrador desactiva una cuenta de prueba. Su sesión no debe permitir acceder a módulos protegidos.

El administrador no puede quitarse su propio rol ni desactivar su propia cuenta. Los roles de cuentas existentes permanecen fijos para conservar el historial. Usuarios y hospitales se inhabilitan, no se eliminan físicamente.

## 4. Módulo 2: datos hospitalarios y académicos

La demostración ya contiene cinco especialidades, dos hospitales ficticios, dos tutores y una convocatoria futura de Medicina Interna. Consulta sus fechas en pantalla: se calculan respecto al día en que se cargaron los datos.

Para crear otro ciclo desde el principio:

1. **Gestión académica → Hospitales:** registra nombre, convenio válido hasta una fecha posterior al fin del rote y estado habilitado.
2. **Especialidades:** registra la especialidad si no existe.
3. **Convocatorias:** nombre, especialidad, inicio y fin del rote; apertura y cierre de postulación. El cierre debe ser posterior a la apertura y anterior al inicio del rote. La convocatoria nace en borrador.
4. **Cupos hospitalarios:** selecciona convocatoria, hospital, tutor activo, capacidad y reserva. Un hospital tiene una sola oferta por convocatoria.
5. Vuelve a **Postulación y rotes** y abre la convocatoria. La ventana de fechas también debe estar vigente para postular.

Pruebas negativas: reserva mayor que capacidad; tutor inactivo; convenio que vence antes del rote; ofertas del mismo hospital/especialidad con fechas solapadas. Deben rechazarse. Durante postulaciones abiertas los cupos quedan protegidos; los ajustes posteriores se realizan con convocatoria cerrada y antes del inicio.

### Importar nómina

Como administrador, abre Usuarios y seguridad y carga `examples/nomina.csv`:

```csv
name,email,matricula,academic_average,merit,eligible
Interna Importada Demo,importada@siclin.test,LP-DEMO-101,88.50,75,1
```

`eligible` admite 0 o 1. Máximo 500 filas. No se permiten correos o matrículas duplicadas. Si una fila es inválida se rechaza todo el archivo; no queda una importación parcial. Las cuentas importadas usan una contraseña aleatoria no compartida: establece el acceso mediante recuperación de contraseña y Mailpit, o cambia la contraseña desde administración.

## 5. Módulo 3: postulación

Usa la convocatoria futura precargada:

1. Ingresa como `interno@siclin.test`. En Postulación y rotes, despliega **Registrar mis preferencias**. Selecciona hospital A primero y B segundo.
2. Confirma y abre el comprobante con QR. Imprímelo si lo necesitas.
3. Comprueba que no puedes volver a enviar o editar esa postulación.
4. Repite con `interno2@siclin.test`, priorizando B y luego A.
5. Repite con `interno3@siclin.test`, priorizando A y luego B.
6. Como coordinador, cierra las postulaciones.

No debe aceptar postulaciones de una cuenta inhabilitada, fuera de fechas, con plazas duplicadas o de otra convocatoria. El comprobante solo se consulta por su propietario o Coordinación. Las plazas que el interno no ordenó siguen siendo opciones admisibles con satisfacción cero.

## 6. Módulo 6: demostrar Investigación Operativa

### Matriz manual y criterios

1. Entra como coordinador a **Análisis de escenarios**.
2. En la matriz manual, usa el ejemplo inicial:

| Política | E1 | E2 | E3 |
|---|---:|---:|---:|
| A1 | 90 | 60 | 20 |
| A2 | 75 | 65 | 40 |
| A3 | 80 | 70 | 60 |

3. Usa α = 0.5 y calcula.
4. Esperado: A1 gana Maximax; A3 gana Maximin, Laplace, Hurwicz y Savage. A3 tiene Hurwicz 70 y arrepentimiento máximo 10.
5. Despliega sensibilidad: con α = 0 gana A3; con α = 1 gana A1. El cuadro muestra el cambio entre políticas sin alterar la matriz.
6. Elige A3 y escribe una justificación. La política queda disponible para calcular rotes. No se selecciona ni publica automáticamente.

### Simulación con cupos reales

1. En **Simular cambios de capacidad**, selecciona la convocatoria cerrada con los tres postulantes.
2. Ejecuta las nueve combinaciones. Tarda aproximadamente nueve búsquedas cortas más el intercambio HTTP.
3. Con dos hospitales de capacidad 3 y reserva 1, espera por escenario:
   - E1: cuatro cupos utilizables para tres internos; tres asignados.
   - E2: capacidad 2 por hospital, reserva 1; dos asignados y uno pendiente.
   - E3: capacidad 1 y reserva 1; cero cupos utilizables y tres pendientes.
4. Revisa cobertura, primeras preferencias, utilidad, matriz de arrepentimiento y sensibilidad.
5. Comprueba en Gestión académica que los cupos reales siguen siendo 3/1: la simulación trabaja sobre copias.

La identidad del interno pendiente puede variar por mérito, preferencias, equilibrio y tiempo de búsqueda. Los totales de factibilidad no deben exceder la capacidad.

Consulta `docs/INVESTIGACION_OPERATIVA.md` para explicar variables, restricciones, objetivo jerárquico y fórmula de utilidad. Los escenarios y coeficientes son didácticos; no deben presentarse como mediciones institucionales.

## 7. Calcular y aprobar la asignación

1. En Postulación y rotes, elige un estudio con política aprobada.
2. Pulsa **Calcular propuesta**.
3. Revisa asignados, pendientes, primeras preferencias, detalle de plazas y violaciones duras.
4. Antes de aprobar, los internos todavía no deben ver una nueva asignación en su libreta.
5. Pulsa **Aprobar y publicar**. Debe volver a validar los datos y publicar en una transacción.
6. Ingresa como cada interno: debe aparecer el rote en Seguimiento y Libreta. En Inicio se muestra una notificación; Mailpit captura el correo de publicación.
7. Repetir la aprobación de la misma propuesta debe rechazarse.

### Casos que conviene mostrar al docente

- **Escasez:** antes de publicar, con convocatoria cerrada, baja cada hospital a capacidad 2/reserva 1. Calcula: habrá pendientes y el portal impedirá publicar. Restablece 3/1, recalcula y aprueba.
- **Datos desactualizados:** calcula una propuesta, cambia el mérito/habilitación de un postulante y trata de aprobar. Debe pedir recalcular.
- **Reoptimización:** después de publicar, y antes del inicio/sin seguimiento ni guardias, calcula otra propuesta. Envía la asignación anterior al motor para penalizar cambios. Solo al aprobar se sustituye la asignación vigente; se conservan propuestas e historial.
- **Capacidad vigente:** el portal no deja reducir cupos utilizables por debajo de los internos ya asignados. Primero debe autorizarse una redistribución factible.
- **Cruce de rotes:** una postulación cuyo interno tiene otra asignación aprobada con fechas superpuestas queda no habilitada para ese cálculo y no se publica como válida.

## 8. Módulo 4: seguimiento y ajustes

### Seguimiento hoy, sin esperar al rote futuro

Ejecuta una vez:

```bash
docker compose exec portal php artisan db:seed --class=ClinicalDemoSeeder
```

Crea el ejemplo **DEMO · Seguimiento clínico en curso**, para `interno.clinico@siclin.test` con la contraseña demo. Usa Cirugía, termina dos días después de su creación y queda bajo `tutor@siclin.test`. Se identifica en bitácora como demostración retrospectiva.

1. Ingresa como tutor A y abre Seguimiento clínico.
2. En el ejemplo activo, registra asistencia de hoy.
3. Activa evaluación por competencias. Introduce conocimiento 80, procedimientos 60, comunicación 100 y profesionalismo 80. Esperado: nota 80, promedio de cuatro criterios con igual peso.
4. Revisa historial. Un segundo registro del mismo día debe rechazarse.
5. Una fecha futura o fuera del rote debe rechazarse.
6. Ingresa como tutor B: no debe consultar ni registrar seguimiento de ese interno.
7. Ingresa como interno clínico: consulta tus datos y abre una incidencia de corrección. No puedes calificarte.
8. Como coordinador, registra la resolución y revisa su trazabilidad.

El porcentaje de asistencia mostrado se refiere a **días registrados**, no a todos los días laborables esperados. Una ausencia justificada no se interpreta como predicción de abandono.

### Reasignación

En un rote futuro sin registros ni guardias, Coordinación puede elegir una plaza compatible de la misma convocatoria con cupo disponible y registrar el motivo. Los rotes iniciados no se reescriben retrospectivamente. Las actas emitidas antes del cambio conservan su versión; emite una nueva acta para documentar el estado actual.

### Permuta con consentimiento

1. Usa dos internos de la misma convocatoria futura, asignados a hospitales distintos.
2. Cada interno puede ver su ID de rote en Seguimiento. La otra persona comparte voluntariamente ese ID.
3. El primer interno solicita permuta indicando ID del otro rote y motivo.
4. El segundo interno ve la solicitud y acepta o rechaza.
5. Antes del consentimiento, Coordinación no puede aprobar.
6. Después de aceptar, Coordinación aprueba. Se intercambian plazas/tutores sin cambiar la ocupación total de los hospitales.
7. Si entretanto cambiaron las plazas, comenzó el rote o se registraron guardias/seguimiento, la permuta debe rechazarse y revisarse.

## 9. Módulo 7: disponibilidad, guardias y tutores

1. Como tutor, abre Guardias y tutores y registra disponibilidad. Solo puedes registrar la propia.
2. Como coordinador, crea un calendario, por ejemplo “Semana de demostración”. Configura descanso mínimo 12 horas (valor de prueba, pendiente de norma institucional).
3. Añade uno o varios turnos con interno/rote, inicio y fin dentro del periodo del rote. El botón “Añadir turno” permite varias filas.
4. Timefold asigna tutores disponibles del hospital; primero busca cobertura y luego equilibrio de minutos de supervisión.
5. Revisa turnos pendientes. Ningún lote incompleto debe aprobarse.
6. Aprueba el calendario completo. Revisa la carga publicada por tutor.
7. Intenta crear un turno superpuesto o con descanso insuficiente para el mismo tutor/interno: debe quedar pendiente y no publicarse.
8. Intenta reducir el descanso de un lote nuevo para eludir el descanso del calendario anterior: se conserva el mayor descanso entre los dos.
9. Un interno solo ve sus turnos aprobados. Un tutor ve aquellos que supervisa.

Para probar **cumplimiento hoy**, el ejemplo clínico incluye una guardia ya terminada. Entra como tutor A, abre Seguimiento clínico → Cumplimiento de guardias y pulsa “Confirmar realizada”. Debe registrarse una sola vez. No se permite marcar una guardia futura como realizada.

Para generar otra guardia del ejemplo clínico, elige mañana, por ejemplo 08:00–14:00, y asegúrate de que haya disponibilidad completa y descanso respecto a la guardia de muestra. No se pueden modificar retrospectivamente rotes que tengan guardias vinculadas.

## 10. Módulo 5: libreta, actas e indicadores

1. Como interno, consulta Libreta y reportes: solo tus rotes, asistencia registrada y promedio de notas. Usa Imprimir libreta para imprimir/guardar PDF desde el navegador.
2. Como coordinador, revisa ocupación por hospital: capacidad, reserva, asignados y porcentaje sobre cupos utilizables.
3. Emite el acta de una convocatoria publicada.
4. Descarga el PDF. Debe contener internos, hospitales, especialidad, fechas y QR.
5. Abre Verificar: se comprueba el archivo mediante SHA-256. La página pública muestra solo identificador, fecha y huella; no expone la nómina.
6. Un interno no puede descargar el acta consolidada de todos sus compañeros.
7. Emite otra versión: ambas quedan en el historial con identificadores distintos.

El QR usa `APP_URL`. Un teléfono no puede abrir `localhost` de tu computadora; para una demostración desde otro dispositivo se necesita configurar una dirección accesible y revisar la exposición de red. La prueba local se hace con el enlace “Verificar”.

**El QR no es una firma digital del SEDES.** El acta usa formato académico demostrativo y debe validarse institucionalmente antes de considerarse documento oficial.

## 11. Bitácora

Como coordinador o administrador, consulta acceso, configuración, postulación, política elegida, propuesta, aprobación, permutas, seguimiento y documentos. No hay acciones de edición o borrado en la interfaz. Esto no sustituye una solución de auditoría externa contra administradores de base de datos.

## 12. Pruebas automatizadas

### Laravel aislado

```bash
./scripts/test.sh
```

SQLite en memoria; no altera los datos demo. La prueba de integración real aparece omitida a propósito si no activas `RUN_INTEGRATION`.

### Quarkus

```bash
cd optimizer-quarkus
./mvnw test
```

Prueba capacidad, reserva, habilitación, preferencias, errores de entrada, guardias y escala de 180 internos/45 grupos de plazas. Requiere Java 21; el wrapper descarga Maven. Alternativamente puedes ejecutarlo en un contenedor Maven montando `optimizer-quarkus`.

### PostgreSQL y Quarkus reales

Con los servicios activos, crea una base **separada** una sola vez:

```bash
docker compose exec postgres-db psql -U unifranz_user -d postgres -c 'CREATE DATABASE siclin_test;'
```

Si ya existe, omite ese comando. Ejecuta:

```bash
docker compose exec -T \
  -e APP_ENV=testing -e RUN_INTEGRATION=1 \
  -e DB_CONNECTION=pgsql -e DB_DATABASE=siclin_test \
  -e SESSION_DRIVER=array -e CACHE_STORE=array -e MAIL_MAILER=array \
  portal php vendor/bin/phpunit --configuration phpunit.pgsql.xml
```

La prueba utiliza los mismos endpoints de aplicación con PostgreSQL real. La integración llama a Quarkus sin simular sus respuestas, calcula nueve escenarios, aprueba tres asignaciones y planifica una guardia. Los correos de esta prueba se mantienen en memoria.

## 13. Reinicios y errores frecuentes

- Detener sin perder datos: `docker compose stop`.
- Reanudar: `docker compose up -d`.
- Código Java modificado: `docker compose up -d --build optimizer`.
- Dependencias PHP modificadas: `docker compose exec portal composer install`.
- Cambios de `.env`/Compose: `docker compose up -d --force-recreate portal`.
- Caché de vistas: `docker compose exec portal php artisan view:clear`.
- Postulación cerrada: comprueba estado **y** fechas. No basta con que diga “abierta”.
- Sin política disponible: calcula un estudio y aprueba explícitamente una alternativa.
- Propuesta bloqueada: revisa pendientes, elegibilidad, cupos y cambios posteriores al cálculo.
- Guardias pendientes: comprueba hospital/tutor, disponibilidad completa, descanso e interno ocupado.
- Motor ocupado: la API limita las búsquedas simultáneas; espera a que termine otra ejecución y reintenta.

No uses `migrate:fresh`, `docker compose down -v` ni borres volúmenes si quieres conservar tus datos. `db:seed` normal no reinicia una base existente. Para otra demostración crea una convocatoria nueva.
