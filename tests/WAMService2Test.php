<?php

use Trismegiste\Prolog\WAMService;
use Trismegiste\Prolog\Program;

/**
 * Test for the WAMService itself
 * Testing arithmetic, list, structure and comparison
 *
 * @author flo
 */
class WAMService2Test extends WAM_TestCase
{

    public function testFixtures2()
    {
        $wam = new WAMService();

        $solve = $wam->runQuery("consult('" . FIXTURES_DIR . "fixtures2.pro').");
        $this->checkSuccess($solve);

        return $wam;
    }

    /**
     * @depends testFixtures2
     */
    public function testArithmetics(WAMService $wam)
    {
        $solve = $wam->runQuery("factorial(6, X).");
        $this->checkOneValueSuccess($solve, 'X', 720);
        $this->assertEquals(6, $solve[0]->backtrackCount);
        $this->assertGreaterThan(0, $solve[0]->opCount);
        $this->assertGreaterThan(0, $solve[0]->elapsedTime);
    }

    /**
     * @depends testFixtures2
     */
    public function testLists(WAMService $wam)
    {
        $tab = range(0, 13);
        $hypothesisX = '[' . implode(', ', $tab) . ']';
        $hypothesisR = '[' . implode(', ', array_reverse($tab)) . ']';
        shuffle($tab);
        $chaos = '[' . implode(', ', $tab) . ']';

        $solve = $wam->runQuery("qsort($chaos, X).");
        $this->checkOneValueSuccess($solve, 'X', $hypothesisX);

        $solve = $wam->runQuery("qsort($chaos, X), reverse(X, R), length(R, N).");
        $this->checkOneSolutionSuccess($solve, array('X' => $hypothesisX, 'R' => $hypothesisR, 'N' => count($tab)));
    }

    /**
     * @depends testFixtures2
     */
    public function testStructure(WAMService $wam)
    {
        $solve = $wam->runQuery('p(S,le,chat).');
        $this->checkOneValueSuccess($solve, 'S', 'snm(determinant(le), nom(chat), masculin)');
        $solve = $wam->runQuery('p(S,X,blanche).');
        $this->checkOneSolutionSuccess($solve, array(
            'S' => 'snm(determinant(souris), nom(blanche), feminin)',
            'X' => 'souris'));
    }

}
