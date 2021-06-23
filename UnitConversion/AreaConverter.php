<?php

namespace Keiwen\Utils\UnitConversion;


class AreaConverter extends UnitConverter
{

    public const SQUARE_METER = 'Square metre';
    public const SQUARE_KILOMETRE = 'Square kilometre';
    public const SQUARE_DECIMETRE = 'Square decimetre';
    public const SQUARE_CENTIMETRE = 'Square centimetre';
    public const SQUARE_MILLIMETRE = 'Square millimetre';
    public const SQUARE_MICROMETRE = 'Square micrometre';
    public const SQUARE_MILE = 'Square mile';
    public const SQUARE_INCH = 'Square inch';
    public const SQUARE_FOOT = 'Square foot';
    public const SQUARE_YARD = 'Square yard';
    public const ARE = 'Are';
    public const ACRE = 'Acre';
    public const HECTARE = 'Hectare';


    /**
     * @inheritdoc
     */
    public function getBaseUnit(): string
    {
        return static::SQUARE_METER;
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
            static::SQUARE_METER => 'm²',
            static::SQUARE_KILOMETRE => 'km²',
            static::SQUARE_DECIMETRE => 'dm²',
            static::SQUARE_CENTIMETRE => 'cm²',
            static::SQUARE_MILLIMETRE => 'mm²',
            static::SQUARE_MICROMETRE => 'μm²',
            static::SQUARE_MILE => 'sq mi',
            static::SQUARE_INCH => 'sq in',
            static::SQUARE_FOOT => 'sq ft',
            static::SQUARE_YARD => 'sq yd',
            static::ARE => 'a',
            static::ACRE => 'ac',
            static::HECTARE => 'ha',
        );
    }


    /**
     * @inheritdoc
     */
    public function convertToBaseUnit(float $value, string $fromUnit) : float
    {
        switch($fromUnit) {
            case static::SQUARE_KILOMETRE: return $value * (10 ** 6);
            case static::SQUARE_DECIMETRE: return $value * (10 ** -2);
            case static::SQUARE_CENTIMETRE: return $value * (10 ** -4);
            case static::SQUARE_MILLIMETRE: return $value * (10 ** -6);
            case static::SQUARE_MICROMETRE: return $value * (10 ** -12);
            case static::SQUARE_MILE: return $value / (1609.344 ** 2);
            case static::SQUARE_INCH: return $value * (0.0254 ** 2);
            case static::SQUARE_FOOT: return $value * (0.3048 ** 2);
            case static::SQUARE_YARD: return $value * (0.9144 ** 2);
            case static::ARE: return $value * 100;
            case static::ACRE: return $value * 4046.8564224;
            case static::HECTARE: return $value * (10 ** 4);
        }
        return $value;
    }


    /**
     * @inheritdoc
     */
    public function convertFromBaseUnit(float $value, string $toUnit) : float
    {
        switch($toUnit) {
            case static::SQUARE_KILOMETRE: return $value * (10 ** -6);
            case static::SQUARE_DECIMETRE: return $value * (10 ** 2);
            case static::SQUARE_CENTIMETRE: return $value * (10 ** 4);
            case static::SQUARE_MILLIMETRE: return $value * (10 ** 6);
            case static::SQUARE_MICROMETRE: return $value * (10 ** 12);
            case static::SQUARE_MILE: return $value * (1609.344 ** 2);
            case static::SQUARE_INCH: return $value / (0.0254 ** 2);
            case static::SQUARE_FOOT: return $value / (0.3048 ** 2);
            case static::SQUARE_YARD: return $value / (0.9144 ** 2);
            case static::ARE: return $value / 100;
            case static::ACRE: return $value / 4046.8564224;
            case static::HECTARE: return $value * (10 ** -4);
        }
        return $value;
    }




}
