<?php

namespace Trismegiste\Prolog\Inner;

/**
 * class ChoicePoint implements the choice point concept, as presented by Ait-Kaci
 */
class ChoicePoint
{

    public $arguments;             // the Ai variables
    public /* Environment */ $lastEnviron;      // current environment when creating the choicepoint
    public $returnAddress;            // current continuation pointer (cp)
    public /* ChoicePoint */ $lastCP;           // last ChoicePoint on stack
    public /* ChoicePoint */ $cutPoint;         // copy of B0
    public $nextClause;               // current instruction pointer + 1
    public $trailPointer;             // current trail pointer

    // constructor gets A (argument variables vector), trailPtr (trail pointer) and
    // anAddress (current return address / continuation pointer)

    public function __construct($a, $trailPtr, $anAddress)
    {
        $this->arguments = array();
        $this->lastEnviron = null;
        $this->lastCP = null;
        $this->returnAddress = $anAddress;
        foreach ($a as $item)
            $this->arguments[] = new Variable($item);
        $this->trailPointer = $trailPtr;
    }

}
