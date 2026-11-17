# Reglas y supuestos

Todas las reglas académicas son de **demostración**, pendientes de validación institucional. SICLIN es exclusivo del internado de Medicina, sede académica La Paz; no contiene pacientes, diagnósticos ni recetas. Los hospitales demo son ficticios. Una ubicación hospitalaria no acredita un convenio oficial.

- Una convocatoria representa una especialidad y un bloque con fechas inclusivas. Una propuesta cubre ese bloque; no certifica por sí sola toda la trayectoria anual.
- Coordinación configura cohortes y requisitos: especialidad, días mínimos por bloque y número de bloques. Vincular una cohorte a la convocatoria incluye a todos sus internos en la validación de completitud. Quienes no postularon, no están habilitados o tienen trayectoria incompatible quedan pendientes y bloquean la publicación.
- Sin cohorte, el ámbito explícito es la lista de postulantes. Se muestra como convocatoria por postulación. Nunca se infiere una cohorte institucional real.
- Una opción no incluida entre las preferencias puede utilizarse como alternativa compatible; el formulario informa esta regla. Preferencias duplicadas se rechazan. Una postulación enviada es inalterable por el interno.
- Apertura inclusiva y cierre exclusivo, interpretados en America/La_Paz. La configuración y los timestamps de aplicación usan consistentemente esa zona; PostgreSQL almacena estos timestamps sin conversión implícita adicional. Las fechas académicas son fechas locales.
- Mérito de demostración: 70 % promedio académico + 30 % mérito normalizado, ambos 0–100. En la función objetivo multiplica la satisfacción: puede cambiar quién recibe una primera opción aun si todos reciben plaza. Pesos A1/A2/A3 se documentan en el código y modelo IO.
- Capacidad se define por hospital/especialidad/bloque. Reserva no excede capacidad. Los tutores tienen límite agregado de internos simultáneos; se resta la supervisión comprometida en periodos superpuestos. Es una regla conservadora: reserva el máximo para todo el bloque.
- Convenio vigente hasta el fin, hospital y tutor activos. No se suman cupos de ofertas temporales superpuestas para el mismo hospital/especialidad.
- Reoptimización, reasignaciones y permutas solo antes del inicio y sin seguimiento/guardias vinculadas. Cambios posteriores requieren periodos nuevos para preservar el historial; no se reescriben rotes iniciados.
- Permuta requiere consentimiento de ambas partes, misma convocatoria y hospitales diferentes; Coordinación aplica ambos cambios atómicamente.
- Una guardia tiene un interno y un tutor. No se admiten cruces ni descansos inferiores al parámetro del calendario (0–48 h); duración máxima de turno: 24 h. La especialidad/hospital del tutor se filtra por oferta compatible. La carga se compara en relación con minutos disponibles, combinando intervalos superpuestos sin contarlos dos veces.
- Evaluaciones: cuatro competencias iniciales con pesos editables que deben sumar 100 %. Cada nota guarda sus pesos y valores; redondeo al entero más cercano. Correcciones se solicitan como incidencia y no borran el registro previo.
- Incidencias no generan probabilidades de abandono. Los estados y respuestas son registrados por responsables humanos.
- Las decisiones de escenarios son políticas con reasignación permitida después de observar capacidad, no planes fijos que conozcan el futuro. Los factores editables reducen capacidad autorizada; los demás parámetros permanecen fijos. A1 preferencias/mérito, A2 equilibrio, A3 equilibrio y reserva autorizada. Ninguna alternativa se selecciona automáticamente.
- Las matrices manuales son ejercicios hipotéticos explícitos. Laplace supone equiprobabilidad; no estima probabilidades institucionales.
- El registro de auditoría no se edita desde el portal. Un administrador de PostgreSQL podría modificarlo: no se presenta como absolutamente inmutable.

Validaciones institucionales pendientes: dominios autorizados, hospitales/convenios, duración real de especialidades, rúbrica, mérito y desempate, descansos y supervisión. No hay integración externa con SEDES ni firma digital certificada.
