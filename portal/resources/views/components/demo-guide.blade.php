<section class="card">
    <h2>Tu recorrido de demostración</h2>
    <p>Los cambios se guardan de verdad en esta instancia. Usa una sesión distinta por rol, o cierra sesión antes de entrar con otra cuenta.</p>
    @switch(auth()->user()->role)
        @case('administrador')
            <ol class="demo-steps">
                <li>En <a href="{{ route('management', 'usuarios') }}">Cuentas de usuario</a>, crea <strong>invitado@siclin.test</strong> con rol Interno y cuenta activa.</li>
                <li>Cierra sesión. En «Olvidé mi contraseña», selecciona «Activar cuenta» y solicita el código para ese correo.</li>
                <li>Abre <a href="http://localhost:8030" target="_blank" rel="noopener">el buzón local</a>, copia el código de seis dígitos y define la contraseña.</li>
                <li>Ingresa con la nueva cuenta. Después vuelve como administrador y muestra <a href="{{ route('audit') }}">la bitácora</a> y <a href="{{ route('permissions') }}">los permisos</a>.</li>
            </ol>
            <p class="help">Para repetir la creación sin reiniciar, utiliza otro correo ficticio.</p>
            @break
        @case('coordinador')
            <ol class="demo-steps">
                <li>En <a href="{{ route('rotations') }}">Postulación y rotes</a>, busca «Convocatoria de demostración · Medicina Interna». Diego y Valeria ya postularon; Ana puede hacerlo durante la presentación.</li>
                <li>Cierra las postulaciones. En <a href="{{ route('scenarios') }}">Escenarios y decisiones</a>, selecciona esa convocatoria y pulsa «Simular nueve combinaciones».</li>
                <li>Espera el resultado. Revisa cobertura, preferencias y sensibilidad. Elige A1 y justifica: «Priorizamos las preferencias y el mérito en esta convocatoria de demostración».</li>
                <li>Vuelve a rotes, selecciona el estudio aprobado y calcula. Abre el detalle; pasa a revisión, aprueba y publica.</li>
                <li>En <a href="{{ route('reports') }}">Documentos y reportes</a>, genera el acta de Medicina Interna y descarga el PDF.</li>
            </ol>
            <p class="help">El seguimiento de Camila ya está preparado para el tutor. La convocatoria de Medicina Interna queda para el cálculo en vivo.</p>
            @break
        @case('tutor')
            <ol class="demo-steps">
                <li>Usa <strong>tutor@siclin.test</strong>. Abre <a href="{{ route('clinical') }}">Mis internos</a> y busca a Camila Clínica Demo.</li>
                <li>En «Registrar asistencia y evaluación», elige la fecha de hoy y Presente. Activa la rúbrica y escribe 80, 60, 100 y 80 en ese orden.</li>
                <li>Guarda y abre el historial. Con los pesos iniciales (25 % cada criterio), la nota calculada es <strong>80</strong>.</li>
                <li>En «Cumplimiento de guardias», confirma la guardia finalizada de Camila. Después genera la libreta de tu ámbito de supervisión en <a href="{{ route('reports') }}">Mis reportes</a>.</li>
                <li>En <a href="{{ route('shifts') }}">Disponibilidad y guardias</a>, registra un intervalo futuro dentro del periodo de Medicina Interna para demostrar la planificación.</li>
            </ol>
            <p class="help">Hay un registro por día. Si ya registraste hoy, consulta el historial o usa otro día transcurrido del rote; no repitas la misma fecha.</p>
            @break
        @case('interno')
            @if(auth()->user()->email === 'interno.clinico@siclin.test')
                <ol class="demo-steps">
                    <li>En <a href="{{ route('clinical') }}">Mis rotes y solicitudes</a> consulta tu rote de Cirugía, la asistencia y la evaluación que registró el tutor.</li>
                    <li>Reporta una incidencia de tipo Corrección: «Solicito revisar la observación registrada durante la demostración».</li>
                    <li>En <a href="{{ route('reports') }}">Mi libreta y documentos</a> genera y descarga tu libreta. Consulta la guardia confirmada por el tutor.</li>
                    <li>Coordinación puede responder la incidencia; después podrás consultar la resolución aquí.</li>
                </ol>
            @else
                <ol class="demo-steps">
                    <li>Para postular en vivo usa <strong>interno@siclin.test</strong> (Ana). Diego y Valeria ya tienen postulaciones de ejemplo.</li>
                    <li>En <a href="{{ route('rotations') }}">Oferta y postulación</a>, abre «Registrar mis preferencias» de Medicina Interna. Elige Hospital A primero y Hospital B segundo.</li>
                    <li>Confirma la postulación y abre el comprobante con QR.</li>
                    <li>Después de que Coordinación publique, consulta <a href="{{ route('clinical') }}">Mis rotes y solicitudes</a> y tus notificaciones.</li>
                </ol>
                <p class="help">Para mostrar asistencia, notas y libreta desde el primer acceso, entra con interno.clinico@siclin.test.</p>
            @endif
            @break
    @endswitch
</section>
<section class="card">
    <h2>Repetir la presentación</h2>
    @php($prepared = json_decode(\Illuminate\Support\Facades\DB::table('settings')->where('key', 'demo_prepared')->value('value') ?? '{}', true))
    <p>Datos preparados el {{ $prepared['prepared_on'] ?? 'día de la carga inicial' }}. Las fechas de rotes y guardias dependen de ese día.</p>
    <p>El comando <code>./scripts/demo-up.sh</code> conserva los avances. Para comenzar una nueva presentación, ejecuta <code>./scripts/demo-up.sh --reset</code> desde el repositorio: guarda una copia de la base de demo, reinicia sus casos y genera nuevas contraseñas.</p>
    <p class="help">Los accesos están en <code>.local/presentation-accounts.json</code>. La guía completa y el guion de Investigación Operativa están en <code>docs/GUIA_DEMOS.md</code> y <code>docs/EXPOSICION_IO.md</code>.</p>
</section>
