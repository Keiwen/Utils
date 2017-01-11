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
    public static function getValuePartFromTotal(float $value, float $total, int $partCount, bool $floor = true) {
        $valuePart = $partCount * $value / $total;
        if($floor) {
            $valuePart = floor($valuePart) + 1;
        } else {
            $valuePart = ceil($valuePart);
        }
        if($valuePart <= 0) $valuePart = 1;
        if($valuePart > $partCount) $valuePart = $partCount;
        return $valuePart;
    }


    /**
     * @param float $value
     * @param float $total
     * @param bool  $floor
     * @return int
     */
    public static function getHalf(float $value, float $total, bool $floor = true)
    {
        return static::getValuePartFromTotal($value, $total, 2, $floor);
    }


    /**
     * @param float $value
     * @param float $total
     * @param bool  $floor
     * @return int
     */
    public static function getThird(float $value, float $total, bool $floor = true)
    {
        return static::getValuePartFromTotal($value, $total, 3, $floor);
    }


    /**
     * @param float $value
     * @param float $total
     * @param bool  $floor
     * @return int
     */
    public static function getQuarter(float $value, float $total, bool $floor = true)
    {
        return static::getValuePartFromTotal($value, $total, 4, $floor);
    }


}
