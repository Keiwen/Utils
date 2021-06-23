<?php
namespace Keiwen\Utils\Math;

class RadixSystem {

    public const DEFAULT_DIGITS = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";

    protected $radix;
    protected $digits = array();
    protected $hasMultipleCharsDigit = false;
    protected $outputMultiple = false;

    /**
     * RadixSystem constructor.
     * @param int $radix
     * @param string[]|string $digits if string provided, each char considered as digit
     * @throws \RuntimeException when radix < 2 or digits unhandled
     */
    public function __construct(int $radix, $digits = '')
    {
        if ($radix < 2) {
            throw new \RuntimeException('Cannot create radix system with radix < 2');
        }
        if (empty($digits)) $digits = static::DEFAULT_DIGITS;
        if (is_string($digits)) $digits = str_split($digits);
        if (!is_array($digits)) {
            throw new \RuntimeException('Digits should be provided as string or array');
        }
        if (count($digits) > $radix) $digits = array_slice($digits, 0, $radix);
        if (count($digits) < $radix) $digits = range(0, $radix - 1);
        $this->radix = $radix;
        foreach ($digits as $digit) {
            $digit = (string) $digit;
            $this->digits[] = $digit;
            if (strlen($digit) > 1) $this->hasMultipleCharsDigit = true;
        }
        $this->setOutputMultiple(false);
    }


    /**
     * output 'single' is formatted like '123', considering that
     * each char represent a digit.
     * output 'multiple' is formatted like '(1,23)', number delimited
     * with parenthesis and each digit separated by commas.
     * If digits contained a multiple chars element, output is forced to multiple.
     *
     * @param bool $outputMultiple
     */
    public function setOutputMultiple(bool $outputMultiple)
    {
        if ($this->hasMultipleCharsDigit) {
            $this->outputMultiple = true;
        } else {
            $this->outputMultiple = $outputMultiple;
        }
    }


    /**
     * @param int|string $decimalNumber
     */
    public function fromDecimal($decimalNumber)
    {
        $decimalNumber = (int) $decimalNumber;
        $radixDigits = array();
        $quotient = $decimalNumber;
        do {
            [$quotient, $remainder] = Divisibility::getEuclideanDivision($quotient, $this->radix);
            $radixDigits[] = $remainder;
        } while ($quotient > 0);

        //reverse array to have highest radix power first
        $radixDigits = array_reverse($radixDigits);
        //value are on decimal radix, convert each digit
        foreach ($radixDigits as &$radixDigit) {
            $radixDigit = $this->digits[$radixDigit];
        }
        unset($radixDigit);

        if($this->outputMultiple) {
            return '(' . implode(',', $radixDigits) . ')';
        }

        return implode('', $radixDigits);
    }


    /**
     * @param int|string $radixNumber
     */
    public function toDecimal($radixNumber)
    {
        $radixDigits = $this->formatNumberAsArray($radixNumber);

        $reversedDigits = array_flip($this->digits);
        $power = count($radixDigits) - 1;

        $decimalNumber = 0;
        foreach ($radixDigits as $radixDigit) {
            //retrieve decimal value corresponding to this digit
            $decimalDigit = $reversedDigits[$radixDigit] ?? 0;
            //use radix power to convert to decimal number
            $decimalNumber += $decimalDigit * ($this->radix ** $power);
            $power--;
        }

        return $decimalNumber;
    }


    /**
     * check if number respect digits from this system
     * @param int|string $number
     * @return bool
     */
    public function belongToSystem($number)
    {
        $radixDigits = $this->formatNumberAsArray($number);
        $reversedDigits = array_flip($this->digits);
        foreach ($radixDigits as $radixDigit) {
            if(!isset($reversedDigits[$radixDigit])) return false;
        }
        return true;
    }


    /**
     * @param int|string $number
     * @return array
     */
    protected function formatNumberAsArray($number)
    {
        //be sure to get a string
        $radixNumber = (string) $number;
        //check pattern for multiple output
        $multipleOutputPattern = "/^\((([^,()]*),)*([^,()]*)\)$/";
        if(preg_match($multipleOutputPattern, $radixNumber)) {
            $radixNumber = trim($radixNumber, '()');
            $radixDigits = explode(',', $radixNumber);
        } else {
            $radixDigits = str_split($radixNumber);
        }
        return $radixDigits;
    }


    /**
     * @param $number
     * @param int|RadixSystem $radix if int given, create a basic system
     * @return string
     * @throws \RuntimeException when radix unhandled
     */
    public function convertToRadix($number, $radix)
    {
        $decimalNumber = $this->toDecimal($number);
        if(is_int($radix)) {
            $radix = new static($radix);
        }
        if(!$radix instanceof RadixSystem) {
            throw new \RuntimeException('Cannot create target radix: if object given, should be instance of RadixSystem');
        }
        return $radix->fromDecimal($decimalNumber);
    }


    /**
     * @return int
     */
    public function getRadix()
    {
        return $this->radix;
    }

    /**
     * @return array
     */
    public function getDigits()
    {
        return $this->digits;
    }

}
