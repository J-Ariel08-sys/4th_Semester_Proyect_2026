package bo.edu.unifranz;
import io.quarkus.test.junit.QuarkusTest;
import org.junit.jupiter.api.Test;
import static io.restassured.RestAssured.given;
import static org.hamcrest.Matchers.*;
@QuarkusTest
class OptimizationResourceTest {
    @Test void invalidRequestIsBadRequest() {
        given().contentType("application/json").body("{}").post("/api/optimizar").then().statusCode(400);
    }
    @Test void computesRealAssignments() {
        given().contentType("application/json").body("""
            {"plazas":[{"id":"a","capacidad":1,"reserva":0}],
             "internos":[{"id":"i","habilitado":true,"merito":80,"preferencias":["a"]}],
             "politica":{"pesoPreferencias":10,"pesoMerito":1,"pesoEquilibrio":0,"pesoCambios":5},"segundos":1}
            """).post("/api/optimizar").then().statusCode(200).body("asignaciones.size()",is(1)).body("hardScore",is(0)).body("requiereAprobacion",is(true));
    }
}
