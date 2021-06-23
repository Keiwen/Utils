<?php

namespace Keiwen\Utils\UnitConversion;


class SpeedConverter extends UnitConverter
{

    public const METRE_PER_SECOND = 'Metre per second';
    public const KILOMETRE_PER_HOUR = 'Kilometre per hour';
    public const MILE_PER_SECOND = 'Mile per second';
    public const MILE_PER_HOUR = 'Mile per hour';
    public const FOOT_PER_SECOND = 'Foot per second';
    public const INCH_PER_SECOND = 'Inch per second';
    public const KNOT = 'Knot';
    public const MACH = 'Mach';
    public const SPEED_OF_LIGHT = 'Speed of light in vacuum';

    /**
     * @inheritdoc
     */
    public function getBaseUnit(): string
    {
        return static::METRE_PER_SECOND;
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
            static::METRE_PER_SECOND => 'm/s',
            static::KILOMETRE_PER_HOUR => 'km/h',
            static::MILE_PER_SECOND => 'mps',
            static::MILE_PER_HOUR => 'mph',
            static::FOOT_PER_SECOND => 'fps',
            static::INCH_PER_SECOND => 'ips',
            static::KNOT => 'kn',
            static::MACH => 'M',
            static::SPEED_OF_LIGHT => 'c',
        );
    }


    /**
     * @inheritdoc
     */
    public function convertToBaseUnit(float $value, string $fromUnit) : float
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
    public function convertFromBaseUnit(float $value, string $toUnit) : float
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
