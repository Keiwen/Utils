<?php

namespace Keiwen\Utils\UnitConversion;


class VolumeConverter extends UnitConverter
{

    public const CUBIC_METER = 'Cubic metre';
    public const CUBIC_DECIMETRE = 'Cubic decimetre';
    public const CUBIC_CENTIMETRE = 'Cubic centimetre';
    public const CUBIC_MILLIMETRE = 'Cubic millimetre';
    public const CUBIC_MICROMETRE = 'Cubic micrometre';
    public const CUBIC_MILE = 'Cubic mile';
    public const CUBIC_INCH = 'Cubic inch';
    public const CUBIC_FOOT = 'Cubic foot';
    public const CUBIC_YARD = 'Cubic yard';
    public const LITRE = 'Litre';
    public const DECILITRE = 'Decilitre';
    public const CENTILITRE = 'Centilitre';
    public const MILLILITRE = 'Millilitre';
    public const GALLON_IMP = 'Gallon (imperial)';
    public const GALLON_US = 'Gallon (US)';
    public const OUNCE_IMP = 'Ounce (imperial)';
    public const OUNCE_US_CUSTOMARY = 'Ounce (US customary)';
    public const OUNCE_US_LEGAL = 'Ounce (US legal)';
    public const CUP_IMP = 'Cup (imperial)';
    public const CUP_US_CUSTOMARY = 'Cup (US customary)';
    public const CUP_US_LEGAL = 'Cup (US legal)';
    public const CUP_CA = 'Cup (CA)';
    public const CUP_METRIC = 'Cup (Metric)';


    /**
     * @inheritdoc
     */
    public function getBaseUnit(): string
    {
        return static::CUBIC_METER;
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
            static::CUBIC_METER => 'm³',
            static::CUBIC_DECIMETRE => 'dm³',
            static::CUBIC_CENTIMETRE => 'cm³',
            static::CUBIC_MILLIMETRE => 'mm³',
            static::CUBIC_MICROMETRE => 'μm³',
            static::CUBIC_MILE => 'cu mi',
            static::CUBIC_INCH => 'cu in',
            static::CUBIC_FOOT => 'cu ft',
            static::CUBIC_YARD => 'cu yd',
            static::LITRE => 'L',
            static::DECILITRE => 'dL',
            static::CENTILITRE => 'cL',
            static::MILLILITRE => 'mL',
            static::GALLON_IMP => 'gal',
            static::GALLON_US => 'gal (us)',
            static::OUNCE_IMP => 'fl oz',
            static::OUNCE_US_CUSTOMARY => 'US fl oz (customary)',
            static::OUNCE_US_LEGAL => 'US fl oz (legal)',
            static::CUP_IMP => 'c',
            static::CUP_US_CUSTOMARY => 'c (US customary)',
            static::CUP_US_LEGAL => 'c (US legal)',
            static::CUP_CA => 'c (CA)',
            static::CUP_METRIC => 'c (metric)',
        );
    }


    /**
     * @inheritdoc
     */
    public function convertToBaseUnit(float $value, string $fromUnit) : float
    {
        switch($fromUnit) {
            case static::CUBIC_DECIMETRE: return $value * (10 ** -3);
            case static::CUBIC_CENTIMETRE: return $value * (10 ** -6);
            case static::CUBIC_MILLIMETRE: return $value * (10 ** -9);
            case static::CUBIC_MICROMETRE: return $value * (10 ** -12);
            case static::CUBIC_MILE: return $value / (1609.344 ** 3);
            case static::CUBIC_INCH: return $value * (0.0254 ** 3);
            case static::CUBIC_FOOT: return $value * (0.3048 ** 3);
            case static::CUBIC_YARD: return $value * (0.9144 ** 3);
            case static::LITRE: return $value * (10 ** -3);
            case static::DECILITRE: return $value * (10 ** -4);
            case static::CENTILITRE: return $value * (10 ** -5);
            case static::MILLILITRE: return $value * (10 ** -6);
            case static::GALLON_IMP: return $value * 4.54609 * (10 ** -3);
            case static::GALLON_US: return $value * 3.785411784 * (10 ** -3);
            case static::OUNCE_IMP: return $value * 28.4130625 * (10 ** -6);
            case static::OUNCE_US_CUSTOMARY: return $value * 29.5735295625 * (10 ** -6);
            case static::OUNCE_US_LEGAL: return $value * 30 * (10 ** -6);
            case static::CUP_IMP: return $value * 284.130625 * (10 ** -6);
            case static::CUP_US_CUSTOMARY: return $value * 236.5882365 * (10 ** -6);
            case static::CUP_US_LEGAL: return $value * 240 * (10 ** -6);
            case static::CUP_CA: return $value * 227.3045 * (10 ** -6);
            case static::CUP_METRIC: return $value * 250 * (10 ** -6);
        }
        return $value;
    }


    /**
     * @inheritdoc
     */
    public function convertFromBaseUnit(float $value, string $toUnit) : float
    {
        switch($toUnit) {
            case static::CUBIC_DECIMETRE: return $value * (10 ** 3);
            case static::CUBIC_CENTIMETRE: return $value * (10 ** 6);
            case static::CUBIC_MILLIMETRE: return $value * (10 ** 9);
            case static::CUBIC_MICROMETRE: return $value * (10 ** 12);
            case static::CUBIC_MILE: return $value * (1609.344 ** 3);
            case static::CUBIC_INCH: return $value / (0.0254 ** 3);
            case static::CUBIC_FOOT: return $value / (0.3048 ** 3);
            case static::CUBIC_YARD: return $value / (0.9144 ** 3);
            case static::LITRE: return $value * (10 ** 3);
            case static::DECILITRE: return $value * (10 ** 4);
            case static::CENTILITRE: return $value * (10 ** 5);
            case static::MILLILITRE: return $value * (10 ** 6);
            case static::GALLON_IMP: return $value / 4.54609 * (10 ** 3);
            case static::GALLON_US: return $value / 3.785411784 * (10 ** 3);
            case static::OUNCE_IMP: return $value / 28.4130625 * (10 ** 6);
            case static::OUNCE_US_CUSTOMARY: return $value / 29.5735295625 * (10 ** 6);
            case static::OUNCE_US_LEGAL: return $value / 30 * (10 ** 6);
            case static::CUP_IMP: return $value / 284.130625 * (10 ** 6);
            case static::CUP_US_CUSTOMARY: return $value / 236.5882365 * (10 ** 6);
            case static::CUP_US_LEGAL: return $value / 240 * (10 ** 6);
            case static::CUP_CA: return $value / 227.3045 * (10 ** 6);
            case static::CUP_METRIC: return $value / 250 * (10 ** 6);
        }
        return $value;
    }




}
