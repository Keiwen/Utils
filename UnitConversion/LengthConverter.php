<?php

namespace Keiwen\Utils\UnitConversion;


class LengthConverter extends UnitConverter
{

    const METRE = 'Metre';
    const KILOMETRE = 'Kilometre';
    const DECIMETRE = 'Decimetre';
    const CENTIMETRE = 'Centimetre';
    const MILLIMETRE = 'Millimetre';
    const MICROMETRE = 'Micrometre';
    const NANOMETRE = 'Nanometre';
    const PICOMETRE = 'Picometre';
    const FEMTOMETRE = 'Femtometre';
    const LIGHTYEAR = 'Light-year';
    const MILE = 'Mile';
    const NAUTICAL_MILE = 'Nautical mile';
    const INCH = 'Inch';
    const FOOT = 'Foot';
    const YARD = 'Yard';
    const ANGSTROM = 'Ångström';

    protected static $siBaseUnit = self::METRE;
    protected static $physicalMinimum = 0;
    protected static $physicalMaximum = null;


    /**
     * @inheritdoc
     */
    public static function getUnitSymbol(string $unit) : string
    {
        switch($unit) {
            case static::METRE: return 'm';
            case static::KILOMETRE: return 'km';
            case static::DECIMETRE: return 'dm';
            case static::CENTIMETRE: return 'cm';
            case static::MILLIMETRE: return 'mm';
            case static::MICROMETRE: return 'μm';
            case static::NANOMETRE: return 'nm';
            case static::PICOMETRE: return 'pm';
            case static::FEMTOMETRE: return 'fm';
            case static::LIGHTYEAR: return 'ly';
            case static::MILE: return 'mi';
            case static::NAUTICAL_MILE: return 'M';
            case static::INCH: return 'in';
            case static::FOOT: return 'ft';
            case static::YARD: return 'yd';
            case static::ANGSTROM: return 'Å';
        }
        return '';
    }


    /**
     * @inheritdoc
     */
    public static function convertToBaseUnit(float $value, string $fromUnit) : float
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
    public static function convertFromBaseUnit(float $value, string $toUnit) : float
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
