<?php

namespace Keiwen\Utils\Competition;

class GameDuel extends AbstractGame
{

    const RESULT_WON = 'W';
    const RESULT_DRAWN = 'D';
    const RESULT_LOSS = 'L';

    public function __construct($keyHome, $keyAway)
    {
        if ($keyHome == $keyAway) throw new CompetitionException(sprintf('Cannot create duel for similar player (key %d)', $keyHome));
        parent::setPlayers(array($keyHome, $keyAway));
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
        $players = $this->getPlayers();
        $playersNames = array();
        foreach ($players as $startingOrder => $player) {
            // set with direct string or numeric
            if (is_string($player) || is_numeric($player)) {
                $playersNames[] = $player;
            }
            // if object, try to get names method
            elseif (is_object($player)) {
                if (method_exists($player, 'getName')) $playersNames[] = $player->getName();
            // set with keys
            } else {
                $playersNames[] = $this->getPlayerKeyThatStartedAt($startingOrder);
            }
        }
        return join(" - ", $playersNames);
    }


    /**
     * @return int|string
     */
    public function getKeyHome()
    {
        return $this->getPlayerKeyThatStartedAt(1);
    }

    /**
     * @return int|string
     */
    public function getKeyAway()
    {
        return $this->getPlayerKeyThatStartedAt(2);
    }

    /**
     * @return bool true if reversed
     */
    public function reverseHomeAway(): bool
    {
        if ($this->isPlayed()) return false;
        $playerKeyList = array_keys($this->playersStartingOrder);
        $this->setPlayers(array_reverse($playerKeyList));
        return true;
    }

    /**
     * After game is played, save scores
     * @param int $scoreHome
     * @param int $scoreAway
     * @return bool true if set
     */
    public function setScores(int $scoreHome, int $scoreAway): bool
    {
        if ($this->isPlayed()) return false;
        $this->setPlayerPerformanceType($this->getKeyHome(), 'score', $scoreHome);
        $this->setPlayerPerformanceType($this->getKeyAway(), 'score', $scoreAway);

        if($scoreHome > $scoreAway) {
            $this->setPlayerResult($this->getKeyHome(), self::RESULT_WON);
            $this->setPlayerResult($this->getKeyAway(), self::RESULT_LOSS);
        } else if ($scoreHome < $scoreAway) {
            $this->setPlayerResult($this->getKeyHome(), self::RESULT_LOSS);
            $this->setPlayerResult($this->getKeyAway(), self::RESULT_WON);
        } else {
            $this->setPlayerResult($this->getKeyHome(), self::RESULT_DRAWN);
            $this->setPlayerResult($this->getKeyAway(), self::RESULT_DRAWN);
        }
        $this->played = true;
        if ($this->isAffected()) {
            $this->affectedTo->updateGamesPlayed();
        }
        return true;
    }

    public function getScoreHome(): int
    {
        return $this->getPlayerPerformanceType($this->getKeyHome(), 'score');
    }

    public function getScoreAway(): int
    {
        return $this->getPlayerPerformanceType($this->getKeyAway(), 'score');
    }

    public function hasHomeWon(): bool
    {
        return $this->getPlayerResult($this->getKeyHome()) == self::RESULT_WON;
    }

    public function hasAwayWon(): bool
    {
        return $this->getPlayerResult($this->getKeyAway()) == self::RESULT_WON;
    }

    public function isDraw(): bool
    {
        return $this->getPlayerResult($this->getKeyHome()) == self::RESULT_DRAWN;
    }

    public function hasPlayerWon($playerKey): bool
    {
        return $this->getPlayerResult($playerKey) == self::RESULT_WON;
    }


}
