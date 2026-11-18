<x-layout :title="$definition['title']">
    <div class="page-heading"><div><p class="eyebrow">CONFIGURACIÓN DEL INTERNADO</p><h1>{{ $definition['title'] }}</h1><p>Administra los datos y las autorizaciones de acceso.</p></div></div>
    <div class="tabs">
        @foreach(\App\Http\Controllers\ManagementController::definitions() as $key => $def)
            @if(auth()->user()->isAdministrator() || $key !== 'usuarios')
                <a class="{{ $resource === $key ? 'active' : '' }}" href="{{ route('management', $key) }}">{{ $def['title'] }}</a>
            @endif
        @endforeach
    </div>
    @if(in_array($resource, ['usuarios', 'habilitaciones', 'internos']))
        <section class="card">
            <h2>Crear, completar y habilitar</h2>
            <p>Administración crea la cuenta con su correo, rol y contraseña. Coordinación completa los datos del interno y autoriza su ingreso; para tutores y coordinadores utiliza Habilitación de cuentas. Los administradores pueden realizar ambos pasos.</p>
            @if($resource === 'usuarios')
                <p class="help">Si dejas vacía la contraseña inicial, se enviará un código de activación por correo. Definir o recuperar una contraseña no reemplaza la habilitación de Coordinación.</p>
            @elseif(! $edit)
                <p class="help">Selecciona «Completar datos» en la cuenta que vas a habilitar. Las cuentas pendientes pueden tener contraseña y seguir bloqueadas para el ingreso.</p>
            @endif
        </section>
    @endif
    @if($edit || ! in_array($resource, ['internos', 'tutores', 'habilitaciones']))
        <section class="card" id="editor">
            <h2>{{ $edit ? 'Editar registro #'.$edit->id : 'Nuevo registro' }}</h2>
            @if($edit && $definition['table'] === 'users')
                <p class="record-name">{{ $edit->name }} · {{ $edit->email }} · {{ ucfirst($edit->role) }}</p>
                <p class="help">Contraseña: {{ $edit->activated_at ? 'definida' : 'pendiente' }}. Acceso: {{ $edit->role === 'administrador' || ($edit->access_approved_at && ($edit->role !== 'interno' || $edit->eligible)) ? 'autorizado' : 'pendiente de Coordinación' }}. Cuenta: {{ $edit->active ? 'activa' : 'inactiva' }}.</p>
            @endif
            <form method="post" action="{{ route('management.save', [$resource, $edit?->id]) }}">
                @csrf
                <div class="form-grid">
                    @foreach($definition['fields'] as $field => $label)
                        @php($value = $field === 'access_enabled' ? (bool) $edit?->access_approved_at : ($edit?->$field ?? ($field === 'active' ? true : (in_array($field, ['merit', 'reserve']) ? 0 : ''))))
                        @if(in_array($field, ['active', 'eligible', 'access_enabled']))
                            <label class="check"><input type="hidden" name="{{ $field }}" value="0"><input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $value))>{{ $label }}</label>
                        @elseif(isset($choices[$field]))
                            @php($isInternCohort = $resource === 'internos' && $field === 'cohort_id')
                            <label>{{ $label }}<select name="{{ $field }}" @required(! $isInternCohort && ($field !== 'cohort_id' || $resource === 'requisitos'))>@if(! $isInternCohort)<option value="">Selecciona una opción</option>@endif @foreach($choices[$field] as $option => $text)<option value="{{ $option }}" @selected((string) old($field, $isInternCohort ? ((bool) $value ? '1' : '0') : $value) === (string) $option)>{{ $text }}</option>@endforeach</select></label>
                        @else
                            @php($type = match($field) {'email' => 'email', 'academic_average', 'merit', 'capacity', 'reserve', 'supervision_limit', 'entry_year', 'min_days', 'required_blocks' => 'number', 'starts_on', 'ends_on', 'agreement_until' => 'date', 'opens_at', 'closes_at' => 'datetime-local', default => 'text'})
                            <label>{{ $label }}<input type="{{ $type }}" name="{{ $field }}" value="{{ old($field, $value) }}" @if($type === 'number') min="{{ in_array($field, ['min_days', 'required_blocks', 'supervision_limit']) ? 1 : 0 }}" step="{{ $field === 'academic_average' ? '0.01' : '1' }}" @if(in_array($field, ['academic_average', 'merit'])) max="100" @endif @elseif($type === 'date') min="{{ now()->toDateString() }}" @elseif($type === 'datetime-local') min="{{ now()->format('Y-m-d\TH:i') }}" @endif @required($field !== 'phone')></label>
                        @endif
                    @endforeach
                    @if($resource === 'usuarios')
                        <x-password-field :label="$edit ? 'Nueva contraseña (opcional)' : 'Contraseña inicial (opcional)'" minlength="12" />
                        <x-password-field name="password_confirmation" label="Confirmar contraseña" minlength="12" />
                        <p class="help full">Usa al menos 12 caracteres con letras y números. En una edición, deja ambos campos vacíos para conservar la clave. Cambiar la clave cierra las sesiones anteriores. Si cambias el correo, define una nueva contraseña o activa el acceso por correo.</p>
                    @endif
                    @if($resource === 'internos')
                        <label>Teléfono de contacto<input name="phone" maxlength="40" value="{{ old('phone', $edit?->phone) }}"></label>
                        <p class="help full">Completa cohorte, matrícula, promedio y méritos. Marca «Habilitado académicamente» para autorizar el ingreso y la postulación. La cuenta también debe estar activa y tener contraseña.</p>
                    @endif
                </div>
                <div class="form-actions"><button>Guardar registro</button>@if($edit)<a href="{{ route('management', $resource) }}">Cancelar edición</a>@endif</div>
            </form>
            @if($edit)
                <form method="post" action="{{ route('management.destroy', [$resource, $edit->id]) }}" class="deletion-form" data-confirm="¿Eliminar este registro? Se retirará de la gestión y la acción quedará registrada en bitácora.">
                    @csrf @method('DELETE')
                    <h3>Eliminar registro</h3>
                    <p class="help">La eliminación retira el registro de los listados de gestión. Se conservan los datos vinculados a rotes, seguimiento y documentos para mantener el historial. Eliminar una cuenta también bloquea su ingreso.</p>
                    <label>Motivo de eliminación<textarea name="reason" minlength="10" maxlength="1000" required placeholder="Indica por qué debe eliminarse este registro"></textarea></label>
                    <div class="form-actions"><button class="button danger">Eliminar registro</button></div>
                </form>
            @endif
        </section>
    @endif
    <section class="card">
        <div class="card-head"><h2>Registros</h2><span class="badge neutral">{{ $rows->total() }} en total</span></div>
        <div class="table-wrap"><table><thead><tr><th>ID / nombre</th>
            @foreach($definition['fields'] as $field => $label)<th>{{ $label }}</th>@endforeach
            @if(in_array($resource, ['usuarios', 'internos']))<th>Acceso al portal</th>@endif
            <th>Acción</th></tr></thead><tbody>
            @forelse($rows as $row)
                <tr><td>#{{ $row->id }} {{ $row->name ?? '' }}@if($resource === 'habilitaciones')<br><small>{{ $row->email }} · {{ $row->role }}</small>@endif</td>
                    @foreach($definition['fields'] as $field => $label)
                        <td>@if($field === 'access_enabled'){{ $row->access_approved_at && ($row->role !== 'interno' || $row->eligible) ? 'Autorizado' : 'Pendiente' }}@elseif($resource === 'internos' && $field === 'cohort_id'){{ $row->cohort_id ? 'Sí' : 'No' }}@else{{ in_array($field, ['active', 'eligible']) ? ($row->$field ? 'Sí' : 'No') : ($choices[$field][$row->$field] ?? $row->$field) }}@endif</td>
                    @endforeach
                    @if(in_array($resource, ['usuarios', 'internos']))<td>{{ ! $row->active ? 'Inactiva' : (! $row->activated_at ? 'Sin contraseña' : ($row->role !== 'administrador' && (! $row->access_approved_at || ($row->role === 'interno' && ! $row->eligible)) ? 'Pendiente de Coordinación' : 'Habilitado')) }}</td>@endif
                    <td><a href="{{ route('management.edit', [$resource, $row->id]) }}">{{ in_array($resource, ['internos', 'habilitaciones']) ? 'Completar datos' : 'Editar' }}</a>
                    @if($resource === 'usuarios' && ! $row->activated_at && $row->active)<form method="post" action="{{ route('users.activate', $row->id) }}">@csrf<button class="button small">Enviar activación</button></form>@endif</td>
                </tr>
            @empty<tr><td colspan="20" class="empty">Todavía no hay registros.</td></tr>@endforelse
        </tbody></table></div>
        {{ $rows->links() }}
    </section>
</x-layout>
