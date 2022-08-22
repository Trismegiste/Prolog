<?php

use Trismegiste\Prolog\WAMService;
use Trismegiste\Prolog\Program;

/**
 * Test for the WAM itself
 * Testing simple queries on starwars
 *
 * @author flo
 */
class WAMService1Test extends \Tests\Trismegiste\Prolog\WamTestCase
{

    public function testFixtures1()
    {
        $wam = new WAMService();

        $solve = $wam->runQuery("consult('" . __DIR__ . '/fixtures/' . "fixtures1.pro').");
        $this->checkSuccess($solve);

        return $wam;
    }

    /**
     * @depends testFixtures1
     */
    public function testIdentity(WAMService $wam)
    {
        $solve = $wam->runQuery("equal(luke, luke).");
        $this->checkSuccess($solve);
    }

    /**
     * @depends testFixtures1
     */
    public function testUnification(WAMService $wam)
    {
        $solve = $wam->runQuery("equal(luke, X).");
        $this->checkOneValueSuccess($solve, 'X', 'luke', false);
    }

    /**
     * @depends testFixtures1
     */
    public function testFamilyTree1(WAMService $wam)
    {
        $solve = $wam->runQuery("grandmother(X, luke).");
        $this->assertCount(3, $solve);
        $this->assertTrue($solve[0]->succeed);
        $this->assertEquals('ruwee', $solve[0]->getQueryVars()['X']);
        $this->assertTrue($solve[1]->succeed);
        $this->assertEquals('shmi', $solve[1]->getQueryVars()['X']);
        $this->assertFalse($solve[2]->succeed);
    }

    /**
     * @depends testFixtures1
     */
    public function testFamilyTree2(WAMService $wam)
    {
        $solve = $wam->runQuery("grandmother(shmi, X).");
        $this->assertCount(3, $solve);
        $this->assertTrue($solve[0]->succeed);
        $this->assertEquals('luke', $solve[0]->getQueryVars()['X']);
        $this->assertTrue($solve[1]->succeed);
        $this->assertEquals('leia', $solve[1]->getQueryVars()['X']);
        $this->assertFalse($solve[2]->succeed);
    }

    /**
     * @depends testFixtures1
     */
    public function testFamilyTree3(WAMService $wam)
    {
        $solve = $wam->runQuery("brother(luke, leia).");
        $this->assertCount(3, $solve);
        $this->assertTrue($solve[0]->succeed);
        $this->assertTrue($solve[1]->succeed);
        $this->assertFalse($solve[2]->succeed);
    }

    /**
     * @depends testFixtures1
     */
    public function testPredicateArg(WAMService $wam)
    {
        $solve = $wam->runQuery("equal(molecule(carbon, oxygen), molecule(carbon, X)).");
        $this->checkOneValueSuccess($solve, 'X', 'oxygen', false);
    }

}
