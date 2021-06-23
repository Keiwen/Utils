<?php

namespace Keiwen\Utils\Elo;


class EloRace
{

    protected $rawList = array();
    protected $rawListKeys = array();
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
        $this->rawList = $rankedEloList;
        $this->rawListKeys = array_keys($rankedEloList);
        $this->rankedEloList = $rankedEloList;
        foreach($this->rankedEloList as &$competitor) {
            if(!$competitor instanceof EloRating) {
                if(!is_int($competitor)) throw new \RuntimeException('Invalid elo value');
                $competitor = new EloRating($competitor);
            }
        }
        unset($competitor);
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
        $competitorKey = $this->rawListKeys[$competitorIndex];
        $competitor = $this->rankedEloList[$competitorKey];

        $result = EloSystem::LOSS;
        $gain = 0;
        foreach($this->rankedEloList as $key => $eloRating) {
            if($key == $competitorKey) {
                $result = EloSystem::WIN;
                continue;
            }
            $duel = new EloDuel($competitor, $eloRating);
            $gain += $duel->getGain($result);
        }
        $gain = $gain / $this->competitorsCount;
        $gain = round($gain);
        $gain = $competitor->getEloSystem()->adjustGainLimit($gain);
        return $gain;
    }


    /**
     * compute gain for all competitors
     */
    protected function computeGains()
    {
        $index = 0;
        foreach($this->rankedEloList as $key => $competitor) {
            $this->gains[$index + 1] = $this->computeGain($index);
            $index++;
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
    public function updateElo()
    {
        if(!empty($this->updatedList)) return;

        $this->updatedList = $this->rawList;
        $rank = 0;
        foreach($this->updatedList as $key => &$competitor) {
            $rank++;
            $gain = $this->getGain($rank);
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
        if(empty($this->updatedList)) $this->updateElo();
        return $this->updatedList;
    }

}
