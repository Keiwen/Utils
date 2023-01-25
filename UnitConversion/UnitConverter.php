<?php

namespace Keiwen\Utils\UnitConversion;


abstract class UnitConverter
{

    const DOMAIN_AREA = 'area';
    const DOMAIN_ENERGY = 'energy';
    const DOMAIN_LENGTH = 'length';
    const DOMAIN_MASS = 'mass';
    const DOMAIN_PLANE_ANGLE = 'planeAngle';
    const DOMAIN_PRESSURE = 'pressure';
    const DOMAIN_SPEED = 'speed';
    const DOMAIN_TEMPERATURE = 'temperature';
    const DOMAIN_VOLUME = 'volume';

    public function __construct()
    {
    }

    /**
     * @return string
     */
    public abstract function getBaseUnit(): string;

    /**
     * @param string $unit
     * @return bool
     */
    public function isBaseUnit(string $unit): bool
    {
        return $unit === $this->getBaseUnit();
    }

    /**
     * @param float  $value
     * @param string $fromUnit
     * @param string $toUnit
     * @return float
     */
    public function convert(float $value, string $fromUnit, string $toUnit): float
    {
        if($fromUnit == $toUnit) return $value;
        $baseValue = $this->convertToBaseUnit($value, $fromUnit);
        return $this->convertFromBaseUnit($baseValue, $toUnit);
    }


    /**
     * @return float|null
     */
    public abstract function getBasePhysicalMinimum();

    /**
     * @return float|null
     */
    public abstract function getBasePhysicalMaximum();


    /**
     * @param float  $value
     * @param string $fromUnit
     * @return float
     */
    public abstract function convertToBaseUnit(float $value, string $fromUnit): float;


    /**
     * @param float  $value
     * @param string $toUnit
     * @return float
     */
    public abstract function convertFromBaseUnit(float $value, string $toUnit): float;


    /**
     * @param float  $value
     * @param string $unit
     * @return bool
     */
    public function isAbovePhysicalMinimum(float $value, string $unit = ''): bool
    {
        if($this->getBasePhysicalMinimum() === null) return true;
        $baseValue = empty($unit) ? $value : $this->convertToBaseUnit($value, $unit);
        return $baseValue >= $this->getBasePhysicalMinimum();
    }

    /**
     * @param float  $value
     * @param string $unit
     * @return bool
     */
    public function isUnderPhysicalMaximum(float $value, string $unit = ''): bool
    {
        if($this->getBasePhysicalMaximum() === null) return true;
        $baseValue = empty($unit) ? $value : $this->convertToBaseUnit($value, $unit);
        return $baseValue <= $this->getBasePhysicalMaximum();
    }


    /**
     * @param float  $value
     * @param string $unit
     * @return bool
     */
    public function isInPhysicalRange(float $value, string $unit = ''): bool
    {
        return $this->isAbovePhysicalMinimum($value, $unit) && $this->isUnderPhysicalMaximum($value, $unit);
    }


    /**
     * @param string $unit
     * @return float|null
     */
    public function getPhysicalMinimum(string $unit = '')
    {
        if($this->getBasePhysicalMinimum() === null) return null;
        $minValue = $this->getBasePhysicalMinimum();
        if(!empty($unit)) $minValue = $this->convertFromBaseUnit($minValue, $unit);
        return $minValue;
    }


    /**
     * @param string $unit
     * @return float|null
     */
    public function getPhysicalMaximum(string $unit = '')
    {
        if($this->getBasePhysicalMaximum() === null) return null;
        $maxValue = $this->getBasePhysicalMaximum();
        if(!empty($unit)) $maxValue = $this->convertFromBaseUnit($maxValue, $unit);
        return $maxValue;
    }


    /**
     * @return array unitName => $symbol
     */
    public abstract function getUnitsSymbol(): array;

    /**
     * @return array
     */
    public function getUnits(): array
    {
        $units = $this->getUnitsSymbol();
        return array_keys($units);
    }


    /**
     * @param string $unit
     * @return string
     */
    public function getUnitSymbol(string $unit): string
    {
        $units = $this->getUnitsSymbol();
        return $units[$unit] ?? '';
    }


    /**
     * @param string $symbol
     * @return string
     */
    public function getUnitName(string $symbol): string
    {
        $unitsSymbol = array_flip($this->getUnitsSymbol());
        return $unitsSymbol[$symbol] ?? '';
    }


    /**
     * @param string $unit
     * @return bool
     */
    public function isUnitValid(string $unit): bool
    {
        return in_array($unit, $this->getUnits());
    }


    /**
     * @param string $domain you can use constants DOMAIN_XXXX
     * @param string|null $namespace specify if specific converter defined outside library
     * @return UnitConverter|null
     */
    public static function getConverter(string $domain, string $namespace = null)
    {
        if($namespace == null) $namespace = __NAMESPACE__;
        $converter = ucfirst(strtolower($domain)) . 'Converter';
        $className = $namespace . '\\' . $converter;
        if(!class_exists($className)) return null;
        return new $className();
    }

}
