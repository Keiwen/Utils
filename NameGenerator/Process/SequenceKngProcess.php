<?php

namespace Keiwen\Utils\NameGenerator\Process;

use Keiwen\Utils\NameGenerator\KngException;

/**
 * Class SequenceKngProcess
 * Sequence process aims to build a random value following a list of characters group
 * Dictionary will contains groups of characters
 * Generation will pick a random value in each char group and concatenate all values
 * @package Keiwen\Utils\NameGenerator\Process
 */
class SequenceKngProcess extends KngProcess
{

    /**
     * Adds a single char group to the dictionary
     * @param string|string[] $term group of chars - string will be converted to array
     * @throws KngException term not group of chars
     */
    public function addToDictionary($term)
    {
        if(is_string($term)) $term = static::splitTerm($term);
        if(!is_array($term) || empty($term)) {
            throw new KngException('Invalid format: term must be an array as group of chars');
        }

        $this->dictionary[] = $term;
        $this->dictionarySize++;
    }



    public function generate(): string
    {
        $this->checkReadyForGeneration();

        $term = '';
        foreach($this->dictionary as $charGroup) {
            // pick a random element in char group
            $randomIndex = random_int(0, count($charGroup) - 1);
            $term .= $charGroup[$randomIndex];
        }

        return $this->formatTerm($term);
    }

    /**
     * Count number of possibilities for term generation
     * @return int
     */
    public function countPossibilities()
    {
        $count = 0;
        foreach($this->dictionary as $charGroup) {
            if($count === 0) {
                $count = count($charGroup);
            } else {
                $count = $count * count($charGroup);
            }
        }
        return $count;
    }



}
