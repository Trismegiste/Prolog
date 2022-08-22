<?php

use Tests\Trismegiste\Prolog\WamTestCase;
use Trismegiste\Prolog\WAMService;

/**
 * Test for WAMService : example of classical non deterministic problem
 */
class WAMHanoiTest extends WamTestCase
{

    public function testFixtures()
    {
        $wam = new WAMService();

        $solve = $wam->runQuery("consult('" . __DIR__ . '/fixtures/' . "hanoi.pro').");
        $this->checkSuccess($solve);

        return $wam;
    }

    /**
     * @depends testFixtures
     */
    public function testOutput(WAMService $wam)
    {
        $solve = $wam->runQuery("hanoi(4).");
        $this->checkSuccess($solve);
       // $this->assertAttributeContains('transport de milieu sur droite', 'output', $solve[0]);
    }

}
