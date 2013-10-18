<?php

namespace Trismegiste\Prolog;

/**
 * Warren's Abstract Machine  -  Implementation by Stefan Buettcher
 *                            -  Ported to PHP by Trismegiste
 *
 * developed:   December 2001 until February 2002
 * ported:      July 2012
 *
 * Program contains the WAM program management class Program. A Program
 * consists of an array of Statements (cf. Statement).
 *
 * Program class manages WAM programs, consisting of list (vector) of statements
 */
class Program
{
    const callWrite = -10;
    const callWriteLn = -11;
    const callNewLine = -12;
    const callConsult = -13;
    const callReconsult = -14;
    const callLoad = -15;
    const callAssert = -16;
    const callRetractOne = -17;
    const callRetractAll = -18;
    const callIsInteger = -19;
    const callIsAtom = -20;
    const callIsBound = -21;
    const callReadLn = -22;
    const callCall = -23;

    private $statements = array();
    public $labels = array();
    public $owner = null;  // WAM

    public function __construct(WAM $anOwner = null)
    {
        $this->owner = $anOwner;
    }

    // TODO Typing null ?
    public function addProgram(/* Program */ $p)
    {
        if ($p == null)
            return;
        $cnt = $p->getStatementCount();
        $canAdd = true;
        for ($i = 0; $i < $cnt; $i++) {
            $s = $p->getStatement($i);
            $label = $s->getLabel();
            if (strlen($label) > 0) {
                if (array_key_exists($label, $this->labels)) {
                    $canAdd = false;
                    $this->owner->writeLn("Error: Multiple occurrence of label \"$label\". Use reconsult.");
                } else {
                    $this->labels[$label] = count($this->statements);
                    $canAdd = true;
                }
            }
            if ($canAdd)
                $this->addStatement($s);
        }
    }

    public function addStatement(Statement $s)
    {
        $this->statements[] = $s;
    }

    public function addStatementAtPosition(Statement $s, $position)
    {
        $tmp = array_splice($this->statements, $position);
        $this->statements[] = $s;
        $this->statements = array_merge($this->statements, $tmp);
    }

    public function getStatementCount()
    {
        return count($this->statements);
    }

    public function getStatement($i)
    {
        return $this->statements[$i];
    }

    public function deleteFromLine($lineNumber)
    {
        $result = 1;
        if ($lineNumber >= 0) {
            $ending = count($this->statements);
            for ($k = $lineNumber + 1; $k < $ending; $k++) {
                if (strlen($this->statements[$k]->getLabel()) > 0) {
                    $ending = $k;
                    break;
                }
            }
            $result = $ending - $lineNumber;
            array_splice($this->statements, $lineNumber, $result);
            $this->updateLabels();
        }
        return $result;
    }

    public function deleteFrom($label)
    {
        return $this->deleteFromLine($this->getLabelIndex($label));
    }

    public function getLastClauseOf($procedureName)
    {
        $line = $this->getLabelIndex($procedureName);
        if ($line >= 0) {
            $finished = false;
            //Statement s;
            do {
                $s = $this->getStatement($line);
                if (($s->operator == Statement::opTryMeElse) || ($s->operator == Statement::opRetryMeElse))
                    $line = $s->jump;
                else
                    $finished = true;
            } while (!$finished);
        }
        return $line;
    }

    public function getLastClauseButOneOf($procedureName)
    {
        $result = -1;
        $line = $this->getLabelIndex($procedureName);
        if ($line >= 0) {
            $finished = false;
            //Statement s;
            do {
                $s = $this->getStatement($line);
                if (in_array($s->operator, array(Statement::opTryMeElse, Statement::opRetryMeElse))) {
                    $result = $line;
                    $line = $s->jump;
                }
                else
                    $finished = true;
            } while (!$finished);
        }
        return $result;
    }

    public function addClause($label, Program $code)
    {
        $line = $this->getLastClauseOf($label);
        if ($line >= 0) {  // there already exists such a label: add via try_me_else
            $s = $this->getStatement($line);
            $index = strpos($s->getLabel(), '~');
            try {
                //int i;
                if ($index > 0)
                    $i = 1 + substr($s->getLabel(), $index + 1);
                else
                    $i = 2;
                // update the just-compiled program
                $newLabel = $label . "~" . $i;
                $code->getStatement(0)->setLabel($newLabel);
                // update the previous clause: trust_me -> try_me_else
                $s->setFunction("try_me_else");
                $s->setArgAt($newLabel, 0);
                $s->arg1 = $newLabel;
                $s->setJump(count($this->statements));
                // update labels and program itself
                $this->addProgram($code);
            } catch (\Exception $e) {
                // TODO maybe do something ?
            }
        } else {  // first label of that kind: just add to code and update jumpings
            $this->addProgram($code);
            $this->updateLabels();
        }
    }

    public function getLabelIndex($label)
    {
        foreach ($this->statements as $i => $item)
            if ($label === $item->getLabel())
                return $i;
        return -1;
    }

    /**
     * updateLabels converts String label names in call, try_me_else and retry_me_else statements
     * to integer values. internal predicates (e.g. write, consult) are transformed to negative line numbers
     */
    public function updateLabels()
    {
        $this->labels = array();
        //String label;
        $cnt = count($this->statements);
        for ($i = 0; $i < $cnt; $i++) {
            $s = $this->statements[$i];
            $label = $s->getLabel();
            if (strlen($label) > 0)
                $this->labels[$label] = $i;
        }
        for ($i = 0; $i < $cnt; $i++) {
            $s = $this->statements[$i];
            if (in_array($s->getFunction(), array("call", "try_me_else", "retry_me_else"))) {
                $label = $s->getArgAt(0);
                $s->setJump(-1);
                if (array_key_exists($label, $this->labels)) // label is a user-defined predicate
                    $s->setJump($this->labels[$label]);
                else {  // label is undefined or a built-in predicate
                    if ($label === "atomic")
                        $s->setJump(self::callIsAtom);
                    else if ($label === "integer")
                        $s->setJump(self::callIsInteger);
                    else if ($label === "bound")
                        $s->setJump(self::callIsBound);
                    else if ($label === "write")
                        $s->setJump(self::callWrite);
                    else if ($label === "writeln")
                        $s->setJump(self::callWriteLn);
                    else if ($label === "call")
                        $s->setJump(self::callCall);
                    else if (in_array($label, array("nl", "newline")))
                        $s->setJump(self::callNewLine);
                    else if ($label === "consult")
                        $s->setJump(self::callConsult);
                    else if ($label === "reconsult")
                        $s->setJump(self::callReconsult);
                    else if ($label === "load")
                        $s->setJump(self::callLoad);
                    else if (in_array($label, array("assert", "assertz")))
                        $s->setJump(self::callAssert);
                    else if (in_array($label, array("retract", "retractone")))
                        $s->setJump(self::callRetractOne);
                    else if ($label === "retractall")
                        $s->setJump(self::callRetractAll);
                    else if ($label === "readln")
                        $s->setJump(self::callReadLn);
                } // end of (!labels.containsKey(label))
            }
        }
    }

    public function __toString()
    {
        $result = "";
        foreach ($this->statements as $i => $st) {
            $line = "(" . str_pad($i, 4, '0', STR_PAD_LEFT) . ")  ";
            $result .= $line . $st;
            if ($i < (count($this->statements) - 1))
                $result .= "\n";
        }
        return $result;
    }

}
