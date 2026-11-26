# SICLIN · Unifranz, sede La Paz

Portal de internado de Medicina con **Laravel 13 / PHP 8.5**, PostgreSQL 16, Blade, CSS/JavaScript compilados con Vite, Nginx, Docker y Mailpit. La optimización de rotes y guardias se ejecuta en PHP mediante colas Laravel. Quarkus no participa en el arranque ni en el cálculo; `optimizer-quarkus/` se conserva como código histórico.

## Instancia de trabajo

Con Docker y Python 3 instalados, ejecuta `INICIAR.bat` en Windows con Docker Desktop abierto o `./scripts/dev-up.sh` desde Linux o macOS.

El portal abre en http://localhost:8000 y el buzón local en http://localhost:8025. El inicio migra la base, conserva sus registros y deja la creación de usuarios a Administración. Después de modificar el código, vuelve a ejecutar el iniciador para reconstruir las imágenes.

En una base nueva, crea el primer administrador desde una terminal en la raíz. Reemplaza el correo de ejemplo por el tuyo.

```text
docker compose exec -T app php artisan siclin:admin admin@example.com --no-interaction
docker compose exec app cat /app/storage/app/private/bootstrap-admin.json
```

Abre el portal con las credenciales generadas. Guarda la contraseña en privado y elimina el archivo de arranque del contenedor cuando hayas iniciado sesión correctamente. Mailpit permite consultar los correos de activación y recuperación en el entorno local.

## Preparar una presentación

Requisitos: Docker Engine, Docker Compose y Python 3. Desde la raíz del repositorio:

```bash
./scripts/demo-up.sh
cat .local/presentation-accounts.json
```

- Portal de demo: http://localhost:8010
- Correos de demo: http://localhost:8030
- Entra con una de las cuentas del archivo privado; cada una tiene su propia contraseña aleatoria.
- Abre **Guía de demostración** dentro del portal para ver el recorrido de tu rol.
- Guía completa: [demostraciones por rol](docs/GUIA_DEMOS.md).
- Para la exposición: [cómo explicar Investigación Operativa](docs/EXPOSICION_IO.md).

| Cuenta | Uso |
|---|---|
| `admin@siclin.test` | Usuarios, activación y auditoría |
| `coordinador@siclin.test` | Oferta, escenarios, asignaciones, guardias y documentos |
| `tutor@siclin.test` | Asistencia y evaluación de Camila |
| `interno@siclin.test` | Ana: postular durante la presentación |
| `interno.clinico@siclin.test` | Camila: consultar seguimiento y libreta desde el inicio |
| `interno2@siclin.test`, `interno3@siclin.test` | Diego y Valeria, con postulaciones preparadas |
| `tutor2@siclin.test` | Segundo ámbito de supervisión |

La demo tiene base, volúmenes, correo y cookie propios. El arranque normal conserva los avances. Para volver a preparar fechas actuales y casos iniciales:

```bash
./scripts/demo-up.sh --reset
```

Este comando **reinicia solo la base de demo** después de guardar su copia en `.local/demo-backups/`; genera nuevas contraseñas. Vuelve a consultar el archivo de cuentas. No reinicia la instancia de trabajo del puerto 8000. La copia es de la base de datos; no es un respaldo completo de los PDF privados.

## Pruebas

```bash
./scripts/test.sh
./scripts/e2e.sh
# Después de preparar una demo inicial, recorrido extendido:
SICLIN_DEMO_TEST=1 SICLIN_E2E_URL=http://localhost:8010 \
  node tests/browser/node_modules/playwright/cli.js test --config tests/browser/playwright.config.js
```

PHPUnit usa PostgreSQL separado (`siclin_test`). Playwright usa la instancia `siclin-e2e`, puertos 8001/8026. `scripts/e2e.sh` instala sus dependencias de Node y Chromium y reinicia únicamente la base de pruebas. Necesita Node/npm en el equipo. El recorrido extendido modifica la demo: reiníciala antes de presentar. Consulta [la evidencia y límites de verificación](docs/PRUEBAS.md).

## Documentación

- [Estado de implementación y pendientes](docs/ESTADO_IMPLEMENTACION.md)
- [Arquitectura actual](docs/ARQUITECTURA.md), [datos](docs/MODELO_DATOS.md), [permisos](docs/ROLES_Y_PERMISOS.md)
- [Modelo matemático y algoritmos PHP](docs/MODELO_IO.md)
- [Reglas y supuestos](docs/REGLAS_Y_SUPUESTOS.md)

```bash
# Detener conservando los datos:
docker compose stop
docker compose -p siclin-demo -f docker-compose.demo.yml stop
# Ver servicios de demo:
docker compose -p siclin-demo -f docker-compose.demo.yml ps
```

Los servicios web están vinculados a localhost. Los datos de ejemplo, rúbricas y pesos son demostrativos; no representan validación académica oficial ni firma digital institucional.
