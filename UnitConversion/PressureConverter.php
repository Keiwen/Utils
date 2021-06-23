<?php

namespace Keiwen\Utils\UnitConversion;


class PressureConverter extends UnitConverter
{

    public const PASCAL = 'Pascal';
    public const HECTOPASCAL = 'Hectopascal';
    public const BAR = 'Bar';
    public const MILLIBAR = 'Millibar';
    public const ATMOSPHERE = 'Atmosphere';
    public const TORR = 'Torr';
    public const MILLIMETRE_MERCURY = 'Millimetre of mercury';
    public const POUNDFORCE_SQUARE_INCH = 'Pound-force per square inch';

    /**
     * @inheritdoc
     */
    public function getBaseUnit(): string
    {
        return static::PASCAL;
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
            static::PASCAL => 'Pa',
            static::HECTOPASCAL => 'hPa',
            static::BAR => 'bar',
            static::MILLIBAR => 'mbar',
            static::ATMOSPHERE => 'atm',
            static::TORR => 'torr',
            static::MILLIMETRE_MERCURY => 'mmHg',
            static::POUNDFORCE_SQUARE_INCH => 'psi',
        );
    }


    /**
     * @inheritdoc
     */
    public function convertToBaseUnit(float $value, string $fromUnit) : float
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
    public function convertFromBaseUnit(float $value, string $toUnit) : float
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
