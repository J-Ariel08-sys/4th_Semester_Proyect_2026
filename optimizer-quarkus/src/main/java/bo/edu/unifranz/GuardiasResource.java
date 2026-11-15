package bo.edu.unifranz;
import bo.edu.unifranz.solver.ShiftOptimization;
import io.smallrye.common.annotation.Blocking;
import jakarta.ws.rs.*;
import jakarta.ws.rs.core.MediaType;
import java.util.concurrent.Semaphore;
@Path("/api/guardias")
@Consumes(MediaType.APPLICATION_JSON) @Produces(MediaType.APPLICATION_JSON)
public class GuardiasResource {
    private static final Semaphore CAPACITY=new Semaphore(1);
    @POST @Blocking
    public ShiftOptimization.Result optimize(ShiftOptimization.Request request) {
        ShiftOptimization.validate(request);
        if(!CAPACITY.tryAcquire()) throw new ServiceUnavailableException("Planificador ocupado.");
        try { return new ShiftOptimization().solve(request); }
        finally { CAPACITY.release(); }
    }
}
