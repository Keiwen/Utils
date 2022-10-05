<?php

namespace Keiwen\Utils\NameGenerator\Process;

use Keiwen\Utils\NameGenerator\KngException;

/**
 * Class CharGroupPatternKngProcess
 * Char Group Pattern process aims to build a random value following a given pattern
 * Dictionary will contains groups of characters with a identifying key
 * Pattern will describe a sequence of char group to pick among (could be defined in initial parameters or with setter)
 * Patterns could contains a list of pattern. A random one will be choosen on generation.
 * Generation will pick a random value in a char group for each pattern step and concatenate all values
 * @package Keiwen\Utils\NameGenerator\Process
 */
class CharGroupPatternKngProcess extends KngProcess
{


    public const PARAMETER_PATTERN = 'pattern';

    protected $patternList = array();


    /**
     * CharGroupPatternKngProcess constructor.
     * @param array $parameters
     * @throws KngException multiple term detected not provided as array
     */
    public function __construct(array $parameters = array())
    {
        parent::__construct($parameters);

        if(!empty($parameters[static::PARAMETER_PATTERN])) {
            $pattern = $parameters[static::PARAMETER_PATTERN];
            if(is_string($pattern) && empty(static::detectSeparator($pattern))) {
                //consider we have only one pattern
            } else {
                //consider we have multiple pattern
                if(is_string($pattern)) $pattern = static::splitTerm($pattern);
                $patternList = $pattern;
                if(!is_array($patternList)) {
                    throw new KngException('Invalid format: multiple pattern detected, should be provided as array');
                }
                foreach($patternList as $pattern) {
                    $this->addPattern($pattern);
                }
            }
        }
    }


    /**
     * @param string|string[] $pattern
     * @throws KngException pattern not as list
     */
    public function addPattern($pattern)
    {
        if(is_string($pattern)) $pattern = static::splitTerm($pattern);
        if(!is_array($pattern) || empty($pattern)) {
            throw new KngException('Invalid format: pattern must be array (list of group keys)');
        }
        $this->patternList[] = $pattern;
    }


    /**
     * Adds a single char group to the dictionary
     * @param string|array $term group of chars - string will be converted to array
     * @param string $key key to representing the group, to be used in pattern
     * @throws KngException term not group of char
     */
    public function addToDictionary($term, string $key = '')
    {
        if(is_string($term)) $term = static::splitTerm($term);
        if(!is_array($term) || empty($term)) {
            throw new KngException('Invalid format: term must be an array as group of chars');
        }

        // check if key already defined to track dictionary size ...
        if(!isset($this->dictionary[$key])) {
            $this->dictionarySize++;
        }

        // ... but do not block, allow override
        $this->dictionary[$key] = $term;
    }


    /**
     * Adds multiple terms to the dictionary
     * @param array $terms associative map key => term
     */
    public function addListToDictionary(array $terms)
    {
        foreach($terms as $key => $term) {
            $this->addToDictionary($term, $key);
        }
    }


    /**
     * @return bool
     */
    public function isPatternEmpty(): bool
    {
        return empty($this->patternList);
    }

    /**
     * Check if process is valid / ready to generate
     * @return bool
     * @throws KngException empty dictionnary / no pattern defined
     */
    public function checkReadyForGeneration(): bool
    {
        parent::checkReadyForGeneration();
        if($this->isPatternEmpty()) {
            throw new KngException('Cannot generate: no pattern defined');
        }
        return true;
    }


    public function generate(): string
    {
        $this->checkReadyForGeneration();

        //pick a random pattern
        $randomIndex = random_int(0, count($this->patternList) - 1);
        $randomPattern = $this->patternList[$randomIndex];

        $term = '';
        foreach($randomPattern as $key) {
            //by default, add raw key to term
            $nextInTerm = $key;
            if(isset($this->dictionary[$key])) {
                // else pick a random element in char group
                $randomIndex = random_int(0, count($this->dictionary[$key]) - 1);
                $nextInTerm = $this->dictionary[$key][$randomIndex];
            }
            $term .= $nextInTerm;
        }

        return $this->formatTerm($term);
    }

    /**
     * Count number of possibilities for term generation
     * @return int|null
     */
    public function countPossibilities()
    {
        if($this->isPatternEmpty()) return 0;
        if(count($this->patternList) > 1) {
            //way too complex to compute, considering intersections of all patterns
            return null;
        }
        $pattern = reset($this->patternList);
        $count = 0;
        foreach($pattern as $key) {
            //ignore non-randomized element of pattern
            if(isset($this->dictionary[$key])) {
                if($count === 0) {
                    $count = count($this->dictionary[$key]);
                } else {
                    $count = $count * count($this->dictionary[$key]);
                }
            }
        }
        return $count;
    }


    /**
     * @param array $patternList
     * @return static
     */
    public static function createFromPatterns(array $patternList)
    {
        $process = new static();
        foreach($patternList as $pattern) {
            $process->addPattern($pattern);
        }
        return $process;
    }


}
