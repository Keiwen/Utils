<?php

namespace Keiwen\Utils\Competition;


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


    public function getNextGame(): ?AbstractGame
    {
        if ($this->completed) return null;
        $nextRound = -1;
        $nextGameCandidate = null;
        foreach ($this->groups as $group) {
            $nextGameInGroup = $group->getNextGame();
            if (empty($nextGameInGroup)) continue;
            if ($nextRound == -1 || $nextRound > $nextGameInGroup->getCompetitionRound()) {
                $nextRound = $nextGameInGroup->getCompetitionRound();
                $nextGameCandidate = $nextGameInGroup;
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
