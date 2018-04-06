<?php

namespace Keiwen\Utils\Elo;


class EloSystem
{

    const WIN = 'W';
    const TIE = 'T';
    const LOSS = 'L';

    protected static $maxDiff = 800;
    protected static $maxGain = 0;
    protected static $multiplier = 400;
    protected static $defaultKFactor = 20;

    /**
     * @param string|int $result can use string constants [W/T/L] or 0 for loss or 1 for win
     * @return float
     */
    public static function getResultFactor($result)
    {
        if($result === 0 || $result === 1) return $result;
        if($result == self::WIN) return 1;
        if($result == self::LOSS) return 0;
        return 0.5;
    }

    /**
     * @param int $maxDiff
     */
    public static function setMaxDiff(int $maxDiff)
    {
        static::$maxDiff = $maxDiff;
    }

    /**
     * Max diff considered between 2 elos, considering that above,
     * lowest player has no chance to beat highest,
     * diff in computing will be limited to this value
     * Default is 800, 0 is no limit
     * @return int
     */
    public static function getMaxDiff()
    {
        return static::$maxDiff;
    }

    /**
     * @param int $maxGain
     */
    public static function setMaxGain(int $maxGain)
    {
        static::$maxGain = $maxGain;
    }

    /**
     * Max gain that could be earned in a single match/competition.
     * Default is 0 (no limit)
     * @return int
     */
    public static function getMaxGain()
    {
        return static::$maxGain;
    }

    /**
     * @param int $multiplier
     */
    public static function setMultiplier(int $multiplier)
    {
        static::$multiplier = $multiplier;
    }

    /**
     * Used to adjust result probability according to elo diff.
     * usually equal to maxDiff / 2
     * Default is 400
     * @return int
     */
    public static function getMultiplier()
    {
        return static::$multiplier;
    }

    /**
     * @param int $defaultKFactor
     */
    public static function setDefaultKFactor(int $defaultKFactor)
    {
        static::$defaultKFactor = $defaultKFactor;
    }

    /**
     * Default value used to adjust match/competition gain.
     * @see EloRating::getKFactor()
     * @return int
     */
    public static function getDefaultKFactor()
    {
        return static::$defaultKFactor;
    }


    /**
     * @param mixed $value raw value
     * @param mixed $max max value (absolute)
     * @return mixed adjusted value
     */
    protected static function adjustMaxLimit($value, $max)
    {
        if(!$max) return $value;
        if($value > $max) $value = $max;
        if($value < -$max) $value = -$max;
        return $value;
    }

    /**
     * @param int $diff
     * @return int
     */
    public static function adjustDiffLimit(int $diff)
    {
        return static::adjustMaxLimit($diff, static::getMaxDiff());
    }

    /**
     * @param int $gain
     * @return int
     */
    public static function adjustGainLimit(int $gain)
    {
        return static::adjustMaxLimit($gain, static::getMaxGain());
    }

}
