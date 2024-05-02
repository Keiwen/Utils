<?php

namespace Keiwen\Utils\Competition;


abstract class AbstractFixedCalendarCompetition extends AbstractCompetition
{
    protected $calendar;

    protected $nextRoundNumber = 1;


    public function __construct(array $players)
    {
        parent::__construct($players);
        $this->generateCalendar();
        $this->consolidateCalendar();
    }

    abstract protected function generateCalendar();


    protected function consolidateCalendar()
    {
        $gameNumber = 1;
        foreach ($this->calendar as $round => $gamesOfTheRound) {
            foreach ($gamesOfTheRound as $index => $game) {
                // for each game, give a number to order it
                /** @var AbstractGame $game */
                $game->affectTo($this, $gameNumber);
                $this->gameRepository[$gameNumber] = array(
                    'round' => $round,
                    'index' => $index,
                );
                $gameNumber++;
            }
        }
    }

    /**
     * @return array round => [games]
     */
    public function getCalendar(): array
    {
        return $this->calendar;
    }

    /**
     * get games for given round
     * @param int $round
     * @return AbstractGame[] games of the round
     */
    public function getGamesByRound(int $round): array
    {
        return $this->calendar[$round] ?? array();
    }

    /**
     * get game with a given number
     * @param int $gameNumber
     * @return AbstractGame|null game if found
     */
    public function getGameByNumber(int $gameNumber): ?AbstractGame
    {
        if (!isset($this->gameRepository[$gameNumber])) return null;
        $round = $this->gameRepository[$gameNumber]['round'] ?? 0;
        $index = $this->gameRepository[$gameNumber]['index'] ?? 0;
        if (empty($round)) return null;
        if (!isset($this->calendar[$round])) return null;
        return $this->calendar[$round][$index] ?? null;
    }


    /**
     * @return AbstractGame[]
     */
    public function getGames(): array
    {
        $games = array();
        for ($i = 1; $i <= $this->getGameCount(); $i++) {
            $games[] = $this->getGameByNumber($i);
        }
        return $games;
    }


    /**
     * @param int $gameNumber
     * @return int|null round number if found
     */
    public function getGameRound(int $gameNumber): ?int
    {
        if (!isset($this->gameRepository[$gameNumber])) return null;
        return $this->gameRepository[$gameNumber]['round'] ?? null;
    }


    /**
     * @param int $gameNumber
     */
    protected function setNextGame(int $gameNumber)
    {
        parent::setNextGame($gameNumber);
        $this->nextRoundNumber = $this->getGameRound($gameNumber);
        if (empty($this->nextRoundNumber)) $this->nextRoundNumber = -1;
    }


    protected function roundGapInCalendar(int $currentRound, int $roundGap): int
    {
        $nextRound = $currentRound + $roundGap;
        if ($nextRound > $this->roundCount) $nextRound -= $this->roundCount;
        if ($nextRound < 1) $nextRound += $this->roundCount;
        return $nextRound;
    }

    /**
     * @return bool
     */
    public function canGameBeAdded(): bool
    {
        return false;
    }

    /**
     * Call this method if players needs to be re-seeded after completing a round
     * Only if GameDuel are used
     */
    protected function reseedPlayers()
    {
        $lastRoundGames = $this->getGamesByRound($this->currentRound);
        foreach ($lastRoundGames as $game) {
            if (!($game instanceof GameDuel)) continue;
            if ($game->hasAwayWon()) {
                // switch both seeds
                $homeSeed = $this->getPlayerSeed($game->getKeyHome());
                $this->playersSeeds[$game->getKeyHome()] = $homeSeed + 1;
                $this->playersSeeds[$game->getKeyAway()] = $homeSeed;
            }
        }
        // call back order rankings
        $this->rankingsHolder->computeRankingsOrder();
    }



    /**
     * @param int|string $playerKey
     * @param int $seed
     * @return bool
     */
    public function canPlayerReachSeed($playerKey, int $seed): bool
    {
        // in re-seeding competitions, you can reach seed if you have enough games
        $playerRanking = $this->rankingsHolder->getRanking($playerKey);
        if (empty($playerRanking)) return false;
        $playerRank = $this->rankingsHolder->getEntityRank($playerKey);
        $toBePlayedForPlayer = $this->getMaxGameCountByPlayer($playerKey) - $playerRanking->getPlayed();
        $canReach = ($toBePlayedForPlayer >= ($playerRank - $seed));
        return $canReach;
    }

    /**
     * @param int|string $playerKey
     * @param int $seed
     * @return bool
     */
    public function canPlayerDropToSeed($playerKey, int $seed): bool
    {
        // in re-seeding competitions, you can reach seed if you have enough games
        $playerRanking = $this->rankingsHolder->getRanking($playerKey);
        if (empty($playerRanking)) return false;
        $playerRank = $this->rankingsHolder->getEntityRank($playerKey);
        $toBePlayedForPlayer = $this->getMaxGameCountByPlayer($playerKey) - $playerRanking->getPlayed();
        $canDrop = ($toBePlayedForPlayer >= ($seed - $playerRank));
        return $canDrop;
    }




}
