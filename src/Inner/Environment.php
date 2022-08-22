<?php

namespace Trismegiste\Prolog\Inner;

/**
 * class Environment for storing local variables that must not be overridden
 */
class Environment
{

    public $variables;
    public /* Environment */ $lastEnviron;
    public $returnAddress;

    // constructor gets the current return address (continuation pointer) and a pointer to the previous environment on stack
    public function __construct($anAddress, $anEnv)
    {
        $this->lastEnviron = $anEnv;
        $this->returnAddress = $anAddress;
        $this->variables = array();
    }

}