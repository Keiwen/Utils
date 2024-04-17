<?php

namespace Keiwen\Utils\Elo;


abstract class AbstractEloMultiplayer
{

    /** @var EloRating[] key => elo object */
    protected $eloList;
    protected $competitorsCount = 0;
    protected $winProbabilities = array();
    protected $gains = array();


    /**
     * EloRace constructor.
     *
     * @param EloRating[]|int[] $eloList list of competitors elo (object or just int)
     */
    public function __construct(array $eloList)
    {
        $this->eloList = $eloList;
        // transform to ELORating objects
        foreach($this->eloList as &$competitor) {
            if(!$competitor instanceof EloRating) {
                if(!is_int($competitor)) throw new \RuntimeException('Invalid elo value');
                $competitor = new EloRating($competitor);
            }
        }
        unset($competitor);
        $this->competitorsCount = count($this->eloList);
        $this->computeWinProbabilities();
    }


    /**
     * compute win probability for specific competitor
     * @param int|string $competitorKey
     * @return float
     */
    protected function computeWinProbability($competitorKey): float
    {
        $competitor = $this->eloList[$competitorKey];

        $totalProb = 0;
        foreach($this->eloList as $key => $eloRating) {
            if($key == $competitorKey) {
                continue;
            }
            $duel = new EloDuel($competitor, $eloRating);
            $totalProb += $duel->getWinProbability();
        }
        // average total probability
        $totalProb = $totalProb / ($this->competitorsCount - 1);
        // adjust to multiplayer: *2 to 'cancel' duel effect, / total competitor
        $totalProb = $totalProb * 2 / $this->competitorsCount;
        // example: 4 players with same ELO, all have 50 % win prob
        // here, simple average will give 0.5, but none of them have a 50 % chance as they are 4, they only have 1/4 chance!
        return $totalProb;
    }


    /**
     * compute win probabilities for all competitors
     */
    protected function computeWinProbabilities()
    {
        foreach($this->eloList as $key => $competitor) {
            $this->winProbabilities[$key] = $this->computeWinProbability($key);
        }
    }


    /**
     * get win probability for specific competitor
     * @param int|string $competitorKey
     * @return float
     */
    public function getWinProbability($competitorKey): float
    {
        return $this->winProbabilities[$competitorKey] ?? 0;
    }

    /**
     * @return float[]
     */
    public function getWinProbabilities(): array
    {
        return $this->winProbabilities;
    }


    /**
     * @return int[] competitor key => gain
     */
    public function getGains()
    {
        $gains = array();
        foreach($this->eloList as $competitorKey => $competitor) {
            $gains[$competitorKey] = $this->getGain($competitorKey);
        }
        return $gains;
    }


    /**
     * get gain for specific competitor
     * @param int|string $competitorKey
     * @return int
     */
    abstract public function getGain($competitorKey): int;


    /**
     * update competitors values
     */
    protected function updateElo()
    {
        $this->gains = $this->getGains();

        foreach($this->gains as $competitorKey => $gain) {
            $this->eloList[$competitorKey]->gainElo($gain);
        }

        // update win probabilities
        $this->computeWinProbabilities();
    }

    /**
     * Get gains used to update ELO
     * @return array
     */
    public function getPastGains(): array
    {
        return $this->gains;
    }

    /**
     * set result and update ELO
     */
    abstract public function setResult();


    /**
     * @param int|string $competitorKey
     * @return int|null null if not found
     */
    public function getElo($competitorKey): ?int
    {
        $competitor = $this->eloList[$competitorKey];
        if (empty($competitor)) return null;
        return $competitor->getElo();
    }

    /**
     * @return int[]
     */
    public function getEloList(): array
    {
        $eloList = array();
        foreach($this->eloList as $competitorKey => $competitor) {
            $eloList[$competitorKey] = $this->getElo($competitorKey);
        }
        return $eloList;
    }

}
