<?php

namespace Keiwen\Utils\Elo;


class EloBrawl extends EloRace
{

    /**
     * EloBrawl constructor.
     *
     * @param EloRating[]|int[] $rankedEloList list of competitors elo (object or just int) with winner first
     */
    public function __construct(array $rankedEloList)
    {
        parent::__construct($rankedEloList);
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

        if ($competitorIndex == 0) {
            // win over all other
            $gain = 0;
            foreach($this->rankedEloList as $key => $eloRating) {
                if($key == $competitorKey) {
                    continue;
                }
                $duel = new EloDuel($competitor, $eloRating);
                $gain += $duel->getGain(EloSystem::WIN);
            }
            $gain = $gain / $this->competitorsCount;
            $gain = round($gain);
            $gain = $competitor->getEloSystem()->adjustGainLimit($gain);
            return $gain;
        } else {
            // loose against winner
            $firstRating = reset($this->rankedEloList);
            $duel = new EloDuel($competitor, $firstRating);
            $gain = $duel->getGain(EloSystem::LOSS);
            $gain = $gain / $this->competitorsCount;
            $gain = round($gain);
            $gain = $competitor->getEloSystem()->adjustGainLimit($gain);
            return $gain;
        }

    }

}
