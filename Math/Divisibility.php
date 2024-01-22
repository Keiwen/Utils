<?php
namespace Keiwen\Utils\Math;

class Divisibility {

	/**
	 * @param int $number
	 * @param int $divisor
	 * @return bool
	 */
	public static function isNumberDivisibleBy(int $number, int $divisor) {
		if(empty($divisor)) return false;
		if($number % $divisor == 0) return true;
		return false;
	}


	/**
	 * @param int $number
	 * @return bool
	 */
	public static function isNumberEven(int $number) {
		return self::isNumberDivisibleBy($number, 2);
	}


	/**
	 * @param int $number
	 * @return bool
	 */
	public static function isNumberOdd(int $number) {
		return !self::isNumberEven($number);
	}


    /**
     * @param float $value
     * @param float $total
     * @param int   $partCount
     * @param bool  $floor
     * @return int
     */
    public static function getPartFromTotal(float $value, int $partCount, bool $floor = true) {
        if ($partCount == 0) return 0;
        $part = $value / $partCount;
        if($floor) {
            $part = floor($part) + 1;
        } else {
            $part = ceil($part);
        }
        if($part < 0) $part = 0;
        if($part > $value) $part = $value;
        return $part;
    }


    /**
     * @param float $value
     * @param bool  $floor
     * @return int
     */
    public static function getHalf(float $value, bool $floor = true)
    {
        return static::getPartFromTotal($value, 2, $floor);
    }


    /**
     * @param float $value
     * @param bool  $floor
     * @return int
     */
    public static function getThird(float $value, bool $floor = true)
    {
        return static::getPartFromTotal($value, 3, $floor);
    }


    /**
     * @param float $value
     * @param bool  $floor
     * @return int
     */
    public static function getQuarter(float $value, bool $floor = true)
    {
        return static::getPartFromTotal($value, 4, $floor);
    }


    /**
     * @param int $number
     * @param int $divisor
     * @return int[] array with: quotient, remainder
     */
    public static function getEuclideanDivision(int $number, int $divisor) {
        if ($divisor == 0) return array(0, 0);
        $euclidean = array();
        $euclidean[] = intdiv($number, $divisor);
        $euclidean[] = $number % $divisor;
        return $euclidean;
    }


    /**
     * For given number, get the highest power founds in this number decomposition
     * Example for number 15 and power 2, if you decompose by power of 2:
     * 15 = (1 * 2^3) + (1 * 2^2) + (1 * 2^1)
     * the 'highest power' found is 3
     * remainder is 7 (15 - 1 * 2^3 = 15 - 8 = 7),
     * number 16 for power 2 will give 'highest power' of 4
     * @param int $number
     * @param int $power
     * @param int $remainder reference
     * @return int
     */
    public static function getHighestPowerOf(int $number, int $power, int &$remainder = 0)
    {
        $radix = new RadixSystem($power);
        $radixDigits = array();
        $radix->fromDecimal($number, $radixDigits);
        $maxPower = count($radixDigits) - 1;
        if ($maxPower < 0) $maxPower = 0;
        $remainder = $number - (pow($power, $maxPower));
        return $maxPower;
    }

}
