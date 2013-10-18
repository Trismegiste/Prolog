<?php

/**
 * This is a prototype for testing WAMService
 *
 * @author flo
 */
class WAM_TestCase extends PHPUnit_Framework_TestCase
{

    protected function checkSuccess($solve)
    {
        $this->assertCount(1, $solve);
        $this->assertTrue($solve[0]->succeed);
    }

    protected function checkFailure($solve)
    {
        $this->assertCount(1, $solve);
        $this->assertFalse($solve[0]->succeed);
    }

    /**
     * Check solution of one variable with one success ending with failure or not
     *
     * @param array $solve
     * @param string $key
     * @param mixed $value
     * @param type $withEndingFailure : Is backtracking creating a last entry with failure ?
     */
    protected function checkOneValueSuccess($solve, $key, $value, $withEndingFailure = true)
    {
        $this->checkOneSolutionSuccess($solve, array($key => $value), $withEndingFailure);
    }

    /**
     * Check solution of variables array with one success ending with failure or not
     *
     * @param array $solve
     * @param array $expected
     * @param type $withEndingFailure : Is backtracking creating a last entry with failure ?
     */
    protected function checkOneSolutionSuccess($solve, array $expected, $withEndingFailure = true)
    {
        $this->assertCount(1 + ($withEndingFailure ? 1 : 0), $solve);
        $this->assertTrue($solve[0]->succeed);
        $this->assertCount(count($expected), $solve[0]->getQueryVars());
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $solve[0]->getQueryVars());
            $this->assertEquals($value, $solve[0]->getQueryVars()[$key]);
        }
        if ($withEndingFailure)
            $this->assertFalse($solve[1]->succeed);
    }

}
