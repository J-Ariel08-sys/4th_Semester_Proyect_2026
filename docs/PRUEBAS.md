# Evidencia de revisión · 17 de noviembre de 2026

## Resultados ejecutados en este equipo

| Comprobación | Resultado |
|---|---|
| PHPUnit, PHP 8.5.12 y PostgreSQL separado | **42 pruebas, 503 aserciones, 0 fallos**; 9.714 s en la ejecución final de Docker |
| Caso de tamaño: 180 internos / 45 grupos, un bloque | **269 ms** en esa ejecución; cobertura y capacidades comprobadas por el test. Es una medición local, no garantía de rendimiento |
| Playwright, entorno `siclin-e2e` | **4 pruebas completas, 0 fallos**, 30.9 s |
| Playwright, entorno `siclin-demo` | **2 pruebas completas, 0 fallos**, 36.6 s |
| Estilo PHP | Laravel Pint ejecutado sobre los archivos modificados |
| Formato y scripts | `git diff --check`, `bash -n` y compilación sintáctica del script Python sin errores |

Registros locales: `.local/verification/phpunit.log`, `e2e.log` y `demo-browser.log`. Capturas de los cuatro paneles: `.local/demo-screenshots/`. Estos archivos se excluyen de Git.

## Qué recorrieron las pruebas

**PHPUnit:** permisos y ámbito de datos; OTP, límites, vencimiento y consumo; cálculo PHP de rotes, criterios de decisión y guardias; versiones y publicación; PDF/QR; importación; seguimiento y rúbrica; permutas; demo repetible; rechazo de reinicios fuera de la base exclusiva de demo. Las vistas se recompilan desde sus fuentes en un directorio separado por proceso.

**Navegador de pruebas:** creación/activación de cuenta; OTP recibido mediante SMTP real capturado en Mailpit; worker detenido/reiniciado; recuperación concurrente con un solo consumo; rechazo de contraseña anterior y revocación de otra sesión; navegación de los cuatro roles a 320/390/768/1280 px; postulación, cálculo en cola, revisión, aprobación y publicación; comprobación de PDF, QR y privacidad; asistencia, rúbrica y cumplimiento.

**Navegador de demo:** todos los destinos del menú de los cuatro roles responden 200 y no desbordan a 320 px; una sola sección de menú abierta; guía accesible; Ana postula; Coordinación ejecuta nueve optimizaciones de escenarios, aprueba A1 y publica; PDF descargable; Lucía registra disponibilidad; calendario de Ana calculado/aprobado y visible para Ana; asistencia/nota 80/guardia de Camila; Camila registra una incidencia; Coordinación resuelve; Camila ve la respuesta y descarga su libreta.

Los flujos de cálculo, correo y generación de archivos usan implementaciones reales. Las cuentas, hospitales y datos iniciales son ficticios.

## Fallos encontrados y corregidos

1. La plantilla de rotes tenía dos directivas `@endif` adyacentes. Una instancia sin vistas previamente compiladas devolvía 500. Las instancias antiguas podían ocultarlo con su caché. Se separaron las directivas y se volvió a comprobar la página en una compilación limpia.
2. El volumen persistente de almacenamiento retenía vistas compiladas entre versiones. Se trasladaron a `/tmp/siclin-views` por contenedor y se aisló la compilación de pruebas.
3. La cabecera CSV de importación desbordaba el ancho móvil. Se permite dividir el texto de `code`; se recorrieron todos los enlaces del menú a 320 px.
4. Varias secciones del menú se abrían juntas por compartir nombre de ruta. Se eliminaron destinos duplicados y se distingue el recurso académico de la ruta activa.

## Reproducir

```bash
./scripts/dev-up.sh
./scripts/test.sh
./scripts/e2e.sh
# Preparar una demo inicial; --reset reemplaza solo sus datos y claves.
./scripts/demo-up.sh --reset
SICLIN_DEMO_TEST=1 SICLIN_E2E_URL=http://localhost:8010 \
  node tests/browser/node_modules/playwright/cli.js test --config tests/browser/playwright.config.js
# Dejar los recorridos listos para una persona después de las pruebas:
./scripts/demo-up.sh --reset
```

Ejecuta las suites de navegador en secuencia: `e2e.sh` instala dependencias en el mismo directorio que usa la prueba de demo. En este equipo, npm emitió un aviso de compatibilidad con Node 20.20.2 y Playwright utilizó su compilación de Chromium alternativa para Linux; las seis pruebas terminaron correctamente. El workflow conserva Node 22.14.0 para las pruebas y el build de Vite utiliza Node dentro de Docker.

## Límites de esta evidencia

- SMTP externo y recepción en la cuenta institucional **pendientes de credenciales y confirmación de recepción**.
- No se ejecutó GitHub Actions remoto ni un despliegue público.
- No se certifica carga concurrente institucional, disponibilidad anual, optimalidad de todo el calendario ni validación académica de parámetros.
- La copia antes de reiniciar la demo respalda su base, no los PDF privados ni la configuración completa del sistema.
- La revisión visual cubrió los paneles y la navegación indicada. No equivale a una auditoría formal de accesibilidad ni de seguridad.

## Estado entregado para presentar

Después del recorrido automatizado se ejecutó `demo-up.sh --reset`. Se verificaron 8 cuentas, 2 postulaciones precargadas, 1 rote clínico, 0 registros de asistencia y 0 guardias confirmadas. Los recuentos de usuarios/postulaciones/asignaciones/seguimiento de la base de trabajo permanecieron iguales antes y después del reinicio. La demo quedó disponible en 8010, con Mailpit en 8030 y las claves nuevas en `.local/presentation-accounts.json` (permisos 600). Se volvió a comprobar el acceso y toda la navegación de los cuatro roles tras el reinicio.
