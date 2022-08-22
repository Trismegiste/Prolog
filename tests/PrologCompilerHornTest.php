<?php

use Trismegiste\Prolog\PrologCompiler;
use Trismegiste\Prolog\CompilerStructure;
use Trismegiste\Prolog\WAMService;
use Trismegiste\Prolog\Program;

/**
 * Test Prolog Compiler for Horn clauses
 * Check the resulting CompilerStructure
 *
 * @author flo
 */
class PrologCompilerHornTest extends \PHPUnit\Framework\TestCase
{

    private $compiler = null;

    protected function setUp(): void
    {
        $this->compiler = new PrologCompiler(new WAMService());
    }

    protected function tearDown(): void
    {
        unset($this->compiler);
    }

    public function testProgram()
    {
        $programList = $this->compiler->stringToList("mortal(X) :- human(X).");
        $struc = new CompilerStructure();
        $this->compiler->program($programList, $struc);
        $this->assertEquals(CompilerStructure::PROGRAM, $struc->type);
        return $struc->head;
    }

    /**
     * @depends testProgram
     */
    public function testClause(CompilerStructure $struc)
    {
        $this->assertEquals(CompilerStructure::CLAUSE, $struc->type);
        return $struc;
    }

    /**
     * @depends testClause
     */
    public function testClauseHead(CompilerStructure $struc)
    {
        $struc = $struc->head;
        $this->assertEquals(CompilerStructure::HEAD, $struc->type);
        return $struc;
    }

    /**
     * @depends testClause
     */
    public function testClauseBody(CompilerStructure $struc)
    {
        $struc = $struc->tail;
        $this->assertEquals(CompilerStructure::BODY, $struc->type);
        return $struc;
    }

    /**
     * @depends testClauseHead
     */
    public function testPredicate(CompilerStructure $struc)
    {
        $struc = $struc->head;
        $this->assertEquals(CompilerStructure::PREDICATE, $struc->type);
        $this->assertEquals('mortal', $struc->value);
    }

    /**
     * @depends testClauseHead
     */
    public function testList(CompilerStructure $struc)
    {
        $struc = $struc->tail;
        $this->assertEquals(CompilerStructure::LISTX, $struc->type);
        return $struc;
    }

    /**
     * @depends testList
     */
    public function testTerm1(CompilerStructure $struc)
    {
        $struc = $struc->head;
        $this->assertEquals(CompilerStructure::VARIABLE, $struc->type);
        $this->assertEquals('X', $struc->value);
    }

    /**
     * @depends testClauseBody
     */
    public function testBodyPredicate(CompilerStructure $struc)
    {
        $this->assertNull($struc->tail);
        $struc = $struc->head;
        $this->assertEquals(CompilerStructure::CALL, $struc->type);
        $struc = $struc->head;
        $this->assertEquals(CompilerStructure::PREDICATE, $struc->type);
        $this->assertEquals('human', $struc->value);
        return $struc->tail;
    }

    /**
     * @depends testBodyPredicate
     */
    public function testTerm2(CompilerStructure $struc)
    {
        $this->assertEquals(CompilerStructure::LISTX, $struc->type);
        $struc = $struc->head;
        $this->assertEquals(CompilerStructure::VARIABLE, $struc->type);
        $this->assertEquals('X', $struc->value);
    }

}

?>
