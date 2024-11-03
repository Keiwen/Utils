<?php

namespace Keiwen\Utils\Competition\Game;

use Keiwen\Utils\Competition\Exception\CompetitionException;

class GameDuel extends AbstractGame
{

    const RESULT_WON = 'W';
    const RESULT_DRAWN = 'D';
    const RESULT_LOSS = 'L';

    protected $forfeit = false;
    protected $bye = false;

    protected static $forfeitScoreFor = 3;

    public function __construct($keyHome, $keyAway)
    {
        if ($keyHome === $keyAway) throw new CompetitionException(sprintf('Cannot create duel for similar player (key %d)', $keyHome));
        if ($keyAway === null) $this->bye = true;
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
        if ($this->isByeGame()) {
            return $playersNames[0] . ' (BYE)';
        }
        return join(" - ", $playersNames);
    }

    public function hasForfeit(): bool
    {
        return $this->forfeit;
    }

    public function isByeGame(): bool
    {
        return $this->bye;
    }

    public static function getForfeitScoreFor(): int
    {
        return static::$forfeitScoreFor;
    }

    public static function setForfeitScoreFor(int $scoreFor)
    {
        static::$forfeitScoreFor = $scoreFor;
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
        if ($this->isByeGame()) return $this->setEndOfBye();

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


    /**
     * After game is 'played', while a player won by forfeit
     * @param bool $awayPlayer
     * @return bool
     */
    public function setForfeit(bool $awayPlayer = true): bool
    {
        if ($this->isPlayed()) return false;
        if ($awayPlayer) {
            $this->setPlayerPerformanceType($this->getKeyHome(), 'score', static::getForfeitScoreFor());
            $this->setPlayerPerformanceType($this->getKeyAway(), 'score', 0);
            $this->setPlayerResult($this->getKeyHome(), self::RESULT_WON);
            $this->setPlayerResult($this->getKeyAway(), self::RESULT_LOSS);
        } else {
            $this->setPlayerPerformanceType($this->getKeyHome(), 'score', 0);
            $this->setPlayerPerformanceType($this->getKeyAway(), 'score', static::getForfeitScoreFor());
            $this->setPlayerResult($this->getKeyHome(), self::RESULT_LOSS);
            $this->setPlayerResult($this->getKeyAway(), self::RESULT_WON);
        }
        $this->forfeit = true;
        $this->played = true;
        if ($this->isAffected()) {
            $this->affectedTo->updateGamesPlayed();
        }
        return true;
    }


    /**
     * flag game as ended if it's a bye
     * @return bool
     */
    public function setEndOfBye(): bool
    {
        if (!$this->isByeGame()) return false;
        if ($this->isPlayed()) return false;
        $this->setPlayerResult($this->getKeyHome(), self::RESULT_WON);
        $this->played = true;
        if ($this->isAffected()) {
            $this->affectedTo->updateGamesPlayed();
        }
        return true;
    }


    public function getScoreHome(): int
    {
        $score = $this->getPlayerPerformanceType($this->getKeyHome(), 'score');
        return $score ?? 0;
    }

    public function getScoreAway(): int
    {
        $score = $this->getPlayerPerformanceType($this->getKeyAway(), 'score');
        return $score ?? 0;
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
