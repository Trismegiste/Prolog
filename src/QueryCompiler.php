<?php

namespace Trismegiste\Prolog;

/**
 * Warren's Abstract Machine  -  Implementation by Stefan Buettcher
 *                            -  Ported to PHP by Trismegiste
 *
 * developed:   December 2001 until February 2002
 * ported:      July 2012
 *
 * QueryCompiler.java contains the QueryCompiler class, a child-class of
 * the Compiler class. QueryCompiler compiles user-written queries into WAM
 * code for execution.
 */
class QueryCompiler extends Compiler
{

    public function __construct(WAM $anOwner)
    {
        $this->owner = $anOwner;
        $this->errorString = "";
        $this->varPrefix = "Q";
    }

    private function query(array &$prog, CompilerStructure $struc)
    {
        $oldProg = $prog;
        $struc->type = CompilerStructure::QUERY;
        $struc->head = new CompilerStructure();
        $struc->tail = new CompilerStructure();
        if (($this->body($prog, $struc->tail)) && ($this->token($prog, "."))) {
            $struc->head->type = CompilerStructure::HEAD;
            $struc->head->tail = null;
            $struc->head->head = new CompilerStructure();
            $struc->head->head->type = CompilerStructure::PREDICATE;
            $struc->head->head->value = "query$~1/1";
            $struc->head->tail = null;
            return true;
        }
        $prog = $oldProg;
        return false;
    }

    public function compile($aQuery)
    {
        $queryList = $this->stringToList($aQuery);
        $struc = new CompilerStructure();
        $this->errorString = "";
        $this->owner->debug($queryList, 2);
        if ($this->query($queryList, $struc)) {
            if (count($queryList) > 0) {
                if (stlen($this->errorString) > 0)
                    $this->owner->writeLn($this->errorString);
                return null;
            }
            $this->owner->debug("Structure: " . $struc->__toString(), 2);
            $this->substitutionList = array();
            return $this->structureToCode($struc);
        }
        if (strlen($this->errorString) > 0)
            $this->owner->writeLn($this->errorString);
        return null;
    }

}
