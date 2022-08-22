<?php

namespace Trismegiste\Prolog;

/**
 * Warren's Abstract Machine  -  Implementation by Stefan Buettcher
 *                            -  Ported to PHP by Trismegiste
 *
 * developed:   December 2001 until February 2002
 * ported:      July 2012
 *
 * CompilerStructure contains the CompilerStructure class, which is needed
 * for transforming the input vector (Prolog program) to the output Program
 * (WAM code) via a certain program structure graph.
 *
 * Each instance of CompilerStructure represents a node in the program graph
 * of the original Prolog program. Every node is of a certain type (see constants
 * below).
 */
class CompilerStructure
{
    const NO_TYPE = -1;   // no type or unknown type
    const QUERY = +0;   // this is a query (list), composed of
    // a set of conditions
    const TERM = +1;   // this is a term, e.g. "s(a, Y)"
    const LISTX = +2;   // this is a list, e.g. "s", "a, X, c"
    const CONSTANT = +3;   // this is a constant, e.g. "a", "b", "z"
    const VARIABLE = +4;   // this is a variable, e.g. "A", "B", "Z"
    const PREDICATE = +5;   // this is a predicate, e.g. "father", "length"
    const CLAUSE = +6;   // this is a clause, composed of a HEAD (this.head) and a BODY (this.tail)
    const PROGRAM = +7;   // this is a whole Prolog program, i.e. a list of PROCEDUREs
    const HEAD = +8;   // this is a PROCEDURE's head, composed of a PREDICATE name and a parameter LIST
    const BODY = +9;   // this is a PROCEDURE's body, i.e. a list of CONDITIONs
    const CALL = 10;   // this is a condition, e.g. "father(X, Y)", composed of the PREDICATE name
    // and a LIST of calling arguments
    const UNIFICATION = 12;   // this is a unification of the form "X = Y" (args in head and tail).
    const ASSIGNMENT = 13;   // this is an assignment of the form "X = 1 + 3",
    // where X can be found in head, + in tail.value and 1 (3) in tail.head (tail.tail)
    const EXPRESSION = 14;   // this is an arithmetic expression, to be used in ASSIGNMENTs,
    // in "X = 1 + 3", "1 + 3" would be the expression, with + as value,
    // 1 as (constant) head and 3 as (constant) tail
    const COMPARISON = 15;   // something like "X < 5" or "Z > Y"
    const STRUCTURE = 16;   // this is a structure, e.g. "s(x, y, X)", "auto(mobil, nix_is)"
    const CUT = 17;   // a cut instruction ("!")

    public $type;                     // the type of the node, as explained above
    public $head;
    public $tail; // sub-nodes in case of non-trivial nodes (lists, queries, ...)
    public $value;                 // the value, e.g. the variable's name in case of type == VARIABLE

    /**
     * create a new structure of unknown type
     */
    public function __construct($aType = self::NO_TYPE, $aValue = '')
    {
        $this->type = $aType;
        $this->head = null;
        $this->tail = null;
        $this->value = $aValue;
    }

    /**
     * return the string that shall be used to display this node on the screen
     * TODO simple keyword are missing (example : cut)
     */
    public function __toString()
    {
        if ($this->type == self::NO_TYPE)
            return "[no type]";
        else if (($this->type == self::TERM) || ($this->type == self::QUERY)) {
            if ($this->tail == null)
                return $this->head->__toString();
            else
                return $this->head->__toString() . "(" . $this->tail->__toString() . ")";
        }
        else if ($this->type == self::PREDICATE)
            return $this->value;
        else if ($this->type == self::CONSTANT)
            return "const " . $this->value;
        else if ($this->type == self::VARIABLE)
            return "var " . $this->value;
        else if ($this->type == self::PROGRAM) {
            if ($this->tail == null)
                return "\n" . $this->head->__toString();
            else
                return "\n" . $this->head->__toString() . $this->tail->__toString();
        }
        else if ($this->type == self::CLAUSE) {
            if ($this->tail == null)
                return $this->head->__toString() . ".";
            else
                return $this->head->__toString() . " :-\n" . $this->tail->__toString() . ".";
        }
        else if ($this->type == self::HEAD) {
            if ($this->tail == null)
                return $this->head->__toString();
            else
                return $this->head->__toString() . "(" . $this->tail->__toString() . ")";
        }
        else if ($this->type == self::BODY) {
            if ($this->tail == null)
                return "  " . $this->head->__toString();
            else
                return "  " . $this->head->__toString() . ",\n" . $this->tail->__toString();
        }
        else if ($this->type == self::CALL) {
            if ($this->tail == null)
                return $this->head->__toString();
            else
                return $this->head->__toString() . "(" . $this->tail->__toString() . ")";
        }
        else if ($this->type == self::COMPARISON) {
            return $this->head->__toString() . " " . $this->value . " " . $this->tail->__toString();
        } else if ($this->type == self::LISTX) {
            if ($this->head == null)
                return "[]";
            else {
                if ($this->tail == null)
                    return $this->head->__toString();
                else
                    return $this->head->__toString() . ", " . $this->tail->__toString();
            }
        }
        else if ($this->type == self::STRUCTURE) {
            if ($this->tail == null)
                return $this->head->__toString();
            else
                return $this->head->__toString() . "(" . $this->tail->__toString() . ")";
        }
        else
            return "[unknown type]";
    }

// end of CompilerStructure->__toString()
}

// end of class CompilerStructure
