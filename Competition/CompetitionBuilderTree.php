<?php

namespace Keiwen\Utils\Competition;

class CompetitionBuilderTree
{

    /** @var string */
    protected $name = '';
    /** @var CompetitionBuilderPhase[] */
    protected $builderPhases = array();
    protected $iterations = array();

    const PLAYER_PACK_QUALIFIED = 'qualified';
    const PLAYER_PACK_STAGNATION = 'stagnation';
    const PLAYER_PACK_UNUSED = 'unused';

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @param string $name
     * @return CompetitionBuilderPhase
     */
    public function addPhase(string $name): CompetitionBuilderPhase
    {
        $phase = new CompetitionBuilderPhase($name);
        $this->builderPhases[$name] = $phase;
        return $phase;
    }

    public function getPhase(string $name): ?CompetitionBuilderPhase
    {
        return $this->builderPhases[$name] ?? null;
    }


    public function getPhaseAfter(string $name): ?CompetitionBuilderPhase
    {
        $phaseNames = array_keys($this->builderPhases);
        $searchIndex = array_search($name, $phaseNames, true);
        // if given phase not found, nothing after
        if ($searchIndex === false) return null;
        // if next index out of bond, means that no further phase
        if (count($phaseNames) <= ($searchIndex + 1)) return null;

        $nextPhaseName = $phaseNames[$searchIndex + 1];
        return $this->getPhase($nextPhaseName);
    }

    /**
     * @return CompetitionBuilderPhase[]
     */
    public function getPhases(): array
    {
        return $this->builderPhases;
    }


    /**
     * @param array $players
     * @param string $iterationName
     * @param string $playerEloAccess method to access ELO in object or field name to access elo in array (leave empty if ELO is not used)
     * @param array $teamComposition $teamKey => list of players keys
     * @return CompetitionTree|null
     */
    public function startIteration(array $players, string $iterationName = '', string $playerEloAccess = '', array $teamComposition = array()): ?CompetitionTree
    {
        if (empty($this->builderPhases)) return null;

        $iteration = new CompetitionTree($this, $players, $iterationName, $playerEloAccess, $teamComposition);
        $this->iterations[$iterationName] = $iteration;
        return $iteration;
    }


    /**
     * @param string $iterationName
     * @return CompetitionTree|null null if not found
     */
    public function getIteration(string $iterationName = ''): ?CompetitionTree
    {
        return $this->iterations[$iterationName] ?? null;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }


}
