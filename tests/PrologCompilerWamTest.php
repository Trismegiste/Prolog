<?php

use Trismegiste\Prolog\PrologCompiler;
use Trismegiste\Prolog\WAMService;

/**
 * Test the generated instructions for Warren Abstract Machine
 * An instruction is a Statement
 * Comparisons are made with toString
 *
 * @author flo
 */
class PrologCompilerWamTest extends \PHPUnit\Framework\TestCase
{

    private $compiler = null;

    protected function setUp(): void
    {
        $this->compiler = new PrologCompiler(new WAMService());
    }

    protected function assertEqualsNoSpacing($expected, $tested)
    {
        $this->assertEquals(0, strcmp(trim($expected), trim($tested)));
    }

    protected function tearDown(): void
    {
        unset($this->compiler);
    }

    public function testClauseUnif()
    {
        $p = $this->compiler->compile('equal(X, X).');
        $wamCode = <<<WAM
equal:        trust_me
              allocate
              get_variable Y0 A0
              get_value Y0 A1
              deallocate
              proceed
WAM;
        $wamCode = explode("\n", $wamCode);
        for ($k = 0; $k < $p->getStatementCount(); $k++)
            $this->assertEqualsNoSpacing($p->getStatement($k), $wamCode[$k]);
    }

    public function testClauseFacts()
    {
        $p = $this->compiler->compile('mother(shmi, anakin). mother(padme, luke).');
        $wamCode = <<<WAM
mother:       try_me_else mother~2
              get_constant shmi A0
              get_constant anakin A1
              proceed
mother~2:     trust_me
              get_constant padme A0
              get_constant luke A1
              proceed
WAM;
        $wamCode = explode("\n", $wamCode);
        for ($k = 0; $k < $p->getStatementCount(); $k++)
            $this->assertEqualsNoSpacing($p->getStatement($k), $wamCode[$k]);
    }

    public function testClauseHorn()
    {
        $p = $this->compiler->compile('grandmother(X, Y) :- mother(X, Z), mother(Z,Y).');
        $wamCode = <<<WAM
grandmother:  trust_me
              allocate
              get_variable Y0 A0
              get_variable Y1 A1
              put_value Y0 A0
              put_value Y2 A1
              call mother
              put_value Y2 A0
              put_value Y1 A1
              call mother
              deallocate
              proceed
WAM;
        $wamCode = explode("\n", $wamCode);
        for ($k = 0; $k < $p->getStatementCount(); $k++)
            $this->assertEqualsNoSpacing($p->getStatement($k), $wamCode[$k]);
    }

    public function testClauseNot()
    {
        $p = $this->compiler->compile('not(Call) :- call(Call), !, fail. not(Call).');
        $wamCode = <<<WAM
not:          try_me_else not~2
              allocate
              get_variable Y0 A0
              get_level Y1
              put_value Y0 A0
              call call
              cut Y1
              call fail
              deallocate
              proceed
not~2:        trust_me
              allocate
              get_variable Y0 A0
              deallocate
              proceed
WAM;
        $wamCode = explode("\n", $wamCode);
        for ($k = 0; $k < $p->getStatementCount(); $k++)
            $this->assertEqualsNoSpacing($p->getStatement($k), $wamCode[$k]);
    }

    public function testClauseListAppend()
    {
        $p = $this->compiler->compile('append([], Z, Z). append([A|B], Z, [A|ZZ]) :- append(B, Z, ZZ).');
        $wamCode = <<<WAM
append:       try_me_else append~2
              allocate
              get_constant [] A0
              get_variable Y0 A1
              get_value Y0 A2
              deallocate
              proceed
append~2:     trust_me
              allocate
              get_variable Y0 A0
              unify_list Y3 Y1 Y2
              unify_variable Y0 Y3
              get_variable Y4 A1
              get_variable Y5 A2
              unify_list Y7 Y1 Y6
              unify_variable Y5 Y7
              put_value Y2 A0
              put_value Y4 A1
              put_value Y6 A2
              call append
              deallocate
              proceed
WAM;
        $wamCode = explode("\n", $wamCode);
        for ($k = 0; $k < $p->getStatementCount(); $k++)
            $this->assertEqualsNoSpacing($p->getStatement($k), $wamCode[$k]);
    }

    public function testClauseArithmetics()
    {
        $p = $this->compiler->compile('factorial(0, 1). factorial(N, X) :- N > 0, N1 is N - 1, factorial(N1, P), X is N * P.');
        $wamCode = <<<WAM
factorial:    try_me_else factorial~2
              get_constant 0 A0
              get_constant 1 A1
              proceed
factorial~2:  trust_me
              allocate
              get_variable Y0 A0
              get_variable Y1 A1
              put_constant 0 Y2
              bigger Y0 Y2
              put_constant 1 Y3
              is Y4 - Y0 Y3
              put_value Y4 A0
              put_value Y5 A1
              call factorial
              is Y1 * Y0 Y5
              deallocate
              proceed
WAM;
        $wamCode = explode("\n", $wamCode);
        for ($k = 0; $k < $p->getStatementCount(); $k++)
            $this->assertEqualsNoSpacing($p->getStatement($k), $wamCode[$k]);
    }

    public function testClauseStructure()
    {
        $p = $this->compiler->compile('p(snm(determinant(X), nom(Y), G), X, Y) :- sn(X, Y), genre(X, G).');
        $wamCode = <<<WAM
p:            trust_me
              allocate
              get_variable Y0 A0
              put_constant snm Y1
              put_constant determinant Y2
              put_constant [] Y4
              unify_list Y5 Y3 Y4
              unify_struc Y6 Y2 Y5
              put_constant nom Y7
              put_constant [] Y9
              unify_list Y10 Y8 Y9
              unify_struc Y11 Y7 Y10
              put_constant [] Y13
              unify_list Y14 Y12 Y13
              unify_list Y15 Y11 Y14
              unify_list Y16 Y6 Y15
              unify_struc Y17 Y1 Y16
              unify_variable Y0 Y17
              get_value Y3 A1
              get_value Y8 A2
              put_value Y3 A0
              put_value Y8 A1
              call sn
              put_value Y3 A0
              put_value Y12 A1
              call genre
              deallocate
              proceed
WAM;
        $wamCode = explode("\n", $wamCode);
        for ($k = 0; $k < $p->getStatementCount(); $k++)
            $this->assertEqualsNoSpacing($p->getStatement($k), $wamCode[$k]);
    }

    /**
     * Test for the prolog keyword assert
     * @covers Trismegiste\Prolog\PrologCompiler::compileSimpleClause
     */
    public function testSimpleClause()
    {
        $p = $this->compiler->compileSimpleClause('father(anakin, luke).');
        $wamCode = <<<WAM
father:       trust_me
              get_constant anakin A0
              get_constant luke A1
              proceed
WAM;
        $wamCode = explode("\n", $wamCode);
        for ($k = 0; $k < $p->getStatementCount(); $k++)
            $this->assertEqualsNoSpacing($p->getStatement($k), $wamCode[$k]);
    }

}
