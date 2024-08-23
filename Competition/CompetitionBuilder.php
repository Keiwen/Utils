<?php

namespace Keiwen\Utils\Competition;


use Keiwen\Utils\Mutator\ArrayMutator;

class CompetitionBuilder
{

    const TYPE_CHAMPIONSHIP_DUEL = 'championship_duel';
    const TYPE_CHAMPIONSHIP_RACE = 'championship_race';
    const TYPE_CHAMPIONSHIP_BRAWL = 'championship_brawl';
    const TYPE_CHAMPIONSHIP_PERF = 'championship_perf';
    const TYPE_ELIMINATION_CONTEST = 'elimination_contest';
    const TYPE_ELIMINATION_THRESHOLD = 'elimination_threshold';
    const TYPE_CHAMPIONSHIP_BUBBLE = 'championship_bubble';
    const TYPE_CHAMPIONSHIP_SWISS = 'championship_swiss';
    const TYPE_TOURNAMENT_DUEL = 'tournament_duel';
    const TYPE_TOURNAMENT_DOUBLE_ELIM = 'tournament_double_elimination';
    const TYPE_TOURNAMENT_SWAP = 'tournament_swap';
    const TYPE_TOURNAMENT_GAUNTLET = 'tournament_gauntlet';

    const OPTION_BONUS = 'bonus';
    const OPTION_MALUS = 'malus';
    const OPTION_POINTS_BY_POSITION = 'points_by_position';
    const OPTION_POINTS_FOR_WON = 'points_for_won';
    const OPTION_POINTS_FOR_DRAWN = 'points_for_drawn';
    const OPTION_POINTS_FOR_LOSS = 'points_for_loss';
    const OPTION_SERIES_COUNT = 'series_count';
    const OPTION_SHUFFLE_CALENDAR = 'shuffle_calendar';
    const OPTION_SHUFFLE_PLAYER = 'shuffle_player';
    const OPTION_INCLUDE_THIRD_PLACE_GAME = 'include_third_place_game';
    const OPTION_BEST_SEED_ALWAYS_HOME = 'best_seed_always_home';
    const OPTION_PRE_ROUND_SHUFFLE = 'pre_round_shuffle';
    const OPTION_PERF_RANK_METHOD = 'perf_rank_method';
    const OPTION_DUEL_POINT_METHOD = 'duel_point_method';
    const OPTION_DUEL_TIE_BREAKER_METHOD = 'duel_tie_breaker_method';
    const OPTION_PLAYERS_PASSING_COUNT = 'players_passing_count';
    const OPTION_PLAYERS_ELIMINATED_PER_ROUND = 'players_eliminated_per_round';
    const OPTION_MIN_PERF_FIRST_ROUND = 'min_perf_first_round';
    const OPTION_MIN_PERF_STEP_ROUND = 'min_perf_step_round';
    const OPTION_ROUNDS_COUNT = 'rounds_count';
    const OPTION_QUALIFICATION_SPOTS = 'qualification_spots';
    const OPTION_ELIMINATION_SPOTS = 'elimination_spots';


    protected $name = '';
    protected $type;
    protected $typeFQNClass;
    protected $options = array(
        self::OPTION_BONUS => 0,
        self::OPTION_MALUS => 0,
        self::OPTION_POINTS_BY_POSITION => array(10, 7, 5, 3, 2, 1),
        self::OPTION_POINTS_FOR_WON => 3,
        self::OPTION_POINTS_FOR_DRAWN => 1,
        self::OPTION_POINTS_FOR_LOSS => 0,
        self::OPTION_SERIES_COUNT => 1,
        self::OPTION_SHUFFLE_CALENDAR => false,
        self::OPTION_SHUFFLE_PLAYER => false,
        self::OPTION_INCLUDE_THIRD_PLACE_GAME => false,
        self::OPTION_BEST_SEED_ALWAYS_HOME => false,
        self::OPTION_PRE_ROUND_SHUFFLE => false,
        self::OPTION_PERF_RANK_METHOD => RankingPerformances::RANK_METHOD_SUM,
        self::OPTION_DUEL_POINT_METHOD => RankingDuel::POINT_METHOD_BASE,
        self::OPTION_DUEL_TIE_BREAKER_METHOD => RankingDuel::POINT_METHOD_BASE,
        self::OPTION_PLAYERS_PASSING_COUNT => array(),
        self::OPTION_PLAYERS_ELIMINATED_PER_ROUND => 0,
        self::OPTION_MIN_PERF_FIRST_ROUND => 0,
        self::OPTION_MIN_PERF_STEP_ROUND => 1,
        self::OPTION_ROUNDS_COUNT => 0,
        self::OPTION_QUALIFICATION_SPOTS => 0,
        self::OPTION_ELIMINATION_SPOTS => 0,
    );

    protected $performances = array();
    protected $expenses = array();

    public function __construct(string $type, array $options = array())
    {
        $this->type = $type;
        $this->typeFQNClass = static::getFQNClassForType($type);
        if (!is_subclass_of($this->typeFQNClass, AbstractCompetition::class)) {
            throw new CompetitionException(sprintf('Cannot create builder for competition type %s, associated class %s not found or not child of AbstractCompetition. Did you forget to declare it in getFQNClassForType() class?', $type, $this->typeFQNClass));
        }
        $this->options = array_merge($this->options, $options);
        foreach ($this->options as $optionName => &$optionValue) {
            static::checkOptionValue($optionName, $optionValue);
        }
    }

    /**
     * @return string[]
     */
    public static function getCompetitionTypes(): array
    {
        return array(
            self::TYPE_CHAMPIONSHIP_DUEL,
            self::TYPE_CHAMPIONSHIP_RACE,
            self::TYPE_CHAMPIONSHIP_BRAWL,
            self::TYPE_CHAMPIONSHIP_PERF,
            self::TYPE_ELIMINATION_CONTEST,
            self::TYPE_ELIMINATION_THRESHOLD,
            self::TYPE_CHAMPIONSHIP_BUBBLE,
            self::TYPE_CHAMPIONSHIP_SWISS,
            self::TYPE_TOURNAMENT_DUEL,
            self::TYPE_TOURNAMENT_DOUBLE_ELIM,
            self::TYPE_TOURNAMENT_SWAP,
            self::TYPE_TOURNAMENT_GAUNTLET,
        );
    }

    /**
     * @param string $type
     * @return bool
     */
    public static function isCompetitionType(string $type): bool
    {
        return in_array($type, self::getCompetitionTypes());
    }


    /**
     * @return string[]
     */
    public static function getPerfRankMethods(): array
    {
        return RankingPerformances::getRankMethods();
    }

    /**
     * @param bool $tieBreaker
     * @return string[]
     */
    public static function getDuelPointsMethods(bool $tieBreaker = false): array
    {
        return RankingDuel::getPointMethods($tieBreaker);
    }


    public static function getFQNClassForType(string $type): ?string
    {
        switch ($type) {
            case self::TYPE_CHAMPIONSHIP_DUEL: return CompetitionChampionshipDuel::class;
            case self::TYPE_CHAMPIONSHIP_RACE: return CompetitionChampionshipRace::class;
            case self::TYPE_CHAMPIONSHIP_BRAWL: return CompetitionChampionshipBrawl::class;
            case self::TYPE_CHAMPIONSHIP_PERF: return CompetitionChampionshipPerformances::class;
            case self::TYPE_ELIMINATION_CONTEST: return CompetitionEliminationContest::class;
            case self::TYPE_ELIMINATION_THRESHOLD: return CompetitionEliminationThreshold::class;
            case self::TYPE_CHAMPIONSHIP_BUBBLE: return CompetitionChampionshipBubble::class;
            case self::TYPE_CHAMPIONSHIP_SWISS: return CompetitionChampionshipSwiss::class;
            case self::TYPE_TOURNAMENT_DUEL: return CompetitionTournamentDuel::class;
            case self::TYPE_TOURNAMENT_DOUBLE_ELIM: return CompetitionTournamentDoubleElimination::class;
            case self::TYPE_TOURNAMENT_SWAP: return CompetitionTournamentSwap::class;
            case self::TYPE_TOURNAMENT_GAUNTLET: return CompetitionTournamentGauntlet::class;
        }
        return null;
    }


    public static function getAvailableOptionsForType(string $type): array
    {

        $optionsForType = array(
            self::OPTION_QUALIFICATION_SPOTS, self::OPTION_ELIMINATION_SPOTS,
            self::OPTION_BONUS, self::OPTION_MALUS,
        );
        switch ($type) {
            case self::TYPE_CHAMPIONSHIP_DUEL:
                $optionsForType[] = self::OPTION_POINTS_FOR_WON;
                $optionsForType[] = self::OPTION_POINTS_FOR_DRAWN;
                $optionsForType[] = self::OPTION_POINTS_FOR_LOSS;
                $optionsForType[] = self::OPTION_SERIES_COUNT;
                $optionsForType[] = self::OPTION_SHUFFLE_CALENDAR;
                $optionsForType[] = self::OPTION_DUEL_POINT_METHOD;
                $optionsForType[] = self::OPTION_DUEL_TIE_BREAKER_METHOD;
                break;
            case self::TYPE_CHAMPIONSHIP_RACE:
                $optionsForType[] = self::OPTION_POINTS_BY_POSITION;
                break;
            case self::TYPE_CHAMPIONSHIP_BRAWL:
                break;
            case self::TYPE_CHAMPIONSHIP_PERF:
                $optionsForType[] = self::OPTION_PERF_RANK_METHOD;
                break;
            case self::TYPE_ELIMINATION_CONTEST:
                $optionsForType[] = self::OPTION_PLAYERS_PASSING_COUNT;
                $optionsForType[] = self::OPTION_PLAYERS_ELIMINATED_PER_ROUND;
                $optionsForType[] = self::OPTION_PERF_RANK_METHOD;
                break;
            case self::TYPE_ELIMINATION_THRESHOLD:
                $optionsForType[] = self::OPTION_MIN_PERF_FIRST_ROUND;
                $optionsForType[] = self::OPTION_MIN_PERF_STEP_ROUND;
                $optionsForType[] = self::OPTION_PERF_RANK_METHOD;
                break;
            case self::TYPE_CHAMPIONSHIP_BUBBLE:
                $optionsForType[] = self::OPTION_POINTS_FOR_WON;
                $optionsForType[] = self::OPTION_POINTS_FOR_DRAWN;
                $optionsForType[] = self::OPTION_POINTS_FOR_LOSS;
                $optionsForType[] = self::OPTION_ROUNDS_COUNT;
                $optionsForType[] = self::OPTION_DUEL_POINT_METHOD;
                $optionsForType[] = self::OPTION_DUEL_TIE_BREAKER_METHOD;
                break;
            case self::TYPE_CHAMPIONSHIP_SWISS:
                $optionsForType[] = self::OPTION_POINTS_FOR_WON;
                $optionsForType[] = self::OPTION_POINTS_FOR_DRAWN;
                $optionsForType[] = self::OPTION_POINTS_FOR_LOSS;
                $optionsForType[] = self::OPTION_ROUNDS_COUNT;
                $optionsForType[] = self::OPTION_DUEL_POINT_METHOD;
                $optionsForType[] = self::OPTION_DUEL_TIE_BREAKER_METHOD;
                break;
            case self::TYPE_TOURNAMENT_DUEL:
                $optionsForType[] = self::OPTION_POINTS_FOR_WON;
                $optionsForType[] = self::OPTION_POINTS_FOR_DRAWN;
                $optionsForType[] = self::OPTION_POINTS_FOR_LOSS;
                $optionsForType[] = self::OPTION_INCLUDE_THIRD_PLACE_GAME;
                $optionsForType[] = self::OPTION_BEST_SEED_ALWAYS_HOME;
                $optionsForType[] = self::OPTION_PRE_ROUND_SHUFFLE;
                $optionsForType[] = self::OPTION_DUEL_POINT_METHOD;
                $optionsForType[] = self::OPTION_DUEL_TIE_BREAKER_METHOD;
                break;
            case self::TYPE_TOURNAMENT_DOUBLE_ELIM:
                $optionsForType[] = self::OPTION_POINTS_FOR_WON;
                $optionsForType[] = self::OPTION_POINTS_FOR_DRAWN;
                $optionsForType[] = self::OPTION_POINTS_FOR_LOSS;
                $optionsForType[] = self::OPTION_BEST_SEED_ALWAYS_HOME;
                $optionsForType[] = self::OPTION_PRE_ROUND_SHUFFLE;
                $optionsForType[] = self::OPTION_DUEL_POINT_METHOD;
                $optionsForType[] = self::OPTION_DUEL_TIE_BREAKER_METHOD;
                break;
            case self::TYPE_TOURNAMENT_SWAP:
                $optionsForType[] = self::OPTION_POINTS_FOR_WON;
                $optionsForType[] = self::OPTION_POINTS_FOR_DRAWN;
                $optionsForType[] = self::OPTION_POINTS_FOR_LOSS;
                $optionsForType[] = self::OPTION_BEST_SEED_ALWAYS_HOME;
                $optionsForType[] = self::OPTION_DUEL_POINT_METHOD;
                $optionsForType[] = self::OPTION_DUEL_TIE_BREAKER_METHOD;
                break;
            case self::TYPE_TOURNAMENT_GAUNTLET:
                $optionsForType[] = self::OPTION_POINTS_FOR_WON;
                $optionsForType[] = self::OPTION_POINTS_FOR_DRAWN;
                $optionsForType[] = self::OPTION_POINTS_FOR_LOSS;
                $optionsForType[] = self::OPTION_DUEL_POINT_METHOD;
                $optionsForType[] = self::OPTION_DUEL_TIE_BREAKER_METHOD;
                break;
        }
        return $optionsForType;
    }


    public static function getDefaultRankingHolderForType(string $type): RankingsHolder
    {
        switch ($type) {
            case self::TYPE_CHAMPIONSHIP_RACE:
                $defaultRankingHolder = RankingRace::generateDefaultRankingsHolder();
                break;
            case self::TYPE_CHAMPIONSHIP_BRAWL:
                $defaultRankingHolder = RankingBrawl::generateDefaultRankingsHolder();
                break;
            case self::TYPE_CHAMPIONSHIP_PERF:
            case self::TYPE_ELIMINATION_CONTEST:
            case self::TYPE_ELIMINATION_THRESHOLD:
                $defaultRankingHolder = RankingPerformances::generateDefaultRankingsHolder();
                break;
            default:
                $defaultRankingHolder = RankingDuel::generateDefaultRankingsHolder();
                break;
        }
        return $defaultRankingHolder;
    }


    public static function checkOptionValue(string $optionName, &$optionValue)
    {
        switch ($optionName) {
            case self::OPTION_POINTS_BY_POSITION:
            case self::OPTION_PLAYERS_PASSING_COUNT:
                if (!is_array($optionValue)) {
                    throw new CompetitionException(sprintf('Option %s must be given as array of integer', $optionName));
                }
                break;
            case self::OPTION_PERF_RANK_METHOD:
                if (!in_array($optionValue, static::getPerfRankMethods())) {
                    throw new CompetitionException(sprintf('Option %s is invalid. Possible values are: %s', $optionName,
                        implode(', ', static::getPerfRankMethods())));
                }
                break;
            case self::OPTION_DUEL_POINT_METHOD:
                if (!in_array($optionValue, static::getDuelPointsMethods())) {
                    throw new CompetitionException(sprintf('Option %s is invalid. Possible values are: %s', $optionName,
                        implode(', ', static::getDuelPointsMethods())));
                }
                break;
            case self::OPTION_DUEL_TIE_BREAKER_METHOD:
                if (!in_array($optionValue, static::getDuelPointsMethods(true))) {
                    throw new CompetitionException(sprintf('Option %s is invalid. Possible values are: %s', $optionName,
                        implode(', ', static::getDuelPointsMethods(true))));
                }
                break;
            case self::OPTION_SHUFFLE_CALENDAR:
            case self::OPTION_SHUFFLE_PLAYER:
            case self::OPTION_INCLUDE_THIRD_PLACE_GAME:
            case self::OPTION_BEST_SEED_ALWAYS_HOME:
            case self::OPTION_PRE_ROUND_SHUFFLE:
                // force boolean value
                if ($optionValue) {
                    $optionValue = true;
                } else {
                    $optionValue = false;
                }
                break;
            case self::OPTION_BONUS:
            case self::OPTION_MALUS:
            case self::OPTION_POINTS_FOR_WON:
            case self::OPTION_POINTS_FOR_DRAWN:
            case self::OPTION_POINTS_FOR_LOSS:
            case self::OPTION_SERIES_COUNT:
            case self::OPTION_PLAYERS_ELIMINATED_PER_ROUND:
            case self::OPTION_MIN_PERF_FIRST_ROUND:
            case self::OPTION_MIN_PERF_STEP_ROUND:
            case self::OPTION_ROUNDS_COUNT:
            case self::OPTION_QUALIFICATION_SPOTS:
            case self::OPTION_ELIMINATION_SPOTS:
                if (!is_int($optionValue)) {
                    throw new CompetitionException(sprintf('Option %s must be given as integer', $optionName));
                }
                break;
        }
    }

    /**
     * @param array $playersList
     * @param string $playerEloAccess method to access ELO in object or field name to access elo in array (leave empty if ELO is not used)
     * @param array $teamComposition $teamKey => list of players keys
     * @return AbstractCompetition
     * @throws CompetitionException
     */
    public function buildForPlayers(array $playersList, string $playerEloAccess = '', array $teamComposition = array()): AbstractCompetition
    {

        if ($this->getOptionValue(self::OPTION_SHUFFLE_PLAYER)) {
            $playersList = ArrayMutator::shufflePreservingKeys($playersList);
        }

        // setup competition object
        switch ($this->getType()) {
            case self::TYPE_CHAMPIONSHIP_DUEL:
                $competition = new CompetitionChampionshipDuel($playersList, $this->getOptionValue(self::OPTION_SERIES_COUNT), $this->getOptionValue(self::OPTION_SHUFFLE_CALENDAR));
                break;
            case self::TYPE_CHAMPIONSHIP_RACE:
                $competition = new CompetitionChampionshipRace($playersList);
                break;
            case self::TYPE_CHAMPIONSHIP_BRAWL:
                $competition = new CompetitionChampionshipBrawl($playersList);
                break;
            case self::TYPE_CHAMPIONSHIP_PERF:
                $competition = new CompetitionChampionshipPerformances($playersList, $this->getPerformancesTypes(true));
                break;
            case self::TYPE_ELIMINATION_CONTEST:
                $competition = new CompetitionEliminationContest($playersList, $this->getPerformancesTypes(true), $this->getOptionValue(self::OPTION_PLAYERS_PASSING_COUNT), $this->getOptionValue(self::OPTION_PLAYERS_ELIMINATED_PER_ROUND));
                break;
            case self::TYPE_ELIMINATION_THRESHOLD:
                $competition = new CompetitionEliminationThreshold($playersList, $this->getPerformancesTypes(true), $this->getOptionValue(self::OPTION_MIN_PERF_FIRST_ROUND), $this->getOptionValue(self::OPTION_MIN_PERF_STEP_ROUND));
                break;
            case self::TYPE_CHAMPIONSHIP_BUBBLE:
                $competition = new CompetitionChampionshipBubble($playersList, $this->getOptionValue(self::OPTION_ROUNDS_COUNT));
                break;
            case self::TYPE_CHAMPIONSHIP_SWISS:
                $competition = new CompetitionChampionshipSwiss($playersList, $this->getOptionValue(self::OPTION_ROUNDS_COUNT));
                break;
            case self::TYPE_TOURNAMENT_DUEL:
                $competition = new CompetitionTournamentDuel($playersList, $this->getOptionValue(self::OPTION_INCLUDE_THIRD_PLACE_GAME), $this->getOptionValue(self::OPTION_BEST_SEED_ALWAYS_HOME), $this->getOptionValue(self::OPTION_PRE_ROUND_SHUFFLE));
                break;
            case self::TYPE_TOURNAMENT_DOUBLE_ELIM:
                $competition = new CompetitionTournamentDoubleElimination($playersList, $this->getOptionValue(self::OPTION_BEST_SEED_ALWAYS_HOME), $this->getOptionValue(self::OPTION_PRE_ROUND_SHUFFLE));
                break;
            case self::TYPE_TOURNAMENT_SWAP:
                $competition = new CompetitionTournamentSwap($playersList, $this->getOptionValue(self::OPTION_BEST_SEED_ALWAYS_HOME));
                break;
            case self::TYPE_TOURNAMENT_GAUNTLET:
                $competition = new CompetitionTournamentGauntlet($playersList);
                break;
            default:
                throw new CompetitionException('Cannot build competition for type ' . $this->getType());
        }
        $competition->setQualificationSpots($this->getOptionValue(self::OPTION_QUALIFICATION_SPOTS));
        $competition->setEliminationSpots($this->getOptionValue(self::OPTION_ELIMINATION_SPOTS));

        $competition->setTeamComposition($teamComposition);
        $competition->setPlayerEloAccess($playerEloAccess);

        // adjust rankings rules
        $rankingHolder = $competition->getRankingsHolder();
        $rankingHolder->setPointsByBonus($this->getOptionValue(self::OPTION_BONUS));
        $rankingHolder->setPointsByMalus($this->getOptionValue(self::OPTION_MALUS));
        $rankingHolder->setPerfRankMethod($this->getOptionValue(self::OPTION_PERF_RANK_METHOD));
        $rankingHolder->setDuelPointMethod($this->getOptionValue(self::OPTION_DUEL_POINT_METHOD));
        $rankingHolder->setDuelTieBreakerMethod($this->getOptionValue(self::OPTION_DUEL_TIE_BREAKER_METHOD));

        switch ($this->getType()) {
            case self::TYPE_CHAMPIONSHIP_DUEL:
            case self::TYPE_CHAMPIONSHIP_BUBBLE:
            case self::TYPE_CHAMPIONSHIP_SWISS:
            case self::TYPE_TOURNAMENT_DUEL:
            case self::TYPE_TOURNAMENT_DOUBLE_ELIM:
            case self::TYPE_TOURNAMENT_SWAP:
            case self::TYPE_TOURNAMENT_GAUNTLET:
                $rankingHolder->setPointsAttributionForResult(GameDuel::RESULT_WON, $this->getOptionValue(self::OPTION_POINTS_FOR_WON));
                $rankingHolder->setPointsAttributionForResult(GameDuel::RESULT_DRAWN, $this->getOptionValue(self::OPTION_POINTS_FOR_DRAWN));
                $rankingHolder->setPointsAttributionForResult(GameDuel::RESULT_LOSS, $this->getOptionValue(self::OPTION_POINTS_FOR_LOSS));
                break;
            case self::TYPE_CHAMPIONSHIP_RACE:
                $pointsAttribution = $this->getOptionValue(self::OPTION_POINTS_BY_POSITION);
                if (!empty($pointsAttribution)) {
                    $rankingHolder->resetPointAttribution();
                    $position = 0;
                    foreach ($pointsAttribution as $points) {
                        $position++;
                        $rankingHolder->setPointsAttributionForResult($position, $points);
                    }
                }
                break;
        }

        foreach ($this->getPerformancesTypes() as $type) {
            $rankingHolder->addPerformanceTypeToRank($type);
        }
        foreach ($this->getExpensesTypes() as $type) {
            $rankingHolder->addExpenseTypeToRank($type);
        }

        // update game played will finish to initialize competition (at least compute current round to 1)
        $competition->updateGamesPlayed();

        return $competition;
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
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }


    public function getOptions(): array
    {
        return $this->options;
    }


    public function getOptionValue(string $optionName)
    {
        return $this->options[$optionName] ?? null;
    }


    /**
     * @return array
     */
    public function getPerformances(): array
    {
        return $this->performances;
    }

    /**
     * @param bool $toBeSumOnly
     * @return string[]
     */
    public function getPerformancesTypes(bool $toBeSumOnly = false): array
    {
        if (!$toBeSumOnly) return array_keys($this->performances);
        $types = array();
        foreach ($this->performances as $type => $toBeSum) {
            if ($toBeSum) $types[] = $type;
        }
        return $types;
    }

    /**
     * @param string $performance
     * @param bool $toBeSum
     * @return $this
     */
    public function addPerformance(string $performance, bool $toBeSum = true): self
    {
        $this->performances[$performance] = $toBeSum;
        return $this;
    }

    /**
     * @return array
     */
    public function getExpenses(): array
    {
        return $this->expenses;
    }

    /**
     * @return string[]
     */
    public function getExpensesTypes(): array
    {
        return array_keys($this->expenses);
    }


    /**
     * @param string $expense
     * @param int $startingCapital
     * @return $this
     */
    public function addExpense(string $expense, int $startingCapital = 0): self
    {
        $this->expenses[$expense] = $startingCapital;
        return $this;
    }


}
