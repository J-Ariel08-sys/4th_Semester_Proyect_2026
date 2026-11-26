package bo.edu.unifranz.solver;
import ai.timefold.solver.core.api.domain.entity.PlanningEntity;
import ai.timefold.solver.core.api.domain.lookup.PlanningId;
import ai.timefold.solver.core.api.domain.variable.PlanningVariable;
@PlanningEntity
public class Rotation {
    @PlanningId public String id;
    public Optimization.Intern intern;
    @PlanningVariable(valueRangeProviderRefs = "places", allowsUnassigned = true)
    public Optimization.Place place;
    public Rotation() {}
    public Rotation(Optimization.Intern intern) { this.id = intern.id(); this.intern = intern; }
}
