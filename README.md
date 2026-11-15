# SICLIN · UNIFRANZ, sede La Paz

Portal de asignación y seguimiento del internado de Medicina. Laravel 13 + PostgreSQL 16 + Quarkus 3.39.4 + Timefold 1.35.0. Interfaz Blade/CSS responsive, sin necesidad de Node para ejecutarla.

## Ejecutar

Requisitos: Docker y Docker Compose. Desde este directorio:

```bash
./scripts/dev-up.sh
```

El script construye los contenedores, crea la clave si falta, aplica migraciones y carga datos ficticios **solo si la base no tiene usuarios**. No borra bases ni volúmenes.

- Portal: http://localhost:8000
- Bandeja de correo local: http://localhost:8025
- Salud de Quarkus: http://localhost:8081/health
- Administrador: `admin@siclin.test`
- Coordinador: `coordinador@siclin.test`
- Tutor: `tutor@siclin.test` / `tutor2@siclin.test`
- Internos: `interno@siclin.test`, `interno2@siclin.test`, `interno3@siclin.test`
- Contraseña de las cuentas ficticias: `SiclinDemo2026!`

Para probar seguimiento y guardias terminadas sin esperar al inicio de la convocatoria:

```bash
docker compose exec portal php artisan db:seed --class=ClinicalDemoSeeder
```

Crea un rote activo ficticio, calculado con Quarkus, para `interno.clinico@siclin.test`, con la misma clave de demostración. No modifica la convocatoria futura. Ejecutarlo de nuevo no duplica el ejemplo.

## Guías

- [Prueba funcional detallada por rol y módulo](docs/PRUEBAS_FUNCIONALES.md).
- [Modelo de Investigación Operativa, fórmulas y supuestos](docs/INVESTIGACION_OPERATIVA.md).
- [Correspondencia con el informe, alcance y validaciones pendientes](docs/ALCANCE_Y_ARQUITECTURA.md).

## Pruebas

```bash
./scripts/test.sh
cd optimizer-quarkus
./mvnw test
```

La primera usa SQLite en memoria. La integración usa una base PostgreSQL separada, `siclin_test`, y Quarkus real; los pasos están en la guía funcional. Nunca ejecutes las pruebas contra `rotes_clinicos_db`.

## Operación local

```bash
docker compose logs -f portal optimizer
docker compose stop
docker compose up -d
```

La base persiste en un volumen. El código Laravel está montado desde `portal/`; los cambios Java requieren `docker compose up -d --build optimizer`. Si cambias dependencias PHP, ejecuta `docker compose exec portal composer install` además de reconstruir la imagen cuando corresponda.

Los puertos están vinculados a `127.0.0.1`. Esta configuración usa servidor PHP de desarrollo, credenciales ficticias y correo capturado localmente; no representa un despliegue institucional. Las actas QR son verificables pero no equivalen a firma digital del SEDES.
