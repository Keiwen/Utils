<?php

namespace Keiwen\Utils\UnitConversion;


class PressureConverter extends UnitConverter
{

    const PASCAL = 'Pascal';
    const HECTOPASCAL = 'Hectopascal';
    const BAR = 'Bar';
    const MILLIBAR = 'Millibar';
    const ATMOSPHERE = 'Atmosphere';
    const TORR = 'Torr';
    const MILLIMETRE_MERCURY = 'Millimetre of mercury';
    const POUNDFORCE_SQUARE_INCH = 'Pound-force per square inch';

    protected static $siBaseUnit = self::PASCAL;
    protected static $physicalMinimum = 0;
    protected static $physicalMaximum = null;


    /**
     * @inheritdoc
     */
    public static function getUnitSymbol(string $unit) : string
    {
        switch($unit) {
            case static::PASCAL: return 'Pa';
            case static::HECTOPASCAL: return 'hPa';
            case static::BAR: return 'bar';
            case static::MILLIBAR: return 'mbar';
            case static::ATMOSPHERE: return 'atm';
            case static::TORR: return 'torr';
            case static::MILLIMETRE_MERCURY: return 'mmHg';
            case static::POUNDFORCE_SQUARE_INCH: return 'psi';
        }
        return '';
    }


    /**
     * @inheritdoc
     */
    public static function convertToBaseUnit(float $value, string $fromUnit) : float
    {
        switch($fromUnit) {
            case static::HECTOPASCAL: return $value * 100;
            case static::BAR: return $value * (10 ** 5);
            case static::MILLIBAR: return $value * (10 ** 2);
            case static::ATMOSPHERE: return $value * 101325;
            case static::TORR: return $value * 101325 / 760;
            case static::MILLIMETRE_MERCURY: return $value * 133.322387415;
            //pound-force = 1 pound avoirdupois * standard gravity = 0.45359237 kg * 9.80665 m/s²
            //also 1 inch = 0.0254 m
            case static::POUNDFORCE_SQUARE_INCH: return $value * 0.45359237 * 9.80665 / (0.0254 ** 2);
        }
        return $value;
    }


    /**
     * @inheritdoc
     */
    public static function convertFromBaseUnit(float $value, string $toUnit) : float
    {
        switch($toUnit) {
            case static::HECTOPASCAL: return $value / 100;
            case static::BAR: return $value * (10 ** -5);
            case static::MILLIBAR: return $value * (10 ** -2);
            case static::ATMOSPHERE: return $value / 101325;
            case static::TORR: return $value / 101325 * 760;
            case static::MILLIMETRE_MERCURY: return $value / 133.322387415;
            case static::POUNDFORCE_SQUARE_INCH: return $value / 0.45359237 / 9.80665 * (0.0254 ** 2);
        }
        return $value;
    }




}
