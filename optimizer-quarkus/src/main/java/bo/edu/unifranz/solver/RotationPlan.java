package bo.edu.unifranz.solver;
import ai.timefold.solver.core.api.domain.solution.*;
import ai.timefold.solver.core.api.domain.valuerange.ValueRangeProvider;
import ai.timefold.solver.core.api.score.buildin.hardmediumsoftlong.HardMediumSoftLongScore;
import java.util.List;
@PlanningSolution
public class RotationPlan {
    @ValueRangeProvider(id = "places") @ProblemFactCollectionProperty
    public List<Optimization.Place> places;
    @PlanningEntityCollectionProperty public List<Rotation> rotations;
    @ProblemFactProperty public Optimization.Policy policy;
    @PlanningScore public HardMediumSoftLongScore score;
    public RotationPlan() {}
    public RotationPlan(List<Optimization.Place> places, List<Rotation> rotations, Optimization.Policy policy) {
        this.places = places; this.rotations = rotations; this.policy = policy;
    }
}
