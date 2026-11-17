# Estado de SICLIN

Revisión del 17 de noviembre de 2026. El backend activo utiliza Laravel/PHP; Quarkus se conserva únicamente como código histórico. La implementación se revisó sobre el commit `e930b8b` y se mantuvieron los servicios existentes.

## Cambios de esta revisión

- Navegación sin enlaces repetidos, sección activa correcta y panel inicial con tres tareas según el rol.
- Estilo más sobrio, formulario de acceso compacto y corrección de desbordamiento en móviles.
- Instancia de presentación separada (8010/8030), cuentas con contraseñas aleatorias y guía contextual por rol.
- Dos postulaciones de ejemplo más Ana disponible para postular; Camila preparada para seguimiento clínico.
- Arranque de demo que conserva avances y reinicio explícito protegido, con dump previo de la base de demo.
- Corregido un error de compilación de la vista de rotes (`@endif` adyacentes), oculto en entornos con caché antigua. Las vistas compiladas dejaron de persistir junto a los datos; los tests recompilan las fuentes.
- Documentación corregida: comandos Docker vigentes, credenciales generadas, arquitectura PHP y guion de Investigación Operativa.

## Funciones implementadas

| Módulo | Comportamiento disponible |
|---|---|
| M1 · Acceso y seguridad | Cuatro roles, cuentas/activación, OTP por correo, recuperación y revocación de sesiones, auditoría |
| M2 · Gestión académica | Hospitales, convenios, especialidades, periodos, cupos/reservas, internos, tutores, cohortes/requisitos, importación CSV con vista previa |
| M3 · Postulación y rotes | Preferencias, comprobante privado, motor PHP en cola, revisión/aprobación/publicación, validación de versión, reoptimización |
| M4 · Seguimiento | Asistencia, rúbrica, incidencias/resolución, permutas consensuadas, reasignación previa al inicio |
| M5 · Documentos | Actas y libreta PDF, QR de verificación, integridad SHA-256, CSV limitado al ámbito del usuario |
| M6 · Escenarios | Matrices de beneficios/costos, cinco criterios, empates, sensibilidad de α, simulación de nueve combinaciones y elección justificada |
| M7 · Guardias | Disponibilidad, cálculo de tutores, cruces/descanso, aprobación y registro de cumplimiento |

La disponibilidad de estas funciones no equivale a validación institucional completa. La evidencia reproducible de las pruebas se mantiene en [PRUEBAS.md](PRUEBAS.md).

## Pendientes externos y límites

- **Correo institucional externo:** requiere credenciales SMTP y comprobar recepción en la bandeja real. SMTP local con Mailpit sí está cubierto por pruebas de navegador.
- **Validación académica:** convenios, cupos, pesos de mérito, rúbrica, descansos y utilidad de escenarios necesitan aprobación y datos reales.
- **Planificación anual:** el motor resuelve rotes por convocatoria/bloque y comprueba compatibilidad con lo publicado; no optimiza simultáneamente todo el año.
- **Guardias:** asigna tutores a fechas e internos introducidos por Coordinación. No genera fechas ni aplica un límite semanal institucional que no fue especificado.
- **Cambios durante un rote iniciado:** se documentan como incidencias; falta modelar segmentos/vigencias para una reasignación durante el periodo conservando su trayectoria.
- **Documentos oficiales:** PDF/QR verifican registros internos; no son firma digital certificada ni integración con SEDES.
- **Producción:** faltan despliegue HTTPS, operación y validación de carga con usuarios/datos reales. El Compose entregado es local.
- **CI remoto:** el workflow está configurado; ejecutarlo en GitHub depende de publicar estos cambios y del entorno del repositorio. No se afirma una ejecución remota desde esta sesión.

Para presentar: [GUIA_DEMOS.md](GUIA_DEMOS.md). Para explicar el modelo: [EXPOSICION_IO.md](EXPOSICION_IO.md).
