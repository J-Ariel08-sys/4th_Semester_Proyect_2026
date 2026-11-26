package bo.edu.unifranz.solver;
import org.junit.jupiter.api.Test;
import jakarta.ws.rs.BadRequestException;
import java.util.*;
import static org.junit.jupiter.api.Assertions.*;
class OptimizationTest {
    private final Optimization.Policy policy = new Optimization.Policy(10, 1, 0, 5);
    @Test void respectsCapacityReservesAndEligibility() {
        var request = new Optimization.Request(List.of(new Optimization.Place("h1",2,1)),
            List.of(new Optimization.Intern("i1",true,90,List.of("h1"),null),
                    new Optimization.Intern("i2",true,70,List.of("h1"),null),
                    new Optimization.Intern("i3",false,100,List.of("h1"),null)),policy,1);
        var result = new Optimization().solve(request);
        assertEquals(0,result.hardScore()); assertEquals(1,result.asignaciones().size());
        assertEquals("i1",result.asignaciones().getFirst().internoId());
        assertEquals(2,result.pendientes().size()); assertTrue(result.requiereAprobacion());
    }
    @Test void satisfiesDifferentFirstPreferences() {
        var request = new Optimization.Request(List.of(new Optimization.Place("a",1,0),new Optimization.Place("b",1,0)),
            List.of(new Optimization.Intern("i1",true,80,List.of("b","a"),null),new Optimization.Intern("i2",true,80,List.of("a","b"),null)),policy,1);
        var result = new Optimization().solve(request);
        assertEquals(2,result.primerasPreferencias()); assertTrue(result.pendientes().isEmpty());
    }
    @Test void noPlacesReturnsPendingInsteadOfInventingAssignments() {
        var result = new Optimization().solve(new Optimization.Request(List.of(),List.of(new Optimization.Intern("i",true,10,List.of(),null)),policy,1));
        assertTrue(result.asignaciones().isEmpty()); assertEquals(1,result.pendientes().size());
    }
    @Test void rejectsInvalidInputs() {
        assertThrows(BadRequestException.class,()->Optimization.validate(null));
        assertThrows(BadRequestException.class,()->Optimization.validate(new Optimization.Request(List.of(new Optimization.Place("a",1,2)),List.of(),policy,1)));
        assertThrows(BadRequestException.class,()->Optimization.validate(new Optimization.Request(List.of(),List.of(new Optimization.Intern("i",true,50,List.of("unknown"),null)),policy,1)));
        assertThrows(BadRequestException.class,()->Optimization.validate(new Optimization.Request(List.of(),List.of(),policy,0)));
    }
    @Test void scoresPreferenceBalanceAndChangePenalty() {
        var place=new Optimization.Place("a",3,0);
        var first=new Rotation(new Optimization.Intern("i1",true,80,List.of("a"),"old")); first.place=place;
        var second=new Rotation(new Optimization.Intern("i2",true,60,List.of("a"),null)); second.place=place;
        var plan=new RotationPlan(List.of(place),List.of(first,second),new Optimization.Policy(1,1,1,2));
        // 200 preference + 140 merit - 200 change - floor(4*100/3) occupancy.
        assertEquals(7,new RotationScore().calculateScore(plan).softScore());
    }

    @Test @org.junit.jupiter.api.Timeout(60)
    void handlesReportScaleOf180InternsAnd45CapacityGroups() {
        List<Optimization.Place> places=new ArrayList<>();
        List<Optimization.Intern> interns=new ArrayList<>();
        for(int i=0;i<45;i++) places.add(new Optimization.Place("p"+i,5,1));
        for(int i=0;i<180;i++) interns.add(new Optimization.Intern("i"+i,true,80,List.of("p"+(i%45)),null));
        long start=System.nanoTime();
        var result=new Optimization().solve(new Optimization.Request(places,interns,policy,2));
        assertEquals(180,result.asignaciones().size()); assertEquals(0,result.hardScore());
        assertTrue(result.ocupacion().values().stream().allMatch(count->count<=4));
        System.out.println("SICLIN benchmark 180 interns / 45 groups: "+((System.nanoTime()-start)/1_000_000)+" ms");
    }
}
