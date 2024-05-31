<?php

namespace Trismegiste\Prolog;

use Trismegiste\Prolog\Inner\ChoicePoint;
use Trismegiste\Prolog\Inner\Environment;
use Trismegiste\Prolog\Inner\KeyValue;
use Trismegiste\Prolog\Inner\Trail;
use Trismegiste\Prolog\Inner\Variable;

/**
 * Warren's Abstract Machine  -  Implementation by Stefan Buettcher
 * developed:   December 2001 until February 2002
 *
 * Translated from Java to PHP 5.3 by Florent Genette (Trismegiste)
 * http://github.com/Trismegiste/Wam
 * June to July 2012 (yes, 10 years after the original version !)
 *
 * WAM.php contains an abstract WAM without input or output
 * WAM code operations are described in Ait Kaci:
 * Warren's Abstract Machine -- A Tutorial Reconstruction
 */
abstract class WAM extends PrologContext
{

    const UNB = 0;  // variable-related constants:
    const REF = 1;  // tag == REF means this variable is a reference
    const CON = 2;  // this one has been bound to an immediate constant
    const LIS = 3;  // is a list
    const STR = 4;  // is a structure
    const ASSERT = 9;  // this variable is no real variable but only used for trailing assert operations
    const opAllocate = 1;   // Statement constants, see there
    const opBigger = 2;
    const opCall = 3;
    const opCreateVariable = 4;
    const opCut = 5;
    const opDeallocate = 6;
    const opGetConstant = 7;
    const opGetValue = 8;
    const opGetVariable = 9;
    const opHalt = 10;
    const opIs = 11;
    const opGetLevel = 12;
    const opNoOp = 13;
    const opProceed = 14;
    const opPutConstant = 15;
    const opPutValue = 16;
    const opPutVariable = 17;
    const opRetryMeElse = 18;
    const opSmaller = 19;
    const opTrustMe = 20;
    const opTryMeElse = 21;
    const opUnifyList = 22;
    const opUnifyStruc = 23;
    const opUnequal = 24;
    const opUnifyVariable = 25;
    const opBiggerEq = 27;
    const opSmallerEq = 28;
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

    // internal parameters, accessible by using the "set" command
    public $debugOn = 0;   // display debug information?
    protected $benchmarkOn = 0;   // show benchmark information?
    protected $maxOpCount = 50000000;  // artificial stack overflow limit
    public $opCount, $backtrackCount;
    protected Program $p;         // the program(s) loaded into memory
    protected Trail $trail;       // undo-list (WAM trail)
    protected $failed;    // set to true upon an unsuccessful binding operation
    protected array $displayQValue = []; //new boolean[100];   // which Query-Variables do have to displayed upon success?
    protected int $displayQCount = 0;     // how many of them?
    // the WAM's register set
    protected array $queryVariables = []; // query variables, to be accessed by Q1, Q2, and so on
    protected int $programCounter = 0; // program counter
    protected int $continuationPointer = 0; // continuation pointer
    protected $choicePoint = null; // last choicepoint on stack
    protected $cutPoint = null; // current choicepoint for cut instruction
    protected $env = null; // last environment on stack
    protected array $arguments = [];      // argument registers

    /**
     * creates a new WAM with program data initialized to aProgram
     *
     * @param Program $aProgram
     */
    public function __construct(Program $aProgram)
    {
        $aProgram->owner = $this;
        $this->p = $aProgram;
        $this->reset();
    }

    /**
     * resets sets all WAM parameters to their initial values
     */
    protected function reset(): void
    {
        $this->arguments = array();  // no argument registers so far
        $this->arguments[] = new Variable();
        $this->env = new Environment(999999999, null);  // empty environment
        $this->continuationPointer = -1;  // no continuation point
        $this->trail = new Trail($this);   // for undoing assert (see below)
        $this->queryVariables = array();
        $this->displayQCount = 0;
        $this->displayQValue = array_fill(0, 100, false);
        $this->choicePoint = null;
        $this->cutPoint = null;
    }

    /**
     * reads a String line from standard input
     */
    abstract protected function readLn();

    /**
     * displays a string
     */
    abstract public function write(string $s): void;

    /**
     * displays a string followed by CRLF
     */
    abstract public function writeLn(string $s): void;

    /**
     * displays a debug information line
     *
     * @param string $s
     * @param int $debugLevel
     */
    public function debug($s, $debugLevel): void
    {
        if ($debugLevel < 0) {
            if ($this->benchmarkOn > 0) {
                $this->writeLn($s);
            }
        } else if ($this->debugOn >= $debugLevel) {
            $this->writeLn($s);
        }
    }

    /**
     * formats an integer to a string
     *
     * @param integer $i
     * @return string
     */
    protected function int2FormatStr($i): string
    {
        return str_pad($i, 4, '0', STR_PAD_LEFT);
    }

    /**
     * displays the values of all internal parameters that can be modyfied
     * using the "set" command
     */
    protected function displayInternalVariables(): void
    {
        $this->getInternalVariable("autostop");
        $this->getInternalVariable("benchmark");
        $this->getInternalVariable("debug");
    }

    // sets the internal parameter specified by variable to a new value
    protected function setInternalVariable($variable, $value): void
    {
        try {
            switch ($variable) {
                case "autostop":
                    $this->maxOpCount = $this->parseInt($value);
                    break;
                case "benchmark":
                    $this->benchmarkOn = $this->parseInt($value);
                    break;
                case "debug" :
                    $this->debugOn = $this->parseInt($value);
                    break;
            }
            $this->getInternalVariable($variable);
        } catch (\Exception $e) {
            $this->writeLn("An error occurred. Illegal query.");
        }
    }

    // displays the value of the internal parameter specified by variable
    protected function getInternalVariable($variable): void
    {
        switch ($variable) {
            case "autostop" :
                $this->writeLn("Internal variable AUTOSTOP = " . $this->maxOpCount);
                break;
            case "benchmark":
                $this->writeLn("Internal variable BENCHMARK = " . $this->benchmarkOn);
                break;
            case "debug" :
                $this->writeLn("Internal variable DEBUG = " . $this->debugOn);
                break;
            default:
                $this->writeLn("Unknown internal variable.");
        }
    }

    protected function parseInt($number): int
    {
        if (!is_numeric($number)) {
            throw new \InvalidArgumentException('NumberFormatException');
        }

        return (int) $number;
    }

    // returns the Variable pointer belonging to a string, e.g. "A3", "Y25"
    protected function get_ref(string $name): ?Variable
    {
        $anArray = null;
        switch ($name[0]) {
            case 'Y': $anArray = &$this->env->variables;
                break;
            case 'A': $anArray = &$this->arguments;
                break;
            case 'Q': $anArray = &$this->queryVariables;
                break;
            default: return null;
        }
        $index = (int) substr($name, 1);
        $cnt = count($anArray);
        while ($cnt++ < ($index + 1)) {
            $anArray[] = new Variable();
        }

        return $anArray[$index];
    }

    // gives a name to a variable; usually used on Qxx variables that occur within the query
    protected function create_variable($v, string $name): void
    {
        if (strcmp($name, "_") != 0) {  // keep "_" from being displayed as solution
            $q = $this->get_ref($v);
            $q->name = $name;
            // update displayQ-stuff
            $i = $this->parseInt(substr($v, 1));
            if (!$this->displayQValue[$i]) {
                $this->displayQCount++;
                $this->displayQValue[$i] = true;
            }
        }
        $this->programCounter++;
    }

// end of WAM.create_variable(String, String)
    // comparison manages "<", "<=", ">=", ">" and "!="
    protected function comparison($s1, $s2, $comparator)
    {
        // comparator values: 1 = "<", 2 = "<=", 3 = ">=", 4 = ">", 5 = "!="
        $v1 = $this->get_ref($s1)->deref();
        $v2 = $this->get_ref($s2)->deref();
        if (($v1->tag == self::CON) && ($v2->tag == self::CON)) {
            //int compareValue;
            if (is_numeric($v1->value) && is_numeric($v2->value)) {
                $compareValue = $v1->value - $v2->value;
            } else {
                $compareValue = strcmp($v1->value, $v2->value);
            }
            switch ($comparator) {
                case 1: if ($compareValue < 0)
                        $this->programCounter++;
                    else
                        $this->backtrack();
                    break;
                case 2: if ($compareValue <= 0)
                        $this->programCounter++;
                    else
                        $this->backtrack();
                    break;
                case 3: if ($compareValue >= 0)
                        $this->programCounter++;
                    else
                        $this->backtrack();
                    break;
                case 4: if ($compareValue > 0)
                        $this->programCounter++;
                    else
                        $this->backtrack();
                    break;
                case 5: if ($compareValue != 0)
                        $this->programCounter++;
                    else
                        $this->backtrack();
                    break;
                default: $this->backtrack();
            }
        } else {
            $this->backtrack();
        }
    }

// end of WAM.comparison(String, String, String)

    protected function smaller($s1, $s2)
    {
        $this->comparison($s1, $s2, 1);
    }

// end of WAM.smaller(String, String)

    protected function smallereq($s1, $s2)
    {
        $this->comparison($s1, $s2, 2);
    }

// end of WAM.smallereq(String, String)

    protected function biggereq($s1, $s2)
    {
        $this->comparison($s1, $s2, 3);
    }

// end of WAM.biggereq(String, String)

    protected function bigger($s1, $s2)
    {
        $this->comparison($s1, $s2, 4);
    }

// end of WAM.bigger(String, String)

    protected function unequal($s1, $s2)
    {
        $this->comparison($s1, $s2, 5);
    }

// end of WAM.unequal(String, String)
    // is manages integer arithmetic (floating point may be added later)
    protected function is($target, $op, $s1, $s2)
    {
        //Variable v1, v2, v3;
        //int z1, z2, z3;
        // convert s1 or the value of the variable referenced by s1 to int value
        if (is_numeric($s1)) {
            $z1 = $s1;
        } else {
            $v1 = $this->get_ref($s1)->deref();
            if (($v1->tag != self::CON) || !is_numeric($v1->value)) {
                $this->backtrack();
                return;
            }
            $z1 = $v1->value;
        }
        // convert s2 or the value of the variable referenced by s2 to int value
        if (is_numeric($s2)) {
            $z2 = $s2;
        } else {
            $v2 = $this->get_ref($s2)->deref();
            if (($v2->tag != self::CON) || !is_numeric($v2->value)) {
                $this->backtrack();
                return;
            }
            $z2 = $v2->value;
        }
        // check which variable is referenced by target
        $v3 = $this->get_ref($target)->deref();
        try {
            $z3 = 0;
            // do the arithmetic
            if ($op == '+')
                $z3 = $z1 + $z2;
            if ($op == '-')
                $z3 = $z1 - $z2;
            if ($op == '*')
                $z3 = $z1 * $z2;
            if ($op == '/')
                $z3 = $z1 / $z2;
            if ($op == '%')
                $z3 = $z1 % $z2;
            // if v3 (the target) has already been bound, consider this an equality check
//      if ((v3.tag == CON) && (parseInt(v3.value) != z3))      // do not allow this for now, since problems might occur
//        backtrack();
            if ($v3->tag == self::REF) {
                // if it has not been bound yet, bind it to constant value z3 (the integer number)
                $this->trail->addEntry($v3);
                $v3->tag = self::CON;
                $v3->value = "" . $z3;
                $this->programCounter++;
            }
            // only when alle stricke reissen: backtrack!
            else
                $this->backtrack();
        } catch (\Exception $e) {
            $this->backtrack();
        }
    }

// end of WAM.is(String, String, String, String)

    protected function get_variable($s1, $s2)
    {
        $Vn = $this->get_ref($s1);
        $Ai = $this->get_ref($s2);
        $Vn->copyFrom($Ai);
        $this->programCounter++;
    }

// end of WAM.get_variable(String, String)

    protected function get_value($s1, $s2)
    {
        $this->unify_variable($s2, $s1);
    }

// end of WAM.get_value(String, String)

    protected function get_constant($c, $variable)
    {
        $v = $this->get_ref($variable)->deref();
        $fail = true;
        if ($v->tag == self::REF) {
            $this->trail->addEntry($v);
            $v->tag = self::CON;
            $v->value = $c;
            $fail = false;
        } elseif ($v->tag == self::CON) {
            if (strcmp($c, $v->value) == 0)
                $fail = false;
        }
        if ($fail)
            $this->backtrack();
        else
            $this->programCounter++;
    }

// end of WAM.get_constant(String, String)

    protected function unify_variable2($v1, $v2)
    {
        if (($v1 === null) || ($v2 === null))
            return false;
        $v1 = $v1->deref();
        $v2 = $v2->deref();
        if ($v1 === $v2)
            return true;

        if ($v1->tag == self::REF) {
            $this->trail->addEntry($v1);
            $v1->copyFrom($v2);
            return true;
        }
        if ($v2->tag == self::REF) {
            $this->trail->addEntry($v2);
            $v2->copyFrom($v1);
            return true;
        }

        if (($v1->tag == self::CON) && ($v2->tag == self::CON)) {
            if ($v1->value === $v2->value)
                return true;
            else
                return false;
        }

        if ((($v1->tag == self::LIS) && ($v2->tag == self::LIS)) || (($v1->tag == self::STR) && ($v2->tag == self::STR)))
            if (($this->unify_variable2($v1->head, $v2->head)) && ($this->unify_variable2($v1->tail, $v2->tail)))
                return true;

        return false;
    }

// end of WAM.unify_variable2(Variable, Variable)

    protected function unify_list2(Variable $liste, Variable $head, Variable $tail)
    {
//    liste = list.deref();
//    head = head.deref();
//    tail = tail.deref();
        if ($liste->tag == self::REF) {
            $this->trail->addEntry($liste);
            $liste->tag = self::LIS;
            $liste->head = $head;
            $liste->tail = $tail;
            return true;
        }
        if ($liste->tag == self::LIS) {
            if ($this->unify_variable2($head, $liste->head))
                if ($this->unify_variable2($tail, $liste->tail))
                    return true;
        }
        return false;
    }

// end of WAM.unify_list2(Variable, Variable, Variable)

    protected function unify_struc2(Variable $struc, Variable $head, Variable $tail)
    {
//    struc = struc.deref();
//    head = head.deref();
//    tail = tail.deref();
        if ($struc->tag == self::REF) {
            $this->trail->addEntry($struc);
            $struc->tag = self::STR;
            $struc->head = $head;
            $struc->tail = $tail;
            return true;
        }
        if ($struc->tag == self::STR) {
            if ($this->unify_variable2($head, $struc->head))
                if ($this->unify_variable2($tail, $struc->tail))
                    return true;
        }
        return false;
    }

// end of WAM.unify_struc2(Variable, Variable, Variable)

    protected function unify_variable($s1, $s2)
    {
        $v1 = $this->get_ref($s1);
        $v2 = $this->get_ref($s2);
        if ($this->unify_variable2($v1, $v2))
            $this->programCounter++;
        else
            $this->backtrack();
    }

// end of WAM.unify_variable(String, String)

    protected function unify_list($l, $h, $t)
    {
        $liste = $this->get_ref($l);
        $head = $this->get_ref($h);
        $tail = $this->get_ref($t);
        if ($this->unify_list2($liste, $head, $tail))
            $this->programCounter++;
        else
            $this->backtrack();
    }

// end of WAM.unify_list(String, String, String)

    protected function unify_struc($s, $h, $t)
    {
        $struc = $this->get_ref($s);
        $head = $this->get_ref($h);
        $tail = $this->get_ref($t);
        if ($this->unify_struc2($struc, $head, $tail))
            $this->programCounter++;
        else
            $this->backtrack();
    }

// end of WAM.unify_struc(String, String, String)

    protected function put_constant($c, $a)
    {
        $Ai = $this->get_ref($a);
        $Ai->tag = self::CON;
        $Ai->value = $c;
        $this->programCounter++;
    }

// end of WAM.put_constant(String, String)

    protected function put_list($h, $t, $a)
    {
        $Ai = $this->get_ref($a);
        $Ai->tag = self::LIS;
        $Ai->head = $this->get_ref($h)->deref();
        $Ai->tail = $this->get_ref($t)->deref();
        $this->programCounter++;
    }

// end of WAM.put_list(String, String, String);

    protected function put_value($s1, $s2)
    {
        $Vi = $this->get_ref($s1);
        $An = $this->get_ref($s2);
        $An->copyFrom($Vi);
        $this->programCounter++;
    }

// end of WAM.put_value(String, String)

    protected function put_variable($s1, $s2)
    {
        $Vn = $this->get_ref($s1)->deref();
        $Ai = $this->get_ref($s2);
        $Ai->tag = self::REF;
        $Ai->reference = $Vn;
        $this->programCounter++;
    }

// end of WAM.put_variable(String, String)

    protected function try_me_else($whom)
    {
        //int i;
        $cp = new ChoicePoint($this->arguments, $this->trail->getLength(), $this->continuationPointer);
        $cp->lastCP = $this->choicePoint;
        $cp->cutPoint = $this->cutPoint;
        $this->choicePoint = $cp;
        $cp->nextClause = $whom;
        $cp->lastEnviron = $this->env;
        $this->programCounter++;
    }

// end of WAM.try_me_else(int)

    protected function proceed()
    {
        $this->programCounter = $this->continuationPointer;
    }

// end of WAM.proceed()

    protected function is_bound(Variable $v)
    {
        $v = $v->deref();
        if ($v->tag == self::REF)
            $this->backtrack();
        else
            $this->programCounter++;
    }

// end of WAM.is_bound(String)

    protected function allocate()
    {
        $environment = new Environment($this->continuationPointer, $this->env);
        $this->env = $environment;
        $this->programCounter++;
    }

// end of WAM.allocate()

    protected function deallocate()
    {
        $this->continuationPointer = $this->env->returnAddress;
        $this->env = $this->env->lastEnviron;
        $this->programCounter++;
    }

// end of WAM.deallocate()

    protected function call($target)
    {
        if ($target >= 0) {
            $this->continuationPointer = $this->programCounter + 1;
            $this->cutPoint = $this->choicePoint;
            $this->programCounter = $target;
        } else  // linenumbers < 0 indicate internal predicates, e.g. writeln
        if (!$this->internalPredicate($target))
            $this->backtrack();
    }

// end of WAM.call(int)

    protected function cut($Vn)
    {
        $v = $this->get_ref($Vn);
        $this->choicePoint = $v->cutLevel;
        $this->programCounter++;
    }

// end of WAM.cut(String)

    protected function get_level($Vn)
    {
        $v = $this->get_ref($Vn);
        $v->cutLevel = $this->cutPoint;
        $this->programCounter++;
    }

// of WAM.get_level(String)

    /*     * ****************** END WAM CODE OPERATIONS ******************* */

    // called upon an unsuccessful binding operation or a call with non-existent target
    protected function backtrack()
    {
        //int i;
        if ($this->debugOn > 0)
            $this->writeLn("-> backtrack");
        $this->backtrackCount++;
        $this->failed = true;
        if ($this->choicePoint !== null) {
            $this->continuationPointer = $this->choicePoint->returnAddress;
            $this->programCounter = $this->choicePoint->nextClause;
            $this->env = $this->choicePoint->lastEnviron;
            $tp = $this->choicePoint->trailPointer;
            for ($i = $this->trail->getLength() - 1; $i >= $tp; $i--)
                $this->trail->undo($i);
            $this->trail->setLength($tp);
            $this->arguments = $this->choicePoint->arguments;
            $this->cutPoint = $this->choicePoint->cutPoint;
            $this->choicePoint = $this->choicePoint->lastCP;
        } else {
            for ($i = $this->trail->getLength() - 1; $i >= 0; $i--)
                $this->trail->undo($i);
            $this->programCounter = -1;
        }
    }

    /**
     * internalPredicate manages the execution of all built-in predicates,
     * e.g. write, consult, isbound
     *
     * @param integer $index
     * @return boolean
     */
    protected function internalPredicate($index)
    {
        $result = true;
        $v = $this->arguments[0];
        switch ($index) {
            case self::callCall:  // internal predicate call(X)
                $v2tmp = new Variable($v);
                $v2 = $v2tmp->deref();
                $intg = null;
                $target = -1;
                if ($v2->tag == self::CON) {
                    if (array_key_exists($v2->value, $this->p->labels))
                        $target = $this->p->labels[$v2->value];
                } else if ($v2->tag == self::STR) {
                    if (array_key_exists($v2->head->value, $this->p->labels)) {
                        $target = $this->p->labels[$v2->head->value];
                        $tail = $v2->tail;
                        $cnt = 0;
                        while ($tail !== null) {
                            $this->get_ref("A" . $cnt)->tag = self::REF;
                            $this->get_ref("A" . $cnt)->reference = $tail->head;
                            $cnt++;
                            $tail = $tail->tail;
                        }
                    }
                }
                if ($target >= 0)
                    $this->call($target);
                else
                    $this->backtrack();
                break;
            case self::callIsAtom :
                $this->isAtom($v->deref());
                break;
            case self::callIsInteger :
                $this->isInteger($v->__toString());
                break;
            case self::callIsBound :
                $this->is_bound($v);
                break;
            case self::callWrite :
                $this->write($v->__toString());
                $this->programCounter++;
                break;
            case self::callWriteLn :
                $this->writeLn($v->__toString());
                $this->programCounter++;
                break;
            case self::callNewLine :
                $this->writeLn("");
                $this->programCounter++;
                break;
            case self::callAssert :
                $this->assert($v->head->__toString(), $v->__toString());
                return true;
                break;
            case self::callRetractOne :
                if ($this->retract($v->__toString()))
                    $this->programCounter++;
                else
                    $this->backtrack();
                break;
            case self::callRetractAll :
                $this->retractall($v->__toString());
                break;
            case self::callLoad :
                $this->load($v->__toString());
                break;
            case self::callConsult :
                $this->consult($v->__toString());
                break;
            case self::callReadLn :
                $w = new Variable("", $this->readLn());  // TODO untested
                $this->unify_variable2($v->deref(), $w);
                $this->programCounter++;
                break;
            default :
                $result = false;
        }
        return $result;
    }

    /**
     * Load WAM Code
     *
     * @param string $fileName
     */
    protected function load($fileName)
    {
        $prog = CodeReader::readProgram($fileName);
        if ($prog == null)
            if (false === strpos($fileName, ".wam")) {  // if compilation didn't work, try with different file extension
                $this->writeLn("File \"" . $fileName . "\" could not be opened.");
                $this->writeLn("Trying \"" . $fileName . ".wam\" instead.");
                $prog = CodeReader::readProgram($fileName . ".wam");
            }
        if ($prog == null)
            $this->backtrack();
        else {
            $this->p->addProgram($prog);
            $this->p->updateLabels();
            $this->programCounter++;
        }
    }

    protected function isAtom(Variable $v)
    {
        $v = $v->deref();
        if (($v->tag == self::CON) || ($v->tag == self::REF))
            $this->programCounter++;
        else
            $this->backtrack();
    }

    // checks if stuff contains an integer number
    protected function isInteger($stuff)
    {
        try {
            $this->parseInt($stuff);
            $this->programCounter++;
        } catch (\Exception $e) {
            $this->backtrack();
        }
    }

    // assert asserts a new clause to the current program
    protected function assert($label, $clause)
    {
        $pc = new PrologCompiler($this);
        $prog = $pc->compileSimpleClause($clause . ".");
        if ($prog != null) {
            $this->p->addClause($label, $prog);
            $this->programCounter++;
            $v = new Variable("", $label);
            $v->tag = self::ASSERT;
            $this->trail->addEntry($v);
        } else
            $this->backtrack();
    }

    protected function removeProgramLines($fromLine)
    {
        $size = $this->p->getStatementCount();
        $removed = $this->p->deleteFromLine($fromLine);
        if ($this->programCounter >= $fromLine) {
            if ($this->programCounter >= $fromLine + $removed)
                $this->programCounter -= $removed;
            else
                $this->backtrack();
        }
    }

    // retract undoes an assert action
    public function retract($clauseName)
    {
        $index1 = $this->p->getLastClauseOf($clauseName);
        $index2 = $this->p->getLastClauseButOneOf($clauseName);
        if ($index1 >= 0) {
            $this->removeProgramLines($index1);
            if ($index2 >= 0) {
                $s = $this->p->getStatement($index2);
                $s->setFunction("trust_me");
                $s->setArgAt("", 0);
                $s->arg1 = "";
            }
            return true;
        } else
            return false;
    }

    // calls retract(String) until it returns false
    protected function retractall($clauseName)
    {
        $success = false;
        $this->failed = false;
        while ($this->retract($clauseName)) {
            if ($this->failed)
                return;
            $success = true;
        };
        if ($success)
            $this->programCounter++;
        else
            $this->backtrack();
    }

    // consult compiles a prolog program and loads the resulting code into memory
    protected function consult(string $fileName)
    {
        $pc = new PrologCompiler($this);
        $prog = $pc->compileFile($fileName);

        if (is_null($prog)) {
            if (false === strpos($fileName, ".pro")) {  // if compilation didn't work, try with different file extension
                $this->writeLn("Trying \"" . $fileName . ".prolog\" instead.");
                $prog = $pc->compileFile($fileName . ".prolog");
            }
        }

        if (is_null($prog)) {  // program could not be compiled/loaded for whatever reason
            $this->backtrack();
        } else {
            if ($this->debugOn > 1) { // in case of debug mode, display the WAM code
                $this->writeLn($prog->__toString());
            }

            $this->p->owner = $this;
            $this->p->addProgram($prog);  // add program to that already in memory
            $this->p->updateLabels();  // and don't forget to update the jump labels
            $this->programCounter++;
        }
    }

    /** Dump Query and Local Variables */
    public function traceOn()
    {
        $this->write("A=[");
        foreach ($this->arguments as $v) {
            $this->write($v->tag . "/");
            $this->write($v->value);
            $this->write("(" . $v . ") ");
        }
        $this->write("] V=[");
        foreach ($this->env->variables as $v) {
            $this->write($v->tag . "/");
            $this->write($v->value);
            $this->write("(" . $v . ") ");
        }
        $this->writeLn("]");
    }

    /**
     * run starts the actual execution of the program in memory
     */
    public function run()
    {
        // opCount and backtrackCount are used for benchmarking
        $this->opCount = 0;
        $this->backtrackCount = 0;

        $this->failed = true;

        while ($this->programCounter >= 0) {   // programCounter < 0 happens on jump error or backtrack without choicepoint
            $this->failed = false;
            $s = $this->p->getStatement($this->programCounter);  // get current WAM statement

            if ($this->debugOn > 0)  // display statement and line number information in case of debug mode
                $this->writeLn("(" . $this->int2FormatStr($this->programCounter) . ")  " . $s->__toString());

            // we have introduced an artificial stack overflow limit in order to prevent the WAM from infinite execution
            if ($this->opCount++ > $this->maxOpCount) {
                $this->writeLn("Maximum OpCount reached. Think of this as a stack overflow.");
                $this->failed = true;
                break;
            }

            if ($this->debugOn > 1)
                $this->traceOn();

            // select WAM command and execute the responsible method, e.g. "deallocate()"
            $op = $s->operator;
            if ($op == self::opHalt)
                break;
            switch ($op) {
                case self::opAllocate : $this->allocate();
                    break;
                case self::opCall : $this->call($s->jump);
                    break;
                case self::opCut : $this->cut($s->arg1);
                    break;
                case self::opDeallocate :
                    $this->deallocate();
                    break;
                case self::opGetVariable :
                    $this->get_variable($s->arg1, $s->arg2);
                    break;
                case self::opPutValue :
                    $this->put_value($s->arg1, $s->arg2);
                    break;
                case self::opPutVariable :
                    $this->put_variable($s->arg1, $s->arg2);
                    break;
                case self::opGetLevel :
                    $this->get_level($s->arg1);
                    break;
                case self::opGetConstant :
                    $this->get_constant($s->arg1, $s->arg2);
                    break;
                case self::opGetValue :
                    $this->get_value($s->arg1, $s->arg2);
                    break;
                case self::opPutConstant :
                    $this->put_constant($s->arg1, $s->arg2);
                    break;
                case self::opUnifyList :
                    $this->unify_list($s->arg1, $s->arg2, $s->arg3);
                    break;
                case self::opUnifyStruc :
                    $this->unify_struc($s->arg1, $s->arg2, $s->arg3);
                    break;
                case self::opUnifyVariable :
                    $this->unify_variable($s->arg1, $s->arg2);
                    break;
                case self::opRetryMeElse :
                    $this->try_me_else($s->jump);
                    break;
                case self::opTryMeElse :
                    $this->try_me_else($s->jump);
                    break;
                case self::opTrustMe :
                    $this->programCounter++;
                    break;
                case self::opProceed :
                    $this->proceed();
                    break;
                case self::opBigger :
                    $this->bigger($s->arg1, $s->arg2);
                    break;
                case self::opBiggerEq :
                    $this->biggereq($s->arg1, $s->arg2);
                    break;
                case self::opSmaller :
                    $this->smaller($s->arg1, $s->arg2);
                    break;
                case self::opSmallerEq :
                    $this->smallereq($s->arg1, $s->arg2);
                    break;
                case self::opUnequal :
                    $this->unequal($s->arg1, $s->arg2);
                    break;
                case self::opIs :
                    $this->is($s->arg1, $s->arg2[0], $s->arg3, (string) $s->getArgAt(3));
                    break;
                case self::opNoOp :
                    $this->programCounter++;
                    break;
                case self::opCreateVariable :
                    $this->create_variable($s->arg1, $s->arg2);
                    break;
                default: // invalid command: backtrack!
                    $this->writeLn("Invalid operation in line " . $this->int2FormatStr($this->programCounter));
                    $this->backtrack();
            }

            if ($this->debugOn > 1) {
                $this->traceOn();
            }
        } // end of while (programCounter >= 0)
        if ($this->failed) {
            while ($this->choicePoint !== null) {
                $this->backtrack();
            }
            $this->backtrack();
        }
        if ($this->benchmarkOn > 0) {
            $this->writeLn("# operations: " . $this->opCount);
            $this->writeLn("# backtracks: " . $this->backtrackCount);
        }
    }

}
