<?php

namespace Trismegiste\Prolog;

/**
 * Contract for an environment of Prolog Machine
 *
 * Needs improvement : some methods from WAMService could be
 * there here
 *
 * @author flo
 */
abstract class PrologContext
{

    /**
     * Run a query
     *
     * @param string $s the query
     * @return mixed
     */
    abstract public function runQuery(string $s);

    /**
     * Retract the last clause identified by its predicate
     */
    abstract public function retract($v);

    /**
     * Assert a simple clause
     */
    public function assertClause($s)
    {
        return $this->runQuery("assert($s).");
    }

    /**
     * Load a WAM code in context
     */
    public function loadWam($filename)
    {
        return $this->runQuery("load('$filename').");
    }

    /**
     * Load a Prolog program in context
     */
    public function loadProlog($filename)
    {
        return $this->runQuery("consult('$filename').");
    }

}
