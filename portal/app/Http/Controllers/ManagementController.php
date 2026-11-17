<?php

namespace App\Http\Controllers;

use App\Services\Audit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ManagementController extends Controller
{
    public static function definitions(): array
    {
        return [
            'usuarios' => ['table' => 'users', 'title' => 'Cuentas de usuario', 'fields' => ['name' => 'Nombre completo', 'email' => 'Correo', 'role' => 'Rol', 'active' => 'Cuenta habilitada']],
            'internos' => ['table' => 'users', 'title' => 'Internos y habilitación', 'fields' => ['cohort_id' => 'Cohorte (opcional)','matricula' => 'Matrícula', 'academic_average' => 'Promedio académico (0–100)', 'merit' => 'Méritos (0–100)', 'eligible' => 'Habilitado académicamente']],
            'tutores' => ['table' => 'users', 'title' => 'Límites de supervisión', 'fields' => ['supervision_limit' => 'Internos simultáneos autorizados']],
            'cohortes' => ['table' => 'cohorts', 'title' => 'Cohortes de La Paz', 'fields' => ['name' => 'Nombre de cohorte', 'entry_year' => 'Año de ingreso', 'active' => 'Habilitada']],
            'requisitos' => ['table' => 'academic_requirements', 'title' => 'Requisitos de trayectoria (demostración)', 'fields' => ['cohort_id' => 'Cohorte', 'specialty_id' => 'Especialidad', 'min_days' => 'Días mínimos por bloque', 'required_blocks' => 'Bloques requeridos']],
            'hospitales' => ['table' => 'hospitals', 'title' => 'Hospitales y convenios', 'fields' => ['name' => 'Nombre', 'agreement_until' => 'Convenio válido hasta', 'active' => 'Habilitado']],
            'especialidades' => ['table' => 'specialties', 'title' => 'Especialidades', 'fields' => ['name' => 'Nombre']],
            'convocatorias' => ['table' => 'cycles', 'title' => 'Convocatorias de rotación', 'fields' => ['name' => 'Nombre', 'cohort_id' => 'Cohorte (opcional)', 'specialty_id' => 'Especialidad', 'starts_on' => 'Inicio del rote', 'ends_on' => 'Fin del rote', 'opens_at' => 'Apertura de postulaciones', 'closes_at' => 'Cierre de postulaciones']],
            'plazas' => ['table' => 'places', 'title' => 'Cupos hospitalarios', 'fields' => ['cycle_id' => 'Convocatoria', 'hospital_id' => 'Hospital', 'tutor_id' => 'Tutor responsable', 'capacity' => 'Capacidad autorizada', 'reserve' => 'Cupos de reserva']],
        ];
    }

    private function definition(string $resource): array
    {
        abort_unless(isset(self::definitions()[$resource]), 404);
        if ($resource === 'usuarios') {
            abort_unless(auth()->user()->role === 'administrador', 403);
        } else {
            abort_unless(auth()->user()->role === 'coordinador', 403);
        }

        return self::definitions()[$resource];
    }

    public function index(string $resource, ?int $id = null): View
    {
        $definition = $this->definition($resource);
        $rows = DB::table($definition['table'])->when(in_array($resource, ['internos', 'tutores']), fn ($q) => $q->where('role', $resource === 'internos' ? 'interno' : 'tutor'))->orderByDesc('id')->paginate(20);
        $edit = $id ? DB::table($definition['table'])->find($id) : null;
        if ($id) {
            abort_unless($edit, 404);
        }
        if ($edit && in_array($resource, ['internos', 'tutores'])) {
            abort_unless($edit->role === ($resource === 'internos' ? 'interno' : 'tutor'), 404);
        }
        $choices = [
            'cohort_id' => DB::table('cohorts')->where('active',true)->pluck('name','id')->all(),
            'role' => ['administrador' => 'Administrador', 'coordinador' => 'Coordinador', 'tutor' => 'Tutor', 'interno' => 'Interno'],
            'specialty_id' => DB::table('specialties')->pluck('name', 'id')->all(),
            'cycle_id' => DB::table('cycles')->where('starts_on', '>', now()->toDateString())->pluck('name', 'id')->all(),
            'hospital_id' => DB::table('hospitals')->where('active', true)->pluck('name', 'id')->all(),
            'tutor_id' => DB::table('users')->where('role', 'tutor')->where('active', true)->pluck('name', 'id')->all(),
        ];

        return view('management', compact('definition', 'resource', 'rows', 'edit', 'choices'));
    }

    public function save(Request $request, string $resource, ?int $id = null): RedirectResponse
    {
        $definition = $this->definition($resource);
        $rules = match ($resource) {
            'usuarios' => ['name' => 'required|string|max:150', 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($id)], 'role' => ['required', Rule::in(['administrador', 'coordinador', 'tutor', 'interno'])], 'active' => 'boolean', 'password' => 'prohibited', 'eligible' => 'prohibited', 'academic_average' => 'prohibited', 'merit' => 'prohibited'],
            'internos' => ['cohort_id' => 'nullable|exists:cohorts,id', 'matricula' => ['required', 'string', 'max:50', Rule::unique('users')->ignore($id)], 'academic_average' => 'required|numeric|between:0,100', 'merit' => 'required|integer|between:0,100', 'eligible' => 'boolean', 'role' => 'prohibited', 'active' => 'prohibited', 'password' => 'prohibited', 'email' => 'prohibited'],
            'tutores' => ['supervision_limit' => 'required|integer|between:1,500'],
            'cohortes' => ['name' => ['required','string','max:150',Rule::unique('cohorts')->ignore($id)], 'entry_year' => 'required|integer|between:2000,2100', 'active' => 'boolean'],
            'requisitos' => ['cohort_id' => 'required|exists:cohorts,id', 'specialty_id' => 'required|exists:specialties,id', 'min_days' => 'required|integer|between:1,730', 'required_blocks' => 'required|integer|between:1,20'],
            'hospitales' => ['name' => ['required', 'string', 'max:150', Rule::unique('hospitals')->ignore($id)], 'agreement_until' => 'required|date', 'active' => 'boolean'],
            'especialidades' => ['name' => ['required', 'string', 'max:150', Rule::unique('specialties')->ignore($id)]],
            'convocatorias' => ['cohort_id' => 'nullable|exists:cohorts,id','name' => 'required|string|max:150', 'specialty_id' => 'required|exists:specialties,id', 'starts_on' => 'required|date', 'ends_on' => 'required|date|after_or_equal:starts_on', 'opens_at' => 'required|date', 'closes_at' => 'required|date|after:opens_at|before:starts_on'],
            'plazas' => ['cycle_id' => 'required|exists:cycles,id', 'hospital_id' => 'required|exists:hospitals,id', 'tutor_id' => 'required|exists:users,id', 'capacity' => 'required|integer|between:0,500', 'reserve' => 'required|integer|min:0|lte:capacity'],
        };
        if ($resource === 'usuarios') {
            $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        }
        $data = $request->validate($rules);
        foreach (['active', 'eligible'] as $field) {
            if (($rules[$field] ?? null) === 'boolean') {
                $data[$field] = $request->boolean($field);
            }
        }
        if ($resource === 'usuarios') { \App\Services\EmailPolicy::validate($data['email']); }
        abort_if(in_array($resource, ['internos', 'tutores']) && ! $id, 422, 'El administrador debe crear la cuenta primero.');
        DB::transaction(function () use ($definition, $data, $resource, $id) {
            if ($definition['table'] === 'users') { DB::table('users')->orderBy('id')->lockForUpdate()->get(); }
            $existing = $id ? DB::table($definition['table'])->where('id', $id)->lockForUpdate()->first() : null;
            if ($id) {
                abort_unless($existing, 404);
            }
            if ($resource === 'usuarios') {
                $data['email'] = strtolower($data['email']);
                if ($existing && $existing->role === 'administrador' && $existing->active && (! $data['active'] || $data['role'] !== 'administrador') && DB::table('users')->where('role', 'administrador')->where('active', true)->count() <= 1) {
                    throw ValidationException::withMessages(['role' => 'Debe conservarse al menos un administrador activo.']);
                }
                if ($existing && $existing->role !== $data['role'] && (DB::table('assignments')->where('user_id', $id)->orWhere('tutor_id', $id)->exists() || DB::table('places')->where('tutor_id', $id)->exists() || DB::table('applications')->where('user_id', $id)->exists())) {
                    throw ValidationException::withMessages(['role' => 'La cuenta tiene historial académico incompatible con el cambio de rol.']);
                }
                if (! $existing) { $data['password'] = Hash::make(\Illuminate\Support\Str::random(64)); }
                if ($existing && ($existing->email !== $data['email'] || ! $data['active'] || $existing->role !== $data['role'])) {
                    $data['security_version'] = $existing->security_version + 1;
                    $data['remember_token'] = null;
                    if ($existing->email !== $data['email']) { $data['activated_at'] = null; $data['email_verified_at'] = null; }
                    DB::table('sessions')->where('user_id', $id)->delete();
                    DB::table('otp_challenges')->where('user_id', $id)->update(['revoked_at' => now()]);
                }
            }
            if (in_array($resource, ['internos', 'tutores'])) {
                abort_unless($existing->role === ($resource === 'internos' ? 'interno' : 'tutor'), 404);
            }
            if ($resource === 'requisitos' && DB::table('academic_requirements')->where('cohort_id',$data['cohort_id'])->where('specialty_id',$data['specialty_id'])->when($id,fn ($q)=>$q->where('id','!=',$id))->exists()) { throw ValidationException::withMessages(['specialty_id'=>'Ya hay una regla para esta cohorte y especialidad.']); }
            if ($resource === 'convocatorias' && $existing && $existing->status !== 'borrador') {
                throw ValidationException::withMessages(['name' => 'Solo puedes editar convocatorias en borrador.']);
            }
            if ($resource === 'plazas') {
                $cycle = DB::table('cycles')->where('id', $data['cycle_id'])->lockForUpdate()->first();
                if (! in_array($cycle->status, ['borrador', 'cerrada', 'publicada'], true) || now()->gte($cycle->starts_on) || ($existing && ($existing->cycle_id !== (int) $data['cycle_id'] || $existing->hospital_id !== (int) $data['hospital_id'] || $existing->tutor_id !== (int) $data['tutor_id']))) {
                    throw ValidationException::withMessages(['cycle_id' => 'Ajusta capacidad y reserva antes del inicio, con postulaciones cerradas o en borrador. No cambies hospital, convocatoria ni tutor de una plaza existente.']);
                }
                if ($id && DB::table('assignments')->where('place_id', $id)->count() > (int) $data['capacity'] - (int) $data['reserve']) {
                    throw ValidationException::withMessages(['capacity' => 'Primero redistribuye los internos: la nueva capacidad no puede dejar sobrecupos publicados.']);
                }
                $hospital = DB::table('hospitals')->find($data['hospital_id']);
                $tutor = DB::table('users')->find($data['tutor_id']);
                if (! $hospital->active || $hospital->agreement_until < $cycle->ends_on || ! $tutor->active || $tutor->role !== 'tutor') {
                    throw ValidationException::withMessages(['hospital_id' => 'Verifica convenio vigente, hospital activo y tutor activo.']);
                }
                if (DB::table('places')->where('cycle_id', $cycle->id)->where('hospital_id', $hospital->id)->when($id, fn ($q) => $q->where('id', '!=', $id))->exists()) {
                    throw ValidationException::withMessages(['hospital_id' => 'Ya existe un cupo para este hospital en la convocatoria.']);
                }
                if (DB::table('places')->join('cycles', 'cycles.id', '=', 'places.cycle_id')->where('places.hospital_id', $hospital->id)->where('cycles.specialty_id', $cycle->specialty_id)->where('cycles.id', '!=', $cycle->id)->where('cycles.starts_on', '<=', $cycle->ends_on)->where('cycles.ends_on', '>=', $cycle->starts_on)->exists()) {
                    throw ValidationException::withMessages(['cycle_id' => 'Existe otra oferta del mismo hospital y especialidad con fechas superpuestas.']);
                }
            }
            $data['updated_at'] = now();
            if ($id) {
                DB::table($definition['table'])->where('id', $id)->update($data);
            } else {
                $id = DB::table($definition['table'])->insertGetId([...$data, 'created_at' => now()]);
            }
            if ($resource === 'usuarios' && ! $existing && $data['active']) {
                app(\App\Services\OtpService::class)->request($data['email'], 'activation', request()->ip());
            }
            Audit::record('gestion.guardada', ['recurso' => $resource, 'id' => $id]);
        });

        return redirect()->route('management', $resource)->with('status', 'Registro guardado.');
    }

    public function transition(Request $request, int $cycle): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['abierta', 'cerrada'])]]);
        DB::transaction(function () use ($cycle, $data) {
            $row = DB::table('cycles')->where('id', $cycle)->lockForUpdate()->first();
            abort_unless($row, 404);
            $expected = $data['status'] === 'abierta' ? 'borrador' : 'abierta';
            abort_unless($row->status === $expected, 409, 'Transición de convocatoria inválida.');
            if ($data['status'] === 'abierta' && ! DB::table('places')->where('cycle_id', $cycle)->exists()) {
                throw ValidationException::withMessages(['status' => 'Configura al menos una plaza antes de abrir.']);
            }
            DB::table('cycles')->where('id', $cycle)->update(['status' => $data['status'], 'updated_at' => now()]);
            Audit::record('convocatoria.estado', ['id' => $cycle, 'estado' => $data['status']]);
        });

        return back()->with('status', 'Estado actualizado.');
    }
}
