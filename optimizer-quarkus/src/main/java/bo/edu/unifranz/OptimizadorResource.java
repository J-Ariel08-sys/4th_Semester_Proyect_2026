package bo.edu.unifranz;
import bo.edu.unifranz.solver.*;
import jakarta.ws.rs.*;
import jakarta.ws.rs.core.MediaType;
import io.smallrye.common.annotation.Blocking;
import java.util.concurrent.Semaphore;
@Path("/api/optimizar")
@Produces(MediaType.APPLICATION_JSON)
@Consumes(MediaType.APPLICATION_JSON)
public class OptimizadorResource {
    private static final Semaphore CAPACITY = new Semaphore(1);
    @POST @Blocking
    public Optimization.Result optimize(Optimization.Request request) {
        Optimization.validate(request);
        if (!CAPACITY.tryAcquire()) throw new ServiceUnavailableException("Motor ocupado; vuelva a intentar.");
        try { return new Optimization().solve(request); }
        finally { CAPACITY.release(); }
    }
}
