<?php

namespace Keiwen\Utils\RolePlay;

class RolePlaySystem
{

    protected $max = 99;
    protected $min = 0;
    protected $lastTest = array(
        'threshold' => 0,
        'value' => 0,
    );

    /** @var array $attributes */
    protected $attributes;


    /**
     * RolePlaySystem constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes)
    {
        $this->attributes = array_values($attributes);
    }

    /**
     * @param int $max
     */
    public function setMaxForAttribute(int $max)
    {
        $this->max = $max;
    }

    /**
     * @param int $min
     */
    public function setMinForAttribute(int $min)
    {
        $this->min = $min;
    }

    /**
     * @return int
     */
    public function getMaxForAttribute()
    {
        return $this->max;
    }

    /**
     * @return int
     */
    public function getMinForAttribute()
    {
        return $this->min;
    }


    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param mixed $attribute
     * @return bool
     */
    public function hasAttribute($attribute)
    {
        return in_array($attribute, $this->attributes);
    }


    /**
     * @return int
     */
    public function generateRandomValue()
    {
        return random_int($this->min, $this->max);
    }


    /**
     * @param int|null $value
     * @return int
     */
    public function validateValue(int $value = null)
    {
        if ($value === null) {
            $value = $this->generateRandomValue();
        } else {
            if ($value > $this->max) $value = $this->max;
            if ($value < $this->min) $value = $this->min;
        }
        return $value;
    }


    /**
     * @param int $threshold
     * @param int|null $value null to generate a random value
     * @return bool
     */
    public function isTestSucceed(int $threshold, int $value = null)
    {
        $value = $this->validateValue($value);

        $this->lastTest['threshold'] = $threshold;
        $this->lastTest['value'] = $value;

        // if value = max, test fails, you always have a chance to fail!
        return $value < $threshold;
    }

    /**
     * Determine how a test could be critical (success or fail).
     * Use $this->lastTest array
     * @return bool
     */
    public function isLastTestCritical()
    {
        return false;
    }

    /**
     * @return CharSheet
     */
    public function createCharSheet()
    {
        return new CharSheet($this);
    }

}
