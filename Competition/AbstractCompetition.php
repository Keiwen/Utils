<?php

namespace Keiwen\Utils\Competition;

use Keiwen\Utils\Elo\EloBrawl;
use Keiwen\Utils\Elo\EloDuel;
use Keiwen\Utils\Elo\EloRace;
use Keiwen\Utils\Elo\EloRating;
use Keiwen\Utils\Elo\EloSystem;
use Keiwen\Utils\Mutator\ArrayMutator;

abstract class AbstractCompetition
{
    /** @var array $playersSeeds key => seed */
    protected $playersSeeds;
    /** @var array $players key => player */
    protected $players;
    protected $playerCount;
    protected $roundCount = 1;
    protected $currentRound = 0;
    /** @var array $teamComp team key => array of player keys */
    protected $teamComp = array();

    protected $promotionSpots = 0;
    protected $relegationSpots = 0;

    /** @var array $playerEliminationRound key => round on which player has been eliminated */
    protected $playerEliminationRound = array();

    /** @var AbstractGame[] $gameRepository */
    protected $gameRepository = array();
    protected $nextGameNumber = 1;

    /** @var AbstractRanking[] $rankings */
    protected $rankings = array();
    /** @var AbstractRanking[] $orderedRankings */
    protected $orderedRankings = array();
    /** @var AbstractRanking[] $teamRankings */
    protected $teamRankings = array();

    protected $playerEloAccess = '';
    protected $usingEloRating = false;
    protected $usingEloInt = false;
    protected $usingEloArray = false;

    public function __construct(array $players)
    {
        if (count($players) < static::getMinPlayerCount()) throw new CompetitionException(sprintf('Cannot create competition with less than %d players', static::getMinPlayerCount()));
        $this->initializePlayers($players);
        // initialize rankings;
        $this->initializeRanking();
        $this->orderedRankings = $this->orderRankings($this->rankings);
        $this->teamRankings = $this->computeTeamRankings();
    }

    protected function initializePlayers(array $players)
    {
        $this->playerCount = count($players);
        $this->players = $players;
        $this->playersSeeds = array_combine(array_keys($players), range(1, count($players)));
    }

    protected function initializeRanking()
    {
        foreach ($this->playersSeeds as $key => $seed) {
            $this->rankings[$key] = $this->initializePlayerRanking($key, $seed);
        }
    }

    abstract protected function initializePlayerRanking($playerKey, int $playerSeed = 0): AbstractRanking;

    abstract public static function getMinPlayerCount(): int;

    public function getPlayerCount(): int
    {
        return $this->playerCount;
    }

    public function getTeamCount(): int
    {
        return count($this->teamComp);
    }

    /**
     * @param array $teamComp team key => players keys
     */
    public function setTeamComposition(array $teamComp)
    {
        $this->teamComp = $teamComp;
        $this->teamRankings = $this->computeTeamRankings();
    }

    /**
     * @return array team key => players keys
     */
    public function getTeamComposition(): array
    {
        return $this->teamComp;
    }

    public function getGameCount(): int
    {
        return count($this->gameRepository);
    }

    public function getMinGameCountByPlayer(): int
    {
        return $this->getRoundCount();
    }

    /**
     * @param int|string $playerKey specify to get max game count for a specific player (if competition includes some elimination)
     * @return int
     */
    public function getMaxGameCountByPlayer($playerKey = null): int
    {
        $eliminationRound = 0;
        if (!empty($playerKey)) $eliminationRound = $this->getPlayerEliminationRound($playerKey);
        return $eliminationRound ?: $this->getRoundCount();
    }

    /**
     * @param int|string $playerKey
     * @param int $round
     */
    protected function setPlayerEliminationRound($playerKey, int $round)
    {
        $this->playerEliminationRound[$playerKey] = $round;
    }

    /**
     * @param int|string $playerKey
     * @return int 0 if not eliminated
     */
    protected function getPlayerEliminationRound($playerKey): int
    {
        return $this->playerEliminationRound[$playerKey] ?? 0;
    }


    /**
     * @return AbstractGame[]
     */
    public function getGames(): array
    {
        return $this->gameRepository;
    }

    public function getRoundCount(): int
    {
        return $this->roundCount;
    }

    public function getCurrentRound(): int
    {
        return $this->currentRound;
    }

    /**
     * @param int|string $playerKey
     * @return int 0 if not found
     */
    public function getPlayerSeed($playerKey): int
    {
        return $this->playersSeeds[$playerKey] ?? 0;
    }

    /**
     * @param int|string $teamKey
     * @return int 0 if not found
     */
    public function getTeamSeed($teamKey): int
    {
        if (!isset($this->teamComp[$teamKey])) return 0;
        $teams = array_keys($this->teamComp);
        // now have index => team key
        $teams = array_flip($teams);
        // now have team key => index
        return $teams[$teamKey] + 1;
    }

    /**
     * @param int $playerSeed
     * @return int|string|null null if not found
     */
    public function getPlayerKeyOnSeed(int $playerSeed)
    {
        $keysBySeed = array_flip($this->playersSeeds);
        return $keysBySeed[$playerSeed] ?? null;
    }

    /**
     * @param int $teamSeed
     * @return int|string|null null if not found
     */
    public function getTeamKeyOnSeed(int $teamSeed)
    {
        $teams = array_keys($this->teamComp);
        // now have index => team key
        return $teams[$teamSeed - 1] ?? null;
    }

    /**
     * @param int $playerSeed
     * @return mixed|null null if not found
     */
    public function getPlayerOnSeed(int $playerSeed)
    {
        return $this->getPlayer($this->getPlayerKeyOnSeed($playerSeed));
    }

    /**
     * @param int|string $teamKey
     * @return array list of player keys
     */
    public function getPlayerKeysInTeam($teamKey): array
    {
        return $this->teamComp[$teamKey] ?? array();
    }

    /**
     * @param bool $ranked
     * @return array
     */
    public function getPlayers(bool $ranked = false): array
    {
        if (!$ranked) return $this->players;

        $rankedList = array();
        $rankings = $this->getRankings();
        foreach ($rankings as $ranking) {
            $nextPlayerKey = $ranking->getEntityKey();
            $nextPlayer = $this->getPlayer($nextPlayerKey);
            if ($nextPlayer !== null) $rankedList[] = $nextPlayer;
        }

        return $rankedList;
    }

    /**
     * @return int[]|string[] seed => key, ordered by seed
     */
    public function getPlayerKeysSeeded(): array
    {
        $keysBySeed = array_flip($this->playersSeeds);
        ksort($keysBySeed);
        return $keysBySeed;
    }

    /**
     * @return array
     */
    public function getTeamKeys(): array
    {
        return array_keys($this->teamComp);
    }

    /**
     * @param int|string $playerKey
     * @return mixed|null if found, player data
     */
    public function getPlayer($playerKey)
    {
        return $this->players[$playerKey] ?? null;
    }

    /**
     * @param int|string $playerKey
     * @param mixed $playerData
     */
    protected function setPlayer($playerKey, $playerData)
    {
        $this->players[$playerKey] = $playerData;
    }

    /**
     * @param int|string $playerKey
     * @return EloRating|null null if not found, not implemented or not EloRating
     */
    public function getPlayerEloRating($playerKey): ?EloRating
    {
        if (empty($this->playerEloAccess)) return null;
        $playerData = $this->getPlayer($playerKey);
        if (is_object($playerData)) {
            if (method_exists($playerData, $this->playerEloAccess)) {
                $eloRating = call_user_func(array($playerData, $this->playerEloAccess));
                if ($eloRating instanceof EloRating) {
                    // flag ELO use to true
                    $this->usingEloRating = true;
                    return $eloRating;
                } else if (is_int($eloRating)) {
                    $this->usingEloInt = true;
                    return new EloRating($eloRating);
                }
            }
        } elseif (is_array($playerData)) {
            if (isset($playerData[$this->playerEloAccess])) {
                $eloRating = $playerData[$this->playerEloAccess];
                if ($eloRating instanceof EloRating) {
                    // flag ELO use to true
                    $this->usingEloRating = true;
                    $this->usingEloArray = true;
                    return $eloRating;
                } else if (is_int($eloRating)) {
                    $this->usingEloInt = true;
                    $this->usingEloArray = true;
                    return new EloRating($eloRating);
                }
            }
            return null;
        }
        return null;
    }

    /**
     * @param int|string $playerKey
     * @param EloRating $eloRating
     * @return bool
     */
    protected function setPlayerElo($playerKey, EloRating $eloRating): bool
    {
        if (empty($this->playerEloAccess)) return false;
        if (!$this->usingEloArray && !$this->usingEloInt) return false;
        $playerData = $this->getPlayer($playerKey);
        if (is_object($playerData) && $this->usingEloInt) {
            // if we have a player object:
            // - we do not need to update anything if we have ELO object (updated by ref)
            // - but we need to update player object if ELo received as int

            // we should received a getter method, try to guess set method
            // replace first 3 letters ('get'?) by 'set'
            $setter = substr_replace($this->playerEloAccess, 'set', 0, 3);
            if (method_exists($playerData, $setter)) {
                call_user_func(array($playerData, $setter), $eloRating->getElo());
                return true;
            }
        } elseif (is_array($playerData) && $this->usingEloArray) {
            // if we have a player array:
            // but always need to update player data
            if (isset($playerData[$this->playerEloAccess])) {
                if ($this->usingEloInt) {
                    $playerData[$this->playerEloAccess] = $eloRating->getElo();
                } else {
                    $playerData[$this->playerEloAccess] = $eloRating;
                }
                $this->setPlayer($playerKey, $playerData);
                return true;
            }
        }
        return false;
    }

    /**
     * Define method to call on player object to retrieve ELO Rating
     * Competition must be provided with players as objects or array,
     * containing a full ELORating object or just the int ELO value.
     *
     * For object player data, specify here the get method to ELO.
     * If you are using ELORating as object, it will be automatically
     * updated by object reference.
     * If you are using ELO as int, your player class should also
     * have a set method (same name, except with 'set' replacing 'get')
     * with the ELO int value as a parameter, so that its value can be updated.
     *
     * For array player data, specify here the key to ELO.
     * The same key will be used to update ELO data.
     *
     * If conditions are satisfied, competition will update player's ELO
     * @param string $method name of method in player class returning ELO | name of the key in player data array returning ELO
     */
    public function setPlayerEloAccess(string $method)
    {
        $this->playerEloAccess = $method;
    }

    public function getPlayerEloAccess(): string
    {
        return $this->playerEloAccess;
    }

    public function isUsingElo(): bool
    {
        return !empty($this->playerEloAccess);
    }


    /**
     * @param string|int $teamKey
     * @return EloRating|null null if not found
     */
    public function getTeamEloRating($teamKey): ?EloRating
    {
        if (!$this->isUsingElo()) return null;
        $playerKeys = $this->getPlayerKeysInTeam($teamKey);
        if (empty($playerKeys)) return null;
        $eloTeam = 0;
        foreach ($playerKeys as $playerKey) {
            $playerElo = $this->getPlayerEloRating($playerKey);
            $eloTeam += $playerElo->getElo();
        }
        return new EloRating($eloTeam / count($playerKeys));
    }


    /**
     * @return EloRating[] player key => EloRating, ordered from best to worst
     */
    public function getEloRankings(): array
    {
        if (!$this->isUsingElo()) return array();
        $playerKeys = $this->getPlayerKeysSeeded();
        $playerElo = array();
        foreach ($playerKeys as $playerKey) {
            $eloRating = $this->getPlayerEloRating($playerKey);
            if (!empty($eloRating)) $playerElo[$playerKey] = $eloRating;
        }
        uasort($playerElo, array(EloRating::class, 'orderEloRating'));
        $playerElo = array_reverse($playerElo, true);
        return $playerElo;
    }


    /**
     * @return EloRating[] team key => EloRating, ordered from best to worst
     */
    public function getTeamEloRankings(): array
    {
        if (!$this->isUsingElo()) return array();
        $teamKeys = array_keys($this->getTeamComposition());
        $teamElo = array();
        foreach ($teamKeys as $teamKey) {
            $eloRating = $this->getTeamEloRating($teamKey);
            if (!empty($eloRating)) $teamElo[$teamKey] = $eloRating;
        }
        uasort($teamElo, array(EloRating::class, 'orderEloRating'));
        $teamElo = array_reverse($teamElo, true);
        return $teamElo;
    }

    protected function updateEloForGame(AbstractGame $game): bool
    {
        if (!$game->isPlayed()) return false;
        if ($game instanceof GameDuel) {
            $eloHome = $this->getPlayerEloRating($game->getKeyHome());
            $eloAway = $this->getPlayerEloRating($game->getKeyAway());
            if (empty($eloHome) || empty($eloAway)) return false;
            $eloDuel = new EloDuel($eloHome, $eloAway);
            $gameHomeResult = $game->getPlayerResult($game->getKeyHome());
            // convert to ELO result
            switch ($gameHomeResult) {
                case GameDuel::RESULT_WON: $gameHomeResult = EloSystem::WIN; break;
                case GameDuel::RESULT_LOSS: $gameHomeResult = EloSystem::LOSS; break;
                case GameDuel::RESULT_DRAWN: $gameHomeResult = EloSystem::TIE; break;
                default: return false;
            }
            $eloDuel->updateElo($gameHomeResult);
            if ($this->usingEloInt || $this->usingEloArray) {
                $this->setPlayerElo($game->getKeyHome(), $eloHome);
                $this->setPlayerElo($game->getKeyAway(), $eloAway);
            }
            return true;
        } elseif ($game instanceof GameRace || $game instanceof GamePerformances) {
            // get keys in resulting order
            if ($game instanceof GameRace) {
                $playerKeys = $game->getPositions();
                $playerKeys = array_values($playerKeys);
            } else if ($game instanceof GamePerformances) {
                $playerKeys = $game->getGameRanks();
                $playerKeys = array_keys($playerKeys);
            }
            // for now I have a list of keys
            $playersElo = array();
            foreach ($playerKeys as $playerKey) {
                $playerElo = $this->getPlayerEloRating($playerKey);
                if (empty($playerElo)) return false;
                $playersElo[$playerKey] = $playerElo;
            }

            // now I have a array of key => ELORating
            $eloRace = new EloRace($playersElo);
            $eloRace->setResult(array_keys($playersElo));
            if ($this->usingEloInt || $this->usingEloArray) {
                $playersElo = $eloRace->getEloRatingList();
                foreach ($playersElo as $playerKey => $playerElo) {
                    $this->setPlayerElo($playerKey, $playerElo);
                }
            }
            return true;
        } elseif ($game instanceof GameBrawl) {
            // get keys
            $playerKeys = $game->getPlayersKeys();
            $playerKeys = array_values($playerKeys);
            $winnerKey = $game->getWinnerKey();
            // remove winner key from list
            ArrayMutator::removeByValue($playerKeys, $winnerKey);
            // create new array with winner key first
            $playerKeys = array_merge(array($winnerKey), $playerKeys);

            // for now I have a list of keys
            $playersElo = array();
            foreach ($playerKeys as $playerKey) {
                $playerElo = $this->getPlayerEloRating($playerKey);
                if (empty($playerElo)) return false;
                $playersElo[$playerKey] = $playerElo;
            }

            // now I have a array of key => ELORating
            $eloBrawl = new EloBrawl($playersElo);
            $eloBrawl->setResult($winnerKey);
            if ($this->usingEloInt || $this->usingEloArray) {
                $playersElo = $eloBrawl->getEloRatingList();
                foreach ($playersElo as $playerKey => $playerElo) {
                    $this->setPlayerElo($playerKey, $playerElo);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * get game with a given number
     * @param int $gameNumber
     * @return AbstractGame|null game if found
     */
    public function getGameByNumber(int $gameNumber): ?AbstractGame
    {
        return $this->gameRepository[$gameNumber - 1] ?? null;
    }


    /**
     * get next game to play
     * @return AbstractGame|null game if found
     */
    public function getNextGame(): ?AbstractGame
    {
        $nextGame = $this->getGameByNumber($this->nextGameNumber);
        if ($nextGame && $nextGame->isPlayed()) {
            $this->updateRankings($this->nextGameNumber, $this->nextGameNumber);
            $this->nextGameNumber++;
            return $this->getNextGame();
        }
        return $nextGame;
    }

    public function updateGamesPlayed()
    {
        $gameNumber = $this->nextGameNumber;
        // check first if championship already done
        if ($gameNumber == -1) return;
        do {
            $nextGamePlayed = false;
            $game = $this->getGameByNumber($gameNumber);
            if ($game && $game->isPlayed()) {
                $nextGamePlayed = true;
                $gameNumber++;
                $this->currentRound = $game->getCompetitionRound();
            }
        } while ($nextGamePlayed);

        if ($gameNumber != $this->nextGameNumber) {
            $this->updateRankings($this->nextGameNumber, $gameNumber - 1);
            $this->setNextGame($gameNumber);
        }
    }


    /**
     * @param int $gameNumber
     */
    protected function setNextGame(int $gameNumber)
    {
        $this->nextGameNumber = $gameNumber;
        if ($gameNumber <= 0 || $gameNumber > $this->getGameCount()) {
            // set to -1 if out of bounds
            $this->nextGameNumber = -1;
        }
    }

    /**
     * @param int $fromGame
     * @param int $toGame
     */
    protected function updateRankings(int $fromGame, int $toGame) {
        for ($gameNumber = $fromGame; $gameNumber <= $toGame; $gameNumber++) {
            $game = $this->getGameByNumber($gameNumber);
            if (!$game) continue;
            $this->updateRankingsForGame($game);
            $this->updateEloForGame($game);
        }
        $this->orderedRankings = $this->orderRankings($this->rankings);
        $this->teamRankings = $this->computeTeamRankings();
    }

    /**
     * @param AbstractRanking[] $rankings
     * @param bool $byExpenses
     * @return AbstractRanking[]
     */
    protected function orderRankings(array $rankings, bool $byExpenses = false): array
    {
        $rankings = array_values($rankings);
        if (!empty($rankings)) {
            $firstRanking = reset($rankings);
            $rankingClass = get_class($firstRanking);

            if ($firstRanking instanceof RankingDuel && !$byExpenses) {
                // update point method before actually order
                foreach ($rankings as $ranking) {
                    /** @var RankingDuel $ranking */
                    $ranking->updatePointMethodCalcul(false);
                    $ranking->updatePointMethodCalcul(true);
                }
            }

            $rankingMethod = $byExpenses ? 'orderRankingsByExpenses' : 'orderRankings';
            usort($rankings, array($rankingClass, $rankingMethod));
            $rankings = array_reverse($rankings);
        }
        return $rankings;
    }

    abstract protected function updateRankingsForGame($game);

    protected function seedGapInPlayers(int $currentSeed, int $seedGap): int
    {
        $nextSeed = $currentSeed + $seedGap;
        if ($nextSeed > $this->playerCount) $nextSeed -= $this->playerCount;
        if ($nextSeed < 1) $nextSeed += $this->playerCount;
        return $nextSeed;
    }


    abstract protected function addGame(): AbstractGame;

    /**
     * @return bool
     */
    public function canGameBeAdded(): bool
    {
        return true;
    }

    /**
     * @param bool $byExpenses
     * @return AbstractRanking[] first to last
     */
    public function getRankings(bool $byExpenses = false): array
    {
        if ($byExpenses) {
            return $this->orderRankings($this->rankings, $byExpenses);
        }
        return $this->orderedRankings;
    }

    /**
     * @param int|string $playerKey
     * @return AbstractRanking|null null if not found
     */
    public function getPlayerRanking($playerKey): ?AbstractRanking
    {
        return $this->rankings[$playerKey] ?? null;
    }

    /**
     * @param bool $byExpenses
     * @return AbstractRanking[] first to last
     */
    public function getTeamRankings(bool $byExpenses = false): array
    {
        if ($byExpenses) {
            return $this->orderRankings($this->teamRankings, $byExpenses);
        }
        return $this->teamRankings;
    }


    /**
     * @param bool $byExpenses
     * @return AbstractRanking[] first to last
     */
    public function computeTeamRankings(): array
    {
        $teamRankings = array();
        $teamSeed = 1;
        foreach ($this->teamComp as $teamKey => $playerKeys) {
            $teamRanking = $this->initializePlayerRanking($teamKey, $teamSeed);
            $playerRankings = array();
            foreach ($playerKeys as $playerKey) {
                $playerRankings[] = $this->rankings[$playerKey];
            }
            $teamRanking->combinedRankings($playerRankings);

            $teamRankings[$teamKey] = $teamRanking;
            $teamSeed++;
        }

        return $this->orderRankings($teamRankings);
    }

    /**
     * @param int|string $playerKey
     * @return bool
     */
    public function canPlayerWin($playerKey): bool
    {
        return $this->canPlayerReachRank($playerKey, 1);
    }

    /**
     * @param int|string $playerKey
     * @param int $rank
     * @return bool
     */
    public function canPlayerReachRank($playerKey, int $rank): bool
    {
        $rankRanking = $this->orderedRankings[$rank - 1] ?? null;
        $playerRanking = $this->rankings[$playerKey] ?? null;
        if (empty($rankRanking) || empty($playerRanking)) return false;
        return $this->canRankingReachRanking($playerRanking, $rankRanking);
    }

    /**
     * @param int|string $playerKey
     * @param int $rank
     * @return bool
     */
    public function canPlayerDropToRank($playerKey, int $rank): bool
    {
        $rankRanking = $this->orderedRankings[$rank - 1] ?? null;
        $playerRanking = $this->rankings[$playerKey] ?? null;
        if (empty($rankRanking) || empty($playerRanking)) return false;
        return $this->canRankingReachRanking($rankRanking, $playerRanking);
    }


    /**
     * @param AbstractRanking $rankingA
     * @param AbstractRanking $rankingB
     * @return bool true if rankingB can have points equal or greater
     */
    protected function canRankingReachRanking(AbstractRanking $rankingA, AbstractRanking $rankingB): bool
    {
        // if A already eliminated, cannot reach B
        if ($this->getPlayerEliminationRound($rankingA->getEntityKey()) > 0) return false;

        // if we do not know how many max point we can score, it's still reachable!
        if (static::getMaxPointForAGame() === -1) return true;

        $toBePlayedA = $this->getMaxGameCountByPlayer($rankingA->getEntityKey()) - $rankingA->getPlayed();
        $maxPointsA = $rankingA->getPoints() + $toBePlayedA * static::getMaxPointForAGame();
        $toBePlayedB = $this->getMaxGameCountByPlayer($rankingB->getEntityKey()) - $rankingB->getPlayed();
        $minPointsB = $rankingB->getPoints() + $toBePlayedB * static::getMinPointForAGame();
        return $maxPointsA >= $minPointsB;
    }

    /**
     * @param int|string $playerKey
     * @return bool
     */
    public function canPlayerLoose($playerKey): bool
    {
        return $this->canPlayerDropToRank($playerKey, 2);
    }

    /**
     * @param int|string $playerKey
     * @return bool
     */
    public function canPlayerBeLast($playerKey): bool
    {
        return $this->canPlayerDropToRank($playerKey, $this->getPlayerCount());
    }


    /**
     * @param int|string $playerKey
     * @return int 0 if not found
     */
    public function getPlayerRank($playerKey): int
    {
        $rank = 0;
        foreach ($this->orderedRankings as $ranking) {
            $rank++;
            if ($ranking->getEntityKey() === $playerKey) return $rank;
        }
        return 0;
    }


    /**
     * @param int|string $playerKey
     * @return int 0 if not found
     */
    public function getPlayerMaxReachableRank($playerKey): int
    {
        if ($this->canPlayerWin($playerKey)) return 1;
        $playerRank = $this->getPlayerRank($playerKey);
        if (empty($playerRank)) return 0;
        for ($rank = $playerRank - 1; $rank > 1; $rank--) {
            if (!$this->canPlayerReachRank($playerKey, $rank)) return ($rank + 1);
        }
        return $rank + 1;
    }

    /**
     * @param int|string $playerKey
     * @return int 0 if not found
     */
    public function getPlayerMaxDroppableRank($playerKey): int
    {
        if ($this->canPlayerBeLast($playerKey)) return $this->getPlayerCount();
        $playerRank = $this->getPlayerRank($playerKey);
        if (empty($playerRank)) return 0;
        for ($rank = $playerRank + 1; $rank < $this->getPlayerCount(); $rank++) {
            if (!$this->canPlayerDropToRank($playerKey, $rank)) return ($rank - 1);
        }
        return $rank - 1;
    }

    /**
     * @param int|string $teamKey
     * @return int 0 if not found
     */
    public function getTeamRank($teamKey): int
    {
        $rank = 0;
        foreach ($this->teamRankings as $ranking) {
            $rank++;
            if ($ranking->getEntityKey() === $teamKey) return $rank;
        }
        return 0;
    }


    /** @return int -1 if no max defined */
    abstract public static function getMaxPointForAGame(): int;
    abstract public static function getMinPointForAGame(): int;


    /**
     * @return bool
     */
    public function isCompleted(): bool
    {
        if ($this->nextGameNumber != -1) return false;
        return $this->getGameCount() != 0;
    }

    /**
     * @return int
     */
    public function getGamesCompletedCount(): int
    {
        if ($this->isCompleted()) return $this->getGameCount();
        if (empty($this->getGameCount())) return 0;
        return $this->nextGameNumber - 1;
    }

    /**
     * @return int
     */
    public function getGamesToPlayCount(): int
    {
        if (empty($this->getGameCount())) return 0;
        return $this->getGameCount() - $this->getGamesCompletedCount();
    }


    public function setPromotionSpots(int $spots)
    {
        $this->promotionSpots = $spots;
    }

    /**
     * Get how many spots are opened for a promotion at the end of the competition
     * @return int
     */
    public function getPromotionSpots(): int
    {
        return $this->promotionSpots;
    }

    /**
     * @return int[]|string[]
     */
    public function getPlayerKeysForPromotion(): array
    {
        $rankedKeys = array();
        $rankings = $this->getRankings();
        $rankings = array_slice($rankings, 0, $this->getPromotionSpots());
        foreach ($rankings as $ranking) {
            $nextPlayerKey = $ranking->getEntityKey();
            if ($nextPlayerKey !== null) $rankedKeys[] = $nextPlayerKey;
        }
        return $rankedKeys;
    }


    public function setRelegationSpots(int $spots)
    {
        $this->relegationSpots = $spots;
    }

    /**
     * Get how many spots are opened for a relegation at the end of the competition
     * @return int
     */
    public function getRelegationSpots(): int
    {
        return $this->relegationSpots;
    }

    /**
     * @return int[]|string[]
     */
    public function getPlayerKeysForRelegation(): array
    {
        if ($this->getRelegationSpots() == 0) return array();
        $rankedKeys = array();
        $rankings = $this->getRankings();
        $rankings = array_slice($rankings, -($this->getRelegationSpots()));
        foreach ($rankings as $ranking) {
            $nextPlayerKey = $ranking->getEntityKey();
            if ($nextPlayerKey !== null) $rankedKeys[] = $nextPlayerKey;
        }
        return $rankedKeys;
    }

    /**
     * @return int[]|string[]
     */
    public function getPlayerKeysForStagnation(): array
    {
        $rankedKeys = array();
        $rankings = $this->getRankings();
        $stagnationCount = $this->playerCount - $this->getPromotionSpots() - $this->getRelegationSpots();
        if ($stagnationCount <= 0) return array();
        $rankings = array_slice($rankings, $this->getPromotionSpots(), $stagnationCount);
        foreach ($rankings as $ranking) {
            $nextPlayerKey = $ranking->getEntityKey();
            if ($nextPlayerKey !== null) $rankedKeys[] = $nextPlayerKey;
        }
        return $rankedKeys;
    }


    /**
     * @param AbstractCompetition $competition
     * @param bool $ranked
     * @return static
     */
    public static function newCompetitionWithSamePlayers(AbstractCompetition $competition, bool $ranked = false): AbstractCompetition
    {
        $newCompetition = new static($competition->getPlayers($ranked));
        $newCompetition->setTeamComposition($competition->getTeamComposition());
        return $newCompetition;
    }


}
