# Arquitectura vigente

Actualización solicitada el 16-11-2026: todo el backend usa **PHP/Laravel**. Quarkus y Timefold dejaron de ser dependencias del sistema. El directorio `optimizer-quarkus/` se conserva como código histórico, incluido el cambio local que ya existía; no se compila, arranca ni consulta desde SICLIN.

Laravel 13.42.0 (Composer lock), PHP 8.5 (imagen fijada por digest), PostgreSQL 16.10, Blade, CSS/JavaScript con Vite y Mailpit 1.27.4. El frontend se compila con Node 22.14.0 durante el build; Node no se necesita para servir la aplicación. Nginx 1.28.0 sirve recursos y delega a PHP-FPM.

## Procesos

- `web`: http://localhost:8000, vinculado a 127.0.0.1.
- `app`: PHP-FPM; no publica puerto en el host.
- `postgres-db`: datos, sesiones, colas, límites OTP; volumen persistente `pgdata`. Sin puerto publicado.
- `migrate`: migración única y controlada antes de app/workers, tras salud de PostgreSQL.
- `queue-mail`: cola `mail-local` o `mail-external`, cifrada; timeout 30 s.
- `queue-optimization`: cálculos de rotes, escenarios y guardias; timeout 180 s.
- `scheduler`: depura desafíos y detecta cálculos interrumpidos.
- `mailpit`: SMTP interno 1025, bandeja http://localhost:8025. Solo desarrollo.

`retry_after=240 s` supera el timeout del worker. Los jobs reclaman una ejecución mediante actualización condicional/transacción y solo persisten una vez. Reinicios prolongados se identifican como fallos tras cuatro minutos; una ejecución en cola espera al worker. Un fallo no publica resultados.

## Configuración

Docker consume **`.env` de la raíz** mediante Compose y `env_file`. `portal/.env` es exclusivamente para ejecución fuera de Docker: no se copia en imágenes. `scripts/init-env.py` crea secretos faltantes y conserva los existentes; importa APP_KEY y conexión del `.env` previo al primer arranque para no invalidar datos. No ejecutes `key:generate` en cada reinicio.

APP_KEY cifra sesiones y payloads de correo. OTP_SECRET calcula verificadores HMAC. Guarda ambos fuera de Git y con las copias de seguridad. Cada servicio Laravel recibe los mismos valores. El build incorpora vendor y recursos; no hay un bind mount que los oculte. `portal_storage` conserva documentos y archivos privados.

## Seguridad

Cuatro roles persistidos. Administración gestiona cuentas y auditoría; Coordinación los datos académicos. No hay registro público ni selector de rol para iniciar sesión. Rutas, consultas y Gate de descarga aplican alcance. Las libretas/CSV de internos son propias; las de tutor incluyen sus asignaciones. Las actas consolidadas pertenecen a Coordinación. QR público solo expone identificador, integridad, estado y versión.

OTP de seis dígitos: HMAC por desafío/usuario/propósito, 10 minutos, cinco intentos; cooldown 60 s; tres solicitudes/cuenta y veinte/IP en quince minutos. Límites configurables. Después de verificar: autorización en sesión por cinco minutos. PostgreSQL bloquea el desafío durante verificación/consumo. Recuperar revoca sesiones y recordar sesión, incrementa una versión de seguridad y no inicia sesión ni habilita cuentas desactivadas. Los correos se encolan cifrados tras commit; el worker comprueba vigencia y modo de envío antes de enviar. Excepciones SMTP se sanitizan.

## Motor PHP

`RotationOptimizer`: flujo de costo mínimo con cobertura de máxima prioridad, límites por plaza y tutor y costos de preferencia/mérito/cambios/ocupación. `ShiftOptimizer`: búsqueda determinista acotada con poda por cobertura, descansos y carga normalizada por disponibilidad. `RotationService` y `ShiftPlanner` validan independientemente resultados y vigencia antes de publicar. No hay valores aleatorios, temporizadores de simulación ni llamadas a Java.

Los siete módulos comparten PostgreSQL. Las simulaciones guardan entradas y resultados separados; no modifican asignaciones publicadas. Véase [modelo IO](MODELO_IO.md), [contrato interno](CONTRATO_API.md) y [modelo de datos](MODELO_DATOS.md).
