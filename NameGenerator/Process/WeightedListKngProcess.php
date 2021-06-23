<?php

namespace Keiwen\Utils\NameGenerator\Process;

use Keiwen\Utils\NameGenerator\KngException;

/**
 * Class WeightedListKngProcess
 * Weighted list process aims to pick a random value among a list, according to each element's weight (chance to be picked)
 * Dictionary will contains all possible values
 * A weighted list will cumulate values' weights and set a map of minimum weight => value
 * Generation will pick a random number from 1 to cumulative weight and select values in that range from weighted list
 * @package Keiwen\Utils\NameGenerator\Process
 */
class WeightedListKngProcess extends KngProcess
{
    public const PARAMETER_DEFAULT_WEIGHT = 'defaultWeight';

    protected $defaultWeight = 1;
    protected $cumulativeWeight = 0;
    protected $weightedList = array();


    public function __construct(array $parameters = array())
    {
        parent::__construct($parameters);
        if(isset($parameters[static::PARAMETER_DEFAULT_WEIGHT]) && $parameters[static::PARAMETER_DEFAULT_WEIGHT] > 0) {
            $this->defaultWeight = (int) $parameters[static::PARAMETER_DEFAULT_WEIGHT];
        }
    }


    /**
     * Adds a single term to the dictionary
     * @param string $term
     * @param int $weight chance to be picked (0 for default weight)
     * @throws KngException term not string or weight < 1
     */
    public function addToDictionary($term, int $weight = 0)
    {
        if(!is_string($term)) {
            throw new KngException('Invalid format: term must be a string');
        }
        if(empty($weight)) $weight = $this->defaultWeight;
        if($weight < 0) {
            throw new KngException('Invalid format: term\'s weight must be greater than 0');
        }

        // keep a simple dictionary with possible values
        $this->dictionary[] = $term;
        $this->dictionarySize++;

        // build a list with cumulatives weights to generate term faster
        // element would be: 'maximumWeight: term'
        $this->cumulativeWeight += $weight;
        $this->weightedList[$this->cumulativeWeight] = $term;
    }


    /**
     * Adds multiple terms to the dictionary
     * @param array $terms associative map term => weight
     * @throws KngException terms not associative map
     */
    public function addListToDictionary(array $terms)
    {
        foreach($terms as $term => $weight) {
            //cast as int in case of provided as string
            $weight = (int) $weight;
            if(!is_string($term) || !is_int($weight)) {
                throw new KngException('Invalid format: terms list should be associative map of term => weight');
            }
            $this->addToDictionary($term, $weight);
        }
    }



    public function generate(): string
    {
        $this->checkReadyForGeneration();

        $randomWeight = random_int(1, $this->cumulativeWeight);
        $term = '';
        foreach($this->weightedList as $maxWeight => $term) {
            if($randomWeight <= $maxWeight) break;
        }

        return $this->formatTerm($term);
    }

    /**
     * Count number of possibilities for term generation
     * @return int
     */
    public function countPossibilities()
    {
        return $this->dictionarySize;
    }



    /**
     * @param int $defaultWeight
     */
    public function setDefaultWeight(int $defaultWeight)
    {
        $this->defaultWeight = $defaultWeight;
    }


}
