<?php

/*
 * Hiragana
 */

namespace Trismegiste\Prolog\Solution;

/**
 * Str is a structure
 */
class Str extends \ArrayObject
{

    protected $predicate;

    public function __construct($str)
    {
        $this->predicate = $str;
    }

    public function getPredicate()
    {
        return $this->predicate;
    }

    public function __toString()
    {
        $str = $this->predicate . '(';
        $sep = '';
        foreach ($this as $item) {
            $str .= $sep . $item;
            $sep = ', ';
        }
        return $str . ')';
    }

}