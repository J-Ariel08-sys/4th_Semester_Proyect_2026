# Modelo de Investigación Operativa implementado en PHP

## Rotes por bloque

Conjuntos: internos I del ámbito de la convocatoria, plazas P por hospital/especialidad/bloque, tutores T, y asignaciones publicadas anteriores. La formulación conceptual x(i,h,s,b) se descompone por bloque: para una convocatoria se fija s,b y se resuelve x(i,p) binaria. Se revalida la trayectoria entre bloques antes de publicar; no se afirma optimización global simultánea de todo el año.

Parámetros: capacidad C(p), reserva R(p), tutor t(p), límite simultáneo L(t), disponibilidad académica e(i), preferencias ordenadas, mérito m(i), asignación anterior, convenios, requisitos y calendario. El límite de tutor disponible resta rotes ya comprometidos en intervalos superpuestos.

Restricciones:

- Σp x(i,p) ≤ e(i): no duplicación y elegibilidad.
- Σi x(i,p) ≤ C(p) − R(p), con 0 ≤ R(p) ≤ C(p).
- Σi,p:t(p)=t x(i,p) ≤ L(t).
- Solo opciones compatibles; hospital/convenio y tutor activos; fechas sin solapamientos; duración y número de bloques de la especialidad compatibles con requisitos configurados.
- Una publicación completa requiere exactamente una asignación de cada interno del ámbito y cero pendientes. La verificación de salida comprueba conjunto de identidades, duplicados, capacidad, tutor, compatibilidad y completitud.

Prioridad lexicográfica: primero máxima cobertura; después máximo beneficio blando. La red fuente → interno → plaza → tutor → destino separa cobertura de costo: ninguna preferencia compensa una infracción obligatoria.

Satisfacción s(i,p) = floor(100·(n − posición)/n) para una preferencia de posición cero a n−1; cero si no fue listada. Mérito m = round(0.7·promedio + 0.3·méritos).

Beneficio = Σ x·[wP·s + floor(wM·s·m/100) − 100·wC·cambio] − Σp floor(wE·100·ocupación(p)²/capacidad utilizable(p)) − Σt floor(wE·100·carga(t)²/límite disponible(t)). La implementación usa costos marginales convexos por unidad. El mérito multiplica satisfacción: no es la suma constante de promedios cuando todos reciben plaza.

| Política demo | Preferencia | Mérito | Equilibrio | Cambios |
|---|---:|---:|---:|---:|
| A1 | 10 | 8 | 0 | 5 |
| A2 | 2 | 2 | 10 | 5 |
| A3 | 5 | 3 | 8 | 5 |

Todas respetan las reservas autorizadas. El desempate utiliza orden estable de entrada/identificador y caminos recorridos determinísticamente, sin preferencia institucional de desempate presumida.

`RotationOptimizer` usa caminos aumentantes de menor costo en la red residual. Hasta 500 internos, 500 grupos y 100000 pares interno/plaza; plazo configurable de 1–60 s. Si se interrumpe por plazo conserva una solución parcial válida y no prueba imposibilidad. Si se agotan caminos de aumento, la falta de cobertura demuestra insuficiencia de capacidad compatible para este modelo. La interfaz usa “mejor solución encontrada”. El estado técnico y el del plan son independientes.

Ejemplo manual: dos internos con la misma preferencia a>b, capacidad 1 en cada plaza, méritos 90 y 20, política A1. Ambos obtienen plaza. Poner al de mérito 90 en a produce mayor satisfacción ponderada que invertirlos; la prueba intercambia méritos y comprueba el cambio de primera elección.

## Guardias

Las fechas de turnos son entradas de Coordinación. Variables: tutor de cada turno o pendiente. Restricciones: compatibilidad de hospital/especialidad/periodo, disponibilidad completa, máximo 24 h por turno, no cruce de interno/tutor, descanso mínimo configurado frente al plan vigente y otros turnos propuestos.

`ShiftOptimizer` ordena turnos por número de tutores disponibles y realiza búsqueda con poda. Objetivo: mayor cobertura y luego menor Σ minutos(t)² / minutos_disponibles(t). La disponibilidad se une para evitar doble conteo de intervalos. Incluye carga previa y filtra conflictos con calendarios publicados. Límite determinista de nodos `SHIFT_SEARCH_NODES` y límite de tiempo; registra si agotó la búsqueda. Una búsqueda acotada no demuestra óptimo global ni imposibilidad. `ShiftPlanner` vuelve a comprobar la propuesta y la huella de entrada al aprobarla. M4 registra cumplimiento en la misma tabla `shifts` que planifica M7.

## Decisiones y sensibilidad

Matrices rectangulares con alternativas y escenarios identificados, valores finitos ±1000000. Beneficios: utilidad=beneficio. Costos: utilidad=−costo, transformación mostrada explícitamente sin reescalar.

Sobre utilidad u:

- Maximin: max_a min_e u(a,e).
- Maximax: max_a max_e u(a,e).
- Laplace: max_a promedio_e u(a,e); equiprobabilidad como hipótesis.
- Hurwicz: max_a [α·max_e u +(1−α)·min_e u], 0≤α≤1, α=optimismo.
- Savage: min_a max_e [max_k u(k,e)−u(a,e)].

Se conservan empates y resultados intermedios. Ejemplo de costos A1=[4,12], A2=[7,8], A3=[6,10]: Maximin y Laplace eligen A2; Maximax A1; arrepentimientos máximos [4,3,2], Savage A3. Con α=.5 Hurwicz de A2 es −7.5. Los extremos α=0 y α=1 se prueban.

La simulación evalúa políticas con ajustes posteriores permitidos frente a tres factores editables de capacidad. Utilidad común de demostración: 100·cobertura + 25·primeras/habilitados − 10·diferencia de ocupación proporcional. Mantiene los otros parámetros y la misma normalización. No compara directamente puntuaciones internas con pesos distintos. La sensibilidad de α (0…1) y la de capacidad se muestran por separado. Ninguna política se elige sin decisión y justificación de Coordinación.

## Prueba de tamaño definido

Fixture: 180 internos habilitados, una especialidad/bloque, 45 grupos con capacidad 5 y reserva 1: 180 cupos utilizables. Preferencias compatibles y repetibles. Mide un cálculo por bloque y validación de cobertura/capacidad; no simula cinco especialidades ni certifica un rendimiento institucional anual. Los tiempos observados figuran en PRUEBAS.md.

Para presentar estos conceptos sin comenzar por las fórmulas, usa [EXPOSICION_IO.md](EXPOSICION_IO.md). Incluye un ejemplo de asignación calculable a mano y el guion para mostrar los tres motores en la interfaz.
