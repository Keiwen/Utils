<?php

namespace Keiwen\Utils\Elo;


class EloDuel
{

    protected $eloRating;
    protected $eloRatingOpponent;

    /**
     * EloDuel constructor.
     *
     * @param EloRating $eloRating
     * @param EloRating $eloRatingOpponent
     */
    public function __construct(EloRating $eloRating, EloRating $eloRatingOpponent)
    {
        $this->eloRating = $eloRating;
        $this->eloRatingOpponent = $eloRatingOpponent;
    }

    /**
     * @param int $elo
     * @param int $eloOpponent
     * @return static
     */
    public static function prepare(int $elo, int $eloOpponent)
    {
        return new static(new EloRating($elo), new EloRating($eloOpponent));
    }


    /**
     * @return int
     */
    public function getDiff()
    {
        $diff = $this->eloRating->getElo() - $this->eloRatingOpponent->getElo();
        return EloSystem::adjustDiffLimit($diff);
    }


    /**
     * @return float
     */
    public function getWinProbability()
    {
        $diff = $this->getDiff();
        $pow = 10 ** (-$diff / EloSystem::getMultiplier());
        return 1 / (1 + $pow);
    }


    /**
     * @param string|int $result
     * @param int   $opponentGain
     * @return int
     */
    public function getGain($result, int &$opponentGain = 0)
    {
        $resultFactor = EloSystem::getResultFactor($result);
        $gain = $this->eloRating->getKFactor() * ($resultFactor - $this->getWinProbability());
        $gain = round($gain);
        $gain = EloSystem::adjustGainLimit($gain);
        if($this->eloRating->getKFactor() == $this->eloRatingOpponent->getKFactor()) {
            $opponentGain = -$gain;
        } else {
            $opponentGain = $this->eloRatingOpponent->getKFactor() * (1 - $resultFactor - $this->getWinProbability());
            $opponentGain = round($opponentGain);
            $opponentGain = EloSystem::adjustGainLimit($opponentGain);
        }
        return $gain;
    }

    /**
     * @param string|int $result
     */
    public function update($result)
    {
        $gain = $this->getGain($result, $oppGain);
        $this->eloRating->gainElo($gain);
        $this->eloRatingOpponent->gainElo($oppGain);
    }


    /**
     * @param bool $opponent true to get opponent's elo
     * @return int
     */
    public function getElo(bool $opponent = false)
    {
        return $opponent ? $this->eloRatingOpponent->getElo() : $this->eloRating->getElo();
    }

}
