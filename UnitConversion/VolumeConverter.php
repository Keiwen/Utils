<?php

namespace Keiwen\Utils\UnitConversion;


class VolumeConverter extends UnitConverter
{

    const CUBIC_METER = 'Cubic metre';
    const CUBIC_DECIMETRE = 'Cubic decimetre';
    const CUBIC_CENTIMETRE = 'Cubic centimetre';
    const CUBIC_MILLIMETRE = 'Cubic millimetre';
    const CUBIC_MICROMETRE = 'Cubic micrometre';
    const CUBIC_MILE = 'Cubic mile';
    const CUBIC_INCH = 'Cubic inch';
    const CUBIC_FOOT = 'Cubic foot';
    const CUBIC_YARD = 'Cubic yard';
    const LITRE = 'Litre';
    const DECILITRE = 'Decilitre';
    const CENTILITRE = 'Centilitre';
    const MILLILITRE = 'Millllitre';
    const GALLON_IMP = 'Gallon (imperial)';
    const GALLON_US = 'Gallon (US)';
    const OUNCE_IMP = 'Ounce (imperial)';
    const OUNCE_US_CUSTOMARY = 'Ounce (US customary)';
    const OUNCE_US_LEGAL = 'Ounce (US legal)';
    const CUP_IMP = 'Cup (imperial)';
    const CUP_US_CUSTOMARY = 'Cup (US customary)';
    const CUP_US_LEGAL = 'Cup (US legal)';
    const CUP_CA = 'Cup (CA)';
    const CUP_METRIC = 'Cup (Metric)';


    protected static $siBaseUnit = self::CUBIC_METER;
    protected static $physicalMinimum = 0;
    protected static $physicalMaximum = null;


    /**
     * @inheritdoc
     */
    public static function getUnitSymbol(string $unit) : string
    {
        switch($unit) {
            case static::CUBIC_METER: return 'm³';
            case static::CUBIC_DECIMETRE: return 'dm³';
            case static::CUBIC_CENTIMETRE: return 'cm³';
            case static::CUBIC_MILLIMETRE: return 'mm³';
            case static::CUBIC_MICROMETRE: return 'μm³';
            case static::CUBIC_MILE: return 'cu mi';
            case static::CUBIC_INCH: return 'cu in';
            case static::CUBIC_FOOT: return 'cu ft';
            case static::CUBIC_YARD: return 'cu yd';
            case static::LITRE: return 'L';
            case static::DECILITRE: return 'dL';
            case static::CENTILITRE: return 'cL';
            case static::MILLILITRE: return 'mL';
            case static::GALLON_IMP: return 'gal';
            case static::GALLON_US: return 'gal (us)';
            case static::OUNCE_IMP: return 'fl oz';
            case static::OUNCE_US_CUSTOMARY: return 'US fl oz (customary)';
            case static::OUNCE_US_LEGAL: return 'US fl oz (legal)';
            case static::CUP_IMP: return 'c';
            case static::CUP_US_CUSTOMARY: return 'c (US customary)';
            case static::CUP_US_LEGAL: return 'c (US legal)';
            case static::CUP_CA: return 'c (CA)';
            case static::CUP_METRIC: return 'c (metric)';
        }
        return '';
    }


    /**
     * @inheritdoc
     */
    public static function convertToBaseUnit(float $value, string $fromUnit) : float
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
    public static function convertFromBaseUnit(float $value, string $toUnit) : float
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
