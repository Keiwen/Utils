<?php

namespace Keiwen\Utils\Random;


class DiceCollection
{

    protected $diceList = array();
    protected $defaultFace = 6;
    protected $lastRoll = array();


    /**
     * Dice constructor.
     *
     * @param int $number default 0
     * @param int $defaultFaces (at least 2, default 6)
     */
    function __construct(int $number = 0, int $defaultFaces = 6)
    {
        if($defaultFaces < 2) throw new \RuntimeException("Cannot use dice with less than 2 faces");
        $this->defaultFace = $defaultFaces;
        if($number > 0) {
            $this->addBasicDice($number);
        }
    }


    /**
     * add basic dice to collection
     * @param int $number number of dice to add (default 1)
     * @param int $faces number of dice's faces (0 will add default type)
     */
    public function addBasicDice(int $number = 1, int $faces = 0)
    {
        if($faces < 2) $faces = $this->defaultFace;
        $dice = new Dice($faces);
        $this->addDice($dice, $number);
    }

    /**
     * remove basic dice from collection
     * @param int $number number of dice to remove (default 1)
     * @param int $faces number of dice's faces (0 will remove default type)
     */
    public function removeBasicDice(int $number = 1, int $faces = 0)
    {
        $this->addBasicDice(-$number, $faces);
    }


    /**
     * add dice to collection
     * @param Dice $dice
     * @param int  $number
     */
    public function addDice(Dice $dice, int $number = 1)
    {
        $faces = $dice->getFaces();
        if(!isset($this->diceList[$faces])) {
            $this->diceList[$faces] = array();
        }
        for($i = 1; $i <= $number; $i++) {
            $this->diceList[$faces][] = clone $dice;
        }
        if($number < 0) {
            //called for removal
            $removed = 0;
            foreach($this->diceList[$faces] as $index => $diceStored) {
                if($diceStored == $dice) {
                    array_splice($array, $index, 1); //array_slice will reformat array indexes
                    $removed--;
                    if($removed == $number) break;
                }
            }

        }
    }


    /**
     * remove dice from collection
     * @param Dice $dice
     * @param int  $number
     */
    public function removeDice(Dice $dice, int $number = 1)
    {
        $this->addDice($dice, -$number);
    }


    /**
     * Roll current dice collection
     * @param array $throws reference to retrieve throw values
     * @param bool  $throwsByFace order throw values by face (array($faces => values[]) insteand of sequential)
     * @return int sum of throws (for numerical values only)
     */
    public function roll(array &$throws = array(), bool $throwsByFace = false)
    {
        $this->lastRoll = array();
        $total = 0;
        foreach($this->diceList as $faces => $diceList) {
            foreach($diceList as $dice) {
                /** @var Dice $dice */
                $throw = $dice->roll();
                $throws[] = $throw;
                if(is_numeric($throw)) $total += $throw;
                $this->lastRoll[$faces][] = $throw;
            }
        }
        if($throwsByFace) {
           $throws = $this->lastRoll;
        }
        return $total;
    }


    /**
     * Check if all throws were equals in last roll
     * @param bool $doubleOnly true to check if at least 2 throws were equals, not all
     * @param int  $forFaces filter for a dice type, or empty to check all dice
     * @return bool
     */
    public function hadEqualThrows(bool $doubleOnly = false, int $forFaces = 0)
    {
        $allValues = array();
        foreach($this->lastRoll as $faces => $throws) {
            if(empty($faces) || $faces == $forFaces) $allValues = array_merge($allValues, $throws);
        }
        $allValuesCount = count($allValues);
        if($allValuesCount < 2) return false;
        $uniqueValues = array_unique($allValues);
        $uniqueValuesCount = count($uniqueValues);
        if($uniqueValuesCount == 1) return true; //only one value overall
        if($doubleOnly && $allValuesCount > $uniqueValuesCount) return true; //at least one duplicate value
        return false;
    }


    /**
     * Check if last roll contains at least 2 equal throw
     * @return bool
     */
    public function hadDouble()
    {
        return $this->hadEqualThrows(true);
    }


    /**
     * @param int   $faces dice's faces number
     * @param int   $rolls number of roll
     * @param array $throws list of value for each roll
     * @return int sum for all rolls
     */
    public static function rollDice(int $faces = 6, int $rolls = 1, &$throws = array())
    {
        if($rolls < 1) throw new \RuntimeException("Cannot roll dice less than once");
        if($faces < 2) throw new \RuntimeException("Cannot roll dice with less than 2 faces");
        $dice = new Dice($faces);
        $total = 0;
        $throws = array();
        for($i = 1; $i <= $rolls; $i++) {
            $throw = $dice->roll();
            $throws[] = $throw;
            $total += $throw;
        }
        return $total;
    }


}
