<?php

namespace Trismegiste\Prolog;

use Trismegiste\Prolog\Inner\KeyValue;

/**
 * Warren's Abstract Machine  -  Implementation by Stefan Buettcher
 *                            -  Ported to PHP by Trismegiste
 *
 * developed:   December 2001 until February 2002
 * ported:      July 2012
 *
 * Compiler contains the base class Compiler, which both QueryCompiler and
 * PrologCompiler have been derived from. Additionally, it contains a class
 * KeyValue, which is used for implementing mappings from Prolog variable names
 * ("X", "A13", "Name", ...) to WAM variable names ("Y1", "Y2", ...).
 */
abstract class Compiler
{

    protected $owner;
    protected $errorString;
    protected $varPrefix;
    protected $substitutionList = array();  // beware : this key/value set could have the same key multiple times
    private $lastVar;
    private $bodyCalls;

    public function isPredicate($s)
    {
        return ($this->isConstant($s) && (!$this->isNumber($s)));
    }

// end of Compiler.isPredicate()

    public function isVariable($s)
    {
        if (strcmp($s, "_") == 0)
            return true;
        $c = $s[0];
        if (($c >= 'A') && ($c <= 'Z')) {
            $check = preg_match('#^[A-Z][a-zA-Z0-9_]*$#', $s);
            if (!$check)
                $this->errorString = "\"$s\" is no valid variable.";

            return $check;
        }
        else
            return false;
    }

// end of Compiler.isVariable()

    public function isConstant($s)
    {
        $c = $s[0];
        if (($c >= 'a') && ($c <= 'z')) {
            $check = preg_match('#^[a-zA-Z0-9_]*$#', $s);
            if (!$check)
                $this->errorString = "\"$s\" is no valid constant or predicate.";

            return $check;
        }
        if (($c == '\'') && ($s[strlen($s) - 1] == '\''))
            return true;
        if (in_array($s, array(";", ".", "+", "#")))
            return true;
        return $this->isNumber($s);
    }

// end of Compiler.isConstant(String)

    public function isNumber($s)
    {
        return is_numeric($s);
    }

// end of Compiler.isNumber(String)

    public function predicate(array &$prog, CompilerStructure $struc)
    {
        if (count($prog) == 0)
            return false;
        $q0 = (string) $prog[0];
        if ($this->isPredicate($q0)) {
            $struc->type = CompilerStructure::PREDICATE;
            $struc->value = $q0;
            array_shift($prog);
            return true;
        }
        return false;
    }

// end of Compiler.predicate(Vector, CompilerStructure)

    public function constant(array &$prog, CompilerStructure $struc)
    {
        if (count($prog) == 0)
            return false;
        $q0 = (string) $prog[0];
        if ($this->isConstant($q0)) {
            $struc->type = CompilerStructure::CONSTANT;
            if ($q0[0] == '\'')
                $struc->value = substr($q0, 1, strlen($q0) - 2);
            else
                $struc->value = $q0;
            array_shift($prog);
            return true;
        }
        $oldProg = $prog;
        if (($this->token($prog, "[")) && ($this->token($prog, "]"))) {
            $struc->type = CompilerStructure::CONSTANT;
            $struc->value = "[]";
            return true;
        }
        $prog = $oldProg;
        return false;
    }

// end of Compiler.constant(Vector, CompilerStructure)

    public function variable(array &$prog, CompilerStructure $struc)
    {
        if (count($prog) == 0)
            return false;
        $q0 = (string) $prog[0];
        if ($this->isVariable($q0)) {
            $struc->type = CompilerStructure::VARIABLE;
            $struc->value = $q0;
            array_shift($prog);
            return true;
        }
        return false;
    }

// end of Compiler.variable(Vector, CompilerStructure)

    public function structure(array &$prog, CompilerStructure $struc)
    {
        if (count($prog) == 0)
            return false;
        $oldProg = $prog;
        $struc->head = new CompilerStructure();
        $struc->tail = new CompilerStructure();
        $struc->type = CompilerStructure::STRUCTURE;
        if (($this->predicate($prog, $struc->head)) && ($this->token($prog, "("))
                && ($this->listx($prog, $struc->tail)) && ($this->token($prog, ")"))) {
            $struc->head->type = CompilerStructure::CONSTANT;
            return true;
        }
        $prog = $oldProg;
        if (($this->variable($prog, $struc->head)) && ($this->token($prog, "("))
                && ($this->listx($prog, $struc->tail)) && ($this->token($prog, ")")))
            return true;
        $prog = $oldProg;
        return false;
    }

// end of Compiler.structure(Vector, CompilerStructure)

    public function element(array &$prog, CompilerStructure $struc)
    {
        if (count($prog) == 0)
            return false;
        $oldProg = $prog;
        if ($this->structure($prog, $struc))
            return true;
        if ($this->variable($prog, $struc))
            return true;
        if ($this->constant($prog, $struc))
            return true;
        if (($this->token($prog, "[")) && ($this->listx($prog, $struc)) && ($this->token($prog, "]")))
            return true;
        $prog = $oldProg;
        return false;
    }

// end of Compiler.element(Vector, CompilerStructure)

    public function isNextToken(array &$prog, $tok)
    {
        if (count($prog) == 0)
            return false;
        if ($tok == $prog[0])
            return true;
        return false;
    }

// end of Compiler.isNextToken(Vector, String)

    public function token(array &$prog, $tok)
    {
        if (count($prog) == 0)
            return false;
        if ($tok == $prog[0]) {
            array_shift($prog);
            return true;
        }
        return false;
    }

// end of Compiler.token(Vector, String)

    public function atom(array &$prog, CompilerStructure $struc)
    {
        if ($this->constant($prog, $struc))
            return true;
        if ($this->variable($prog, $struc))
            return true;
        return false;
    }

// end of Compiler.atom(Vector, CompilerStructure)

    public function expression(array &$prog, CompilerStructure $struc)
    {
        $oldProg = $prog;
        $struc->type = CompilerStructure::EXPRESSION;
        $struc->head = new CompilerStructure();
        $struc->tail = new CompilerStructure();
        $cnt = 1;
        $tok = "";
        do {
            switch ($cnt) {
                case 1: $tok = "+";
                    break;
                case 2: $tok = "-";
                    break;
                case 3: $tok = "*";
                    break;
                case 4: $tok = "/";
                    break;
                case 5: $tok = "%";
                    break;
            }
            if (($this->atom($prog, $struc->head)) && ($this->token($prog, $tok)) && ($this->atom($prog, $struc->tail))) {
                $struc->value = $tok;
                return true;
            }
            $prog = $oldProg;
        } while (++$cnt <= 4);
        $this->errorString = "Invalid expression on right side of assignment.";
        return false;
    }

// end of Compiler.expression(Vector, CompilerStructure)

    public function condition(array &$prog, CompilerStructure $struc)
    {
        if ($prog == null)
            return false;
        $oldProg = $prog;
        $struc->head = new CompilerStructure();
        $struc->tail = new CompilerStructure();
        // first type of a condition is a comparison
        if ($this->atom($prog, $struc->head)) {
            $struc->type = CompilerStructure::COMPARISON;
            if ($this->isNextToken($prog, ">")) {
                $this->token($prog, ">");
                if ($this->isNextToken($prog, "=")) {
                    if (($this->token($prog, "=")) && ($this->atom($prog, $struc->tail))) {
                        $struc->value = ">=";
                        return true;
                    }
                } else if ($this->atom($prog, $struc->tail)) {
                    $struc->value = ">";
                    return true;
                }
            } else if ($this->isNextToken($prog, "<")) {
                $this->token($prog, "<");
                if ($this->isNextToken($prog, "=")) {
                    if (($this->token($prog, "=")) && ($this->atom($prog, $struc->tail))) {
                        $struc->value = "<=";
                        return true;
                    }
                } else if ($this->atom($prog, $struc->tail)) {
                    $struc->value = "<";
                    return true;
                }
            } else if ($this->isNextToken($prog, "!")) {
                $this->token($prog, "!");
                if (($this->token($prog, "=")) && ($this->atom($prog, $struc->tail))) {
                    $struc->value = "!=";
                    return true;
                }
            } else if ($this->isNextToken($prog, "\\")) {
                $this->token($prog, "\\");
                if (($this->token($prog, "=")) && ($this->atom($prog, $struc->tail))) {
                    $struc->value = "!=";
                    return true;
                }
            }
        } // end of comparison checks

        $prog = $oldProg;
        if (($this->element($prog, $struc->head)) && ($this->token($prog, "=")) && ($this->element($prog, $struc->tail))) {
            $struc->type = CompilerStructure::UNIFICATION;
            return true;
        }

        $prog = $oldProg;
        if (($this->variable($prog, $struc->head)) && ($this->token($prog, "is")) && ($this->expression($prog, $struc->tail))) {
            $struc->type = CompilerStructure::ASSIGNMENT;
            return true;
        }

        $prog = $oldProg;
        if ($this->predicate($prog, $struc->head)) {
            $struc->type = CompilerStructure::CALL;
            if ($this->isNextToken($prog, "(")) {
                $this->token($prog, "(");
                if (($this->listx($prog, $struc->tail)) && ($this->token($prog, ")")))
                    return true;
            }
            else {
                $struc->tail = null;
                return true;
            }
        }

        $prog = $oldProg;
        if ($this->isNextToken($prog, "!")) {
            $this->token($prog, "!");
            $struc->type = CompilerStructure::CUT;
            return true;
        }
        return false;
    }

// end of Compiler.condition(Vector, CompilerStructure)

    public function body(array &$prog, CompilerStructure $struc)
    {
        $oldProg = $prog;
        $struc->type = CompilerStructure::BODY;
        $struc->head = new CompilerStructure();
        $struc->tail = new CompilerStructure();
        if ($this->condition($prog, $struc->head)) {
            if ($this->isNextToken($prog, ",")) {
                $this->token($prog, ",");
                if ($this->body($prog, $struc->tail))
                    return true;
            }
            else {
                $struc->tail = null;
                return true;
            }
        }

        $prog = $oldProg;
        return false;
    }

// end of Compiler.body(Vector, CompilerStructure)

    public function clause(array &$prog, CompilerStructure $struc)
    {
        $oldProg = $prog;
        $struc->type = CompilerStructure::CLAUSE;
        $struc->head = new CompilerStructure();
        $struc->tail = new CompilerStructure();
        if ($this->head($prog, $struc->head)) {
            if ($this->isNextToken($prog, ":")) {
                $this->token($prog, ":");
                if (($this->token($prog, "-")) && ($this->body($prog, $struc->tail)) && ($this->token($prog, ".")))
                    return true;
            }
            else if ($this->isNextToken($prog, ".")) {
                $this->token($prog, ".");
                $struc->tail = null;
                return true;
            }
            else
                $this->errorString = "Missing \".\" at end of clause.";
        }

        $prog = $oldProg;
        return false;
    }

// end of Compiler.clause(Vector, CompilerStructure)

    public function program(array &$prog, CompilerStructure $struc)
    {
        $oldProg = $prog;
        $struc->type = CompilerStructure::PROGRAM;
        $struc->head = new CompilerStructure();
        $struc->tail = new CompilerStructure();
        if ($this->clause($prog, $struc->head)) {
            if ($this->program($prog, $struc->tail))
                return true;
            $struc->tail = null;
            return true;
        }
        return false;
    }

// end of Compiler.program(Vector, CompilerStructure)

    public function head(array &$prog, CompilerStructure $struc)
    {
        $oldProg = $prog;
        $struc->type = CompilerStructure::HEAD;
        $struc->head = new CompilerStructure();
        $struc->tail = new CompilerStructure();
        if ($this->predicate($prog, $struc->head)) {
            if ($this->isNextToken($prog, "(")) {
                $this->token($prog, "(");
                if (($this->listx($prog, $struc->tail)) && ($this->token($prog, ")")))
                    return true;
            }
            else {
                $struc->tail = null;
                return true;
            }
        }
        return false;
    }

// end of Compiler.head(Vector, CompilerStructure)

    public function listx(array &$prog, CompilerStructure $struc)
    {
        $oldProg = $prog;
        $struc->type = CompilerStructure::LISTX;
        $struc->head = new CompilerStructure();
        $struc->tail = new CompilerStructure();
        if ($this->element($prog, $struc->head)) {
            if ($this->isNextToken($prog, "|")) {
                $this->token($prog, "|");
                if ($this->element($prog, $struc->tail))
                    return true;
            }
            else if ($this->isNextToken($prog, ",")) {
                $this->token($prog, ",");
                if ($this->listx($prog, $struc->tail))
                    return true;
            }
            else {
                $struc->tail = null;
                return true;
            }
        }

        $prog = $oldProg;
        return false;
    }

// end of Compiler.list(Vector, CompilerStructure)

    public function stringToList($text)
    {
        //int i;
        $result = array();
        $dummy = "";
        for ($i = 0; $i < strlen($text); $i++) {
            $pos = $text[$i];
            if ($pos == '\'') {
                if (strlen($dummy) > 0)
                    return null;
                $dummy = "'";
                do {
                    $i++;
                    $dummy .= $text[$i];
                    if ($text[$i] == '\'')
                        break;
                } while ($i < strlen($text) - 1);
            }
            else if ($pos != ' ') {
                if (in_array($pos, array('(', ')', '[', ']', ',', '.', '|', '=', '<',
                            '>', '%', '\\', '+', '-', '*', '/'))) {
                    if (strlen($dummy) > 0)
                        $result[] = $dummy;
                    $dummy = "";
                    $result[] = $dummy . $pos;
                }
                else
                    $dummy .= $pos;
            }
            else {
                if (strlen($dummy) > 0)
                    $result[] = $dummy;
                $dummy = "";
            }
        }
        if (strlen($dummy) > 0)
            $result[] = $dummy;
        return $result;
    }

// end of Compiler.stringToList(String)

    public function substituteVariable($variable)
    {
        if ((strlen($variable) > 0) && (strcmp($variable, "_") != 0))
            foreach ($this->substitutionList as $item)
                if (strcmp($variable, $item->key) == 0) {
                    $this->lastVar = $item->stringValue;
                    return $this->lastVar;
                }
        $newVar = $this->varPrefix . count($this->substitutionList);
        $this->substitutionList[] = new KeyValue($variable, $newVar);
        $this->lastVar = $newVar;
        return $newVar;
    }

// end of Compiler.substituteVariable(String)

    public function firstOccurrence($variable)
    {
        if ((strlen($variable) > 0) && (strcmp($variable, "_") != 0))
            foreach ($this->substitutionList as $item)
                if ($variable === $item->key)
                    return false;
        return true;
    }

// end of Compiler.firstOccurrence(String)
    // structureToCode takes a CompilerStructure, generated by the parser, and constructs
    // a WAM program from it, recursively
    // TODO Typing null ?
    public function structureToCode(/* CompilerStructure */ $struc)
    {
        //print_r($struc);
        if ($struc == null)
            return null;
        $result = new Program($this->owner);
        if ($struc->type == CompilerStructure::PROGRAM) {
            if ($struc->head == null)
                return null;
            $result->addProgram($this->structureToCode($struc->head));
            $result->addProgram($this->structureToCode($struc->tail));
        } // end of case CompilerStructure::PROGRAM
        else if ($struc->type == CompilerStructure::CALL) {
            $this->bodyCalls++;
            if ($struc->tail != null) {
                $s = $struc->tail;
                $argCount = 0;
                do {
                    if ($s->head->type == CompilerStructure::CONSTANT)
                        $result->addStatement(new Statement("", "put_constant", $s->head->value, "A" . $argCount));
                    else if ($s->head->type == CompilerStructure::VARIABLE) {
                        if (($this->varPrefix === "Q") && ($this->firstOccurrence($s->head->value)))
                            $result->addStatement(new Statement("", "create_variable", $this->substituteVariable($s->head->value), $s->head->value));
                        $result->addStatement(new Statement("", "put_value", $this->substituteVariable($s->head->value), "A" . $argCount));
                    }
                    else {
                        $result->addProgram($this->structureToCode($s->head));
                        $result->addStatement(new Statement("", "put_value", $this->lastVar, "A" . $argCount));
                    }
                    $argCount++;
                    $s = $s->tail;
                } while ($s != null);
            }
            $result->addStatement(new Statement("", "call", $struc->head->value));
        } // end of case CompilerStructure::CALL
        else if ($struc->type == CompilerStructure::UNIFICATION) {
            $result->addProgram($this->structureToCode($struc->head));
            $headVar = $this->lastVar;
            $result->addProgram($this->structureToCode($struc->tail));
            $tailVar = $this->lastVar;
            $result->addStatement(new Statement("", "unify_variable", $headVar, $tailVar));
        } // end of case CompilerStructure::UNIFICATION
        else if ($struc->type == CompilerStructure::HEAD) {
            $name = $struc->head->value;
            $j1 = strpos($name, '~');
            $j2 = strpos($name, '/');
            $atAll = (int) substr($name, $j2 + 1);
            $name = substr($name, 0, $j2);
            $countr = (int) substr($name, $j1 + 1);
            $name = substr($name, 0, $j1);
            if ($countr == 1)
                $struc->head->value = $name;
            else
                $struc->head->value = $name . '~' . $countr;
            if ($countr < $atAll) {
                if ($countr > 1)
                    $result->addStatement(new Statement($struc->head->value, "retry_me_else", $name . '~' . ($countr + 1)));
                else
                    $result->addStatement(new Statement($struc->head->value, "try_me_else", $name . '~' . ($countr + 1)));
            }
            else
                $result->addStatement(new Statement($struc->head->value, "trust_me", ""));
            if ($struc->tail != null) {
                $s = $struc->tail;
                $argCount = 0;
                do {
                    if ($s->head->type == CompilerStructure::CONSTANT)
                        $result->addStatement(new Statement("", "get_constant", $s->head->value, "A" . $argCount));
                    else if ($s->head->type == CompilerStructure::VARIABLE) {
                        if ($this->firstOccurrence($s->head->value))
                            $result->addStatement(new Statement("", "get_variable",
                                            $this->substituteVariable($s->head->value), "A" . $argCount));
                        else
                            $result->addStatement(new Statement("", "get_value",
                                            $this->substituteVariable($s->head->value), "A" . $argCount));
                    }
                    else {
                        $subst = $this->substituteVariable("");
                        $result->addStatement(new Statement("", "get_variable", $subst, "A" . $argCount));
                        $result->addProgram($this->structureToCode($s->head));
                        $result->addStatement(new Statement("", "unify_variable", $subst, $this->lastVar));
                    }
                    $argCount++;
                    $s = $s->tail;
                } while ($s != null);
            }
        } // end of case CompilerStructure::HEAD
        else if ($struc->type == CompilerStructure::CONSTANT)
            $result->addStatement(new Statement("", "put_constant", $struc->value, $this->substituteVariable("")));
        else if ($struc->type == CompilerStructure::VARIABLE) {
            if (($this->varPrefix === "Q") && ($this->firstOccurrence($struc->value)))
                $result->addStatement(new Statement("", "create_variable", $this->substituteVariable($struc->value), $struc->value));
            $this->substituteVariable($struc->value);
        }
        else if ($struc->type == CompilerStructure::LISTX) {
            if ($struc->head != null) {
                $p = $this->structureToCode($struc->head);  // first of all, compile the list's head (i.e. its first element)
                if ($p == null)
                    return null;
                $result->addProgram($p);
                $headVar = '';
                $tailVar = '';
                if ($struc->head->type == CompilerStructure::VARIABLE)
                    $headVar = $this->substituteVariable($struc->head->value);
                else
                    $headVar = $this->lastVar;
                if ($struc->tail == null) {  // end of list: put NIL sign
                    $tailVar = $this->substituteVariable("");
                    $result->addStatement(new Statement("", "put_constant", "[]", $tailVar));
                } else {  // otherwise compile the tail
                    $p = $this->structureToCode($struc->tail);
                    if ($p == null)
                        return null;
                    $result->addProgram($p);
                    $tailVar = $this->lastVar;
                }  // and finally, unify the list with head and tail
                $result->addStatement(new Statement("", "unify_list", $this->substituteVariable(""), $headVar, $tailVar));
                return $result;
            }
            else // struc.head == null means: this is no real list, but a NIL
                $result->addStatement(new Statement("", "put_constant", "[]", $this->substituteVariable("")));
        } // end of case CompilerStructure::LISTX
        else if ($struc->type == CompilerStructure::STRUCTURE) {
            $result->addProgram($this->structureToCode($struc->head));
            $headVar = $this->lastVar;
            $result->addProgram($this->structureToCode($struc->tail));
            $tailVar = $this->lastVar;
            $result->addStatement(new Statement("", "unify_struc", $this->substituteVariable(""), $headVar, $tailVar));
            return $result;
        } // end of case CompilerStructure::STRUCTURE
        else if ($struc->type == CompilerStructure::CLAUSE) {
            $this->substitutionList = array();
            $this->bodyCalls = 0;
            $result->addProgram($this->structureToCode($struc->head));
            $result->addProgram($this->structureToCode($struc->tail));
            if ((count($this->substitutionList) > 0) || ($this->bodyCalls > 0)) {
                $result->addStatementAtPosition(new Statement("", "allocate", ""), 1);
                $result->addStatement(new Statement("", "deallocate", ""));
            }
            $result->addStatement(new Statement("", "proceed", ""));
        } // end of case CompilerStructure::CLAUSE
        else if ($struc->type == CompilerStructure::BODY) {
            $s = $struc;
            do {
                if ($s->head->type == CompilerStructure::CUT) {
                    $y = $this->substituteVariable("");
                    $result->addStatementAtPosition(new Statement("", "get_level", $y), 0);
                    $result->addStatement(new Statement("", "cut", $y));
                }
                else
                    $result->addProgram($this->structureToCode($s->head));
                $s = $s->tail;
            } while ($s != null);
        } // end of case CompilerStructure::BODY
        else if ($struc->type == CompilerStructure::QUERY) {
            if ($struc->head == null)
                return null;
            $result->addProgram($this->structureToCode($struc->head));
            if ($struc->tail != null)
                $result->addProgram($this->structureToCode($struc->tail));
            $result->addStatement(new Statement("", "halt", ""));
        } // end of case CompilerStructure::BODY
        else if ($struc->type == CompilerStructure::COMPARISON) {
            $result->addProgram($this->structureToCode($struc->head));
            $headVar = $this->lastVar;
            $result->addProgram($this->structureToCode($struc->tail));
            $tailVar = $this->lastVar;
            if ($struc->value === ">")
                $result->addStatement(new Statement("", "bigger", $headVar, $tailVar));
            else if ($struc->value === "<")
                $result->addStatement(new Statement("", "smaller", $headVar, $tailVar));
            else if ($struc->value === ">=")
                $result->addStatement(new Statement("", "biggereq", $headVar, $tailVar));
            else if ($struc->value === "<=")
                $result->addStatement(new Statement("", "smallereq", $headVar, $tailVar));
            else if ($struc->value === "!=")
                $result->addStatement(new Statement("", "unequal", $headVar, $tailVar));
        } // end of case CompilerStructure::COMPARISON
        else if ($struc->type == CompilerStructure::ASSIGNMENT) {
            $result->addProgram($this->structureToCode($struc->tail->head));
            $headVar = $this->lastVar;
            $result->addProgram($this->structureToCode($struc->tail->tail));
            $tailVar = $this->lastVar;
            $result->addProgram($this->structureToCode($struc->head));
            $result->addStatement(new Statement("", "is", $this->lastVar, $struc->tail->value, $headVar . " " . $tailVar));
        } // end of case CompilerStructure::COMPARISON
        return $result;
    }

// end of Compiler.structureToCode(CompilerStructure)
}

// end of class Compiler

