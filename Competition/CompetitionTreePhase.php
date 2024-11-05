<?php

namespace Keiwen\Utils\Competition;


use Keiwen\Utils\Competition\Exception\CompetitionRankingException;
use Keiwen\Utils\Competition\Game\AbstractGame;
use Keiwen\Utils\Competition\Ranking\AbstractRanking;
use Keiwen\Utils\Competition\Type\AbstractCompetition;

class CompetitionTreePhase
{
    /** @var string */
    protected $name = '';
    /** @var AbstractCompetition[] */
    protected $groups = array();
    protected $completed = false;


    /**
     * @param AbstractCompetition[] $groups
     */
    public function __construct(string $name, array $groups)
    {
        $this->name = $name;

        foreach ($groups as $name => $group) {
            if ($group instanceof AbstractCompetition) $this->groups[$name] = $group;
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return AbstractCompetition[]
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    public function getGroup(string $name): ?AbstractCompetition
    {
        return $this->groups[$name] ?? null;
    }

    public function isCompleted(): bool
    {
        if ($this->completed) return true;
        $nextGame = $this->getNextGame();
        return empty($nextGame);
    }


    public function getCurrentRound(): int
    {
        if ($this->completed) return -1;
        $nextGame = $this->getNextGame();
        if (empty($nextGame)) return -1;
        return $nextGame->getCompetitionRound();
    }


    /**
     * @param string|null $groupName if next game found, set with group name
     * @return AbstractGame|null
     */
    public function getNextGame(string &$groupName = null): ?AbstractGame
    {
        if ($this->completed) return null;
        $nextRound = -1;
        $nextGameCandidate = null;
        foreach ($this->groups as $name => $group) {
            $nextGameInGroup = $group->getNextGame();
            if (empty($nextGameInGroup)) continue;
            if ($nextRound == -1 || $nextRound > $nextGameInGroup->getCompetitionRound()) {
                $nextRound = $nextGameInGroup->getCompetitionRound();
                $nextGameCandidate = $nextGameInGroup;
                $groupName = $name;
            }
        }
        if ($nextGameCandidate === null) {
            // actually it's all completed!
            $this->completed = true;
        }
        return $nextGameCandidate;
    }

    /**
     * @param int $round
     * @return AbstractGame[]
     */
    public function getGamesByRound(int $round): array
    {
        $games = array();
        foreach ($this->groups as $group) {
            $roundGamesInGroup = $group->getGamesByRound($round);
            $games = array_merge($games, $roundGamesInGroup);
        }
        return $games;
    }

    public function getGameCount(): int
    {
        $sum = 0;
        foreach ($this->groups as $group) {
            $sum += $group->getGameCount();
        }
        return $sum;
    }

    public function getGamesCompletedCount(): int
    {
        $sum = 0;
        foreach ($this->groups as $group) {
            $sum += $group->getGamesCompletedCount();
        }
        return $sum;
    }

    public function getGamesToPlayCount(): int
    {
        $gameCount = $this->getGameCount();
        if ($gameCount) return 0;
        return $gameCount - $this->getGamesCompletedCount();
    }

    public function getRoundCount(): int
    {
        $sum = 0;
        foreach ($this->groups as $group) {
            $sum += $group->getRoundCount();
        }
        return $sum;
    }


    public function getRankings(bool $byExpenses = false): array
    {
        $rankings = array();
        foreach ($this->groups as $name => $group) {
            $rankings[$name] = $group->getRankings($byExpenses);
        }
        return $rankings;
    }

    public function getTeamRankings(): array
    {
        $rankings = array();
        foreach ($this->groups as $name => $group) {
            $rankings[$name] = $group->getTeamRankings();
        }
        return $rankings;
    }


    /**
     * @param bool $forTeam false by default
     * @param bool $byExpenses
     * @return AbstractRanking[]
     * @throws CompetitionRankingException
     */
    protected function mixGroupRankings(bool $forTeam = false, bool $byExpenses = false): array
    {
        $allRankings = ($forTeam) ? $this->getTeamRankings() : $this->getRankings();
        if (empty($allRankings)) return array();
        $firstGroupRankings = reset($allRankings);
        $firstGroupName = array_key_first($allRankings);
        if (empty($firstGroupRankings)) return array();
        /** @var AbstractRanking $firstRanking */
        $firstRanking = reset($firstGroupRankings);
        if (empty($firstRanking)) return array();
        $firstGroup = $this->getGroup($firstGroupName);
        if (empty($firstGroup)) return array();
        $rankingHolder = $firstGroup->getRankingsHolder();
        $mixedRankingHolder = $rankingHolder->duplicateEmptyHolder();
        try {
            foreach ($allRankings as $groupRankings) {
                foreach ($groupRankings as $ranking) {
                    /** @var AbstractRanking $ranking */
                    $mixedRanking = clone $ranking;
                    $mixedRankingHolder->integrateRanking($mixedRanking);
                }
            }
        } catch (CompetitionRankingException $e) {
            throw new CompetitionRankingException(sprintf('Cannot build mixed rankings: %s', $e->getMessage()));
        }

        $mixedRankingHolder->computeRankingsOrder();
        // even for team we need to use getRankings here and not teamRankings
        return ($byExpenses && !$forTeam) ? $mixedRankingHolder->getRankingsByExpenses() : $mixedRankingHolder->getRankings();
    }



    /**
     * @param bool $byExpenses
     * @return AbstractRanking[]
     * @throws CompetitionRankingException
     */
    public function getMixedRankings(bool $byExpenses = false): array
    {
        return $this->mixGroupRankings(false, $byExpenses);
    }



    /**
     * @param string[]|int[] $keys
     * @param bool $byExpenses
     * @return AbstractRanking[]
     * @throws CompetitionRankingException
     */
    public function getMixedRankingsForKeys(array $keys, bool $byExpenses = false): array
    {
        $filtered = array();
        $allMixed = $this->getMixedRankings($byExpenses);
        foreach ($allMixed as $ranking) {
            if (in_array($ranking->getEntityKey(), $keys)) {
                $filtered[] = $ranking;
            }
        }
        return $filtered;
    }


    /**
     * @return AbstractRanking[]
     * @throws CompetitionRankingException
     */
    public function getMixedRankingsForQualification(): array
    {
        return $this->getMixedRankingsForKeys($this->getPlayerKeysForQualification());
    }

    /**
     * @return AbstractRanking[]
     * @throws CompetitionRankingException
     */
    public function getMixedRankingsForStagnation(): array
    {
        return $this->getMixedRankingsForKeys($this->getPlayerKeysForStagnation());
    }

    /**
     * @return AbstractRanking[]
     * @throws CompetitionRankingException
     */
    public function getMixedRankingsForElimination(): array
    {
        return $this->getMixedRankingsForKeys($this->getPlayerKeysForElimination());
    }



    /**
     * @return AbstractRanking[]
     * @throws CompetitionRankingException
     */
    public function getMixedTeamRankings(): array
    {
        return $this->mixGroupRankings(true);
    }



    /**
     * @return int[]|string[]
     */
    public function getPlayerKeysForQualification(): array
    {
        $playerKeys = array();
        foreach ($this->groups as $group) {
            $groupQualification = $group->getPlayerKeysForQualification();
            $playerKeys = array_merge($playerKeys, $groupQualification);
        }
        return $playerKeys;
    }

    /**
     * @return int[]|string[]
     */
    public function getPlayerKeysForStagnation(): array
    {
        $playerKeys = array();
        foreach ($this->groups as $group) {
            $groupStagnation = $group->getPlayerKeysForStagnation();
            $playerKeys = array_merge($playerKeys, $groupStagnation);
        }
        return $playerKeys;
    }

    /**
     * @return int[]|string[]
     */
    public function getPlayerKeysForElimination(): array
    {
        $playerKeys = array();
        foreach ($this->groups as $group) {
            $groupElimination = $group->getPlayerKeysForElimination();
            $playerKeys = array_merge($playerKeys, $groupElimination);
        }
        return $playerKeys;
    }

}
