<?php

namespace Keiwen\Utils\UnitConversion;


class AreaConverter extends UnitConverter
{

    const SQUARE_METER = 'Square metre';
    const SQUARE_KILOMETRE = 'Square kilometre';
    const SQUARE_DECIMETRE = 'Square decimetre';
    const SQUARE_CENTIMETRE = 'Square centimetre';
    const SQUARE_MILLIMETRE = 'Square millimetre';
    const SQUARE_MICROMETRE = 'Square micrometre';
    const SQUARE_MILE = 'Square mile';
    const SQUARE_INCH = 'Square inch';
    const SQUARE_FOOT = 'Square foot';
    const SQUARE_YARD = 'Square yard';
    const ARE = 'Are';
    const ACRE = 'Acre';
    const HECTARE = 'Hectare';

    protected static $siBaseUnit = self::SQUARE_METER;
    protected static $physicalMinimum = 0;
    protected static $physicalMaximum = null;


    /**
     * @inheritdoc
     */
    public static function getUnitSymbol(string $unit) : string
    {
        switch($unit) {
            case static::SQUARE_METER: return 'm²';
            case static::SQUARE_KILOMETRE: return 'km²';
            case static::SQUARE_DECIMETRE: return 'dm²';
            case static::SQUARE_CENTIMETRE: return 'cm²';
            case static::SQUARE_MILLIMETRE: return 'mm²';
            case static::SQUARE_MICROMETRE: return 'μm²';
            case static::SQUARE_MILE: return 'sq mi';
            case static::SQUARE_INCH: return 'sq in';
            case static::SQUARE_FOOT: return 'sq ft';
            case static::SQUARE_YARD: return 'sq yd';
            case static::ARE: return 'a';
            case static::ACRE: return 'ac';
            case static::HECTARE: return 'ha';
        }
        return '';
    }


    /**
     * @inheritdoc
     */
    public static function convertToBaseUnit(float $value, string $fromUnit) : float
    {
        switch($fromUnit) {
            case static::SQUARE_KILOMETRE: return $value * pow(10, 6);
            case static::SQUARE_DECIMETRE: return $value * pow(10, -2);
            case static::SQUARE_CENTIMETRE: return $value * pow(10, -4);
            case static::SQUARE_MILLIMETRE: return $value * pow(10, -6);
            case static::SQUARE_MICROMETRE: return $value * pow(10, -12);
            case static::SQUARE_MILE: return $value / pow(1609.344, 2);
            case static::SQUARE_INCH: return $value * pow(0.0254, 2);
            case static::SQUARE_FOOT: return $value * pow(0.3048, 2);
            case static::SQUARE_YARD: return $value * pow(0.9144, 2);
            case static::ARE: return $value * 100;
            case static::ACRE: return $value * 4046.8564224;
            case static::HECTARE: return $value * pow(10, 4);
        }
        return $value;
    }


    /**
     * @inheritdoc
     */
    public static function convertFromBaseUnit(float $value, string $toUnit) : float
    {
        switch($toUnit) {
            case static::SQUARE_KILOMETRE: return $value * pow(10, -6);
            case static::SQUARE_DECIMETRE: return $value * pow(10, 2);
            case static::SQUARE_CENTIMETRE: return $value * pow(10, 4);
            case static::SQUARE_MILLIMETRE: return $value * pow(10, 6);
            case static::SQUARE_MICROMETRE: return $value * pow(10, 12);
            case static::SQUARE_MILE: return $value * pow(1609.344, 2);
            case static::SQUARE_INCH: return $value / pow(0.0254, 2);
            case static::SQUARE_FOOT: return $value / pow(0.3048, 2);
            case static::SQUARE_YARD: return $value / pow(0.9144, 2);
            case static::ARE: return $value / 100;
            case static::ACRE: return $value / 4046.8564224;
            case static::HECTARE: return $value * pow(10, -4);
        }
        return $value;
    }




}