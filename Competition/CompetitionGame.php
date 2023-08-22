<?php

namespace Keiwen\Utils\Competition;

class CompetitionGame
{

    protected $idHome;
    protected $idAway;
    protected $number = 0;
    protected $played = false;
    protected $affected = false;
    /** @var Championship $affectedChampionship */
    protected $affectedChampionship = null;
    protected $scoreHome = 0;
    protected $scoreAway = 0;


    public function __construct(int $idHome, int $idAway)
    {
        $this->idHome = $idHome;
        $this->idAway = $idAway;
    }

    /**
     * @return bool true if reversed
     */
    public function reverseHomeAway(): bool
    {
        if ($this->isPlayed()) return false;
        $temp = $this->idHome;
        $this->idHome = $this->idAway;
        $this->idAway = $temp;
        return true;
    }

    /**
     * @return int
     */
    public function getIdHome(): int
    {
        return $this->idHome;
    }

    /**
     * @return int
     */
    public function getIdAway(): int
    {
        return $this->idAway;
    }

    /**
     * @return int
     */
    public function getGameNumber(): int
    {
        return $this->number;
    }

    /**
     * @param Championship $championship
     * @param int $gameNumber
     * @return bool true if affected
     */
    public function affectToChampionship(Championship $championship, int $gameNumber): bool
    {
        if ($this->isAffected()) return false;
        $this->affectedChampionship = $championship;
        $this->number = $gameNumber;
        $this->affected = true;
        return true;
    }


    public function isPlayed(): bool
    {
        return $this->played;
    }

    public function isAffected(): bool
    {
        return $this->affected;
    }

    public function getChampionship(): Championship
    {
        return $this->affectedChampionship;
    }

    /**
     * After game is played, save scores
     * @param int $scoreHome
     * @param int $scoreAway
     * @return bool
     */
    public function setScores(int $scoreHome, int $scoreAway)
    {
        if ($this->isPlayed()) return false;
        $this->scoreHome = $scoreHome;
        $this->scoreAway = $scoreAway;
        $this->played = true;
        if ($this->isAffected() && $this->affectedChampionship) {
            $this->affectedChampionship->updateGamesPlayed();
        }
        return true;
    }

    public function getScoreHome(): int
    {
        return $this->scoreHome;
    }

    public function getScoreAway(): int
    {
        return $this->scoreAway;
    }

    public function hasHomeWon(): bool
    {
        if (!$this->isPlayed()) return false;
        return $this->scoreHome > $this->scoreAway;
    }

    public function hasAwayWon(): bool
    {
        if (!$this->isPlayed()) return false;
        return $this->scoreHome < $this->scoreAway;
    }

    public function isDraw(): bool
    {
        if (!$this->isPlayed()) return false;
        return $this->scoreHome == $this->scoreAway;
    }

}
