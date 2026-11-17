<?php

return [
    'administrador' => [
        'Usuarios y permisos' => [['Cuentas de usuario', 'management', 'usuarios'], ['Roles y permisos', 'permissions']],
        'Seguridad y auditoría' => [['Bitácora de acciones', 'audit'], ['Accesos y bloqueos', 'audit', null, '?access=1']],
        'Configuración general' => [['Configuración general', 'settings']],
    ],
    'coordinador' => [
        'Gestión académica y hospitalaria' => [['Internos y habilitación', 'management', 'internos'], ['Cohortes y trayectoria', 'management', 'cohortes'], ['Requisitos de especialidades', 'management', 'requisitos'], ['Hospitales y convenios', 'management', 'hospitales'], ['Especialidades y cupos', 'management', 'plazas'], ['Periodos y calendario', 'management', 'convocatorias']],
        'Postulaciones' => [['Postulaciones recibidas', 'rotations', null, '#postulaciones'], ['Preferencias y pendientes', 'rotations', null, '#postulaciones'], ['Apertura y cierre según calendario', 'rotations']],
        'Asignaciones de rotes' => [['Propuestas', 'rotations', null, '#propuestas'], ['Conflictos', 'rotations', null, '#propuestas'], ['Versiones', 'rotations', null, '#propuestas'], ['Aprobación y publicación', 'rotations', null, '#propuestas']],
        'Escenarios y decisiones' => [['Escenarios y alternativas', 'scenarios'], ['Matriz de decisión', 'scenarios', null, '#matriz'], ['Sensibilidad', 'scenarios', null, '#resultados'], ['Política seleccionada', 'scenarios', null, '#resultados']],
        'Guardias y tutores' => [['Tutores y disponibilidad', 'shifts'], ['Límites de supervisión', 'management', 'tutores'], ['Calendario de guardias', 'shifts', null, '#calendario']],
        'Seguimiento y solicitudes' => [['Asistencia y evaluaciones', 'clinical'], ['Permutas', 'clinical', null, '#permutas'], ['Incidencias', 'clinical'], ['Reasignaciones', 'clinical']],
        'Documentos y reportes' => [['Actas y documentos', 'reports'], ['Indicadores', 'reports'], ['Exportaciones', 'reports', null, '#exportaciones']],
    ],
    'interno' => [
        'Mi perfil académico' => [['Datos y habilitación', 'account']],
        'Oferta y postulación' => [['Oferta disponible', 'rotations'], ['Preferencias', 'rotations'], ['Envío', 'rotations'], ['Comprobante', 'rotations']],
        'Mis rotes' => [['Asignaciones publicadas e historial propio', 'clinical']],
        'Mis guardias' => [['Calendario publicado y supervisión', 'shifts']],
        'Mis solicitudes' => [['Permuta', 'clinical', null, '#permutas'], ['Incidencia', 'clinical'], ['Seguimiento de respuestas', 'clinical']],
        'Mi libreta digital' => [['Asistencia, guardias realizadas y evaluaciones propias', 'reports']],
        'Mis documentos' => [['Comprobantes y documentos emitidos para mi cuenta', 'reports', null, '#documentos']],
    ],
    'tutor' => [
        'Mis internos' => [['Asignaciones bajo supervisión y ficha de seguimiento', 'clinical']],
        'Mi disponibilidad' => [['Mi disponibilidad', 'shifts']],
        'Guardias y supervisión' => [['Calendario asignado y reporte de conflictos', 'shifts', null, '#calendario']],
        'Asistencia' => [['Registro de asistencia y guardias realizadas', 'clinical']],
        'Evaluaciones' => [['Rúbrica, revisión y finalización', 'clinical']],
        'Incidencias' => [['Registro y respuesta de Coordinación', 'clinical']],
        'Mis reportes' => [['Asistencia y evaluaciones dentro de mi alcance', 'reports']],
    ],
];
