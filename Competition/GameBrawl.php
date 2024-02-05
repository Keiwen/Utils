<?php

namespace Keiwen\Utils\Competition;

class GameBrawl extends AbstractGame
{

    /** @var CompetitionChampionshipBrawl $affectedChampionship */
    protected $affectedTo = null;

    const RESULT_WON = 'W';
    const RESULT_LOSS = 'L';

    public function __construct(array $idPlayers)
    {
        parent::setPlayers($idPlayers);
    }

    /**
     * After game is played, save winner
     * @param int $idPlayer
     * @return bool
     */
    public function setWinner(int $idPlayerWinner)
    {
        if ($this->isPlayed()) return false;
        foreach ($this->players as $idPlayer => $ord) {
            if ($idPlayer == $idPlayerWinner) {
                $this->setPlayerResult($idPlayer, self::RESULT_WON);
            } else {
                $this->setPlayerResult($idPlayer, self::RESULT_LOSS);
            }
        }
        $this->played = true;
        if ($this->isAffected()) {
            $this->affectedTo->updateGamesPlayed();
        }
        return true;
    }

    /**
     * @param int $idPlayer
     * @return bool
     */
    public function hasPlayerWon(int $idPlayer): bool
    {
        return $this->getPlayerResult($idPlayer) == self::RESULT_WON;
    }

}
