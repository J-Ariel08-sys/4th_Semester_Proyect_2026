<section class="card">
    <h2>Recorrido del sistema</h2>
    <p class="help">El botón Modo oscuro / Modo claro cambia el tema y recuerda tu elección. En pantallas pequeñas, abre Menú para navegar. Los campos de contraseña permiten Mostrar / Ocultar.</p>
    <p>Los cambios se guardan de verdad en esta instancia. Usa una sesión distinta por rol, o cierra sesión antes de entrar con otra cuenta.</p>
    @switch(auth()->user()->role)
        @case('administrador')
            <ol class="demo-steps">
                <li>En <a href="{{ route('management', 'usuarios') }}">Cuentas de usuario</a>, crea una cuenta con correo nuevo, rol Interno, cuenta activa y contraseña confirmada de al menos doce caracteres con letras y números.</li>
                <li>Cierra sesión e intenta ingresar con la nueva cuenta: se mostrará que falta la habilitación de Coordinación.</li>
                <li>Entra como coordinador. Crea una cohorte para este ejemplo y abre <a href="{{ route('management', 'habilitaciones') }}">Habilitar cuentas</a> → Completar datos. Asigna cohorte, matrícula, promedio y méritos; marca Habilitado académicamente y guarda.</li>
                <li>Ingresa con el correo y la contraseña de la cuenta nueva. Vuelve como administrador para editar su contraseña y mostrar <a href="{{ route('audit') }}">la bitácora</a>.</li>
                <li>Para probar eliminación, abre la edición de esa cuenta de ejemplo, escribe el motivo y pulsa Eliminar registro. Comprueba el evento en bitácora y que ya no pueda ingresar.</li>
            </ol>
            <p class="help">El administrador tiene acceso a todos los módulos y también puede habilitar cuentas. Usa una cohorte nueva para que el ejemplo de acceso no modifique las propuestas preparadas.</p>
            @break
        @case('coordinador')
            <p>Para autorizar nuevas cuentas, abre <a href="{{ route('management', 'habilitaciones') }}">Habilitar cuentas</a>. Los internos requieren completar su ficha académica; tutores y coordinadores requieren Autorizar ingreso al portal.</p>
            <ol class="demo-steps">
                <li>En <a href="{{ route('rotations') }}">Postulación y rotes</a>, busca «Convocatoria · Medicina Interna». Diego y Valeria ya postularon; Ana puede hacerlo durante la presentación.</li>
                <li>Cierra las postulaciones. En <a href="{{ route('scenarios') }}">Escenarios y decisiones</a>, selecciona esa convocatoria y pulsa «Simular nueve combinaciones».</li>
                <li>Espera el resultado. Revisa cobertura, preferencias y sensibilidad. Elige A1 y justifica: «Priorizamos las preferencias y el mérito en esta convocatoria».</li>
                <li>Vuelve a rotes, selecciona el estudio aprobado y calcula. Abre el detalle; pasa a revisión, aprueba y publica.</li>
                <li>En <a href="{{ route('reports') }}">Documentos y reportes</a>, genera el acta de Medicina Interna y descarga el PDF.</li>
            </ol>
            <p class="help">El seguimiento de Camila ya está preparado para el tutor. La convocatoria de Medicina Interna queda para el cálculo en vivo.</p>
            @break
        @case('tutor')
            <ol class="demo-steps">
                <li>Usa <strong>tutor@siclin.test</strong>. Abre <a href="{{ route('clinical') }}">Mis internos</a> y busca a Camila Clínica.</li>
                <li>En «Registrar asistencia y evaluación», elige la fecha de hoy y Presente. Activa la rúbrica y escribe 80, 60, 100 y 80 en ese orden.</li>
                <li>Guarda y abre el historial. Con los pesos iniciales (25 % cada criterio), la nota calculada es <strong>80</strong>.</li>
                <li>En «Cumplimiento de guardias», confirma la guardia finalizada de Camila. Después genera la libreta de tu ámbito de supervisión en <a href="{{ route('reports') }}">Mis reportes</a>.</li>
                <li>En <a href="{{ route('shifts') }}">Disponibilidad y guardias</a>, registra un intervalo futuro dentro del periodo de Medicina Interna para verificar la planificación.</li>
            </ol>
            <p class="help">Hay un registro por día. Si ya registraste hoy, consulta el historial o usa otro día transcurrido del rote; no repitas la misma fecha.</p>
            @break
        @case('interno')
            @if(auth()->user()->email === 'interno.clinico@siclin.test')
                <ol class="demo-steps">
                    <li>En <a href="{{ route('clinical') }}">Mis rotes y solicitudes</a> consulta tu rote de Cirugía, la asistencia y la evaluación que registró el tutor.</li>
                    <li>Reporta una incidencia de tipo Corrección: «Solicito revisar la observación registrada».</li>
                    <li>En <a href="{{ route('reports') }}">Mi libreta y documentos</a> genera y descarga tu libreta. Consulta la guardia confirmada por el tutor.</li>
                    <li>Coordinación puede responder la incidencia; después podrás consultar la resolución aquí.</li>
                </ol>
            @elseif(str_starts_with(auth()->user()->email, 'interno.demo'))
                <ol class="demo-steps">
                    <li>En <a href="{{ route('clinical') }}">Mis rotes y solicitudes</a>, consulta tus asignaciones, asistencia, notas e incidencias. Las cuentas 01 a 12 tienen tres rotes; 13 a 16 participan en el caso de cupos insuficientes.</li>
                    <li>En <a href="{{ route('rotations') }}">Oferta y postulación</a>, consulta tus comprobantes. Para postular a Pediatría durante la presentación, usa <strong>interno.demo07@siclin.test</strong>: todavía no postuló allí.</li>
                    <li>En <a href="{{ route('reports') }}">Mi libreta y documentos</a>, genera tu libreta. Sofía y Gabriel ya tienen un PDF disponible.</li>
                </ol>
            @elseif(auth()->user()->email === 'interno.pendiente@siclin.test')
                <p>La cuenta está habilitada para ingresar cuando Coordinación completa su ficha académica. Coordinación puede asignarle cohorte y habilitación en Gestión académica → Habilitación de internos; después podrá postular a una oferta compatible.</p>
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
@php($expanded = json_decode(\Illuminate\Support\Facades\DB::table('settings')->where('key', 'demo_expanded_v1')->value('value') ?? '{}', true))
@if($expanded)
<section class="card">
    <h2>Casos preparados</h2>
    <p>Cargados el {{ $expanded['prepared_on'] }}. Los datos son ficticios; los cálculos y los PDF se generan con los servicios del sistema.</p>
    @if(auth()->user()->role === 'coordinador')
        <div class="table-wrap"><table><thead><tr><th>Apartado</th><th>Caso listo para mostrar</th></tr></thead><tbody>
            <tr><td>Gestión académica</td><td>Dos cohortes, sus requisitos, internos habilitados y Alex pendiente. Centros C/D activos y E con convenio vencido.</td></tr>
            <tr><td>Postulación y rotes</td><td>Pediatría abierta con seis postulaciones; Medicina Interna con una propuesta en revisión; Ginecología publicada; Cirugía en borrador.</td></tr>
            <tr><td>Escenarios y decisiones</td><td>Tres estudios calculados. En «Cupos insuficientes» hay cuatro postulantes y dos cupos utilizables; en Medicina Interna, doce asignados y ninguno pendiente.</td></tr>
            <tr><td>Seguimiento</td><td>Cirugía en curso: doce internos, asistencia y notas. Dos incidencias abiertas y dos resueltas; permuta de Ginecología consentida, pendiente de aprobación.</td></tr>
            <tr><td>Documentos y reportes</td><td>Tres actas PDF, libretas personales y un archivo de Salud Pública con historial.</td></tr>
            <tr><td>Guardias y tutores</td><td>Tres guardias de Cirugía, dos realizadas y una pendiente de confirmar.</td></tr>
        </tbody></table></div>
        <p class="help">Primero muestra los resultados preparados. Para una acción en vivo, aprueba la propuesta de Medicina Interna o resuelve una incidencia de Cirugía. Cambiar cupos o habilitaciones obliga a recalcular las propuestas afectadas.</p>
    @elseif(auth()->user()->role === 'tutor')
        <p>En Mis internos, busca a <strong>Sofía</strong> con Lucía, <strong>Gabriel</strong> con Mateo o <strong>Mariana</strong> con Elena. Abre el historial de Cirugía y registra la fecha de hoy si aún está libre. En Mis reportes puedes generar una libreta de tu supervisión.</p>
    @elseif(auth()->user()->role === 'administrador')
        <p>En Cuentas de usuario encontrarás las cuentas del sistema: internos y tutores. Alex Pendiente permite explicar que tener una cuenta activa y estar habilitado académicamente son condiciones distintas.</p>
    @else
        <p>Para ver un historial completo desde el primer acceso, consulta las asignaciones de <strong>Sofía</strong>. Para realizar una postulación nueva a Pediatría, utiliza la cuenta de <strong>Daniela</strong>.</p>
    @endif
</section>
@endif
<section class="card">
    <h2>Repetir la presentación</h2>
    @php($prepared = json_decode(\Illuminate\Support\Facades\DB::table('settings')->where('key', 'demo_prepared')->value('value') ?? '{}', true))
    <p>Datos preparados el {{ $prepared['prepared_on'] ?? 'día de la carga inicial' }}. Las fechas de rotes y guardias dependen de ese día.</p>
    <p>El comando <code>./scripts/demo-up.sh</code> conserva los avances y agrega los casos una sola vez. Para comenzar desde cero, ejecuta <code>./scripts/demo-up.sh --reset</code>: guarda una copia de la base y reinicia los casos y fechas.</p>
    <p class="help">Los accesos están en <code>.local/presentation-accounts.json</code>. La guía completa y el guion de Investigación Operativa están en <code>docs/GUIA_DEMOS.md</code> y <code>docs/EXPOSICION_IO.md</code>.</p>
</section>
