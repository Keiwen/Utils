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
    protected $scoreFactorDivider = 100;


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
     * If competitor reach the threshold value for gain count (or games played),
     * then modifier value is added to kfactor.
     * No modifier set by default
     *
     * For example, addKFactorGainCountModifier(10, -20) will remove 20 to kfactor
     * after he played his 10th game (10th not included)
     * @param int $threshold
     * @param int $modifier
     */
    public function addKFactorGainCountModifier(int $threshold, int $modifier)
    {
        $this->kFactorGainCountModifier[$threshold] = $modifier;
    }

    /**
     * If competitor reach the threshold value for ELO,
     * then modifier value is added to kfactor.
     * No modifier set by default
     *
     * For example, addKFactorEloModifier(1600, -10) will remove 10 to kfactor
     * if competitor have ELO >= 1600
     * @param int $threshold
     * @param int $modifier
     */
    public function addKFactorEloModifier(int $threshold, int $modifier)
    {
        $this->kFactorEloModifier[$threshold] = $modifier;
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


    /**
     * @param int $scoreFactorDivider
     */
    public function setScoreFactorDivider(int $scoreFactorDivider)
    {
        $this->scoreFactorDivider = $scoreFactorDivider;
    }

    /**
     * Default score factor divider
     * Set to 0 will de-activate score factor
     * @see getScoreFactor()
     * @return int
     */
    public function getScoreFactorDivider()
    {
        return $this->scoreFactorDivider;
    }


    /**
     * Value that may used to adjust match/competition gain according to score difference.
     * By default this factor can vary between 1 (no impact) to 2 (double gain).
     * Default function is 2 - (1/ (1 + (scoreDiff**3 / divider) )).
     * With divider = 100, factor 2 is approached (1.9) with a 10 difference.
     * Increasing this divider will increase the 'range' of this function.
     * @param int $scoreDiff
     * @return float
     */
    public function getScoreFactor(int $scoreDiff): float
    {
        // you can override this method to set exactly what you want/need
        // IMPORTANT default factor should still be 1 with no impact when
        // score diff is 0 (include cases when scores are not managed)

        // if parameter set to 0, just ignore this factor
        if (static::getScoreFactorDivider() == 0) return 1;

        // gain or loss is defined in gain methods, so we consider here the absolute value of score diff
        if ($scoreDiff < 0) $scoreDiff = -$scoreDiff;

        // we choose here to use a transfer function to adjust a low limit (1, no impact)
        // and high limit (2, double the gain, going above does not sounds relevant by default).
        // so we will have base function: 2 - ( 1/(1 + (x^b / a) ) )
        // b is the order of transfer function will impact on the curve slope, we choose here 3rd order
        // a is a divider that will impact on the curve slope range
        // (from where we really take off from the minimum factor, set to 1
        // to where we get close to the maximum factor, set to 2)
        // we choose here 100 by default, that imply that we get close to max factor with a 10-diff score

        // with these data:
        // diff of 0: factor 1 (no change)
        // diff of 2: factor 1.074 (slight increase)
        // diff of 4: factor of 1.39 (around 40 % more gain)
        // diff of 6: factor of 1.684 (around 70 % more gain)
        // diff of 8: factor of 1.837 (around 85 % more gain)
        // diff of 10: factor of 1.909 (almost reached 2)
        // diff of 20: factor of 1.988 (not lot more than 10-diff)

        $scoreFactor = 2 - (1/ (1 + ($scoreDiff**3 / static::getScoreFactorDivider()) ));

        return $scoreFactor;
    }


}
