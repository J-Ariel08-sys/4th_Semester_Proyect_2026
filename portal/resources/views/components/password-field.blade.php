@props(['name' => 'password', 'label' => 'Contraseña', 'autocomplete' => 'new-password', 'required' => false, 'minlength' => null])
<div>
    <label for="password-{{ $name }}">{{ $label }}</label>
    <div class="password-field">
        <input id="password-{{ $name }}" type="password" name="{{ $name }}" autocomplete="{{ $autocomplete }}" maxlength="255" @required($required) @if($minlength) minlength="{{ $minlength }}" @endif {{ $attributes }}>
        <button type="button" class="button secondary password-toggle" data-password-target="password-{{ $name }}" aria-controls="password-{{ $name }}" aria-pressed="false" aria-label="Mostrar {{ mb_strtolower($label) }}">Mostrar</button>
    </div>
</div>
