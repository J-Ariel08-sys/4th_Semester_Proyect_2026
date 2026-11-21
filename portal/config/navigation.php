<?php

$navigation = [
    'administrador' => [
        'Usuarios y permisos' => [['Cuentas de usuario', 'management', 'usuarios'], ['Roles y permisos', 'permissions']],
        'Seguridad' => [['Bitácora', 'audit'], ['Accesos y bloqueos', 'audit', null, '?access=1']],
        'Configuración' => [['Configuración', 'settings']],
    ],
    'coordinador' => [
        'Registro' => [['Habilitar cuentas', 'management', 'habilitaciones'], ['Internos', 'management', 'internos'], ['Cohortes', 'management', 'cohortes'], ['Requisitos', 'management', 'requisitos'], ['Hospitales', 'management', 'hospitales'], ['Especialidades', 'management', 'especialidades'], ['Cupos', 'management', 'plazas'], ['Convocatorias', 'management', 'convocatorias']],
        'Rotes' => [['Rotes', 'rotations']],
        'Escenarios' => [['Escenarios', 'scenarios']],
        'Guardias y tutores' => [['Calendario de guardias', 'shifts'], ['Tutores', 'management', 'tutores']],
        'Seguimiento' => [['Seguimiento', 'clinical']],
        'Reportes' => [['Reportes', 'reports']],
    ],
    'interno' => [
        'Postulaciones' => [['Postulaciones', 'rotations']],
        'Mis rotes y solicitudes' => [['Mis rotes y solicitudes', 'clinical']],
        'Mis guardias' => [['Mis guardias', 'shifts']],
        'Mi libreta' => [['Mi libreta', 'reports']],
    ],
    'tutor' => [
        'Mis internos' => [['Mis internos', 'clinical']],
        'Mis guardias' => [['Mis guardias', 'shifts']],
        'Mis reportes' => [['Mis reportes', 'reports']],
    ],
];

$navigation['administrador'] += $navigation['coordinador'];

return $navigation;
