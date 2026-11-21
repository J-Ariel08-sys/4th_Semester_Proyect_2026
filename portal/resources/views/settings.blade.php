<x-layout title="Configuración">
    <div class="page-heading"><h1>Configuración</h1></div>
    <section class="card settings-card">
        <form method="post" action="{{ route('settings.save') }}">
            @csrf
            <label for="support">Contacto de soporte</label>
            <input id="support" name="support" required maxlength="200" aria-describedby="support-help"
                value="{{ old('support', json_decode(\Illuminate\Support\Facades\DB::table('settings')->where('key','general')->value('value') ?? '{}',true)['support'] ?? '') }}">
            <p class="help" id="support-help">Se muestra en Ayuda.</p>
            <div class="form-actions"><button>Guardar</button></div>
        </form>
    </section>
</x-layout>
