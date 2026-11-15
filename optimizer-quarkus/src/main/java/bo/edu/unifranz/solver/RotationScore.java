package bo.edu.unifranz.solver;
import ai.timefold.solver.core.api.score.calculator.EasyScoreCalculator;
import ai.timefold.solver.core.api.score.buildin.hardmediumsoftlong.HardMediumSoftLongScore;
import java.util.HashMap;
import java.util.Map;
public class RotationScore implements EasyScoreCalculator<RotationPlan, HardMediumSoftLongScore> {
    @Override public HardMediumSoftLongScore calculateScore(RotationPlan plan) {
        long hard = 0, medium = 0, soft = 0;
        Map<Optimization.Place, Integer> counts = new HashMap<>();
        for (Rotation rotation : plan.rotations) {
            var intern = rotation.intern;
            var place = rotation.place;
            if (place == null) { if (intern.habilitado()) medium--; }
            else {
                if (!intern.habilitado()) hard--;
                counts.merge(place, 1, Integer::sum);
                int rank = intern.preferencias().indexOf(place.id());
                int satisfaction = rank < 0 ? 0 : 100 * (intern.preferencias().size() - rank) / intern.preferencias().size();
                soft += (long) satisfaction * plan.policy.pesoPreferencias();
                soft += (long) intern.merito() * plan.policy.pesoMerito();
            }
            if (intern.plazaAnterior() != null && (place == null || !intern.plazaAnterior().equals(place.id())))
                soft -= 100L * plan.policy.pesoCambios();
        }
        for (var entry : counts.entrySet()) {
            int capacity = entry.getKey().capacidad() - entry.getKey().reserva();
            int count = entry.getValue();
            hard -= Math.max(0, count - capacity);
            soft -= (long) plan.policy.pesoEquilibrio() * count * count * 100 / Math.max(1, capacity);
        }
        return HardMediumSoftLongScore.of(hard, medium, soft);
    }
}
