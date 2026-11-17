<?php

return [
    'administrador' => [
        'Usuarios y permisos' => [['Cuentas de usuario', 'management', 'usuarios'], ['Roles y permisos', 'permissions']],
        'Seguridad y auditoría' => [['Bitácora de acciones', 'audit'], ['Accesos y bloqueos', 'audit', null, '?access=1']],
        'Configuración general' => [['Configuración general', 'settings']],
    ],
    'coordinador' => [
        'Gestión académica y hospitalaria' => [['Internos y habilitación', 'management', 'internos'], ['Cohortes y trayectoria', 'management', 'cohortes'], ['Requisitos de especialidades', 'management', 'requisitos'], ['Hospitales y convenios', 'management', 'hospitales'], ['Especialidades y cupos', 'management', 'plazas'], ['Periodos y calendario', 'management', 'convocatorias']],
        'Postulación y rotes' => [['Postulación y rotes', 'rotations']],
        'Escenarios y decisiones' => [['Escenarios y decisiones', 'scenarios']],
        'Guardias y tutores' => [['Disponibilidad y calendario', 'shifts'], ['Tutores y límites', 'management', 'tutores']],
        'Seguimiento clínico' => [['Seguimiento clínico', 'clinical']],
        'Documentos y reportes' => [['Documentos y reportes', 'reports']],
    ],
    'interno' => [
        'Mi perfil académico' => [['Datos y habilitación', 'account']],
        'Oferta y postulación' => [['Oferta y postulación', 'rotations']],
        'Mis rotes y solicitudes' => [['Mis rotes y solicitudes', 'clinical']],
        'Mis guardias' => [['Mis guardias', 'shifts']],
        'Mi libreta y documentos' => [['Mi libreta y documentos', 'reports']],
    ],
    'tutor' => [
        'Mis internos' => [['Mis internos', 'clinical']],
        'Disponibilidad y guardias' => [['Disponibilidad y guardias', 'shifts']],
        'Mis reportes' => [['Mis reportes', 'reports']],
    ],
];
