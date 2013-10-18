<?php

/*
 * Hiragana
 */

namespace Trismegiste\Prolog\Solution;

/**
 * Lis is a list solution content
 */
class Lis extends \ArrayObject
{

    public function __toString()
    {
        $str = '[';
        $sep = '';
        foreach ($this as $item) {
            $str .= $sep . $item;
            $sep = ', ';
        }
        return $str . ']';
    }

}