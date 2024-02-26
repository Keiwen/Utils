<?php

namespace Keiwen\Utils\Elo;


class EloTeamDuel
{

    protected $rawList = array();
    protected $updatedList = array();
    protected $competitorsCount = 0;

    protected $rawListOpponent = array();
    protected $updatedListOpponent = array();
    protected $opponentCount = 0;

    protected $averageDuel;
    protected $dispatchGain = false;

    /**
     * EloTeam constructor.
     *
     * @param EloRating[]|int[] $eloList list of competitors elo (object or just int) for first team
     * @param EloRating[]|int[] $eloListOpponent list of competitors elo (object or just int) for opponent team
     * @param bool $dispatchGain false by default, all team members of team will earn full gain. Set true to dispatch ELO gain among team members
     */
    public function __construct(array $eloList, array $eloListOpponent, bool $dispatchGain = false)
    {
        $this->rawList = $eloList;
        $averageElo = 0;
        foreach($this->rawList as &$competitor) {
            if(!$competitor instanceof EloRating) {
                if(!is_int($competitor)) throw new \RuntimeException('Invalid elo value');
                $competitor = new EloRating($competitor);
            }
            $averageElo += $competitor->getElo();
        }
        unset($competitor);
        $this->competitorsCount = count($this->rawList);
        $averageElo = new EloRating($averageElo / $this->competitorsCount);

        $this->rawListOpponent = $eloListOpponent;
        $averageEloOpponent = 0;
        foreach($this->rawListOpponent as &$competitor) {
            if(!$competitor instanceof EloRating) {
                if(!is_int($competitor)) throw new \RuntimeException('Invalid elo value');
                $competitor = new EloRating($competitor);
            }
            $averageEloOpponent += $competitor->getElo();
        }
        unset($competitor);
        $this->opponentCount = count($this->rawListOpponent);
        $averageEloOpponent = new EloRating($averageEloOpponent / $this->opponentCount);

        $this->averageDuel = new EloDuel($averageElo, $averageEloOpponent);
        $this->dispatchGain = $dispatchGain;
    }


    public function getDiff()
    {
        return $this->averageDuel->getDiff();
    }


    public function getWinProbability()
    {
        return $this->averageDuel->getWinProbability();
    }

    /**
     * @param string|int $result
     * @param int|null   $opponentGain
     * @return int
     */
    public function getGain($result, &$opponentGain = null)
    {
        $rawGain = $this->averageDuel->getGain($result, $opponentGain);
        if ($this->dispatchGain) {
            $opponentGain = round($opponentGain / $this->opponentCount);
            return round($rawGain / $this->competitorsCount);
        }
        return $rawGain;
    }


    /**
     * update competitors values
     * @param string|int $result
     */
    public function updateElo($result)
    {
        if(!empty($this->updatedList)) return;
        if(!empty($this->updatedListOpponent)) return;

        $gain = $this->getGain($result, $oppGain);
        $this->updatedList = $this->rawList;
        foreach($this->updatedList as $key => &$competitor) {
            if($competitor instanceof EloRating) {
                $competitor->gainElo($gain);
            } else {
                //int
                $competitor += $gain;
            }
        }
        $this->updatedListOpponent = $this->rawListOpponent;
        foreach($this->updatedListOpponent as $key => &$competitor) {
            if($competitor instanceof EloRating) {
                $competitor->gainElo($oppGain);
            } else {
                //int
                $competitor += $oppGain;
            }
        }
    }


    /**
     * @return EloRating[]|int[]
     */
    public function getResultingList(bool $opponent = false)
    {
        return $opponent ? $this->updatedListOpponent : $this->updatedList;
    }


    /**
     * @param bool $opponent true to get opponent's team elo
     * @return int
     */
    public function getTeamElo(bool $opponent = false)
    {
        return $this->averageDuel->getElo($opponent);
    }

}
