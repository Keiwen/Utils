<?php

namespace Keiwen\Utils\Elo;


class EloSystem
{

    public const WIN = 'W';
    public const TIE = 'T';
    public const LOSS = 'L';

    protected $startingElo = 1200;
    protected $maxDiff = 800;
    protected $maxGain = 0;
    protected $multiplier = 400;
    protected $defaultKFactor = 20;
    protected $kFactorGainCountModifier = array(0 => 0);
    protected $kFactorEloModifier = array(0 => 0);


    /**
     * EloSystem constructor.
     * @param int $startingElo
     * @param int $defaultKFactor
     */
    public function __construct(int $startingElo = 1200, int $defaultKFactor = 20)
    {
        $this->setStartingElo($startingElo);
        $this->setDefaultKFactor($defaultKFactor);
    }


    /**
     * @param string|int $result can use string constants [W/T/L] or 0 for loss or 1 for win
     * @return float
     */
    public function getResultFactor($result)
    {
        if($result === 0 || $result === 1) return $result;
        if($result == self::WIN) return 1;
        if($result == self::LOSS) return 0;
        return 0.5;
    }

    /**
     * @param int $maxDiff
     */
    public function setMaxDiff(int $maxDiff)
    {
        $this->maxDiff = $maxDiff;
    }

    /**
     * Max diff considered between 2 elos.
     * Above this diff, lowest player has "no chance" to beat highest.
     * Diff in computing will be limited to this value
     * Default is 800, 0 is no limit
     * @return int
     */
    public function getMaxDiff()
    {
        return $this->maxDiff;
    }

    /**
     * @param int $maxGain
     */
    public function setMaxGain(int $maxGain)
    {
        $this->maxGain = $maxGain;
    }

    /**
     * Max gain that could be earned in a single match/competition.
     * Default is 0 (no limit)
     * @return int
     */
    public function getMaxGain()
    {
        return $this->maxGain;
    }

    /**
     * @param int $multiplier
     */
    public function setMultiplier(int $multiplier)
    {
        $this->multiplier = $multiplier;
    }

    /**
     * Used to adjust result probability according to elo diff.
     * Usually equal to maxDiff / 2
     * Default is 400
     * @return int
     */
    public function getMultiplier()
    {
        return $this->multiplier;
    }

    /**
     * @param int $kFactor
     */
    public function setDefaultKFactor(int $kFactor)
    {
        $this->defaultKFactor = $kFactor;
    }

    /**
     * @return int
     */
    public function getDefaultKFactor()
    {
        return $this->defaultKFactor;
    }


    /**
     * @param int $threshold
     * @param array $table
     * @return int|mixed
     */
    protected static function getValueFromThreshold(int $threshold, array $table)
    {
        if(isset($table[$threshold])) return $table[$threshold];
        $lastValue = 0;
        foreach($table as $th => $value) {
            if($th > $threshold) return $lastValue;
            $lastValue = $value;
        }
        return $lastValue;

    }


    /**
     * Value used to adjust match/competition gain.
     * Could vary according to competitor's games number or current elo.
     * Higher value will increase gain and accelerate competitor's rating change
     * Example with default K factor set as 20:
     * - 40 for first games to quickly place competitor around his "true" value
     * - 10 for highest elo floors
     * => add gainCountMultiplier on threshold 0, value 20
     * => add gainCountMultiplier on threshold 10, value 0
     * => add eloMultiplier on threshold 1800, value -10
     * @param int $gainCount
     * @param int $elo
     * @return int
     */
    public function getKFactor(int $gainCount = 0, int $elo = 0)
    {
        $kFactor = $this->getDefaultKFactor();
        $kFactor += static::getValueFromThreshold($gainCount, $this->getKFactorGainCountModifier());
        $kFactor += static::getValueFromThreshold($elo, $this->getKFactorEloModifier());
        return $kFactor;
    }

    /**
     * @return array
     */
    public function getKFactorGainCountModifier()
    {
        return $this->kFactorGainCountModifier;
    }

    /**
     * @return array
     */
    public function getKFactorEloModifier()
    {
        return $this->kFactorEloModifier;
    }

    /**
     * @param int $threshold
     */
    public function removeKFactorGainCountModifier(int $threshold)
    {
        unset($this->kFactorGainCountModifier[$threshold]);
        if(empty($this->kFactorGainCountModifier)) {
            $this->kFactorGainCountModifier = array(0 => 0);
        }
    }

    /**
     * @param int $threshold
     */
    public function removeKFactorEloModifier(int $threshold)
    {
        unset($this->kFactorEloModifier[$threshold]);
        if(empty($this->kFactorEloModifier)) {
            $this->kFactorEloModifier = array(0 => 0);
        }
    }

    /**
     * @param int $threshold
     * @param int $value
     */
    public function addKFactorGainCountModifier(int $threshold, int $value)
    {
        $this->kFactorGainCountModifier[$threshold] = $value;
    }

    /**
     * @param int $threshold
     * @param int $value
     */
    public function addKFactorEloModifier(int $threshold, int $value)
    {
        $this->kFactorEloModifier[$threshold] = $value;
    }

    /**
     * @param int $startingElo
     */
    public function setStartingElo(int $startingElo)
    {
        $this->startingElo = $startingElo;
    }

    /**
     * Default ELO value on initialization.
     * @return int
     */
    public function getStartingElo()
    {
        return $this->startingElo;
    }

    /**
     * @param mixed $value raw value
     * @param mixed $max max value (absolute)
     * @return mixed adjusted value
     */
    protected static function adjustMaxLimit($value, $max)
    {
        if(!$max) return $value;
        if($value > $max) return $max;
        if($value < -$max) return -$max;
        return $value;
    }

    /**
     * @param int $diff
     * @return int
     */
    public function adjustDiffLimit(int $diff)
    {
        return static::adjustMaxLimit($diff, $this->getMaxDiff());
    }

    /**
     * @param int $gain
     * @return int
     */
    public function adjustGainLimit(int $gain)
    {
        return static::adjustMaxLimit($gain, $this->getMaxGain());
    }

}
