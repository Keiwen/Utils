<?php

namespace Keiwen\Utils\NameGenerator\Process;

use Keiwen\Utils\NameGenerator\KngException;

/**
 * Class RawListKngProcess
 * Raw list process aims to pick a random value among a list
 * Dictionary will contains all possible values
 * Generation will pick a random element in this list
 * @package Keiwen\Utils\NameGenerator\Process
 */
class RawListKngProcess extends KngProcess
{

    /**
     * Adds a single term to the dictionary
     * @param string $term
     * @throws KngException term not string
     */
    public function addToDictionary($term)
    {
        if(!is_string($term)) {
            throw new KngException('Invalid format: term must be a string');
        }
        $this->dictionary[] = $term;
        $this->dictionarySize++;
    }

    public function generate(): string
    {
        $this->checkReadyForGeneration();
        $randomIndex = random_int(0, $this->dictionarySize - 1);
        return $this->formatTerm($this->dictionary[$randomIndex]);
    }

    /**
     * Count number of possibilities for term generation
     * @return int
     */
    public function countPossibilities()
    {
        return $this->dictionarySize;
    }


}
