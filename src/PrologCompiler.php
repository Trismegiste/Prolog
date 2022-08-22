<?php

namespace Trismegiste\Prolog;

/**
 * Warren's Abstract Machine  -  Implementation by Stefan Buettcher
 *                            -  Ported to PHP by Florent Genette
 *
 * developed:   December 2001 until February 2002
 * ported:      July 2012
 *
 * PrologCompiler.java contains the class PrologCompiler, which transforms
 * a Prolog program (given as a string or by its filename) into an equivalent
 * WAM program.
 */
class PrologCompiler extends Compiler
{

    public function __construct(WAM $anOwner)
    {
        $this->owner = $anOwner;
        $this->errorString = "";
        $this->varPrefix = "Y";
    }

    public function compile($programCode)
    {
        $ms = microtime(true);
        $this->owner->debug("Program Code:", 2);
        $this->owner->debug($programCode, 2);
        $programList = $this->stringToList($programCode);
        $this->owner->debug("Program List:", 2);
        $this->owner->debug("String to List: " . (microtime(true) - $ms) . " ms.", -1);
        $this->owner->debug($programList, 2);
        $struc = new CompilerStructure();

        $ms = microtime(true);
        if (($this->program($programList, $struc)) && (count($programList) == 0)) {
            $this->owner->debug("List to Structure: " . (microtime(true) - $ms) . " ms.", -1);
            $this->updateNames($struc);
            $this->owner->debug($struc->__toString(), 2);
            //print_r($struc);
            $ms = microtime(true);
            $p = $this->structureToCode($struc);
            $this->owner->debug("Structure to Code: " . (microtime(true) - $ms) . " ms.", -1);
            return $p;
        } else {
            if (strlen($this->errorString) > 0)
                $this->owner->writeLn($this->errorString);
            return null;
        }
    }

    /**
     * compileSimpleClause can be used in order to implement assert(...) operations
     */
    public function compileSimpleClause($programCode)
    {
        $programList = $this->stringToList($programCode);
        $struc = new CompilerStructure();
        if (($this->clause($programList, $struc)) && (count($programList) == 0)) {
            $program = new CompilerStructure();
            $program->type = CompilerStructure::PROGRAM;
            $program->head = $struc;
            $this->updateNames($program);
            return $this->structureToCode($program);
        }
        else
            return null;
    }

    /**
     * Read and compile a prolog file
     *
     * @param type $fileName
     * @return null
     */
    public function compileFile($fileName)
    {
        $code = "";
        try {
            $ms = microtime(true);
            $atAll = $ms;
            // reading file in java is much different from php
            $r = file($fileName, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($r as $dummy) {
                // if the first char of this line is '%' then it is a comment and we skip it
                if ($dummy[0] == "%")
                    continue;
                $code .= " " . $dummy;
            }
            $this->owner->debug("File Operations: " . (microtime(true) - $ms) . " ms.", -1);
            $p = $this->compile($code);
            return $p;
        } catch (\Exception $io) {
            $this->owner->writeLn("File \" $fileName \" could not be opened.");
            return null;
        }
    }

    private function getProcedureCount($name, array $list)
    {
        if (array_key_exists($name, $list))
            return $list[$name];
        else
            return 0;
    }

    private function setProcedureCount($name, $count, array &$list)
    {
        $list[$name] = $count;
    }

    private function updateNames(CompilerStructure $struc)
    {
        $procedureCount = array();
        //CompilerStructure s, proc;
        $proc = $s = null;
        if (($struc->type == CompilerStructure::PROGRAM) && ($struc->head != null)) {
            $s = $struc;
            do {
                $proc = $s->head->head->head;
                $cnt = $this->getProcedureCount($proc->value, $procedureCount);
                $this->setProcedureCount($proc->value, ++$cnt, $procedureCount);
                $proc->value = $proc->value . '~' . $cnt;
                $s = $s->tail;
            } while ($s != null);
        }
        if (($struc->type == CompilerStructure::PROGRAM) && ($struc->head != null)) {
            $s = $struc;
            do {
                $proc = $s->head->head->head;
                $pv = $proc->value;
                if (strpos($pv, '~') > 0)
                    $pv = substr($pv, 0, strpos($pv, '~'));
                $proc->value .= "/" . $this->getProcedureCount($pv, $procedureCount);
                $s = $s->tail;
            } while ($s != null);
        }
    }

}
