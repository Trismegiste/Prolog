<?php

namespace Trismegiste\Prolog;

/**
 * Interface for output a string
 * @author flo
 */
interface OutputInterface
{

    /**
     * Close a line and start a new one
     * @param string $str
     */
    function writeLn($str);

    /**
     * Continue a line with a string
     * @param string $str
     */
    function write($str);
}
