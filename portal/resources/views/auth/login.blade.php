<x-layout title="Iniciar sesión">
    <section class="login-card">
        <a class="brand" href="{{ route('login') }}"><span class="brand-mark">S</span><span>SICLIN<small>Unifranz · La Paz</small></span></a>
        <h1>Ingresar al portal</h1>
        <p class="muted">Usa la cuenta asignada por tu administrador.</p>
        <form method="post" action="{{ route('login') }}">
            @csrf
            <label>Correo electrónico<input type="email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="nombre@institucion.edu"></label>
            <label>Contraseña<input type="password" name="password" required autocomplete="current-password"></label>
            <button>Ingresar al portal</button>
        </form>
        <a class="recovery-link" href="{{ route('password.request') }}">Olvidé mi contraseña</a>
    </section>
</x-layout>
