<?php

namespace Keiwen\Utils\Competition;

class GameDuel extends AbstractGame
{

    protected $idHome;
    protected $idAway;
    /** @var CompetitionChampionship $affectedChampionship */
    protected $affectedTo = null;
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
     * @param CompetitionChampionship $competition
     * @param int $gameNumber
     * @return bool true if affected
     */
    public function affectTo($competition, int $gameNumber): bool
    {
        if (!$competition instanceof CompetitionChampionship) {
            throw new CompetitionException(sprintf('Duel require %s as affectation, %s given', CompetitionChampionship::class, get_class($competition)));
        }
        return parent::affectTo($competition, $gameNumber);
    }

    public function getChampionship(): ?CompetitionChampionship
    {
        return parent::getAffectation();
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
        if ($this->isAffected() && $this->affectedTo) {
            $this->affectedTo->updateGamesPlayed();
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
