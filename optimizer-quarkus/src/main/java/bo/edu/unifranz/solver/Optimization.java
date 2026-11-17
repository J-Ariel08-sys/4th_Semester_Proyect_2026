package bo.edu.unifranz.solver;
import ai.timefold.solver.core.api.solver.SolverFactory;
import ai.timefold.solver.core.config.solver.SolverConfig;
import jakarta.ws.rs.BadRequestException;
import java.time.Duration;
import java.util.*;
/** One specialty and one academic period per request; Laravel validates calendar compatibility. */
public class Optimization {
    public record Place(String id, int capacidad, int reserva) {}
    public record Intern(String id, boolean habilitado, int merito, List<String> preferencias, String plazaAnterior) {}
    public record Policy(int pesoPreferencias, int pesoMerito, int pesoEquilibrio, int pesoCambios) {}
    public record Request(List<Place> plazas, List<Intern> internos, Policy politica, int segundos) {}
    public record Assignment(String internoId, String plazaId, Integer preferencia) {}
    public record Pending(String internoId, String motivo) {}
    public record Result(String estado, boolean requiereAprobacion, long hardScore, long mediumScore, long softScore,
                        List<Assignment> asignaciones, List<Pending> pendientes, Map<String, Integer> ocupacion,
                        int capacidadUtilizable, int primerasPreferencias) {}
    public static void validate(Request request) {
        require(request != null, "Se requiere un cuerpo JSON.");
        require(request.plazas() != null && request.plazas().size() <= 500, "plazas: lista requerida, máximo 500.");
        require(request.internos() != null && request.internos().size() <= 500, "internos: lista requerida, máximo 500.");
        require(request.politica() != null, "politica requerida.");
        require(request.segundos() >= 1 && request.segundos() <= 30, "segundos: entre 1 y 30.");
        var policy = request.politica();
        for (int weight : new int[]{policy.pesoPreferencias(), policy.pesoMerito(), policy.pesoEquilibrio(), policy.pesoCambios()})
            require(weight >= 0 && weight <= 100, "Pesos: entre 0 y 100.");
        Set<String> places = new HashSet<>(), interns = new HashSet<>();
        for (Place place : request.plazas()) {
            require(place != null && validId(place.id()) && places.add(place.id()), "Identificador de plaza inválido o duplicado.");
            require(place.capacidad() >= 0 && place.capacidad() <= 1000 && place.reserva() >= 0 && place.reserva() <= place.capacidad(), "Capacidad o reserva inválida.");
        }
        for (Intern intern : request.internos()) {
            require(intern != null && validId(intern.id()) && interns.add(intern.id()), "Identificador de interno inválido o duplicado.");
            require(intern.merito() >= 0 && intern.merito() <= 100, "Mérito normalizado: entre 0 y 100.");
            require(intern.preferencias() != null && intern.preferencias().size() <= places.size(), "Lista de preferencias requerida.");
            require(new HashSet<>(intern.preferencias()).size() == intern.preferencias().size() && places.containsAll(intern.preferencias()), "Preferencias duplicadas o desconocidas.");
            require(intern.plazaAnterior() == null || validId(intern.plazaAnterior()), "Plaza anterior inválida.");
        }
    }
    private static boolean validId(String id) { return id != null && !id.isBlank() && id.length() <= 100; }
    private static void require(boolean condition, String message) { if (!condition) throw new BadRequestException(message); }
    public Result solve(Request request) {
        validate(request);
        var plan = new RotationPlan(request.plazas(), new ArrayList<>(request.internos().stream().map(Rotation::new).toList()), request.politica());
        if (!plan.places.isEmpty() && !plan.rotations.isEmpty()) {
            var config = new SolverConfig().withSolutionClass(RotationPlan.class).withEntityClasses(Rotation.class)
                .withEasyScoreCalculatorClass(RotationScore.class).withTerminationSpentLimit(Duration.ofSeconds(request.segundos()))
                .withRandomSeed(0L);
            plan = SolverFactory.<RotationPlan>create(config).buildSolver().solve(plan);
        }
        var score = new RotationScore().calculateScore(plan);
        if (score.hardScore() < 0) throw new IllegalStateException("El resultado incumple restricciones duras.");
        List<Assignment> assignments = new ArrayList<>();
        List<Pending> pending = new ArrayList<>();
        Map<String, Integer> occupancy = new LinkedHashMap<>();
        request.plazas().forEach(p -> occupancy.put(p.id(), 0));
        int first = 0;
        for (Rotation rotation : plan.rotations) {
            if (rotation.place == null) pending.add(new Pending(rotation.id, rotation.intern.habilitado() ? "SIN_CUPO_EN_PROPUESTA" : "NO_HABILITADO"));
            else {
                int rank = rotation.intern.preferencias().indexOf(rotation.place.id());
                assignments.add(new Assignment(rotation.id, rotation.place.id(), rank < 0 ? null : rank + 1));
                occupancy.merge(rotation.place.id(), 1, Integer::sum);
                if (rank == 0) first++;
            }
        }
        return new Result(pending.isEmpty() ? "PROPUESTA_COMPLETA" : "PROPUESTA_CON_PENDIENTES", true,
            score.hardScore(), score.mediumScore(), score.softScore(), assignments, pending, occupancy,
            request.plazas().stream().mapToInt(p -> p.capacidad() - p.reserva()).sum(), first);
    }
}
