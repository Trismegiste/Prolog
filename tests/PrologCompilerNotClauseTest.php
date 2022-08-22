<?php

use Trismegiste\Prolog\PrologCompiler;
use Trismegiste\Prolog\CompilerStructure;
use Trismegiste\Prolog\WAMService;
use Trismegiste\Prolog\Program;

/**
 * Test Prolog Compiler for standard not (negation by failure)
 * Check the resulting CompilerStructure
 *
 * @author flo
 */
class PrologCompilerNotClauseTest extends \PHPUnit\Framework\TestCase
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
        $programList = $this->compiler->stringToList("not(Call) :- call(Call), !, fail. not(Call).");
        $struc = new CompilerStructure();
        $this->compiler->program($programList, $struc);
        $this->assertEquals(CompilerStructure::PROGRAM, $struc->type);
        return $struc;
    }

    /**
     * @depends testProgram
     */
    public function testNot1(CompilerStructure $struc)
    {
        $struc = $struc->head;
        $this->assertEquals(CompilerStructure::CLAUSE, $struc->type);
        $this->assertNotNull($struc->tail);
        return $struc;
    }

    /**
     * @depends testProgram
     */
    public function testNot2(CompilerStructure $struc)
    {
        $struc = $struc->tail;
        $this->assertEquals(CompilerStructure::PROGRAM, $struc->type);
        $struc = $struc->head;
        $this->assertEquals(CompilerStructure::CLAUSE, $struc->type);
        $this->assertNull($struc->tail);
        return $struc->head;
    }

    /**
     * @depends testNot1
     */
    public function testClause1Head(CompilerStructure $struc)
    {
        $struc = $struc->head;
        $this->assertEquals(CompilerStructure::HEAD, $struc->type);
        return $struc;
    }

    /**
     * @depends testNot1
     */
    public function testClause1Body(CompilerStructure $struc)
    {
        $struc = $struc->tail;
        $this->assertEquals(CompilerStructure::BODY, $struc->type);
        return $struc;
    }

    /**
     * @depends testClause1Head
     */
    public function testPredicate(CompilerStructure $struc)
    {
        $struc = $struc->head;
        $this->assertEquals(CompilerStructure::PREDICATE, $struc->type);
        $this->assertEquals('not', $struc->value);
    }

    /**
     * @depends testClause1Head
     */
    public function testHead1List1(CompilerStructure $struc)
    {
        $struc = $struc->tail;
        $this->assertEquals(CompilerStructure::LISTX, $struc->type);
        $struc = $struc->head;
        $this->assertEquals(CompilerStructure::VARIABLE, $struc->type);
        $this->assertEquals('Call', $struc->value);
    }

    /**
     * @depends testClause1Body
     */
    public function testBodyPredicate(CompilerStructure $struc)
    {
        $this->assertNotNull($struc->tail);
        $this->assertEquals(CompilerStructure::BODY, $struc->tail->type);
        // test call(Call)
        $call = $struc->head;
        $this->assertEquals(CompilerStructure::CALL, $call->type);
        $call = $call->head;
        $this->assertEquals(CompilerStructure::PREDICATE, $call->type);
        $this->assertEquals('call', $call->value);
        $call = $call->tail;
        $this->assertEquals(CompilerStructure::LISTX, $call->type);
        $call = $call->head;
        $this->assertEquals(CompilerStructure::VARIABLE, $call->type);
        $this->assertEquals('Call', $call->value);
        // test cut
        $cut = $struc->tail;
        $this->assertNotNull($cut->tail);
        $this->assertEquals(CompilerStructure::BODY, $cut->type);
        $this->assertEquals(CompilerStructure::CUT, $cut->head->type);
        // test fail
        $fail = $cut->tail;
        $this->assertEquals(CompilerStructure::BODY, $fail->type);
        $this->assertNull($fail->tail);
        $fail = $fail->head;
        $this->assertEquals(CompilerStructure::CALL, $fail->type);
        $fail = $fail->head;
        $this->assertEquals(CompilerStructure::PREDICATE, $fail->type);
        $this->assertEquals('fail', $fail->value);
    }

    /**
     * @depends testNot2
     */
    public function testClause2Head(CompilerStructure $struc)
    {
        $this->assertEquals(CompilerStructure::HEAD, $struc->type);
        $this->assertEquals(CompilerStructure::PREDICATE, $struc->head->type);
        $this->assertEquals('not', $struc->head->value);
        // arg of predicate
        $struc = $struc->tail;
        $this->assertEquals(CompilerStructure::LISTX, $struc->type);
        $this->assertNull($struc->tail);
        $struc = $struc->head;
        $this->assertEquals(CompilerStructure::VARIABLE, $struc->type);
        $this->assertEquals('Call', $struc->value);
    }

}
