<?php

use Trismegiste\Prolog\WAMService;
use Trismegiste\Prolog\Program;

/**
 * Test for WAMService : metalogic : call, cut, assert, retract
 */
class WAMMetalogicTest extends WAM_TestCase
{

    public function testFixtures3()
    {
        $wam = new WAMService();

        $solve = $wam->runQuery("consult('" . FIXTURES_DIR . "fixtures3.pro').");
        $this->checkSuccess($solve);

        return $wam;
    }

    /**
     * @depends testFixtures3
     */
    public function testAssert(WAMService $wam)
    {
        $solve = $wam->runQuery("assert(robot(c3po)).");
        $solve = $wam->runQuery("robot(X).");
        // no backtrack then no ending with failure (a little odd : improvment to do ? don't know)
        $this->checkOneValueSuccess($solve, 'X', 'c3po', false);

        return $wam;
    }

    /**
     * @depends testAssert
     */
    public function testRetract(WAMService $wam)
    {
        $solve = $wam->runQuery("retract(robot).");
        $solve = $wam->runQuery("assert(robot(ig88)).");
        $solve = $wam->runQuery("robot(X).");
        $this->checkOneValueSuccess($solve, 'X', 'ig88', false);

        return $wam;
    }

    /**
     * @depends testRetract
     */
    public function testRetractAll(WAMService $wam)
    {
        $solve = $wam->runQuery("retractall(robot).");
        $solve = $wam->runQuery("assert(robot(ig88)).");
        $solve = $wam->runQuery("assert(robot(r2d2)).");
        $solve = $wam->runQuery("retract(robot).");
        $solve = $wam->runQuery("robot(X).");
        $this->checkOneValueSuccess($solve, 'X', 'ig88', false);
    }

    /**
     * @depends testFixtures3
     */
    public function testUnknownCall(WAMService $wam)
    {
        $solve = $wam->runQuery("call(foo).");
        $this->checkFailure($solve);
    }

    /**
     * @depends testFixtures3
     */
    public function testUnknownCallSTR(WAMService $wam)
    {
        $solve = $wam->runQuery("call(foo(bar)).");
        $this->checkFailure($solve);
    }

    /**
     * @depends testFixtures3
     */
    public function testForUnif(WAMService $wam)
    {
        $solve = $wam->runQuery("unif1(male, X).");
        $this->checkOneValueSuccess($solve, 'X', 'luke', false);
    }

    /**
     * @depends testFixtures3
     */
    public function testForUnif2(WAMService $wam)
    {
        $solve = $wam->runQuery("unif2(father, anakin ,X).");
        $this->checkOneValueSuccess($solve, 'X', 'luke', false);
    }

    /**
     * @depends testFixtures3
     */
    public function testAssertBacktracked(WAMService $wam)
    {
        $solve = $wam->runQuery("retractall(robot).");
        $solve = $wam->runQuery("assert(robot(c3po)).");
        $solve = $wam->runQuery("assert(robot(ig88)).");
        $solve = $wam->runQuery("donothing(robot(r2d2)).");
        $solve = $wam->runQuery("robot(X).");
        $this->assertCount(2, $solve);
        foreach (array('c3po', 'ig88') as $k => $name) {
            $this->assertTrue($solve[$k]->succeed);
            $this->assertEquals($name, $solve[$k]->getQueryVars()['X']);
        }
    }

}
