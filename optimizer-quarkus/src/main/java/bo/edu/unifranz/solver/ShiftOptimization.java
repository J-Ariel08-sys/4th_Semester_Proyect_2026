package bo.edu.unifranz.solver;

import ai.timefold.solver.core.api.domain.entity.PlanningEntity;
import ai.timefold.solver.core.api.domain.lookup.PlanningId;
import ai.timefold.solver.core.api.domain.solution.*;
import ai.timefold.solver.core.api.domain.valuerange.ValueRangeProvider;
import ai.timefold.solver.core.api.domain.variable.PlanningVariable;
import ai.timefold.solver.core.api.score.buildin.hardmediumsoftlong.HardMediumSoftLongScore;
import ai.timefold.solver.core.api.score.calculator.EasyScoreCalculator;
import ai.timefold.solver.core.api.solver.SolverFactory;
import ai.timefold.solver.core.config.solver.SolverConfig;
import jakarta.ws.rs.BadRequestException;
import java.time.Duration;
import java.util.*;

/** Assign supervisors to fixed shifts, with availability prefiltered by the portal. */
public class ShiftOptimization {
    public record Slot(String id, String internoId, long inicio, long fin, List<String> tutoresPermitidos) {}
    public record Request(List<Slot> turnos, Map<String, Long> cargaPreviaMinutos, int descansoHoras, int segundos) {}
    public record Assignment(String turnoId, String tutorId) {}
    public record Result(long hardScore, long mediumScore, long softScore, List<Assignment> turnos) {}
    @PlanningEntity
    public static class Shift {
        @PlanningId public String id;
        public Slot slot;
        @PlanningVariable(valueRangeProviderRefs="tutors", allowsUnassigned=true) public String tutor;
        public Shift() {}
        public Shift(Slot slot) { this.id=slot.id(); this.slot=slot; }
    }
    @PlanningSolution
    public static class Plan {
        @ValueRangeProvider(id="tutors") @ProblemFactCollectionProperty public List<String> tutors;
        @PlanningEntityCollectionProperty public List<Shift> shifts;
        @ProblemFactProperty public Request request;
        @PlanningScore public HardMediumSoftLongScore score;
        public Plan() {}
        public Plan(Request request) {
            this.request=request;
            this.tutors=new ArrayList<>(request.cargaPreviaMinutos().keySet());
            Collections.sort(this.tutors);
            this.shifts=new ArrayList<>(request.turnos().stream().map(Shift::new).toList());
        }
    }
    public static class Score implements EasyScoreCalculator<Plan,HardMediumSoftLongScore> {
        public HardMediumSoftLongScore calculateScore(Plan plan) {
            long hard=0, medium=0, soft=0;
            Map<String,Long> loads=new HashMap<>(plan.request.cargaPreviaMinutos());
            long rest=plan.request.descansoHoras()*3600L;
            for (int i=0;i<plan.shifts.size();i++) {
                Shift shift=plan.shifts.get(i);
                if (shift.tutor==null) { medium--; continue; }
                if (!shift.slot.tutoresPermitidos().contains(shift.tutor)) hard--;
                loads.merge(shift.tutor,(shift.slot.fin()-shift.slot.inicio())/60,Long::sum);
                for (int j=0;j<i;j++) {
                    Shift other=plan.shifts.get(j);
                    if (other.tutor!=null && (shift.tutor.equals(other.tutor) || shift.slot.internoId().equals(other.slot.internoId()))
                        && shift.slot.inicio()<other.slot.fin()+rest && other.slot.inicio()<shift.slot.fin()+rest) hard--;
                }
            }
            for (long load:loads.values()) soft-=load*load;
            return HardMediumSoftLongScore.of(hard,medium,soft);
        }
    }
    public static void validate(Request request) {
        require(request!=null && request.turnos()!=null && request.cargaPreviaMinutos()!=null,"Solicitud de guardias requerida.");
        require(request.turnos().size()<=100 && request.cargaPreviaMinutos().size()<=500,"Máximo 100 turnos y 500 tutores.");
        require(request.descansoHoras()>=0 && request.descansoHoras()<=48 && request.segundos()>=1 && request.segundos()<=30,"Descanso o duración de búsqueda inválidos.");
        for (var entry:request.cargaPreviaMinutos().entrySet()) require(id(entry.getKey()) && entry.getValue()!=null && entry.getValue()>=0 && entry.getValue()<=1000000,"Carga de tutor inválida.");
        Set<String> ids=new HashSet<>();
        for (Slot slot:request.turnos()) {
            require(slot!=null && id(slot.id()) && ids.add(slot.id()) && id(slot.internoId()),"Turno duplicado o identificador inválido.");
            require(slot.inicio()>=0 && slot.fin()>slot.inicio() && slot.fin()-slot.inicio()<=86400 && slot.fin()<32503680000L,"Duración de turno inválida.");
            require(slot.tutoresPermitidos()!=null && request.cargaPreviaMinutos().keySet().containsAll(slot.tutoresPermitidos()),"Tutor desconocido.");
        }
    }
    private static boolean id(String id) { return id!=null && !id.isBlank() && id.length()<=100; }
    private static void require(boolean condition,String message) { if(!condition) throw new BadRequestException(message); }
    public Result solve(Request request) {
        validate(request); Plan plan=new Plan(request);
        if (!plan.tutors.isEmpty() && !plan.shifts.isEmpty()) {
            plan=SolverFactory.<Plan>create(new SolverConfig().withSolutionClass(Plan.class).withEntityClasses(Shift.class)
                .withEasyScoreCalculatorClass(Score.class).withRandomSeed(0L).withTerminationSpentLimit(Duration.ofSeconds(request.segundos())))
                .buildSolver().solve(plan);
        }
        var score=new Score().calculateScore(plan);
        if(score.hardScore()<0) throw new IllegalStateException("Calendario con conflictos.");
        return new Result(score.hardScore(),score.mediumScore(),score.softScore(),plan.shifts.stream().map(s->new Assignment(s.id,s.tutor)).toList());
    }
}
