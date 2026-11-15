# Investigación Operativa en SICLIN

## 1. Qué decide cada módulo

- Módulo 6: evalúa políticas frente a escenarios, aplica criterios bajo incertidumbre y registra la alternativa elegida por Coordinación.
- Módulo 3: calcula una propuesta de asignación usando los pesos de esa política, con los cupos reales.
- Módulo 4: conserva el seguimiento y permite ajustes autorizados. Reoptimizar antes del inicio penaliza cambios respecto a la asignación anterior.
- Módulo 7: asigna tutores a guardias de internos con horario ya definido, sin cruces y con descansos configurables.

Una alternativa es una política controlable; un escenario es una condición externa. A3 no se declara ganadora antes de calcular. Los valores, pesos y escenarios incluidos son didácticos y requieren validación de la Coordinación de Internado.

## 2. Asignación de rotes: formulación

Una convocatoria corresponde a una especialidad y un periodo. Cada plaza agrupa hospital, convocatoria, tutor, capacidad y reserva. “45 plazas” en la prueba de rendimiento significa 45 grupos con capacidad, no 45 puestos individuales para 180 personas.

Para internos `i ∈ I` y plazas `p ∈ P`:

- `x_ip ∈ {0,1}`: el interno i recibe la plaza p.
- `u_i ∈ {0,1}`: el interno habilitado queda pendiente.
- `c_p`: capacidad autorizada; `r_p`: reserva; `a_p = c_p − r_p`.
- `e_i`: habilitación académica, cuenta activa y ausencia de solapamiento con otro rote aprobado.
- `n_p = Σ_i x_ip`: ocupación de la plaza.

Restricciones:

```text
Σ_p x_ip + u_i = 1       para cada interno habilitado
Σ_p x_ip = 0             para cada interno no habilitado
Σ_i x_ip ≤ c_p − r_p      para cada plaza
0 ≤ r_p ≤ c_p
```

El portal lleva a cero los cupos utilizables de hospitales inactivos, convenios que vencen antes del fin del rote o tutores inactivos. Al aprobar, comprueba nuevamente las condiciones, la versión de los datos, la pertenencia de cada interno/plaza y las capacidades. Las aprobaciones usan transacciones y bloqueos de usuarios para impedir que dos convocatorias concurrentes asignen al mismo interno en fechas incompatibles.

### Objetivo jerárquico

Timefold utiliza `HardMediumSoftLongScore`:

1. **Hard**: cero violaciones de capacidad y habilitación.
2. **Medium**: minimizar internos habilitados pendientes.
3. **Soft**: preferencias, prioridad académica, equilibrio y conservación de asignaciones.

La cobertura precede a los objetivos blandos: no se deja un cupo sin cubrir solo para mejorar una preferencia. Ante escasez se devuelven pendientes; nunca se presentan sobrecupos como solución válida. El portal exige resolver todos los pendientes antes de publicar la convocatoria completa.

Para una lista de `k_i` preferencias y posición `rank_ip` desde 1:

```text
s_ip = floor(100 × (k_i − rank_ip + 1) / k_i)
s_ip = 0 si la plaza no está en la lista
m_i = round(0.70 × promedio_académico_i + 0.30 × méritos_i)
```

Promedio y méritos se registran en escala 0–100. La proporción 70/30 es una regla de demostración, no una norma confirmada de Unifranz. El mérito favorece la selección de internos cuando faltan cupos; si todos están asignados su suma es constante, por lo que **no implica prioridad serial en la elección de hospital**. Las plazas no preferidas siguen siendo admisibles.

El puntaje blando implementado es:

```text
Σ_ip x_ip × (w_pref × s_ip + w_mer × m_i)
− w_eq × Σ_p floor(100 × n_p² / max(1,a_p))
− 100 × w_cambio × cantidad_de_asignaciones_anteriores_modificadas
```

La penalización convexa de ocupación favorece distribuir la carga de acuerdo con la capacidad. Cambiar una asignación anterior también se penaliza. No se modifica retrospectivamente un rote iniciado o con seguimiento/guardias.

| Política | Preferencias | Mérito | Equilibrio | Cambios |
|---|---:|---:|---:|---:|
| A1: preferencias | 10 | 1 | 0 | 5 |
| A2: mérito | 1 | 10 | 0 | 5 |
| A3: equilibrio | 5 | 3 | 3 | 5 |

Las reservas son restricciones de capacidad en las tres políticas; A3 no las crea automáticamente. Configúralas en Gestión académica → Cupos.

Timefold busca buenas soluciones en un tiempo acotado. Un puntaje duro cero acredita factibilidad respecto a las restricciones implementadas; no certifica óptimo global. Los resultados pueden variar entre ejecuciones por terminación temporal, aun con semilla fija. Se guardan entrada, resultado, hash y política para auditar cada ejecución.

## 3. Decisión bajo incertidumbre

La matriz `U_as` contiene utilidades comparables: mayor es mejor. No se aplican estos criterios directamente a capacidades ni a puntajes Timefold de políticas con pesos diferentes.

| Alternativa | E1 | E2 | E3 |
|---|---:|---:|---:|
| A1 | 90 | 60 | 20 |
| A2 | 75 | 65 | 40 |
| A3 | 80 | 70 | 60 |

Ejemplo hipotético inicial, no mediciones de hospitales reales.

- **Maximin:** maximizar `min_s U_as`.
- **Maximax:** maximizar `max_s U_as`.
- **Laplace:** maximizar `(Σ_s U_as)/3`; supone escenarios equiprobables.
- **Hurwicz:** maximizar `α max_s U_as + (1−α) min_s U_as`. Aquí α representa optimismo, entre 0 y 1.
- **Savage:** arrepentimiento `R_as = max_b U_bs − U_as`; minimizar `max_s R_as`.

Resultados con α = 0.5:

| Alternativa | Maximin | Maximax | Laplace | Hurwicz | Savage |
|---|---:|---:|---:|---:|---:|
| A1 | 20 | 90 | 56.67 | 55 | 40 |
| A2 | 40 | 75 | 60 | 57.5 | 20 |
| A3 | 60 | 80 | 70 | 70 | 10 |

A3 gana Maximin, Laplace, Hurwicz y Savage; A1 gana Maximax. Los empates se muestran como varias alternativas, sin selección arbitraria. La sensibilidad de Hurwicz se calcula de α = 0 a 1 en incrementos de 0.1. La decisión final exige justificación y queda registrada.

### Simulación con datos del portal

“Simular nueve combinaciones” ejecuta las tres políticas con:

- E1: 100 % de capacidad autorizada.
- E2: 75 % de capacidad autorizada, redondeada hacia abajo.
- E3: 50 % de capacidad autorizada, redondeada hacia abajo.

La reserva es `min(reserva original, nueva capacidad)`. Cada corrida conserva las postulaciones y cambia solo la política/capacidad de la copia simulada. No modifica plazas ni publica asignaciones.

Una utilidad común evita comparar scores de políticas con distintos pesos:

```text
U = 100 × asignados / habilitados
  + 25 × primeras_preferencias / habilitados
  − 10 × (máxima_ocupación_relativa − mínima_ocupación_relativa)
```

La ocupación relativa es `asignados_p / cupos_utilizables_p`; las plazas con cero cupos se excluyen del rango. Si no hay habilitados se usa denominador 1 y la cobertura es cero. Si hay menos de dos plazas utilizables, el rango es cero. Los coeficientes 100/25/10 son ilustrativos y están documentados para ser revisados, no inferidos de datos reales.

El estudio guarda los nueve resultados resumidos y la entrada utilizada. El escenario crítico del informe no debe interpretarse automáticamente como aumento de capacidad: puede reducir convenios, disponibilidad o supervisión. Esta implementación representa incertidumbre de **capacidad**; demanda asistencial/pandemia requiere datos y reglas adicionales.

## 4. Planificación de guardias

Cada turno tiene interno, hospital (derivado del rote), inicio y fin. La decisión es el tutor asignado. Variables `y_gt ∈ {0,1}` indican que el tutor t supervisa el turno g.

```text
Σ_t y_gt ≤ 1
```

Se exige:

- Turno dentro de las fechas del rote, de duración positiva y como máximo 24 horas.
- Tutor activo vinculado al hospital y disponible durante todo el turno.
- Sin cruces entre turnos del mismo interno o tutor.
- Descanso mínimo antes/después, configurable entre 0 y 48 horas. El valor inicial de 12 horas es demostrativo.
- Frente a un calendario ya aprobado se respeta el mayor descanso entre ambos calendarios.
- Los turnos sin cobertura permanecen pendientes y bloquean la aprobación del lote.

Laravel filtra tutores incompatibles con disponibilidad y calendarios ya publicados. Timefold en `POST /api/guardias` busca primero cero conflictos, luego máxima cobertura, y finalmente minimiza `Σ_t carga_minutos_t²`. La carga previa se cuenta sobre las semanas abarcadas por el calendario propuesto. Penalizar cuadrados favorece repartir horas entre tutores con distinta carga.

La validación final de Laravel vuelve a comprobar el calendario elegido, sin recalcular otra solución: una propuesta factible no se rechaza simplemente porque otra ejecución del buscador elija tutores distintos. El módulo 7 planifica y aprueba; el módulo 4 y la acción de cumplimiento registran lo realizado. La confirmación de cumplimiento solo se admite después del fin del turno.

## 5. Cómo defender el aporte

Demostrar con los mismos datos: una política elegida mediante criterios explícitos; una asignación factible con indicadores; una reducción de cupos que genera pendientes; sensibilidad de α; un calendario con cruces que no se publica; y la trazabilidad de aprobación. Diferenciar resultados calculados, datos ficticios y reglas pendientes de validación.

No se implementa predicción clínica del abandono ni se interpreta una baja médica como indicador automatizado de riesgo. Asistencia e incidencias son registros descriptivos para revisión humana.

Referencias de implementación: [modelo de planificación de Timefold](https://docs.timefold.ai/timefold-solver/1.x/using-timefold-solver/modeling-planning-problems), [guía oficial del solver](https://docs.timefold.ai/timefold-solver/1.x/quickstart/hello-world/hello-world-quickstart).
