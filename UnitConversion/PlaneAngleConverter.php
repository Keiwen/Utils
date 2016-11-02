<?php

namespace Keiwen\Utils\UnitConversion;


class PlaneAngleConverter extends UnitConverter
{

    const RADIAN = 'Radian';
    const DEGREE = 'Degree';
    const ARCMINUTE = 'Arc minute';
    const ARCSECOND = 'Arc second';
    const GRADIAN = 'Gradian';
    const MILLIRADIAN = 'Milliradian';

    protected static $siBaseUnit = self::RADIAN;
    protected static $physicalMinimum = 0;
    protected static $physicalMaximum = null;


    /**
     * @inheritdoc
     */
    public static function getUnitSymbol(string $unit) : string
    {
        switch($unit) {
            case static::RADIAN: return 'rad';
            case static::DEGREE: return '°';
            case static::ARCMINUTE: return '\'';
            case static::ARCSECOND: return '"';
            case static::GRADIAN: return 'grad';
            case static::MILLIRADIAN: return 'mil';
        }
        return '';
    }


    /**
     * @inheritdoc
     */
    public static function convertToBaseUnit(float $value, string $fromUnit) : float
    {
        switch($fromUnit) {
            case static::DEGREE: return $value / M_PI * 180;
            case static::ARCMINUTE: return $value / M_PI * 180 * 60;
            case static::ARCSECOND: return $value / M_PI * 180 * 3600;
            case static::GRADIAN: return $value / M_PI * 200;
            case static::MILLIRADIAN: return $value / 2 / M_PI * 6400;
        }
        return $value;
    }


    /**
     * @inheritdoc
     */
    public static function convertFromBaseUnit(float $value, string $toUnit) : float
    {
        switch($toUnit) {
            case static::DEGREE: return $value * M_PI / 180;
            case static::ARCMINUTE: return $value * M_PI / 180 / 60;
            case static::ARCSECOND: return $value * M_PI / 180 / 3600;
            case static::GRADIAN: return $value * M_PI / 200;
            case static::MILLIRADIAN: return $value * 2 * M_PI / 6400;
        }
        return $value;
    }




}