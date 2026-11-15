# Portal SICLIN

Laravel 13, Blade y CSS. El frontend no requiere una compilación de Node.

Desde la raíz del repositorio:

```bash
./scripts/dev-up.sh
```

Portal: http://localhost:8000. Credenciales ficticias, recorrido de los siete módulos, pruebas y límites: [guía funcional](../docs/PRUEBAS_FUNCIONALES.md).

Para desarrollo sin contenedor se requieren PHP 8.3 o superior compatible con `composer.lock`, Composer, extensiones PostgreSQL/mbstring/XML/ZIP, PostgreSQL y Quarkus. La configuración Docker proporcionada usa PHP 8.4 y es el entorno validado.

Los datos se crean mediante migraciones y seeders. `siclin:setup --demo` no elimina ni sustituye usuarios existentes. `ClinicalDemoSeeder` crea un ejemplo activo adicional para seguimiento y guardias.

Pruebas rápidas: `../scripts/test.sh`. Integración PostgreSQL/Quarkus: `phpunit.pgsql.xml` siguiendo la guía, siempre con una base de pruebas separada.
