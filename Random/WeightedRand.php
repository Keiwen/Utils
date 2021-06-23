<?php

namespace Keiwen\Utils\Random;


class WeightedRand
{

    protected $weightMap;
    protected $cumulativeMap;
    protected $totalWeight;
    protected $referenceMap;

    protected $lastRandomWeight = 0;
    protected $lastRandomKey = '';
    protected $normalizedPower = 0;

    public static $maxNormalizerPower = 2;


    /**
     * WeightedRand constructor.
     *
     * Use normalizeWeightMap() if non-integer weights given
     * @param array $weightMap associative array [key => weight]. Weights should be integers
     * @param array $referenceMap optionnal associative array [$key => element]
     * @throws \RuntimeException when too large numbers reached
     */
    public function __construct(array $weightMap, array $referenceMap = array())
    {
        if(empty($weightMap)) {
            throw new \RuntimeException('Empty weight map');
        }
        $this->normalizedPower = static::normalizeWeightMap($weightMap);
        $this->referenceMap = $referenceMap;
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
        if($this->totalWeight < 1) {
            throw new \RuntimeException('Cumulative weights smaller than 1');
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
     * @param int|null $randomWeight
     * @return mixed corresponding reference or key
     */
    public function random(int &$randomWeight = null)
    {
        $randomWeight = random_int(1, $this->totalWeight);
        foreach($this->cumulativeMap as $key => $cumulWeight) {
            if($randomWeight <= $cumulWeight) {
                break;
            }
        }
//        dump($randomWeight, $this->cumulativeMap, $key);
        if(!isset($key)) {
            throw new \RuntimeException(
                sprintf('Cannot find value for weight %s', $randomWeight)
            );
        }
        $this->lastRandomKey = $key;
        $this->lastRandomWeight = $randomWeight;
        return $this->referenceMap[$key] ?? $key;
    }


    /**
     * @return int
     */
    public function getLastRandomWeight()
    {
        return $this->lastRandomWeight;
    }

    /**
     * @return string
     */
    public function getLastRandomKey()
    {
        return $this->lastRandomKey;
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
            // normalize weight according to last power found
            $weight = $weight * (10 ** $power);
            if($weight > PHP_INT_MAX) {
                throw new \RuntimeException(
                    sprintf('Normalized weight bigger than largest possible integer (%s)', PHP_INT_MAX)
                );
            }
            if(round($weight) != $weight) {
                while($power <= static::$maxNormalizerPower && round($weight) != $weight) {
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
                $weight = (int) ($weight * (10 ** $power));
                if($weight > PHP_INT_MAX) {
                    throw new \RuntimeException(
                        sprintf('Normalized weight bigger than largest possible integer (%s)', PHP_INT_MAX)
                    );
                }
            }
        }
        return $power;
    }


    /**
     * Generate from array with
     * @param array  $data original array of data
     * @param string $weightField field where weight is defined
     * @param string $referenceField field where reference to return is defined (empty to use data array keys)
     * @return static
     */
    public static function generateFromArrayFields(array $data, string $weightField, string $referenceField = '')
    {
        $weightMap = array();
        $referenceMap = array();
        foreach($data as $key => $row) {
            if(isset($row[$weightField])) {
                $weightMap[] = $row[$weightField];
                if(!empty($referenceField)) {
                    $referenceMap[] = isset($row[$referenceField]) ? $row[$referenceField] : $key;
                }
            }
        }

        return new static($weightMap, $referenceMap);
    }

}
