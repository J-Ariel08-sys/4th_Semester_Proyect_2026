package bo.edu.unifranz.solver;
import org.junit.jupiter.api.Test;
import java.util.*;
import static org.junit.jupiter.api.Assertions.*;
class ShiftOptimizationTest {
    @Test void choosesCompatibleTutorsInsteadOfGreedilyBlockingConstrainedShift() {
        var r=new ShiftOptimization.Request(List.of(
            new ShiftOptimization.Slot("a","i1",100000,121600,List.of("t1","t2")),
            new ShiftOptimization.Slot("b","i2",100000,121600,List.of("t1"))),Map.of("t1",0L,"t2",0L),12,1);
        var result=new ShiftOptimization().solve(r);
        assertEquals(0,result.hardScore()); assertEquals(0,result.mediumScore());
        assertEquals("t2",result.turnos().get(0).tutorId()); assertEquals("t1",result.turnos().get(1).tutorId());
    }
    @Test void leavesOverlappingInternShiftPendingEvenWithTwoTutors() {
        var r=new ShiftOptimization.Request(List.of(
            new ShiftOptimization.Slot("a","i1",100000,121600,List.of("t1","t2")),
            new ShiftOptimization.Slot("b","i1",125200,146800,List.of("t1","t2"))),Map.of("t1",0L,"t2",0L),12,1);
        var result=new ShiftOptimization().solve(r); assertEquals(0,result.hardScore()); assertEquals(-1,result.mediumScore());
    }
    @Test void balancesPreviousLoadAndHandlesNoAvailableTutor() {
        var r=new ShiftOptimization.Request(List.of(new ShiftOptimization.Slot("a","i1",100000,121600,List.of("t1","t2"))),Map.of("t1",1000L,"t2",0L),12,1);
        assertEquals("t2",new ShiftOptimization().solve(r).turnos().getFirst().tutorId());
        var empty=new ShiftOptimization.Request(List.of(new ShiftOptimization.Slot("a","i1",100000,121600,List.of())),Map.of(),12,1);
        assertNull(new ShiftOptimization().solve(empty).turnos().getFirst().tutorId());
    }
}
