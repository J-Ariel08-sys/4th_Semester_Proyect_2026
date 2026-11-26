# Motor de SICLIN

Quarkus 3.39.4 / Java 21 / Timefold Solver 1.35.0.

```bash
./mvnw test
./mvnw quarkus:dev
```

Puerto de aplicación: 8081. Puerto HTTP de pruebas: 8082, para poder probar con el contenedor activo. Salud: `/health`. OpenAPI: `/q/openapi`; Swagger UI disponible en desarrollo en `/q/swagger-ui`.

- `POST /api/optimizar`: asignación de rotes por convocatoria. Ejemplo en `../examples/optimizar.json`.
- `POST /api/guardias`: asignación de tutores a turnos, con candidatos disponibles, descanso y balance de carga.

```bash
curl -H 'Content-Type: application/json' --data @../examples/optimizar.json http://localhost:8081/api/optimizar
```

El resultado siempre es una propuesta. Laravel valida de nuevo y publica mediante aprobación explícita. No se garantiza optimalidad global. La búsqueda deja entidades pendientes para evitar violar restricciones duras.

El `Dockerfile` de esta carpeta realiza la construcción completa. `src/main/docker/Dockerfile.jvm` también se conserva para construir a partir de `target/quarkus-app` generado previamente con `./mvnw package`.

El archivo histórico duplicado bajo `optimizer-rotes/src/...` no forma parte del código compilado de Maven; no debe usarse como implementación de referencia. El código activo está bajo `src/main/java`.

Modelo y pruebas funcionales: [Investigación Operativa](../docs/INVESTIGACION_OPERATIVA.md), [Guía del portal](../docs/PRUEBAS_FUNCIONALES.md).
