<?php

namespace Keiwen\Utils\Random;


class Dice
{

    protected $faces = 0;
    protected $faceValues = array();


    /**
     * Dice constructor.
     *
     * @param int            $faces
     * @param int[]|string[] $values array of values
     */
    function __construct(int $faces = 6, array $values = array())
    {
        if($faces < 2) throw new \RuntimeException("Cannot use dice with less than 2 faces");
        $valueRange = range(1, $faces);
        //if no values, set range
        if(empty($values)) $values = $valueRange;
        //if same amount of value, set as this
        if(count($values) == $faces) {
            $this->faceValues = array_combine($valueRange, array_keys($values));
        } else {
            //else, only some values are defined, so check by key
            $this->faceValues = array_combine($valueRange, $valueRange);
            foreach($values as $raw => $value) {
                if(!isset($this->faceValues[$raw])) {
                    throw new \RuntimeException(
                        sprintf('Mismatch: tried to define value %s for unrecognized face %s', $values, $raw)
                    );
                }
                $this->faceValues[$raw] = $value;
            }
        }
        $this->faces = $faces;
    }


    /**
     * Roll dice
     * @return int|string
     */
    public function roll()
    {
        $throw = mt_rand(1, $this->faces);
        return $this->faceValues[$throw];
    }

    /**
     * @param int|string $value
     * @return bool
     */
    public function rollForValue($value)
    {
        $throw = $this->roll();
        return $throw == $value;
    }


    /**
     * @param int|string $value
     * @param int        $rollLimit 100 default, 1 if less than 0
     * @return int number of roll done
     */
    public function rollUntilValue($value, int $rollLimit = 100)
    {
        if($rollLimit <= 0) $rollLimit = 1;
        for($roll = 1; $roll <= $rollLimit; $roll++) {
            if($this->rollForValue($value)) {
                break;
            }
        }
        return $roll;
    }


    /**
     * @return int
     */
    public function getFaces()
    {
        return $this->faces;
    }

    /**
     * @return array rawValue => customValue
     */
    public function getFacesValues()
    {
        return $this->getFacesValues();
    }

}
