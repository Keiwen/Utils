<?php
namespace Keiwen\Utils\Competition;

use Keiwen\Utils\Math\Divisibility;

abstract class AbstractTournamentCompetition extends AbstractFixedCalendarCompetition
{
    protected $bestSeedAlwaysHome = false;
    protected $preRoundShuffle = false;

    /** @var GameDuel[] $gameRepository */
    protected $gameRepository = array();


    public function isBestSeedAlwaysHome(): bool
    {
        return $this->bestSeedAlwaysHome;
    }

    public function hasPreRoundShuffle(): bool
    {
        return $this->preRoundShuffle;
    }


    /**
     * @param int $numberOfPlayers
     * @param int $round
     * @return void
     * @throws CompetitionException
     */
    protected function checkPowerOf2NumberOfPlayer(int $numberOfPlayers, int $round)
    {
        $remainder = 0;
        Divisibility::getHighestPowerOf($numberOfPlayers, 2, $remainder);
        if ($remainder > 0) {
            throw new CompetitionException(sprintf('Cannot create next round with a number of players that is not a power of 2, %d given on round %d', $numberOfPlayers, $round));
        }
    }

    /**
     * Considering number of players given, dispatch duels so that first seeds
     * encounters last seeds. Furthermore, dispatch first seeds among this table
     * @param int $playersCount
     * @return array list of duel (array with 'seedHome' and 'seedAway' keys)
     * @throws CompetitionException
     */
    protected function generateDuelTable(int $playersCount): array
    {
        $this->checkPowerOf2NumberOfPlayer($playersCount, 1);

        // for each player in first half, match with last player available
        // should be X (number of player) + 1 - first player seed
        // for 8 players, we will have
        // 1vs8, 2vs7, 3vs6, 4vs5
        // note: do not add games yet because of next step
        $duelTable = array();
        for ($i = 1; $i <= $playersCount / 2; $i++) {
            $duelTable[$i - 1][] = array(
                'seedHome' => $i,
                'seedAway' => $playersCount + 1 - $i,
            );
        }
        // now we want to avoid duel between high seeds until the end
        // to dispatch, each duel are set in a table part.
        // while this table has more than 1 part,
        // second half of parts are put in first half (in reversed order)
        // for 8 players, we started with 4 parts
        // first iteration will give
        // PART1, PART2
        // 1vs8, 2vs7
        // 4vs5, 3vs6 (not 3vs6 and 4vs5, as we reversed)
        // 2nd iteration will give
        // PART1
        // 1vs8
        // 4vs5
        // 2vs7
        // 3vs6
        // note: we always have halves in parts because number of player is power of 2
        while (count($duelTable) > 1) {
            $partCount = count($duelTable);
            for ($i = $partCount / 2; $i < $partCount; $i++) {
                $firstHalfPart = $partCount - $i - 1;
                $duelTable[$firstHalfPart] = array_merge($duelTable[$firstHalfPart], $duelTable[$i]);
                unset($duelTable[$i]);
            }
        }

        // now that all are dispatched, return first and only part
        $duelTable = reset($duelTable);
        return $duelTable;
    }


    /**
     * @param int $round
     * @param array $losers
     * @return int[]|string[]
     */
    public function getRoundWinners(int $round, array &$losers = array()): array
    {
        $gamesInRound = $this->getGamesByRound($round);
        $winnerKeys = array();
        $losers = array();
        foreach ($gamesInRound as $game) {
            if (!$game->isPlayed()) continue;
            $winnerKeys[] = $game->hasAwayWon() ? $game->getKeyAway() : $game->getKeyHome();
            // we should not have drawn on tournament
            // but if drawn set, we consider that home won
            $loserKey = $game->hasAwayWon() ? $game->getKeyHome() : $game->getKeyAway();
            // ignore loser if null => bye game
            if ($loserKey !== null && $loserKey !== '') $losers[] = $loserKey;
        }
        return $winnerKeys;
    }


    /**
     * add games by matching given players 2 by 2, in received order
     *
     * @param array $playerKeys
     * @throws CompetitionException
     */
    protected function matchPlayers2By2(array $playerKeys)
    {
        $playerKeys = array_values($playerKeys);
        for ($i = 0; $i < count($playerKeys); $i += 2) {
            $this->addGame($playerKeys[$i], $playerKeys[$i + 1], $this->currentRound);
        }
    }


    public function getMinGameCountByPlayer(): int
    {
        return 1;
    }

    public static function getMinPlayerCount(): int
    {
        return 4;
    }


    /**
     * @param int|string $playerKey
     * @param int $playerSeed
     * @return RankingDuel
     */
    protected function initializePlayerRanking($playerKey, int $playerSeed = 0): AbstractRanking
    {
        $ranking =  new RankingDuel($playerKey, $playerSeed);
        $ranking->affectTo($this);
        return $ranking;
    }


    /**
     * get games for given round
     * @param int $round
     * @return GameDuel[] games of the round
     */
    public function getGamesByRound(int $round): array
    {
        return parent::getGamesByRound($round);
    }

    /**
     * get game with a given number
     * @param int $gameNumber
     * @return GameDuel|null game if found
     */
    public function getGameByNumber(int $gameNumber): ?AbstractGame
    {
        return parent::getGameByNumber($gameNumber);
    }

    /**
     * @return GameDuel[]
     */
    public function getGames(): array
    {
        return parent::getGames();
    }

    /**
     * @param int|string $keyHome
     * @param int|string $keyAway
     * @param int $round
     * @return GameDuel
     * @throws CompetitionException
     */
    protected function addGame($keyHome = 1, $keyAway = 2, int $round = 1): AbstractGame
    {
        if ($keyAway !== null && $this->isBestSeedAlwaysHome()) {
            $seedHome = $this->getPlayerSeed($keyHome);
            $seedAway = $this->getPlayerSeed($keyAway);
            if ($seedAway < $seedHome) {
                $tempKey = $keyAway;
                $keyAway = $keyHome;
                $keyHome = $tempKey;
            }
        }
        $gameDuel = new GameDuel($keyHome, $keyAway);
        $gameDuel->setCompetitionRound($round);
        $this->calendar[$round][] = $gameDuel;
        return $gameDuel;
    }

    /**
     * @param GameDuel $game
     */
    protected function updateRankingsForGame($game)
    {
        if (isset($this->rankings[$game->getKeyHome()])) {
            ($this->rankings[$game->getKeyHome()])->saveGame($game);
        }
        if (isset($this->rankings[$game->getKeyAway()])) {
            ($this->rankings[$game->getKeyAway()])->saveGame($game);
        }
    }

    public static function getMaxPointForAGame(): int
    {
        return RankingDuel::getPointsForWon(true);
    }


    public static function getMinPointForAGame(): int
    {
        return RankingDuel::getPointsForLoss(true);
    }



}
