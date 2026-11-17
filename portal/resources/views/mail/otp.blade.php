<h1>SICLIN · {{ $purpose === 'activation' ? 'Activación de cuenta' : 'Recuperación de contraseña' }}</h1>
<p>Tu código de un solo uso es: <strong>{{ $code }}</strong></p>
<p>Vigencia restante: hasta {{ $minutes }} minutos. Introdúcelo en SICLIN para establecer tu contraseña.</p>
<p>No compartas este código. Si no solicitaste este cambio, ignora este mensaje.</p>
