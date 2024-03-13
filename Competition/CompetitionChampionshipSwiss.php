<?php

namespace Keiwen\Utils\Competition;

use Keiwen\Utils\Math\Divisibility;

class CompetitionChampionshipSwiss extends AbstractFixedCalendarCompetition
{

    /** @var GameDuel[] $gameRepository */
    protected $gameRepository = array();

    /**
     * @param array $players
     * @param int $roundCount cannot be less than 2, neither equal or more than player count. Generally between 3 and 9.
     */
    public function __construct(array $players, int $roundCount)
    {
        if ($roundCount < 2) throw new CompetitionException('Cannot create competition with less than 2 rounds');
        if ($roundCount >= count($players)) throw new CompetitionException('Cannot create competition with more rounds than players');
        $this->roundCount = $roundCount;
        parent::__construct($players);
    }

    public static function getMinPlayerCount(): int
    {
        return 3;
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

    protected function generateCalendar(): void
    {
        // generate first round
        $this->generateNextRoundGames();
    }

    protected function generateNextRoundGames()
    {
        $this->currentRound++;

        /** @var RankingDuel[] $rankings */
        $rankings = $this->getRankings();

        // if odd player, the last has a bye (if not already received)
        if (Divisibility::isNumberOdd(count($rankings))) {
            for ($i = (count($rankings) - 1); $i > 0; $i++) {
                // check if player had already received a bye
                if (($rankings[$i])->getWonBye() > 0) continue;

                // this player did not received any bye
                $byeGame = $this->addGame(($rankings[$i])->getEntityKey(), null, $this->currentRound);
                $byeGame->setEndOfBye();

                // remove for further duels
                unset($rankings[$i]);
                $rankings = array_values($rankings);
                break;
            }
        }

        // dispatch games for each other players.
        $this->nextRoundMatchmaking($rankings);

        // consolidate calendar after each round games generation
        $this->consolidateCalendar();
    }


    /**
     * With given rankings, generates all games for next round,
     * considering even count of players (or set a bye before)
     * @param RankingDuel[] $rankings
     */
    protected function nextRoundMatchmaking(array $rankings)
    {
        // do it game by game here
        while (count($rankings) > 0) {
            // reload array values to have 'clean' keys to loop on
            $rankings = array_values($rankings);
            $this->nextGameMatchmaking($rankings);
        }
    }


    /**
     * with given rankings, generate next game
     * Be sure to remove both rankings from the list after matchmaking
     * @param RankingDuel[] $rankings
     * @return GameDuel|null
     * @throws CompetitionException
     */
    protected function nextGameMatchmaking(array &$rankings): ?GameDuel
    {
        if (count($rankings) < 2) {
            $rankings = array();
            return null;
        }

        // match player with another player with ~same rank
        // get next non-already-matched in rankings
        $homeRanking = $rankings[0];
        $found = false;
        for ($i = 1; $i < (count($rankings) - 1); $i++) {
            $awayRanking = $rankings[$i];
            if (!$homeRanking->hasOpponent($awayRanking->getEntityKey())) {
                $found = true;
                break;
            }
        }
        // if no possibilities, match with first player
        if (!$found) {
            $i = 1;
            $awayRanking = $rankings[$i];
        }

        $game = $this->addGame($homeRanking->getEntityKey(), $awayRanking->getEntityKey(), $this->currentRound);
        unset($rankings[0], $rankings[$i]);
        return $game;
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


    public function updateGamesPlayed()
    {
        parent::updateGamesPlayed();

        if ($this->nextGameNumber == -1) {
            // we run out of games, check if new game needed
            if ($this->currentRound >= $this->roundCount) {
                // if current round is above defined round count, it's done!
                return;
            }
            $lastGameNumber = count($this->gameRepository);
            $this->generateNextRoundGames();

            // call back setNextGame with last game number
            $this->setNextGame($lastGameNumber + 1);
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

    /**
     * @param CompetitionChampionshipSwiss $competition
     * @param bool $ranked
     * @return CompetitionChampionshipSwiss
     * @throws CompetitionException
     */
    public static function newCompetitionWithSamePlayers(AbstractCompetition $competition, bool $ranked = false): AbstractCompetition
    {
        $newCompetition = new CompetitionChampionshipSwiss($competition->getPlayers($ranked), $competition->getRoundCount());
        $newCompetition->setTeamComposition($competition->getTeamComposition());
        return $newCompetition;
    }

}
