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

	
}