<?php

namespace Keiwen\Utils\Elo;


class EloRating
{

    protected $elo;
    protected $kFactor;


    /**
     * EloRating constructor.
     *
     * @param int $elo
     * @param int $kFactor default (0) to match EloSystem value
     */
    public function __construct(int $elo, int $kFactor = 0)
    {
        $this->elo = $elo;
        $this->setKFactor($kFactor);
    }


    /**
     * @param int $gain
     */
    public function gainElo(int $gain) {
        $gain = EloSystem::adjustGainLimit($gain);
        $this->elo += $gain;
    }

    /**
     * @return int
     */
    public function getElo()
    {
        return $this->elo;
    }


    /**
     * @param int $kFactor
     */
    public function setKFactor(int $kFactor) {
        $this->kFactor = empty($kFactor) ? EloSystem::getDefaultKFactor() : $kFactor;
    }

    /**
     * Used to adjust match/competition gain.
     * Usually vary according to competitor's games number.
     * Higher value will increase gain and accelerate competitor's rating change
     * Example:
     * - 40 for first games to quickly place competitor around his "true" value
     * - 20 for lower elo floors
     * - 10 for highest elo floors
     * @return int
     */
    public function getKFactor()
    {
        return $this->kFactor;
    }

}
