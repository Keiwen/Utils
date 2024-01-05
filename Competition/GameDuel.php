<?php

namespace Keiwen\Utils\Competition;

class GameDuel extends AbstractGame
{

    /** @var CompetitionChampionshipDuel $affectedChampionship */
    protected $affectedTo = null;

    const RESULT_WON = 'W';
    const RESULT_DRAWN = 'D';
    const RESULT_LOSS = 'L';

    public function __construct(int $idHome, int $idAway)
    {
        if ($idHome == $idAway) throw new CompetitionException(sprintf('Cannot create duel for similar player (id %d)', $idHome));
        parent::setPlayers(array($idHome, $idAway));
    }

    /**
     * @return int
     */
    public function getIdHome(): int
    {
        return $this->getPlayerThatStartedAt(1);
    }

    /**
     * @return int
     */
    public function getIdAway(): int
    {
        return $this->getPlayerThatStartedAt(2);
    }

    /**
     * @return bool true if reversed
     */
    public function reverseHomeAway(): bool
    {
        if ($this->isPlayed()) return false;
        $idPlayers = array_keys($this->players);
        $this->players = array_combine(array_reverse($idPlayers), range(1, count($this->players)));
        return true;
    }

    /**
     * @param CompetitionChampionshipDuel $competition
     * @param int $gameNumber
     * @return bool true if affected
     */
    public function affectTo($competition, int $gameNumber): bool
    {
        if (!$competition instanceof CompetitionChampionshipDuel) {
            throw new CompetitionException(sprintf('Duel require %s as affectation, %s given', CompetitionChampionshipDuel::class, get_class($competition)));
        }
        return parent::affectTo($competition, $gameNumber);
    }

    public function getChampionship(): ?CompetitionChampionshipDuel
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
        $this->setPlayerPerformance($this->getIdHome(), $scoreHome);
        $this->setPlayerPerformance($this->getIdAway(), $scoreAway);

        if($scoreHome > $scoreAway) {
            $this->setPlayerResult($this->getIdHome(), self::RESULT_WON);
            $this->setPlayerResult($this->getIdAway(), self::RESULT_LOSS);
        } else if ($scoreHome < $scoreAway) {
            $this->setPlayerResult($this->getIdHome(), self::RESULT_LOSS);
            $this->setPlayerResult($this->getIdAway(), self::RESULT_WON);
        } else {
            $this->setPlayerResult($this->getIdHome(), self::RESULT_DRAWN);
            $this->setPlayerResult($this->getIdAway(), self::RESULT_DRAWN);
        }
        $this->played = true;
        if ($this->isAffected() && $this->affectedTo) {
            $this->affectedTo->updateGamesPlayed();
        }
        return true;
    }

    public function getScoreHome(): int
    {
        return $this->getPlayerPerformance($this->getIdHome());
    }

    public function getScoreAway(): int
    {
        return $this->getPlayerPerformance($this->getIdAway());
    }

    public function hasHomeWon(): bool
    {
        return $this->getPlayerResult($this->getIdHome()) == self::RESULT_WON;
    }

    public function hasAwayWon(): bool
    {
        return $this->getPlayerResult($this->getIdAway()) == self::RESULT_WON;
    }

    public function isDraw(): bool
    {
        return $this->getPlayerResult($this->getIdHome()) == self::RESULT_DRAWN;
    }

}
