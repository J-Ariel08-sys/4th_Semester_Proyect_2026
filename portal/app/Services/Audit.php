<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Audit
{
    private const ACTIONS = [
        'sesion.inicio' => 'Inicio de sesión',
        'sesion.cierre' => 'Cierre de sesión',
        'sesion.fallida' => 'Intento de inicio de sesión fallido',
        'sesion.pendiente_habilitacion' => 'Ingreso pendiente de habilitación',
        'cuenta.habilitada' => 'Cuenta habilitada',
        'cuenta.habilitacion_revocada' => 'Habilitación de cuenta revocada',
        'cuenta.actualizada' => 'Cuenta actualizada',
        'cuenta.activada' => 'Cuenta activada',
        'clave.definida_por_administrador' => 'Contraseña asignada por Administración',
        'clave.restablecida' => 'Contraseña restablecida',
        'otp.solicitado' => 'Código de verificación solicitado',
        'gestion.guardada' => 'Registro guardado',
        'gestion.eliminada' => 'Registro archivado',
        'convocatoria.estado' => 'Estado de convocatoria actualizado',
        'postulacion.registrada' => 'Postulación registrada',
        'postulacion.excepcion' => 'Excepción de postulación autorizada',
        'asignacion.reasignada' => 'Rote reasignado',
        'optimizacion.encolada' => 'Cálculo de rotes en espera',
        'optimizacion.reintentada' => 'Cálculo de rotes reintentado',
        'optimizacion.cancelada' => 'Cálculo de rotes cancelado',
        'optimizacion.aprobada' => 'Asignaciones publicadas',
        'optimizacion.politica' => 'Política aplicada al cálculo de rotes',
        'plan.en_revision' => 'Plan enviado a revisión',
        'plan.aprobado' => 'Plan aprobado',
        'escenario.calculado' => 'Estudio de decisión calculado',
        'escenario.encolado' => 'Simulación en espera',
        'politica.aprobada' => 'Política de asignación aprobada',
        'seguimiento.registrado' => 'Seguimiento clínico registrado',
        'incidencia.abierta' => 'Incidencia registrada',
        'incidencia.resuelta' => 'Incidencia resuelta',
        'disponibilidad.registrada' => 'Disponibilidad de tutor registrada',
        'guardias.encoladas' => 'Cálculo de guardias en espera',
        'guardias.aprobadas' => 'Calendario de guardias aprobado',
        'guardia.cumplida' => 'Cumplimiento de guardia registrado',
        'permuta.solicitada' => 'Permuta solicitada',
        'permuta.consentimiento' => 'Respuesta a solicitud de permuta registrada',
        'permuta.aprobada' => 'Permuta aprobada',
        'acta.emitida' => 'Acta PDF emitida',
        'libreta.emitida' => 'Libreta PDF emitida',
        'configuracion.actualizada' => 'Configuración actualizada',
    ];

    public static function title(string $action): string
    {
        return self::ACTIONS[$action] ?? 'Acción registrada';
    }

    public static function describe(string $action, ?string $json): string
    {
        $details = json_decode($json ?? '', true);
        if ($action === 'sesion.fallida') {
            return 'El intento no permitió iniciar sesión.';
        }

        $labels = [
            'recurso' => 'Sección',
            'rol' => 'Rol',
            'estado' => 'Estado',
            'motivo' => 'Motivo',
            'convocatoria' => 'Convocatoria',
            'asignacion' => 'Asignación',
            'usuario' => 'Cuenta',
            'tutor' => 'Tutor',
            'lote' => 'Calendario',
            'propuesta' => 'Propuesta',
            'estudio' => 'Estudio',
            'alternativa' => 'Alternativa',
            'fecha' => 'Fecha',
        ];
        $parts = [];
        foreach ($labels as $key => $label) {
            $value = $details[$key] ?? null;
            if ((is_string($value) && trim($value) !== '') || is_int($value)) {
                $parts[] = $label.': '.$value;
            }
        }

        return $parts ? implode(' · ', $parts) : 'Sin detalle adicional';
    }

    public static function record(string $action, array $details = []): void
    {
        DB::table('audit_logs')->insert(['user_id' => Auth::id(), 'action' => $action, 'details' => json_encode($details, JSON_THROW_ON_ERROR), 'created_at' => now()]);
    }
}
