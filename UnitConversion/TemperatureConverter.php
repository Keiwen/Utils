<?php

namespace Keiwen\Utils\UnitConversion;


class TemperatureConverter extends UnitConverter
{

    public const KELVIN = 'Kelvin';
    public const CELSIUS = 'Degree Celsius';
    public const FAHRENHEIT = 'Degree Fahrenheit';
    public const NEWTON = 'Degree Newton';
    public const RANKINE = 'Degree Rankine';
    public const DELISLE = 'Degree Delisle';
    public const REAUMUR = 'Degree Réaumur';
    public const ROMER = 'Degree Rømer';


    /**
     * @inheritdoc
     */
    public function getBaseUnit(): string
    {
        return static::KELVIN;
    }

    /**
     * @inheritdoc
     */
    public function getBasePhysicalMinimum()
    {
        return 0;
    }

    /**
     * @inheritdoc
     */
    public function getBasePhysicalMaximum()
    {
        return null;
    }


    /**
     * @inheritdoc
     */
    public function getUnitsSymbol(): array
    {
        return array(
            static::KELVIN => 'K',
            static::CELSIUS => '°C',
            static::FAHRENHEIT => '°F',
            static::NEWTON => '°N',
            static::RANKINE => '°R',
            static::DELISLE => '°De',
            static::REAUMUR => '°Ré',
            static::ROMER => '°Rø',
        );
    }


    /**
     * @inheritdoc
     */
    public function convertToBaseUnit(float $value, string $fromUnit) : float
    {
        switch($fromUnit) {
            case static::CELSIUS: return $value + 273.15;
            case static::FAHRENHEIT: return ($value + 459.67) * 5 / 9;
            case static::NEWTON: return $value * 100 / 33 + 273.15;
            case static::RANKINE: return $value * 5 / 9;
            case static::DELISLE: return 373.15 - $value * 2 / 3;
            case static::REAUMUR: return $value * 5 / 4 + 273.15;
            case static::ROMER: return ($value - 7.5) * 40 / 21 + 273.15;
        }
        return $value;
    }


    /**
     * @inheritdoc
     */
    public function convertFromBaseUnit(float $value, string $toUnit) : float
    {
        switch($toUnit) {
            case static::CELSIUS: return $value - 273.15;
            case static::FAHRENHEIT: return $value * 9 / 5 - 459.67;
            case static::NEWTON: return ($value - 273.15) * 33 / 100;
            case static::RANKINE: return $value * 9 / 5;
            case static::DELISLE: return (373.15 - $value) * 3 / 2;
            case static::REAUMUR: return ($value - 273.15) * 4 / 5;
            case static::ROMER: return ($value - 273.15) * 21 / 40 + 7.5;
        }
        return $value;
    }




}
