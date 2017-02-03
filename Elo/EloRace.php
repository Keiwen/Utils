<?php

namespace Keiwen\Utils\Elo;


class EloRace
{

    protected $rawList = array();
    protected $updatedList = array();
    /** @var EloRating[] */
    protected $rankedEloList;
    protected $competitorsCount = 0;
    protected $gains = array();


    /**
     * EloRace constructor.
     *
     * @param EloRating[]|int[] $rankedEloList list of competitors elo (object or just int) ranked
     */
    public function __construct(array $rankedEloList)
    {
        $this->rawList = array_values($rankedEloList);
        $this->rankedEloList = $this->rawList;
        foreach($this->rankedEloList as &$competitor) {
            if(!$competitor instanceof EloRating) {
                if(!is_int($competitor)) throw new \RuntimeException('Invalid elo value');
                $competitor = new EloRating($competitor);
            }
        }
        $this->competitorsCount = count($this->rankedEloList);
        $this->computeGains();
    }


    /**
     * compute gain for specific competitor
     * @param int $competitorIndex
     * @return int
     */
    protected function computeGain(int $competitorIndex)
    {
        $competitor = $this->rankedEloList[$competitorIndex];

        $result = EloSystem::LOSS;
        $gain = 0;
        foreach($this->rankedEloList as $index => $eloRating) {
            if($index == $competitorIndex) {
                $result = EloSystem::WIN;
                continue;
            }
            $duel = new EloDuel($competitor, $eloRating);
            $gain += $duel->getGain($result);
        }
        $gain = $gain / $this->competitorsCount;
        $gain = round($gain);
        $gain = EloSystem::adjustGainLimit($gain);
        return $gain;
    }


    /**
     * compute gain for all competitors
     */
    protected function computeGains()
    {
        foreach($this->rankedEloList as $index => $competitor) {
            $this->gains[$index + 1] = $this->computeGain($index);
        }
    }


    /**
     * get gain for specific competitor
     * @param int $rank
     * @return int
     */
    public function getGain(int $rank)
    {
        return $this->gains[$rank] ?? 0;
    }

    /**
     * get gain for all competitors
     * @return int[]
     */
    public function getGains()
    {
        return $this->gains;
    }


    /**
     * update competitors values
     */
    public function update()
    {
        if(!empty($this->updatedList)) return;

        $this->updatedList = $this->rawList;
        foreach($this->updatedList as $index => &$competitor) {
            $gain = $this->getGain($index + 1);
            if($competitor instanceof EloRating) {
                $competitor->gainElo($gain);
            } else {
                //int
                $competitor += $gain;
            }
        }
    }


    /**
     * @return EloRating[]|int[]
     */
    public function getResultingList()
    {
        if(empty($this->updatedList)) $this->update();
        return $this->updatedList;
    }

}