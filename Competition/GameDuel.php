<?php

namespace Keiwen\Utils\Competition;

class GameDuel extends AbstractGame
{

    const RESULT_WON = 'W';
    const RESULT_DRAWN = 'D';
    const RESULT_LOSS = 'L';

    public function __construct(int $idHome, int $idAway)
    {
        if ($idHome == $idAway) throw new CompetitionException(sprintf('Cannot create duel for similar player (id %d)', $idHome));
        parent::setPlayers(array($idHome, $idAway));
    }

    public function getName(): string
    {
        if (!empty($this->name)) return $this->name;
        return $this->getNameWithPlayers();
    }

    /**
     * @return string
     */
    public function getNameWithPlayers(): string
    {
        $players = $this->getFullPlayers();
        $playersNames = array();
        foreach ($players as $player) {
            // set with direct string or numeric
            if (is_string($player) || is_numeric($player)) $playersNames[] = $player;
            // if object, try to get names method
            elseif (is_object($player)) {
                if (method_exists($player, 'getName')) $playersNames[] = $player->getName();
            }
        }
        return join(" - ", $playersNames);
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
     * After game is played, save scores
     * @param int $scoreHome
     * @param int $scoreAway
     * @return bool
     */
    public function setScores(int $scoreHome, int $scoreAway)
    {
        if ($this->isPlayed()) return false;
        $this->setPlayerPerformanceType($this->getIdHome(), 'score', $scoreHome);
        $this->setPlayerPerformanceType($this->getIdAway(), 'score', $scoreAway);

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
        if ($this->isAffected()) {
            $this->affectedTo->updateGamesPlayed();
        }
        return true;
    }

    public function getScoreHome(): int
    {
        return $this->getPlayerPerformanceType($this->getIdHome(), 'score');
    }

    public function getScoreAway(): int
    {
        return $this->getPlayerPerformanceType($this->getIdAway(), 'score');
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

    public function hasPlayerWon(int $idPlayer): bool
    {
        return $this->getPlayerResult($idPlayer) == self::RESULT_WON;
    }


}
