<?php

namespace Trismegiste\Prolog;

/**
 * This class contains one result for a submitted query against the WAMService
 *
 * @author florent
 */
class Solution implements OutputInterface
{

    public $succeed = false;
    protected $output = array('');
    public $elapsedTime = 0;
    public $opCount = 0;
    public $backtrackCount = 0;
    protected $queryVariable = array();

    /**
     * Close a line and start a new one
     * @param string $str
     */
    public function writeLn($str)
    {
        $this->write($str);
        $this->output[] = '';
    }

    /**
     * Continue a line with a string
     * @param string $str
     */
    public function write($str)
    {
        $this->output[count($this->output) - 1] .= $str;
    }

    public function setQueryVar($name, $value)
    {
        $this->queryVariable[$name] = $value;
    }

    public function getQueryVars()
    {
        return new \ArrayIterator($this->queryVariable);
    }

}
