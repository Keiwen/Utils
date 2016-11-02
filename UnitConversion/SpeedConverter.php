<?php

namespace Keiwen\Utils\UnitConversion;


class SpeedConverter extends UnitConverter
{

    const METRE_PER_SECOND = 'Metre per second';
    const KILOMETRE_PER_HOUR = 'Kilometre per hour';
    const MILE_PER_SECOND = 'Mile per second';
    const MILE_PER_HOUR = 'Mile per hour';
    const FOOT_PER_SECOND = 'Foot per second';
    const INCH_PER_SECOND = 'Inch per second';
    const KNOT = 'Knot';
    const MACH = 'Mach';
    const SPEED_OF_LIGHT = 'Speed of light in vacuum';

    protected static $siBaseUnit = self::METRE_PER_SECOND;
    protected static $physicalMinimum = 0;
    protected static $physicalMaximum = null;


    /**
     * @inheritdoc
     */
    public static function getUnitSymbol(string $unit) : string
    {
        switch($unit) {
            case static::METRE_PER_SECOND: return 'm/s';
            case static::KILOMETRE_PER_HOUR: return 'km/h';
            case static::MILE_PER_SECOND: return 'mps';
            case static::MILE_PER_HOUR: return 'mph';
            case static::FOOT_PER_SECOND: return 'fps';
            case static::INCH_PER_SECOND: return 'ips';
            case static::KNOT: return 'kn';
            case static::MACH: return 'M';
            case static::SPEED_OF_LIGHT: return 'c';
        }
        return '';
    }


    /**
     * @inheritdoc
     */
    public static function convertToBaseUnit(float $value, string $fromUnit) : float
    {
        switch($fromUnit) {
            case static::KILOMETRE_PER_HOUR: return $value * 1000 / 3600;
            case static::MILE_PER_SECOND: return $value * 1609.344;
            case static::MILE_PER_HOUR: return $value * 1609.344 / 3600;
            case static::FOOT_PER_SECOND: return $value / 0.0254;
            case static::INCH_PER_SECOND: return $value / 0.3048;
            case static::KNOT: return $value * 1.852 * 1000 / 3600;
            case static::MACH: return $value * 340.3;
            case static::SPEED_OF_LIGHT: return $value * 299792458;
        }
        return $value;
    }


    /**
     * @inheritdoc
     */
    public static function convertFromBaseUnit(float $value, string $toUnit) : float
    {
        switch($toUnit) {
            case static::KILOMETRE_PER_HOUR: return $value / 1000 * 3600;
            case static::MILE_PER_SECOND: return $value / 1609.344;
            case static::MILE_PER_HOUR: return $value / 1609.344 * 3600;
            case static::FOOT_PER_SECOND: return $value * 0.0254;
            case static::INCH_PER_SECOND: return $value * 0.3048;
            case static::KNOT: return $value / 1.852 / 1000 * 3600;
            case static::MACH: return $value / 340.3;
            case static::SPEED_OF_LIGHT: return $value / 299792458;
        }
        return $value;
    }




}