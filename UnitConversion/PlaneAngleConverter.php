<?php

namespace Keiwen\Utils\UnitConversion;


class PlaneAngleConverter extends UnitConverter
{

    public const RADIAN = 'Radian';
    public const DEGREE = 'Degree';
    public const ARCMINUTE = 'Arc minute';
    public const ARCSECOND = 'Arc second';
    public const GRADIAN = 'Gradian';
    public const MILLIRADIAN = 'Milliradian';

    /**
     * @inheritdoc
     */
    public function getBaseUnit(): string
    {
        return static::RADIAN;
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
            static::RADIAN => 'rad',
            static::DEGREE => '°',
            static::ARCMINUTE => '\'',
            static::ARCSECOND => '"',
            static::GRADIAN => 'grad',
            static::MILLIRADIAN => 'mil',
        );
    }


    /**
     * @inheritdoc
     */
    public function convertToBaseUnit(float $value, string $fromUnit) : float
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
    public function convertFromBaseUnit(float $value, string $toUnit) : float
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
