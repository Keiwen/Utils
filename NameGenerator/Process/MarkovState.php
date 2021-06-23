<?php

namespace Keiwen\Utils\NameGenerator\Process;

use Keiwen\Utils\NameGenerator\KngException;

class MarkovState
{

    /** @var string $value */
    protected $value;
    /** @var self[] $neighbors */
    protected $neighbors;


    /**
     * MarkovState constructor.
     * @param string $value
     * @param self[] $neighbors
     */
    public function __construct(string $value, array $neighbors = array())
    {
        $this->value = $value;
        $this->neighbors = $neighbors;
    }


    /**
     * @param self|null $neighbor
     */
    public function addNeighbor($neighbor)
    {
        $this->neighbors[] = $neighbor;
    }

    /**
     * @return self[]
     */
    public function getNeighbors()
    {
        return $this->neighbors;
    }

    /**
     * @param int $index
     * @return MarkovState|null
     */
    public function getNeighbor(int $index)
    {
        return $this->neighbors[$index] ?? null;
    }


    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }


    /**
     * @param bool $ignoreWeight
     * @return MarkovState|null
     */
    public function getRandomNeighbor(bool $ignoreWeight = false)
    {
        if(empty($this->neighbors)) return null;
        if(count($this->neighbors) == 1) return reset($this->neighbors);
        if(!$ignoreWeight) {
            //get random neighbor (we can have same state in multiple neighbor)
            $randomNeighborIndex = random_int(0, count($this->neighbors) - 1);
            return $this->getNeighbor($randomNeighborIndex);
        }

        //we should keep only unique value
        $uniqueValues = array();
        foreach($this->neighbors as $index => $neighbor) {
            $uniqueValues[$neighbor->getValue()] = $index;
        }
        $uniqueValues = array_flip($uniqueValues);
        //we now have only unique states with: originalIndex => 'value'
        $uniqueIndexes = array_keys($uniqueValues);
        //we now have sequential array of originalIndex
        $randomNeighborIndex = random_int(0, count($uniqueIndexes) - 1);
        return $this->getNeighbor($randomNeighborIndex);
    }

}
