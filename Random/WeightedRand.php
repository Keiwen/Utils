<?php

namespace Keiwen\Utils\Random;


class WeightedRand
{

    protected $weightMap;
    protected $cumulativeMap;
    protected $totalWeight;

    protected $lastRandomWeight = 0;
    protected $normalizedPower = 0;

    public static $maxNormalizerPower = 2;


    /**
     * WeightedRand constructor.
     *
     * Use normalizeWeightMap() if non-integer weights given
     * @param array $weightMap associative array [key => weight]. Weights should be integers
     * @throws \RuntimeException when too large numbers reached
     */
    public function __construct(array $weightMap)
    {
        $this->normalizedPower = static::normalizeWeightMap($weightMap);
        $this->weightMap = $weightMap;
        $cumulativeWeight = 0;
        foreach($weightMap as $key => $weight) {
            $cumulativeWeight += $weight;
            $this->cumulativeMap[$key] = $cumulativeWeight;
        }
        $this->totalWeight = $cumulativeWeight;
        if($this->totalWeight > mt_getrandmax()) {
            throw new \RuntimeException(
                sprintf('Cumulative weights bigger than largest possible random value (%s)', mt_getrandmax())
            );
        }
    }

    /**
     * @return array
     */
    public function getCumulativeMap()
    {
        return $this->cumulativeMap;
    }


    /**
     * @return string corresponding key
     */
    public function random()
    {
        $this->lastRandomWeight = mt_rand(1, $this->totalWeight);
        foreach($this->cumulativeMap as $key => $cumulWeight) {
            if($this->lastRandomWeight <= $cumulWeight) return $key;
        }
    }


    /**
     * @return int
     */
    public function getLastRandomWeight()
    {
        return $this->lastRandomWeight;
    }


    /**
     * @param array $weightMap  associative array [key => weight]
     * @throws \RuntimeException when too large numbers reached
     * @return int normalized power used
     */
    public static function normalizeWeightMap(array &$weightMap)
    {
        $power = 0;
        if(static::$maxNormalizerPower < 0) static::$maxNormalizerPower = 0;
        //detect non-integer value and try power of base ten to turn to integer
        foreach($weightMap as $key => $weight) {
            if($weight > PHP_INT_MAX) {
                throw new \RuntimeException(
                    sprintf('Weight bigger than largest possible integer (%s)', PHP_INT_MAX)
                );
            }
            if(round($weight) != $weight) {
                while($power < static::$maxNormalizerPower && round($weight) != $weight) {
                    $power++;
                    $weight = $weight * 10;
                }
                if(round($weight) != $weight) {
                    throw new \RuntimeException(
                        sprintf('Cannot normalize weights, max power reach (10^%s)', static::$maxNormalizerPower)
                    );
                }
            }
        }
        //now we have the max power needed, normalize
        if($power > 0) {
            foreach($weightMap as $key => &$weight) {
                $weight = $weight * pow(10, $power);
                if($weight > PHP_INT_MAX) {
                    throw new \RuntimeException(
                        sprintf('Normalized weight bigger than largest possible integer (%s)', PHP_INT_MAX)
                    );
                }
            }
        }
        return $power;
    }

}
