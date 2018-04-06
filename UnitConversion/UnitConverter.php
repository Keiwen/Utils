<?php

namespace Keiwen\Utils\UnitConversion;


abstract class UnitConverter
{

    protected static $siBaseUnit = '';
    protected static $physicalMinimum = null;
    protected static $physicalMaximum = null;
    protected static $units = array();

    /**
     * @return string
     */
    public static function getSIBaseUnit() : string {
        return static::$siBaseUnit;
    }

    /**
     * @param string $unit
     * @return bool
     */
    public static function isSIBaseUnit(string $unit) : bool
    {
        return $unit == static::getSIBaseUnit();
    }

    /**
     * @param string $unit
     * @return string
     */
    public abstract static function getUnitSymbol(string $unit) : string;

    /**
     * @param float  $value
     * @param string $fromUnit
     * @param string $toUnit
     * @return float
     */
    public static function convert(float $value, string $fromUnit, string $toUnit) : float
    {
        if($fromUnit == $toUnit) return $value;
        $siValue = static::convertToBaseUnit($value, $fromUnit);
        return static::convertFromBaseUnit($siValue, $toUnit);
    }


    /**
     * @param float  $value
     * @param string $fromUnit
     * @return float
     */
    public abstract static function convertToBaseUnit(float $value, string $fromUnit) : float;


    /**
     * @param float  $value
     * @param string $toUnit
     * @return float
     */
    public abstract static function convertFromBaseUnit(float $value, string $toUnit) : float;


    /**
     * @param float  $value
     * @param string $unit
     * @return bool
     */
    public static function isAbovePhysicalMinimum(float $value, string $unit = '') : bool
    {
        if(static::$physicalMinimum === null) return true;
        $siValue = empty($unit) ? $value : static::convertToBaseUnit($value, $unit);
        return $siValue >= static::$physicalMinimum;
    }

    /**
     * @param float  $value
     * @param string $unit
     * @return bool
     */
    public static function isUnderPhysicalMaximum(float $value, string $unit = '') : bool
    {
        if(static::$physicalMaximum === null) return true;
        $siValue = empty($unit) ? $value : static::convertToBaseUnit($value, $unit);
        return $siValue <= static::$physicalMaximum;
    }


    /**
     * @param float  $value
     * @param string $unit
     * @return bool
     */
    public static function isInPhysicalRange(float $value, string $unit = '') : bool
    {
        return static::isUnderPhysicalMaximum($value, $unit) && static::isUnderPhysicalMaximum($value, $unit);
    }


    /**
     * @param string $unit
     * @return float|null
     */
    public static function getPhysicalMinimum(string $unit = '')
    {
        if(static::$physicalMinimum === null) return null;
        $minValue = static::$physicalMinimum;
        if(!empty($unit)) $minValue = static::convertFromBaseUnit($minValue, $unit);
        return $minValue;
    }


    /**
     * @param string $unit
     * @return float|null
     */
    public static function getPhysicalMaximum(string $unit = '')
    {
        if(static::$physicalMaximum === null) return null;
        $maxValue = static::$physicalMaximum;
        if(!empty($unit)) $maxValue = static::convertFromBaseUnit($maxValue, $unit);
        return $maxValue;
    }


    /**
     * @return array
     */
    public static function getUnits()
    {
        if(empty(static::$units)) {
            $rClass = new \ReflectionClass(static::class);
            static::$units = array_values($rClass->getConstants());
        }
        return static::$units;
    }


    /**
     * @return array
     */
    public static function getUnitsSymbol()
    {
        $units = static::getUnits();
        $unitsSymbol = array_fill_keys($units, '');
        foreach($unitsSymbol as $unit => &$symbol) {
            $symbol = static::getUnitSymbol($unit);
        }
        return $unitsSymbol;
    }


    /**
     * @param string $symbol
     * @return string
     */
    public static function getUnitName(string $symbol)
    {
        $unitsSymbol = array_flip(static::getUnitsSymbol());
        return empty($unitsSymbol[$symbol]) ? '' : $unitsSymbol[$symbol];
    }


    /**
     * @param string $unit
     * @return bool
     */
    public static function isUnitValid(string $unit)
    {
        return in_array($unit, static::getUnits());
    }


    /**
     * @param string $domain
     * @return UnitConverter|null
     */
    public static function getConverter(string $domain)
    {
        $converter = ucfirst(strtolower($domain)) . 'Converter';
        $className = __NAMESPACE__ . '\\' . $converter;
        if(!class_exists($className)) return null;
        $rClass = new \ReflectionClass($className);
        return ($rClass->isInstantiable()) ? new $className() : null;
    }

}
