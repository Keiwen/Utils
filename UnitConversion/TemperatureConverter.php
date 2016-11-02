<?php

namespace Keiwen\Utils\UnitConversion;


class TemperatureConverter extends UnitConverter
{

    const KELVIN = 'Kelvin';
    const CELSIUS = 'Degree Celsius';
    const FAHRENHEIT = 'Degree Fahrenheit';
    const NEWTON = 'Degree Newton';
    const RANKINE = 'Degree Rankine';
    const DELISLE = 'Degree Delisle';
    const REAUMUR = 'Degree Réaumur';
    const ROMER = 'Degree Rømer';

    protected static $siBaseUnit = self::KELVIN;
    protected static $physicalMinimum = 0;
    protected static $physicalMaximum = null;


    /**
     * @inheritdoc
     */
    public static function getUnitSymbol(string $unit) : string
    {
        switch($unit) {
            case static::KELVIN: return 'K';
            case static::CELSIUS: return '°C';
            case static::FAHRENHEIT: return '°F';
            case static::NEWTON: return '°N';
            case static::RANKINE: return '°R';
            case static::DELISLE: return '°De';
            case static::REAUMUR: return '°Ré';
            case static::ROMER: return '°Rø';
        }
        return '';
    }


    /**
     * @inheritdoc
     */
    public static function convertToBaseUnit(float $value, string $fromUnit) : float
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
    public static function convertFromBaseUnit(float $value, string $toUnit) : float
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