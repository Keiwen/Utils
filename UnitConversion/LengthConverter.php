<?php

namespace Keiwen\Utils\UnitConversion;


class LengthConverter extends UnitConverter
{

    public const METRE = 'Metre';
    public const KILOMETRE = 'Kilometre';
    public const DECIMETRE = 'Decimetre';
    public const CENTIMETRE = 'Centimetre';
    public const MILLIMETRE = 'Millimetre';
    public const MICROMETRE = 'Micrometre';
    public const NANOMETRE = 'Nanometre';
    public const PICOMETRE = 'Picometre';
    public const FEMTOMETRE = 'Femtometre';
    public const LIGHTYEAR = 'Light-year';
    public const MILE = 'Mile';
    public const NAUTICAL_MILE = 'Nautical mile';
    public const INCH = 'Inch';
    public const FOOT = 'Foot';
    public const YARD = 'Yard';
    public const ANGSTROM = 'Ångström';



    /**
     * @inheritdoc
     */
    public function getBaseUnit(): string
    {
        return static::METRE;
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
            static::METRE => 'm',
            static::KILOMETRE => 'km',
            static::DECIMETRE => 'dm',
            static::CENTIMETRE => 'cm',
            static::MILLIMETRE => 'mm',
            static::MICROMETRE => 'μm',
            static::NANOMETRE => 'nm',
            static::PICOMETRE => 'pm',
            static::FEMTOMETRE => 'fm',
            static::LIGHTYEAR => 'ly',
            static::MILE => 'mi',
            static::NAUTICAL_MILE => 'M',
            static::INCH => 'in',
            static::FOOT => 'ft',
            static::YARD => 'yd',
            static::ANGSTROM => 'Å',
        );
    }


    /**
     * @inheritdoc
     */
    public function convertToBaseUnit(float $value, string $fromUnit) : float
    {
        switch($fromUnit) {
            case static::KILOMETRE: return $value * 1000;
            case static::DECIMETRE: return $value / 10;
            case static::CENTIMETRE: return $value / 100;
            case static::MILLIMETRE: return $value / 1000;
            case static::MICROMETRE: return $value * (10 ** -6);
            case static::NANOMETRE: return $value * (10 ** -9);
            case static::PICOMETRE: return $value * (10 ** -12);
            case static::FEMTOMETRE: return $value * (10 ** -15);
            case static::LIGHTYEAR: return $value * 9460730472580800;
            case static::MILE: return $value * 1609.344;
            case static::NAUTICAL_MILE: return $value * 1852;
            case static::INCH: return $value / 0.0254;
            case static::FOOT: return $value / 0.3048;
            case static::YARD: return $value / 0.9144;
            case static::ANGSTROM: return $value * (10 ** -10);
        }
        return $value;
    }


    /**
     * @inheritdoc
     */
    public function convertFromBaseUnit(float $value, string $toUnit) : float
    {
        switch($toUnit) {
            case static::KILOMETRE: return $value / 1000;
            case static::DECIMETRE: return $value * 10;
            case static::CENTIMETRE: return $value * 100;
            case static::MILLIMETRE: return $value * 1000;
            case static::MICROMETRE: return $value * (10 ** 6);
            case static::NANOMETRE: return $value * (10 ** 9);
            case static::PICOMETRE: return $value * (10 ** 12);
            case static::FEMTOMETRE: return $value * (10 ** 15);
            case static::LIGHTYEAR: return $value / 9460730472580800;
            case static::MILE: return $value / 1609.344;
            case static::NAUTICAL_MILE: return $value / 1852;
            case static::INCH: return $value * 0.0254;
            case static::FOOT: return $value * 0.3048;
            case static::YARD: return $value * 0.9144;
            case static::ANGSTROM: return $value * (10 ** 10);
        }
        return $value;
    }




}
