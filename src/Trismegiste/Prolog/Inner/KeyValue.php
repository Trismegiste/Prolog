<?php

namespace Trismegiste\Prolog\Inner;

/**
 * class KeyValue helps implementing mappings "A=B" (key/value pairs)
 */
class KeyValue
{

    public $key;
    public $stringValue;
    public $intValue;

    // create a new pair with key k and String value v
    public function __construct($k, $v)
    {
        $this->key = $k;
        if (is_string($v)) {
            $this->stringValue = $v;
            $this->intValue = -12345;
        } elseif (is_int($v)) {
            $this->stringValue = '';
            $this->intValue = $v;
        }
    }

    /**
     * in order to display the mapping on the screen (for debug purposes only)
     */
    public function __toString()
    {
        if (strlen($this->stringValue) == 0)
            return "[" . $this->key . "=" . $this->intValue . "]";
        else
            return "[" . $this->key . "=" . $this->stringValue . "]";
    }

}
