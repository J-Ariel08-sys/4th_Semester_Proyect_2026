<?php

namespace App\Http\Controllers;

use App\Services\AccountAccess;
use App\Services\Audit;
use App\Services\EmailPolicy;
use App\Services\OtpService;
use App\Services\RecordDeletion;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ManagementController extends Controller
{
    public static function definitions(): array
    {
        return [
            'usuarios' => ['table' => 'users', 'title' => 'Cuentas de usuario', 'fields' => ['name' => 'Nombre completo', 'email' => 'Correo', 'role' => 'Rol', 'active' => 'Cuenta activa']],
            'habilitaciones' => ['table' => 'users', 'title' => 'Habilitación de cuentas', 'fields' => ['phone' => 'Teléfono de contacto', 'access_enabled' => 'Autorizar ingreso al portal']],
            'internos' => ['table' => 'users', 'title' => 'Internos y habilitación', 'fields' => ['cohort_id' => 'Cohorte', 'matricula' => 'Matrícula', 'academic_average' => 'Promedio académico (0–100)', 'merit' => 'Méritos (0–100)', 'eligible' => 'Habilitado académicamente']],
            'tutores' => ['table' => 'users', 'title' => 'Límites de supervisión', 'fields' => ['supervision_limit' => 'Internos simultáneos autorizados']],
            'cohortes' => ['table' => 'cohorts', 'title' => 'Cohortes de La Paz', 'fields' => ['name' => 'Nombre de cohorte', 'entry_year' => 'Año de ingreso', 'active' => 'Habilitada']],
            'requisitos' => ['table' => 'academic_requirements', 'title' => 'Requisitos de trayectoria', 'fields' => ['cohort_id' => 'Cohorte', 'specialty_id' => 'Especialidad', 'min_days' => 'Días mínimos por bloque', 'required_blocks' => 'Bloques requeridos']],
            'hospitales' => ['table' => 'hospitals', 'title' => 'Hospitales y convenios', 'fields' => ['name' => 'Nombre', 'agreement_until' => 'Convenio válido hasta', 'tutors_count' => 'Tutores por servicio (s)', 'lambda_e1' => 'λ E1 Normal (pac/h)', 'lambda_e2' => 'λ E2 Sobredemanda (pac/h)', 'lambda_e3' => 'λ E3 Contingencia (pac/h)', 'mu_without_interns' => 'μ₀ sin internos (pac/h)', 'mu_with_interns' => 'μ₁ con internos (pac/h)', 'active' => 'Habilitado']],
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
            abort_unless(auth()->user()->managesAcademics(), 403);
        }

        return self::definitions()[$resource];
    }

    public function index(string $resource, ?int $id = null): View
    {
        $definition = $this->definition($resource);
        $rows = DB::table($definition['table'])->whereNull('deleted_at')
            ->when($resource === 'habilitaciones', fn ($query) => $query->where('role', '!=', 'administrador')->orderByRaw('access_approved_at IS NOT NULL'))
            ->when(in_array($resource, ['internos', 'tutores'], true), fn ($query) => $query->where('role', $resource === 'internos' ? 'interno' : 'tutor'));

        $term = request()->query('buscar');
        $search = $definition['table'] === 'users' && is_string($term) ? mb_substr(trim($term), 0, 120) : '';
        if ($search !== '') {
            $pattern = '%'.addcslashes($search, '%_\\').'%';
            $rows->where(fn ($query) => $query->whereLike('name', $pattern)->orWhereLike('email', $pattern)->orWhereLike('matricula', $pattern));
        }
        $rows = $rows->orderByDesc('id')->paginate(20)->appends($search !== '' ? ['buscar' => $search] : []);
        $edit = $id ? DB::table($definition['table'])->whereNull('deleted_at')->find($id) : null;
        if ($id) {
            abort_unless($edit, 404);
        }
        if ($edit && in_array($resource, ['internos', 'tutores'])) {
            abort_unless($edit->role === ($resource === 'internos' ? 'interno' : 'tutor'), 404);
        }
        if ($edit && $resource === 'habilitaciones') {
            abort_if($edit->role === 'administrador', 403);
            if ($edit->role === 'interno') {
                return $this->index('internos', $id);
            }
        }
        $choices = [
            'cohort_id' => $resource === 'internos'
                ? [1 => 'Sí', 0 => 'No']
                : DB::table('cohorts')->whereNull('deleted_at')->where('active', true)->pluck('name', 'id')->all(),
            'role' => ['administrador' => 'Administrador', 'coordinador' => 'Coordinador', 'tutor' => 'Tutor', 'interno' => 'Interno'],
            'specialty_id' => DB::table('specialties')->whereNull('deleted_at')->pluck('name', 'id')->all(),
            'cycle_id' => DB::table('cycles')->whereNull('deleted_at')->where('starts_on', '>', now()->toDateString())->pluck('name', 'id')->all(),
            'hospital_id' => DB::table('hospitals')->whereNull('deleted_at')->where('active', true)->pluck('name', 'id')->all(),
            'tutor_id' => DB::table('users')->whereNull('deleted_at')->whereNotNull('access_approved_at')->where('role', 'tutor')->where('active', true)->pluck('name', 'id')->all(),
        ];

        $io = $resource === 'hospitales'
            ? app(OperationsResearchController::class)->buildPayload($id ?: (request()->integer('hospital_id') ?: null))
            : null;

        return view('management', compact('definition', 'resource', 'rows', 'edit', 'choices', 'search', 'io'));
    }

    public function save(Request $request, string $resource, ?int $id = null): RedirectResponse
    {
        $definition = $this->definition($resource);
        if ($resource === 'internos') {
            $rawCohort = $request->input('cohort_id');
            if ($rawCohort === '0' || $rawCohort === 0 || $rawCohort === false || $rawCohort === 'no' || $rawCohort === '' || $rawCohort === null) {
                $request->merge(['cohort_id' => null]);
            } elseif ($rawCohort === '1' || $rawCohort === 1 || $rawCohort === true || $rawCohort === 'si') {
                $existingCohortId = $id ? DB::table('users')->where('id', $id)->value('cohort_id') : null;
                if (! $existingCohortId || ! DB::table('cohorts')->where('id', $existingCohortId)->whereNull('deleted_at')->where('active', true)->exists()) {
                    $existingCohortId = DB::table('cohorts')->whereNull('deleted_at')->where('active', true)->value('id')
                        ?? DB::table('cohorts')->insertGetId(['name' => 'Cohorte '.now()->year, 'entry_year' => now()->year, 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
                }
                $request->merge(['cohort_id' => $existingCohortId]);
            }
        }
        $rules = match ($resource) {
            'usuarios' => ['name' => 'required|string|max:150', 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($id)], 'role' => ['required', Rule::in(['administrador', 'coordinador', 'tutor', 'interno'])], 'active' => 'boolean', 'password' => ['nullable', 'confirmed', 'max:255', Password::min(12)->letters()->numbers()], 'eligible' => 'prohibited', 'access_approved_at' => 'prohibited', 'academic_average' => 'prohibited', 'merit' => 'prohibited'],
            'habilitaciones' => ['phone' => 'nullable|string|max:40', 'access_enabled' => 'required|boolean', 'password' => 'prohibited', 'role' => 'prohibited', 'active' => 'prohibited', 'email' => 'prohibited'],
            'internos' => ['cohort_id' => ['nullable', Rule::exists('cohorts', 'id')->whereNull('deleted_at')->where('active', true)], 'phone' => 'nullable|string|max:40', 'matricula' => ['required', 'string', 'max:50', Rule::unique('users')->ignore($id)], 'academic_average' => 'required|numeric|between:0,100', 'merit' => 'required|integer|between:0,100', 'eligible' => 'required|boolean', 'role' => 'prohibited', 'active' => 'prohibited', 'password' => 'prohibited', 'email' => 'prohibited'],
            'tutores' => ['supervision_limit' => 'required|integer|between:1,500'],
            'cohortes' => ['name' => ['required', 'string', 'max:150', Rule::unique('cohorts')->ignore($id)], 'entry_year' => 'required|integer|between:2000,2100', 'active' => 'boolean'],
            'requisitos' => ['cohort_id' => ['required', Rule::exists('cohorts', 'id')->whereNull('deleted_at')], 'specialty_id' => ['required', Rule::exists('specialties', 'id')->whereNull('deleted_at')], 'min_days' => 'required|integer|between:1,730', 'required_blocks' => 'required|integer|between:1,20'],
            'hospitales' => ['name' => ['required', 'string', 'max:150', Rule::unique('hospitals')->ignore($id)], 'agreement_until' => ['required', 'date', 'after_or_equal:today'], 'tutors_count' => 'sometimes|integer|between:1,100', 'lambda_e1' => 'sometimes|numeric|between:0.1,500', 'lambda_e2' => 'sometimes|numeric|between:0.1,500', 'lambda_e3' => 'sometimes|numeric|between:0.1,500', 'mu_without_interns' => 'sometimes|numeric|between:0.1,500', 'mu_with_interns' => 'sometimes|numeric|between:0.1,500', 'active' => 'boolean'],
            'especialidades' => ['name' => ['required', 'string', 'max:150', Rule::unique('specialties')->ignore($id)]],
            'convocatorias' => ['cohort_id' => ['nullable', Rule::exists('cohorts', 'id')->whereNull('deleted_at')], 'name' => 'required|string|max:150', 'specialty_id' => ['required', Rule::exists('specialties', 'id')->whereNull('deleted_at')], 'starts_on' => ['required', 'date', 'after_or_equal:today'], 'ends_on' => 'required|date|after_or_equal:starts_on', 'opens_at' => ['required', 'date', 'after_or_equal:today'], 'closes_at' => 'required|date|after:opens_at|before:starts_on'],
            'plazas' => ['cycle_id' => ['required', Rule::exists('cycles', 'id')->whereNull('deleted_at')], 'hospital_id' => ['required', Rule::exists('hospitals', 'id')->whereNull('deleted_at')], 'tutor_id' => ['required', Rule::exists('users', 'id')->whereNull('deleted_at')], 'capacity' => 'required|integer|between:0,500', 'reserve' => 'required|integer|min:0|lte:capacity'],
        };
        if ($resource === 'usuarios') {
            $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        }
        $data = $request->validate($rules);
        foreach (['active', 'eligible', 'access_enabled'] as $field) {
            if (isset($rules[$field]) && is_string($rules[$field]) && str_contains($rules[$field], 'boolean')) {
                $data[$field] = $request->boolean($field);
            }
        }
        if ($resource === 'usuarios') {
            EmailPolicy::validate($data['email']);
        }
        abort_if(in_array($resource, ['internos', 'tutores', 'habilitaciones']) && ! $id, 422, 'El administrador debe crear la cuenta primero.');
        DB::transaction(function () use ($definition, $data, $resource, $id) {
            if ($definition['table'] === 'users') {
                DB::table('users')->orderBy('id')->lockForUpdate()->get();
            }
            $existing = $id ? DB::table($definition['table'])->whereNull('deleted_at')->where('id', $id)->lockForUpdate()->first() : null;
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
                $passwordChanged = ! empty($data['password']);
                if ($passwordChanged) {
                    $data['password'] = Hash::make($data['password']);
                    $data['activated_at'] = now();
                } elseif (! $existing) {
                    $data['password'] = Hash::make(Str::random(64));
                } else {
                    unset($data['password']);
                }
                if ($existing && ($passwordChanged || $existing->email !== $data['email'] || ! $data['active'] || $existing->role !== $data['role'])) {
                    $data += AccountAccess::revoke($existing);
                    if ($existing->email !== $data['email']) {
                        $data['email_verified_at'] = null;
                        $data['activated_at'] = $passwordChanged ? now() : null;
                    }
                    if ($existing->role !== $data['role']) {
                        $data['access_approved_at'] = null;
                        $data['access_approved_by'] = null;
                        $data['eligible'] = false;
                    }
                    if ($existing->id === auth()->id()) {
                        request()->session()->put('security_version', $data['security_version']);
                    }
                }
            }
            if (in_array($resource, ['internos', 'tutores'])) {
                abort_unless($existing->role === ($resource === 'internos' ? 'interno' : 'tutor'), 404);
            }
            if (in_array($resource, ['internos', 'habilitaciones'], true)) {
                abort_if($existing->role === 'administrador', 403);
                if ($resource === 'habilitaciones' && $existing->role === 'interno') {
                    throw ValidationException::withMessages(['access_enabled' => 'Completa la ficha de Internos y habilitación para autorizar a un interno.']);
                }
                $enabled = $resource === 'internos' ? $data['eligible'] : $data['access_enabled'];
                unset($data['access_enabled']);
                $data['access_approved_at'] = $enabled ? ($existing->access_approved_at ?? now()) : null;
                $data['access_approved_by'] = $enabled ? auth()->id() : null;
                if (! $enabled) {
                    $data += AccountAccess::revoke($existing);
                }
                Audit::record($enabled ? 'cuenta.habilitada' : 'cuenta.habilitacion_revocada', ['usuario' => $id, 'rol' => $existing->role]);
            }
            if ($resource === 'requisitos' && DB::table('academic_requirements')->where('cohort_id', $data['cohort_id'])->where('specialty_id', $data['specialty_id'])->when($id, fn ($q) => $q->where('id', '!=', $id))->exists()) {
                throw ValidationException::withMessages(['specialty_id' => 'Ya hay una regla para esta cohorte y especialidad.']);
            }
            if ($resource === 'convocatorias' && $existing && $existing->status !== 'borrador') {
                throw ValidationException::withMessages(['name' => 'Solo puedes editar convocatorias en borrador.']);
            }
            if ($resource === 'plazas') {
                $cycle = DB::table('cycles')->whereNull('deleted_at')->where('id', $data['cycle_id'])->lockForUpdate()->first();
                if (! $cycle || ! in_array($cycle->status, ['borrador', 'cerrada', 'publicada'], true) || now()->gte($cycle->starts_on) || ($existing && ($existing->cycle_id !== (int) $data['cycle_id'] || $existing->hospital_id !== (int) $data['hospital_id'] || $existing->tutor_id !== (int) $data['tutor_id']))) {
                    throw ValidationException::withMessages(['cycle_id' => 'Ajusta capacidad y reserva antes del inicio, con postulaciones cerradas o en borrador. No cambies hospital, convocatoria ni tutor de una plaza existente.']);
                }
                if ($id && DB::table('assignments')->where('place_id', $id)->count() > (int) $data['capacity'] - (int) $data['reserve']) {
                    throw ValidationException::withMessages(['capacity' => 'Primero redistribuye los internos: la nueva capacidad no puede dejar sobrecupos publicados.']);
                }
                $hospital = DB::table('hospitals')->find($data['hospital_id']);
                $tutor = DB::table('users')->find($data['tutor_id']);
                if (! $hospital->active || $hospital->agreement_until < $cycle->ends_on || ! $tutor->active || ! $tutor->access_approved_at || $tutor->role !== 'tutor') {
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
            if ($resource === 'usuarios' && $passwordChanged) {
                Audit::record('clave.definida_por_administrador', ['usuario' => $id]);
            }
            if ($resource === 'usuarios' && $data['active'] && ! $passwordChanged && (! $existing || $existing->email !== $data['email'])) {
                app(OtpService::class)->request($data['email'], 'activation', request()->ip());
            }
            Audit::record('gestion.guardada', ['recurso' => $resource, 'id' => $id]);
        });

        return redirect()->route('management', $resource)->with('status', 'Registro guardado.'.($resource === 'usuarios' ? ' Las cuentas nuevas, salvo administradores, necesitan la habilitación de Coordinación para ingresar.' : ''));
    }

    public function destroy(Request $request, RecordDeletion $deletion, string $resource, int $id): RedirectResponse
    {
        $this->definition($resource);
        $data = $request->validate(['reason' => 'required|string|min:10|max:1000']);
        $deletion->delete($resource, $id, $data['reason']);

        return redirect()->route('management', $resource)->with('status', 'Registro eliminado. La bitácora conserva el motivo y el historial vinculado permanece disponible.');
    }

    public function transition(Request $request, int $cycle): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['abierta', 'cerrada'])]]);
        DB::transaction(function () use ($cycle, $data) {
            $row = DB::table('cycles')->whereNull('deleted_at')->where('id', $cycle)->lockForUpdate()->first();
            abort_unless($row, 404);
            $expected = $data['status'] === 'abierta' ? 'borrador' : 'abierta';
            abort_unless($row->status === $expected, 409, 'Transición de convocatoria inválida.');
            if ($data['status'] === 'abierta' && ! DB::table('places')->whereNull('deleted_at')->where('cycle_id', $cycle)->exists()) {
                throw ValidationException::withMessages(['status' => 'Configura al menos una plaza antes de abrir.']);
            }
            DB::table('cycles')->where('id', $cycle)->update(['status' => $data['status'], 'updated_at' => now()]);
            Audit::record('convocatoria.estado', ['id' => $cycle, 'estado' => $data['status']]);
        });

        return back()->with('status', 'Estado actualizado.');
    }
}
