<?php

namespace Keiwen\Utils\Competition;

class GameDuel extends AbstractGame
{

    const RESULT_WON = 'W';
    const RESULT_DRAWN = 'D';
    const RESULT_LOSS = 'L';

    public function __construct(int $seedHome, int $seedAway)
    {
        if ($seedHome == $seedAway) throw new CompetitionException(sprintf('Cannot create duel for similar player (seed %d)', $seedHome));
        parent::setPlayers(array($seedHome, $seedAway));
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
    public function getSeedHome(): int
    {
        return $this->getPlayerThatStartedAt(1);
    }

    /**
     * @return int
     */
    public function getSeedAway(): int
    {
        return $this->getPlayerThatStartedAt(2);
    }

    /**
     * @return bool true if reversed
     */
    public function reverseHomeAway(): bool
    {
        if ($this->isPlayed()) return false;
        $playerSeedList = array_keys($this->players);
        $this->players = array_combine(array_reverse($playerSeedList), range(1, count($this->players)));
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
        $this->setPlayerPerformanceType($this->getSeedHome(), 'score', $scoreHome);
        $this->setPlayerPerformanceType($this->getSeedAway(), 'score', $scoreAway);

        if($scoreHome > $scoreAway) {
            $this->setPlayerResult($this->getSeedHome(), self::RESULT_WON);
            $this->setPlayerResult($this->getSeedAway(), self::RESULT_LOSS);
        } else if ($scoreHome < $scoreAway) {
            $this->setPlayerResult($this->getSeedHome(), self::RESULT_LOSS);
            $this->setPlayerResult($this->getSeedAway(), self::RESULT_WON);
        } else {
            $this->setPlayerResult($this->getSeedHome(), self::RESULT_DRAWN);
            $this->setPlayerResult($this->getSeedAway(), self::RESULT_DRAWN);
        }
        $this->played = true;
        if ($this->isAffected()) {
            $this->affectedTo->updateGamesPlayed();
        }
        return true;
    }

    public function getScoreHome(): int
    {
        return $this->getPlayerPerformanceType($this->getSeedHome(), 'score');
    }

    public function getScoreAway(): int
    {
        return $this->getPlayerPerformanceType($this->getSeedAway(), 'score');
    }

    public function hasHomeWon(): bool
    {
        return $this->getPlayerResult($this->getSeedHome()) == self::RESULT_WON;
    }

    public function hasAwayWon(): bool
    {
        return $this->getPlayerResult($this->getSeedAway()) == self::RESULT_WON;
    }

    public function isDraw(): bool
    {
        return $this->getPlayerResult($this->getSeedHome()) == self::RESULT_DRAWN;
    }

    public function hasPlayerWon(int $playerSeed): bool
    {
        return $this->getPlayerResult($playerSeed) == self::RESULT_WON;
    }


}
