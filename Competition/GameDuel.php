<?php

namespace Keiwen\Utils\Competition;

class GameDuel extends AbstractGame
{

    const RESULT_WON = 'W';
    const RESULT_DRAWN = 'D';
    const RESULT_LOSS = 'L';

    public function __construct(int $ordHome, int $ordAway)
    {
        if ($ordHome == $ordAway) throw new CompetitionException(sprintf('Cannot create duel for similar player (ord %d)', $ordHome));
        parent::setPlayers(array($ordHome, $ordAway));
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
    public function getOrdHome(): int
    {
        return $this->getPlayerThatStartedAt(1);
    }

    /**
     * @return int
     */
    public function getOrdAway(): int
    {
        return $this->getPlayerThatStartedAt(2);
    }

    /**
     * @return bool true if reversed
     */
    public function reverseHomeAway(): bool
    {
        if ($this->isPlayed()) return false;
        $playerOrdList = array_keys($this->players);
        $this->players = array_combine(array_reverse($playerOrdList), range(1, count($this->players)));
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
        $this->setPlayerPerformanceType($this->getOrdHome(), 'score', $scoreHome);
        $this->setPlayerPerformanceType($this->getOrdAway(), 'score', $scoreAway);

        if($scoreHome > $scoreAway) {
            $this->setPlayerResult($this->getOrdHome(), self::RESULT_WON);
            $this->setPlayerResult($this->getOrdAway(), self::RESULT_LOSS);
        } else if ($scoreHome < $scoreAway) {
            $this->setPlayerResult($this->getOrdHome(), self::RESULT_LOSS);
            $this->setPlayerResult($this->getOrdAway(), self::RESULT_WON);
        } else {
            $this->setPlayerResult($this->getOrdHome(), self::RESULT_DRAWN);
            $this->setPlayerResult($this->getOrdAway(), self::RESULT_DRAWN);
        }
        $this->played = true;
        if ($this->isAffected()) {
            $this->affectedTo->updateGamesPlayed();
        }
        return true;
    }

    public function getScoreHome(): int
    {
        return $this->getPlayerPerformanceType($this->getOrdHome(), 'score');
    }

    public function getScoreAway(): int
    {
        return $this->getPlayerPerformanceType($this->getOrdAway(), 'score');
    }

    public function hasHomeWon(): bool
    {
        return $this->getPlayerResult($this->getOrdHome()) == self::RESULT_WON;
    }

    public function hasAwayWon(): bool
    {
        return $this->getPlayerResult($this->getOrdAway()) == self::RESULT_WON;
    }

    public function isDraw(): bool
    {
        return $this->getPlayerResult($this->getOrdHome()) == self::RESULT_DRAWN;
    }

    public function hasPlayerWon(int $playerOrd): bool
    {
        return $this->getPlayerResult($playerOrd) == self::RESULT_WON;
    }


}
