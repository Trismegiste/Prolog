<?php

namespace Trismegiste\Prolog\Inner;

use Trismegiste\Prolog\PrologContext;
use Trismegiste\Prolog\WAM;

/**
 * Trail implements the WAM's trail (undo-list for bindings performed)
 */
class Trail
{

    private $contents;
    private $machine = null;

    public function __construct(PrologContext $ctx)
    {
        $this->contents = array();
        $this->machine = $ctx;
    }

    public function getLength()
    {
        return count($this->contents);
    }

    public function setLength($length)
    {
        $cnt = count($this->contents);
        if ($length > $cnt)
            $this->contents = array_pad($this->contents, $length, null);
        elseif ($length < $cnt)
            array_splice($this->contents, $length - $cnt);
    }

    public function addEntry(Variable $v)
    {
        $this->contents[] = $v;
    }

    public function getEntry($index)
    {
        return $this->contents[$index];
    }

    public function undo($index)
    {
        $v = $this->contents[$index];
        if ($v !== null) {
            if ($v->tag == WAM::ASSERT)  // @todo Illogical: I use both Interface PrologContext and subclass WAM
                $this->machine->retract($v->value);
            $v->tag = WAM::REF;
            $v->reference = $v;
        }
    }

}
