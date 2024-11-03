<?php

namespace Keiwen\Utils\Competition\Builder;

use Keiwen\Utils\Competition\Exception\CompetitionException;
use Keiwen\Utils\Competition\CompetitionTreePhase;
use Keiwen\Utils\Mutator\ArrayMutator;

class CompetitionBuilderPhase
{
    /** @var string */
    protected $name = '';
    /** @var string */
    protected $dispatchMethod = '';
    /** @var CompetitionBuilder[] */
    protected $builderGroups = array();
    protected $playerSelectorsInTree = array();

    const DISPATCH_METHOD_NONE = '';
    const DISPATCH_METHOD_RANDOM = 'random';


    public function __construct(string $name, string $dispatchMethod = '')
    {
        $this->name = $name;
        if (!in_array($dispatchMethod, static::getDispatchMethods())) $dispatchMethod = static::DISPATCH_METHOD_NONE;
        $this->dispatchMethod = $dispatchMethod;
    }

    /**
     * @return string[]
     */
    public static function getDispatchMethods(): array
    {
        return array(
            self::DISPATCH_METHOD_NONE,
            self::DISPATCH_METHOD_RANDOM,
        );
    }


    public function addGroup(string $type, array $options = array(), string $name = ''): bool
    {
        if (empty($name)) $name = (string) count($this->builderGroups);
        $builder = new CompetitionBuilder($type, $options);
        return $this->addGroupFromBuilder($builder, $name);
    }


    public function addGroupFromBuilder(CompetitionBuilder $builder, string $name = ''): bool
    {
        $cloned = clone $builder;
        $cloned->setName($name);
        $this->builderGroups[$name] = $cloned;
        return true;
    }

    public function getGroup(string $name): ?CompetitionBuilder
    {
        return $this->builderGroups[$name] ?? null;
    }

    /**
     * @return CompetitionBuilder[]
     */
    public function getGroups(): array
    {
        return $this->builderGroups;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDispatchMethod(): string
    {
        return $this->dispatchMethod;
    }


    public function getPlayerSelectors(): array
    {
        return $this->playerSelectorsInTree;
    }

    /**
     * Add a selector so that players can be selected, from the parent tree, to start the phase
     * @param string $phaseName optional: a specific phase name. If empty, take the previous phase. If no previous phase, consider the unused list of player in tree (all players for first phase)
     * @param string $playerPackName optional: see CompetitionTree::PLAYER_PACK_* constants. If empty, take unused + qualified of given phase. Always exclude players marked as eliminated in previous phase
     * @param array $seedRange optional: give start and end or range of seed to include. By default, array(1; -1) is used, including all seeds as -1 indicates no limit
     */
    public function addPlayerSelector(string $phaseName = '', string $playerPackName = '', array $seedRange = array())
    {
        $selector = array();
        if (!empty($phaseName)) $selector['phase'] = $phaseName;
        if (!empty($playerPackName)) $selector['pack'] = $playerPackName;
        if (!empty($seedRange)) {
            if (count($seedRange) === 2 && is_int($seedRange[0]) &&  is_int($seedRange[1])) {
                // check that starting seed is >= 1
                if ($seedRange[0] < 1) $seedRange[0] = 1;
                // check that ending seed is > starting except if -1
                if ($seedRange[1] !== -1 && $seedRange[1] < $seedRange[0]) $seedRange[1] = -1;

                $selector['seedRange'] = $seedRange;
            }
        }
        $this->playerSelectorsInTree[] = $selector;
    }


    /**
     * @param array $players
     * @param string $playerEloAccess method to access ELO in object or field name to access elo in array (leave empty if ELO is not used)
     * @param array $teamComposition $teamKey => list of players keys
     * @return CompetitionTreePhase|null
     * @throws CompetitionException
     */
    public function startPhase(array $players, string $playerEloAccess = '', array $teamComposition = array()): ?CompetitionTreePhase
    {
        if (empty($this->builderGroups)) return null;

        $computedMinPlayers = $this->computeMinPlayersCount();
        if (count($players) < $computedMinPlayers) {
            throw new CompetitionException(sprintf('Not enough players to start phase, at least %d required', $computedMinPlayers));
        }

        $playersDispatch = $this->dispatchPlayers($players);

        $groupCount = 0;
        $competitions = array();
        foreach ($this->builderGroups as $name => $builder) {
            $competition = $builder->buildForPlayers($playersDispatch[$groupCount], $playerEloAccess, $teamComposition);
            $competitions[$name] = $competition;
            $groupCount++;
        }

        return new CompetitionTreePhase($this->getName(), $competitions);
    }


    protected function dispatchPlayers(array $players): array
    {
        $groupCount = count($this->builderGroups);
        $dispatch = array();
        if ($this->dispatchMethod == static::DISPATCH_METHOD_RANDOM) $players = ArrayMutator::shufflePreservingKeys($players);
        switch ($this->dispatchMethod) {
            case static::DISPATCH_METHOD_NONE:
            case static::DISPATCH_METHOD_RANDOM:
            default:
                $dispatch = ArrayMutator::deal($players, $groupCount);
                break;
        }
        return $dispatch;
    }


    /**
     * compute minimum players count needed to build phase
     * (= sum of minimum number of players in each group of this phase)
     * @return int
     */
    public function computeMinPlayersCount(): int
    {
        $sum = 0;
        foreach ($this->getGroups() as $group) {
            $groupFQCN = CompetitionBuilder::getFQCNlassForType($group->getType());
            if (method_exists($groupFQCN, 'getMinPlayerCount')) {
                $sum += (int) ($groupFQCN)::getMinPlayerCount();
            }
        }
        return $sum;
    }

}
