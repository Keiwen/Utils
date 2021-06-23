<?php

namespace Keiwen\Utils\RolePlay;

/**
 * Class RPTest Tests for Role Play. Characters have a list of attribute valued.
 * Test an attribute to determine whether character succeed or failed.
 * @package Keiwen\Utils\Random
 */
class CharSheet
{

    /** @var RolePlaySystem $system */
    protected $system;

    /** @var array $attributesValue */
    protected $attributesValue;

    /**
     * Test constructor.
     * @param string[] $attributes
     * @param array $values
     */
    public function __construct(RolePlaySystem $system, array $values = array())
    {
        $this->system = $system;
        $attributes = $system->getAttributes();
        $values = array_values($values);
        foreach($attributes as $index => $attribute) {
            $value = $values[$index] ?? $system->getMinForAttribute();
            $this->setAttribute($attribute, $value);
        }
    }


    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributesValue;
    }

    /**
     * @param string $attribute
     * @return int
     */
    public function getAttribute(string $attribute)
    {
        return $this->attributesValue[$attribute] ?? $this->system->getMinForAttribute();
    }


    /**
     * @param string $attribute
     * @param int|null $value null to generate a random value
     */
    public function setAttribute(string $attribute, int $value = null)
    {
        //no check in system if attribute exists: no constraint here
        $value = $this->system->validateValue($value);
        $this->attributesValue[$attribute] = $value;
    }


    /**
     * @param string $attribute
     * @param int|null $value null to generate a random value
     * @return bool passed
     */
    public function testAttribute(string $attribute, int $value = null)
    {
        return $this->system->isTestSucceed($this->getAttribute($attribute), $value);
    }


    /**
     * @return RolePlaySystem
     */
    public function getSystem()
    {
        return $this->system;
    }


    /**
     *
     */
    public function reGenerateValues()
    {
        foreach($this->attributesValue as $attribute => $value) {
            $this->setAttribute($attribute);
        }
    }

}
