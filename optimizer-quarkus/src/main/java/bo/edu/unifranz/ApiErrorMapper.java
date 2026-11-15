package bo.edu.unifranz;
import jakarta.ws.rs.WebApplicationException;
import jakarta.ws.rs.core.Response;
import jakarta.ws.rs.ext.ExceptionMapper;
import jakarta.ws.rs.ext.Provider;
import java.util.Map;
@Provider
public class ApiErrorMapper implements ExceptionMapper<WebApplicationException> {
    public Response toResponse(WebApplicationException exception) {
        return Response.status(exception.getResponse().getStatus()).entity(Map.of("error", exception.getMessage())).build();
    }
}
