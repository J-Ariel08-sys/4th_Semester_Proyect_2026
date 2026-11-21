@php
    $canCreate = !in_array($resource, ['internos', 'tutores', 'habilitaciones'], true);
    $createLabel = $resource === 'usuarios' ? 'Nueva cuenta' : 'Nuevo registro';
    $showEditor = $edit || ($canCreate && request()->boolean('nuevo'));
    $title = $showEditor ? ($edit ? ($definition['table'] === 'users' ? 'Editar cuenta' : 'Editar registro') : $createLabel) : $definition['title'];
    $tableFields = array_diff_key($definition['fields'], $resource === 'usuarios' ? ['name' => true, 'active' => true] : ['name' => true]);
    $columnCount = count($tableFields) + (in_array($resource, ['usuarios', 'internos'], true) ? 3 : 2);
@endphp
<x-layout :title="$title">
    <div class="page-heading">
        <h1>{{ $title }}</h1>
        @if(!$showEditor && $canCreate)
            <a class="button" href="{{ route('management', ['resource' => $resource, 'nuevo' => 1]) }}">{{ $createLabel }}</a>
        @endif
    </div>
    @if($showEditor)
        <section class="card" id="editor">
            @if($edit && $definition['table'] === 'users')
                <p class="record-name">{{ $edit->name }} · {{ $edit->email }} · {{ ucfirst($edit->role) }}</p>
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
                        <p class="help full">{{ $edit
                            ? 'Usa 12 caracteres o más, con letras y números. Deja los campos vacíos para conservar la clave.'
                            : 'Usa 12 caracteres o más, con letras y números. Si dejas la clave vacía, la cuenta se activará por correo.' }}</p>
                    @endif
                    @if($resource === 'internos')
                        <label>Teléfono de contacto<input name="phone" maxlength="40" value="{{ old('phone', $edit?->phone) }}"></label>
                        <p class="help full">Marca «Habilitado académicamente» para autorizar el ingreso y la postulación.</p>
                    @endif
                </div>
                <div class="form-actions">
                    <button>Guardar registro</button>
                    <a class="button secondary" href="{{ route('management', $resource) }}">Cancelar</a>
                </div>
            </form>
            @if($edit)
                <details class="deletion-form" @if($errors->hasAny(['reason', 'eliminar'])) open @endif>
                    <summary>Eliminar registro</summary>
                    <form method="post" action="{{ route('management.destroy', [$resource, $edit->id]) }}" data-confirm="¿Eliminar este registro?">
                        @csrf @method('DELETE')
                        <p class="help">El historial se conserva. @if($definition['table'] === 'users') La cuenta perderá el acceso al portal. @endif</p>
                        <label>Motivo<textarea name="reason" minlength="10" maxlength="1000" required>{{ old('reason') }}</textarea></label>
                        <div class="form-actions"><button class="button danger">Eliminar registro</button></div>
                    </form>
                </details>
            @endif
        </section>
    @else
        <section class="card">
            @if($definition['table'] === 'users')
                <form class="record-search" method="get" action="{{ route('management', $resource) }}" role="search">
                    <label>Buscar cuentas
                        <input type="search" name="buscar" maxlength="120" value="{{ $search }}" placeholder="Nombre, correo o matrícula">
                    </label>
                    <button class="button secondary">Buscar</button>
                    @if($search !== '')
                        <a href="{{ route('management', $resource) }}">Limpiar</a>
                    @endif
                </form>
            @endif
            <p class="muted record-count">{{ $rows->total() }} {{ $rows->total() === 1 ? 'registro' : 'registros' }}</p>
            <div class="table-wrap"><table><thead><tr><th>{{ array_key_exists('name', $definition['fields']) || $definition['table'] === 'users' ? 'Nombre' : 'Registro' }}</th>
                @foreach($tableFields as $field => $label)<th>{{ $label }}</th>@endforeach
                @if(in_array($resource, ['usuarios', 'internos']))<th>Acceso al portal</th>@endif
                <th>Acción</th></tr></thead><tbody>
                @forelse($rows as $row)
                    <tr><td>{{ $row->name ?? '#'.$row->id }}@if($resource === 'habilitaciones')<br><small>{{ $row->email }} · {{ $choices['role'][$row->role] }}</small>@endif</td>
                        @foreach($tableFields as $field => $label)
                            <td>@if($field === 'access_enabled'){{ $row->access_approved_at && ($row->role !== 'interno' || $row->eligible) ? 'Autorizado' : 'Pendiente' }}@elseif($resource === 'internos' && $field === 'cohort_id'){{ $row->cohort_id ? 'Sí' : 'No' }}@else{{ in_array($field, ['active', 'eligible']) ? ($row->$field ? 'Sí' : 'No') : ($choices[$field][$row->$field] ?? $row->$field) }}@endif</td>
                        @endforeach
                        @if(in_array($resource, ['usuarios', 'internos']))<td>{{ ! $row->active ? 'Inactiva' : (! $row->activated_at ? 'Sin contraseña' : ($row->role !== 'administrador' && (! $row->access_approved_at || ($row->role === 'interno' && ! $row->eligible)) ? 'Pendiente de Coordinación' : 'Habilitado')) }}</td>@endif
                        <td><a href="{{ route('management.edit', [$resource, $row->id]) }}">{{ in_array($resource, ['internos', 'habilitaciones']) ? 'Completar datos' : 'Editar' }}</a>
                        @if($resource === 'usuarios' && ! $row->activated_at && $row->active)<form method="post" action="{{ route('users.activate', $row->id) }}">@csrf<button class="button small">Enviar activación</button></form>@endif</td>
                    </tr>
                @empty<tr><td colspan="{{ $columnCount }}" class="empty">{{ $search === '' ? 'Todavía no hay registros.' : 'No se encontraron cuentas.' }}</td></tr>@endforelse
            </tbody></table></div>
            {{ $rows->links() }}
        </section>
    @endif
</x-layout>
