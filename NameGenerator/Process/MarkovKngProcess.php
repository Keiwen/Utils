<?php

namespace Keiwen\Utils\NameGenerator\Process;

use Keiwen\Utils\NameGenerator\KngException;

/**
 * Class MarkovKngProcess
 * Markov Sequence process aims to build a random value similar to given values
 * Dictionary will contains initial values
 * MarkovTree will contains a tree representing markov chain, each state representing a char group
 * Markov states is linked to other states (neighbors) that represent next char possibilities.
 * Neighbors weight is based on how often this combination is found in given values
 * Order parameter will determine of many characters are considered when picking the next one (must be defined at initialization)
 * Generation will pick a random initial char, then randomly process markov tree and concatenate char found
 * Parameters include minLength, maxLength (-1 to ignore), maxAttempts (error when term not generated after X fails)
 * Parameters include allowDuplicates (exact match from dictionary), setAllowSubDuplicates (substring match from dictionary)
 * @package Keiwen\Utils\NameGenerator\Process
 */
class MarkovKngProcess extends KngProcess
{


    public const PARAMETER_ORDER = 'order';
    public const PARAMETER_MIN_LENGTH = 'minLength';
    public const PARAMETER_MAX_LENGTH = 'maxLength';
    public const PARAMETER_MAX_ATTEMPTS = 'maxAttempts';
    public const PARAMETER_ALLOW_DUPLICATES = 'allowDuplicates';
    public const PARAMETER_ALLOW_SUB_DUPLICATES = 'allowSubDuplicates';
    public const PARAMETER_IGNORE_WEIGHT = 'ignoreWeight';

    protected $order = 1;
    protected $minLength = 1;
    protected $maxLength = -1;
    protected $maxAttempts = 25;
    protected $allowDuplicates = true;
    protected $allowSubDuplicates = true;
    protected $ignoreWeight = false;

    /** @var MarkovState[] $markovTree */
    protected $markovTree;
    protected $duplicates = array();


    /**
     * MarkovKngProcess constructor.
     * @param array $parameters
     * @throws KngException order lower than 1
     */
    public function __construct(array $parameters = array())
    {
        parent::__construct($parameters);

        if(isset($parameters[static::PARAMETER_ORDER])) {
            $order = (int) $parameters[static::PARAMETER_ORDER];
            if($order < 1) {
                throw new KngException('Invalid format: order must be greater than 1');
            }
            $this->order = $order;
        }
        $min = $parameters[static::PARAMETER_MIN_LENGTH] ?? null;
        $max = $parameters[static::PARAMETER_MAX_LENGTH] ?? null;
        $this->setTermLength($min, $max);
        if(isset($parameters[static::PARAMETER_MAX_ATTEMPTS])) $this->setMaxAttempts($parameters[static::PARAMETER_MAX_ATTEMPTS]);

        $this->setAllowDuplicates(!empty($parameters[static::PARAMETER_ALLOW_DUPLICATES]));
        $this->setAllowSubDuplicates(!empty($parameters[static::PARAMETER_ALLOW_SUB_DUPLICATES]));
        $this->setIgnoreWeight(!empty($parameters[static::PARAMETER_IGNORE_WEIGHT]));

        $this->markovTree = array('' => new MarkovState(''));
    }

    /**
     * @param int|null $min null to keep current value
     * @param int|null $max null to keep current value
     * @throws KngException min > max
     */
    public function setTermLength(int $min = null, int $max = null)
    {
        if($min === null || $min <= 0) $min = $this->minLength;
        if($max === null || $max <= 0) $max = $this->maxLength;
        if($max !== -1 && $min > $max) {
            throw new KngException(sprintf('Invalid format: min length (%s) greater than max lenght (%s)', $min, $max));
        }
        $this->minLength = $min;
        $this->maxLength = $max;
    }


    /**
     * @param int $maxAttempts
     * @throws KngException less than 1 attempt
     */
    public function setMaxAttempts(int $maxAttempts)
    {
        if($maxAttempts <= 0) {
            throw new KngException('Invalid format: Max attempts must be greater than 0');
        }
        $this->maxAttempts = $maxAttempts;
    }


    /**
     * Allow generated terms to be equal to a dictionary entry
     * @param bool $allowDuplicates
     */
    public function setAllowDuplicates(bool $allowDuplicates)
    {
        $this->allowDuplicates = $allowDuplicates;
    }

    /**
     * Allow generated terms to be a substring to a dictionary entry
     * @param bool $allowSubDuplicates
     */
    public function setAllowSubDuplicates(bool $allowSubDuplicates)
    {
        $this->allowSubDuplicates = $allowSubDuplicates;
    }

    /**
     * When picking next characters, choose with equi probability
     * @param bool $ignoreWeight
     */
    public function setIgnoreWeight(bool $ignoreWeight)
    {
        $this->ignoreWeight = $ignoreWeight;
    }


    /**
     * Generate node object when building duplicate tree
     * @return array
     */
    protected static function generateDuplicateNode()
    {
        return array('children' => array());
    }


    protected static function generateMarkovState(string $value, array $neighbors = array())
    {
        return array(
            'value' => $value,
            'neighbors' => $neighbors,
        );
    }


    /**
     * Adds a term to duplicates
     * @param string $term term to add in duplicates (recursively add substrings)
     */
    protected function addToDuplicates(string $term)
    {
        //if not only one char, remove first char and add it
        if(strlen($term) > 1) {
            $this->addToDuplicates(substr($term, 1));
        }
        $this->duplicates[$term] = true;
    }


    /**
     * Check if a term is duplicate to one of entries
     * @param string $term
     * @param bool $allowSubString if false, also check substring duplicates.
     * @return bool
     */
    public function isDuplicate(string $term, bool $allowSubString = true): bool
    {
        $term = strtolower($term);
        if(!$allowSubString) return $this->isSubDuplicate($term);
        foreach($this->dictionary as $entry) {
            if (strtolower($entry) === $term) return true;
        }
        return false;
    }


    /**
     * Check if a term is duplicate to one of entries or its substring
     * @param string $term
     * @return bool
     */
    protected function isSubDuplicate(string $term): bool
    {
        return isset($this->duplicates[$term]);
    }

    /**
     * Adds a single char group to the dictionary
     * @param string $term group of chars - string will be converted to array
     * @throws KngException term not string
     */
    public function addToDictionary($term)
    {
        if(!is_string($term)) {
            throw new KngException('Invalid format: term must be a string');
        }

        $this->dictionary[] = $term;
        $this->dictionarySize++;

        //add to sub-duplicates
        $this->addToDuplicates(strtolower($term));

        $previousState = $this->markovTree[''];
        $key = '';
        $charList = str_split($term);
        foreach($charList as $char) {
            $key .= $char;
            // if key greater than order, remove first char
            if(strlen($key) > $this->order) $key = substr($key, 1);

            if(!isset($this->markovTree[$key])) {
                $this->markovTree[$key] = new MarkovState($char);
            }
            $currentState = $this->markovTree[$key];

            $previousState->addNeighbor($currentState);
            unset($previousState);
            $previousState = $currentState;
        }

        //add dead end
        $previousState->addNeighbor(null);

    }


    /**
     * Check if process is valid / ready to generate
     * @return bool
     * @throws KngException empty dictionary / empty markov tree
     */
    public function checkReadyForGeneration(): bool
    {
        parent::checkReadyForGeneration();
        $firstState = $this->markovTree[''];
        if(empty($firstState->getNeighbors())) {
            throw new KngException('Cannot generate: markov tree empty');
        }
        return true;
    }


    /**
     * Check if generated term is valid according to parameters
     * @param string $term
     * @return bool
     */
    public function isTermValid(string $term): bool
    {
        if(strlen($term) < $this->minLength) return false;
        if($this->maxLength > 0 && strlen($term) > $this->maxLength) return false;
        if(!$this->allowSubDuplicates && $this->isDuplicate($term, false)) return false;
        if(!$this->allowDuplicates && $this->isDuplicate($term, true)) return false;
        return true;
    }


    public function generate(): string
    {
        $this->checkReadyForGeneration();

        $attempts = 0;
        do {
            $attempts++;

            $currentState = $this->markovTree[''];
            $nextState = $currentState->getRandomNeighbor($this->ignoreWeight);
            $term = '';

            while($nextState && ($this->maxLength < 0 || strlen($term) <= $this->maxLength)) {
                $term .= $nextState->getValue();
                //move to next state
                $newState = $nextState->getRandomNeighbor($this->ignoreWeight);
                unset($nextState);
                $nextState = $newState;
                unset($newState);
            }

        } while(!$this->isTermValid($term) && $attempts < $this->maxAttempts);

        if(!$this->isTermValid($term)) {
            throw new KngException(sprintf('Unable to generate term after %d attempts', $attempts));
        }

        return $this->formatTerm($term);
    }


    /**
     * Count number of possibilities for term generation
     * @return null
     */
    public function countPossibilities()
    {
        return null;
    }


    /**
     * @param int $order
     * @return self
     */
    public function convertToOrder(int $order): self
    {
        $parameters = array(
            static::PARAMETER_ORDER => $order,
            static::PARAMETER_MIN_LENGTH => $this->minLength,
            static::PARAMETER_MAX_LENGTH => $this->maxLength,
            static::PARAMETER_MAX_ATTEMPTS => $this->maxAttempts,
            static::PARAMETER_ALLOW_DUPLICATES => $this->allowDuplicates,
            static::PARAMETER_ALLOW_SUB_DUPLICATES => $this->allowSubDuplicates,
            static::PARAMETER_IGNORE_WEIGHT => $this->ignoreWeight,
        );
        $newMarkov = new static($parameters);
        $newMarkov->addListToDictionary($this->dictionary);
        return $newMarkov;
    }


    /**
     * @param array $patternList
     * @return static
     */
    public static function createFromOrder(int $order)
    {
        return new static(array(static::PARAMETER_ORDER => $order));
    }


}
