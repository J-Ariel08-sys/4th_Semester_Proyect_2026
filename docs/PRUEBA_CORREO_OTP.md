# Prueba de activación y recuperación OTP

## 1. Arrancar y entrar como administrador

```bash
./scripts/dev-up.sh
cat .local/demo-accounts.json
```

Abre http://localhost:8000. Usa `admin@siclin.test` y su contraseña **generada localmente** en ese archivo. No existe una contraseña demo compartida en el README ni se restablecen cuentas existentes al reiniciar.

## 2. Agregar tu correo institucional

En **Usuarios y permisos → Cuentas de usuario**, introduce tu nombre, tu dirección real, el rol correspondiente y deja “Cuenta habilitada” marcado. Guarda. No introduzcas una contraseña desde Administración: la primera contraseña la establece el destinatario mediante OTP.

No se presume ningún dominio oficial. Si el administrador configura `ALLOWED_EMAIL_DOMAINS`, solo esos dominios se admiten; en desarrollo también se permiten cuentas `.test`. El remitente SMTP y tu correo destinatario pueden ser diferentes.

## 3. Activación local

1. Cierra la sesión del administrador.
2. En “Olvidé mi contraseña”, escribe tu correo y selecciona **Activar mi cuenta nueva**.
3. Abre http://localhost:8025. Busca el correo de activación dirigido a tu cuenta.
4. Copia los seis dígitos (incluidos ceros iniciales) a la pantalla de verificación de SICLIN.
5. Establece contraseña y confirmación (mínimo 12 caracteres con letras y números).
6. Ingresa normalmente con tu correo y la nueva contraseña. No se inicia sesión automáticamente.

Administración puede reenviar activación desde la fila de la cuenta. Si ya envió un código hace menos de 60 s, la solicitud del destinatario reutiliza el desafío vigente sin invalidarlo.

**Mailpit captura SMTP real dentro de Docker. En modo local ningún mensaje llega a la bandeja institucional externa**, aunque el destinatario sea una dirección real.

## 4. Recuperación local

1. Con una cuenta activada, cierra sesión y elige “Olvidé mi contraseña”.
2. Selecciona **Olvidé mi contraseña** e introduce el correo.
3. Se muestra siempre: “Si la cuenta está habilitada, recibirás instrucciones para recuperar el acceso”.
4. Lee el correo de recuperación en Mailpit y verifica el código en SICLIN.
5. Establece una contraseña diferente y confírmala.
6. Comprueba que la anterior se rechaza, la nueva permite ingresar y una sesión previa abierta en otro navegador vuelve a pedir inicio de sesión.
7. Mailpit debe recibir la notificación de cambio. Reutilizar el código o el permiso de restablecimiento debe fallar.

Valores iniciales: OTP 10 min, cinco intentos, reenvío tras 60 s, tres solicitudes por cuenta cada 15 min y veinte por IP. Verificar abre un permiso de cambio de cinco minutos. Superar intentos invalida el desafío, no deshabilita permanentemente la cuenta. Recuperar no reactiva una cuenta desactivada por Administración.

## 5. SMTP externo

No hay credenciales externas en Git. Edita **`.env` de la raíz**, no `portal/.env`, con los datos del servicio emisor:

```dotenv
MAIL_MODE=external
MAIL_MAILER=smtp
MAIL_HOST=servidor-del-proveedor
MAIL_PORT=587
MAIL_SCHEME=smtp
MAIL_USERNAME=usuario-emisor
MAIL_PASSWORD="credencial-compatible-con-el-proveedor"
MAIL_FROM_ADDRESS=remitente-autorizado
MAIL_FROM_NAME="SICLIN"
TEST_RECIPIENT_EMAIL=tu-correo-real
SMTP_ALLOWED_RECIPIENTS=tu-correo-real
```

Para TLS implícito usa `MAIL_SCHEME=smtps` y el puerto indicado por el proveedor (normalmente 465). Para STARTTLS usa `smtp` y el puerto indicado; la validación de certificados permanece activa. En esta versión Symfony Mailer usa `MAIL_SCHEME`, no se depende de la variable histórica `MAIL_ENCRYPTION`. No asumas que la contraseña ordinaria de una cuenta sirve para SMTP; consulta el tipo de credencial que admite el proveedor.

Antes de cambiar de modo:

```bash
docker compose stop queue-mail
# Editar .env y guardar los valores privados.
docker compose up -d --force-recreate app queue-mail queue-optimization scheduler
docker compose exec -T app php artisan optimize:clear --no-interaction
```

Las colas son distintas (`mail-local` / `mail-external`) y cada job conserva su modo de origen. Los jobs locales pendientes no se trasladan a la cola externa. En desarrollo, los destinatarios externos deben estar en TEST_RECIPIENT_EMAIL o SMTP_ALLOWED_RECIPIENTS. No ejecutes reenvíos masivos ni mezcles colas al cambiar la configuración.

Repite recuperación con tu cuenta activada. Comprueba logs sanitizados/`queue:failed` si no llega. Una tarea completada acredita aceptación del transporte SMTP; **la recepción externa solo queda confirmada cuando tú veas el mensaje en tu bandeja** (revisar también spam). Sin credenciales reales, esta comprobación permanece pendiente.

No publiques el portal con credenciales reales sobre HTTP. El Compose incluido es local; un despliegue público requiere HTTPS y `SESSION_SECURE_COOKIE=true`.

## Diagnóstico y recuperación de workers

```bash
./scripts/diagnose.sh
docker compose logs --tail=50 queue-mail
docker compose exec -T app php artisan queue:failed --no-interaction
# Tras corregir SMTP, reintenta solo el UUID de correo deseado:
docker compose exec -T app php artisan queue:retry UUID --no-interaction
```

El worker descarta OTP vencidos, usados o sustituidos. Si estuvo detenido durante más de la vigencia, solicita un código nuevo. Las excepciones de SMTP no incluyen el cuerpo ni el código; los payloads de jobs y fallos están cifrados con APP_KEY. No uses transporte `log` para OTP.

Referencias oficiales consultadas: [Laravel 13 correo](https://laravel.com/docs/13.x/mail), [Laravel 13 colas](https://laravel.com/docs/13.x/queues), [Mailpit API](https://mailpit.axllent.org/docs/api-v1/), [OWASP recuperación](https://cheatsheetseries.owasp.org/cheatsheets/Forgot_Password_Cheat_Sheet.html).
