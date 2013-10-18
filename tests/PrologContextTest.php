<?php

use Trismegiste\Prolog\PrologContext;

/**
 * Test for the abstract context of Prolog
 * Better abstract = better life
 *
 * @author Flo
 */
class PrologContextTest extends WAM_TestCase
{

    public function testAssertClause()
    {
        $ctx = $this->getMockForAbstractClass('Trismegiste\Prolog\PrologContext');
        $ctx->expects($this->once())
                ->method('runQuery')
                ->with($this->equalTo('assert(father(anakin, luke)).'));
        $ctx->assertClause('father(anakin, luke)');
    }

    public function testLoadProlog()
    {
        $ctx = $this->getMockForAbstractClass('Trismegiste\Prolog\PrologContext');
        $ctx->expects($this->once())
                ->method('runQuery')
                ->with($this->equalTo("consult('nihil.pro')."));
        $ctx->loadProlog('nihil.pro');
    }

    public function testLoadWam()
    {
        $ctx = $this->getMockForAbstractClass('Trismegiste\Prolog\PrologContext');
        $ctx->expects($this->once())
                ->method('runQuery')
                ->with($this->equalTo("load('nihil.wam')."));
        $ctx->loadWam('nihil.wam');
    }

}
