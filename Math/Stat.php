<?php
namespace Keiwen\Utils\Math;

class Stat {

    /**
     * @param int[]|float[] $numbers
     * @return float|int
     */
	public static function getAverage(array $numbers) {
        if(empty($numbers)) return 0;
        return array_sum($numbers) / count($numbers);
	}


}
