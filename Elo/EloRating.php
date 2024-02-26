<?php

namespace Keiwen\Utils\Elo;


class EloRating
{

    /** @var EloSystem $globalSystem */
    protected static $globalSystem;

    protected $elo;
    /** @var EloSystem $eloSystem */
    protected $eloSystem;
    protected $gainCount = 0;


    /**
     * EloRating constructor.
     *
     * @param int|null  $elo default (null) to match EloSystem value
     * @param EloSystem $eloSystem
     */
    public function __construct($elo = null, EloSystem $eloSystem = null)
    {
        if($eloSystem === null) $eloSystem = static::getGlobalSystem();
        $this->eloSystem = $eloSystem;
        if(!is_int($elo)) $elo = $eloSystem->getStartingElo();
        $this->elo = $elo;
    }


    /**
     * @param EloSystem $eloSystem
     */
    public static function setGlobalSystem(EloSystem $eloSystem)
    {
        static::$globalSystem = $eloSystem;
    }

    /**
     * @return EloSystem
     */
    public static function getGlobalSystem()
    {
        if(static::$globalSystem === null) {
            static::$globalSystem = new EloSystem();
        }
        return static::$globalSystem;
    }

    /**
     * @param int $gain
     */
    public function gainElo(int $gain) {
        $gain = $this->eloSystem->adjustGainLimit($gain);
        $this->elo += $gain;
        $this->gainCount++;
    }

    /**
     * @return int
     */
    public function getElo()
    {
        return $this->elo;
    }


    /**
     * @return int
     */
    public function getCurrentKFactor()
    {
        return $this->eloSystem->getKFactor($this->gainCount, $this->elo);
    }

    /**
     * @return int
     */
    public function getGainCount()
    {
        return $this->gainCount;
    }


    /**
     * @param int $gainCount
     * @return $this
     */
    public function setGainCount(int $gainCount)
    {
        $this->gainCount = $gainCount;
        return $this;
    }


    /**
     * @return EloSystem
     */
    public function getEloSystem()
    {
        return $this->eloSystem;
    }


    /**
     * @param EloRating $eloA
     * @param EloRating $eloB
     * @return int
     */
    public static function orderEloRating(self $eloA, self $eloB): int
    {
        // more elo is first
        if ($eloA->getElo() > $eloB->getElo()) return 1;
        if ($eloA->getElo() < $eloB->getElo()) return -1;
        return 0;
    }

}
