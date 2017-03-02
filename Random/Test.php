<?php

namespace Keiwen\Utils\Random;


class Test
{

    public static $max = 99;
    public static $min = 0;
    public static $critMax = 99;
    public static $critMin = 0;


    /**
     * @param int  $threshold
     * @param bool $critical
     * @return bool passed
     */
    public static function test(int $threshold, &$critical = false)
    {
        $rand = mt_rand(static::$min, static::$max);
        if($rand >= $threshold) {
            if($rand >= static::$critMax) $critical = true;
            return false;
        }
        if($rand <= static::$critMin) $critical = true;
        return true;
    }

}
