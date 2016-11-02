<?php

namespace Keiwen\Utils\UnitConversion;


class MassConverter extends UnitConverter
{

    const KILOGRAM = 'Kilogram';
    const GRAM = 'gram';
    const DECIGRAM = 'Decigram';
    const CENTIGRAM = 'Centigram';
    const MILLIGRAM = 'Milligram';
    const MICROGRAM = 'Microgram';
    const NANOGRAM = 'Nanogram';
    const PICOGRAM = 'Picogram';
    const HECTOGRAM = 'Hectogram';
    const TONNE = 'Tonne';
    const CARAT = 'Carat';
    const GRAIN = 'Grain';
    const OUNCE_TROY = 'Ounce (troy)';
    const OUNCE_AVOIRDUPOIS = 'Ounce (avoirdupois)';
    const OUNCE_US_LEGAL = 'Ounce (US legal)';
    const POUND_TROY = 'Pound (troy)';
    const POUND_AVOIRDUPOIS = 'Pound (avoirdupois)';
    const POUND_METRIC = 'Pound (metric)';
    const QUINTAL = 'Quintal';
    const TON_IMP = 'Ton (imp)';
    const TON_US = 'Ton (us)';

    protected static $siBaseUnit = self::KILOGRAM;
    protected static $physicalMinimum = 0;
    protected static $physicalMaximum = null;


    /**
     * @inheritdoc
     */
    public static function getUnitSymbol(string $unit) : string
    {
        switch($unit) {
            case static::KILOGRAM: return 'kg';
            case static::GRAM: return 'g';
            case static::DECIGRAM: return 'dg';
            case static::CENTIGRAM: return 'cg';
            case static::MILLIGRAM: return 'mg';
            case static::MICROGRAM: return 'μg';
            case static::NANOGRAM: return 'ng';
            case static::PICOGRAM: return 'pg';
            case static::HECTOGRAM: return 'hg';
            case static::TONNE: return 't';
            case static::CARAT: return 'ct';
            case static::GRAIN: return 'gr';
            case static::OUNCE_TROY: return 'oz t';
            case static::OUNCE_AVOIRDUPOIS: return 'oz av';
            case static::OUNCE_US_LEGAL: return 'oz';
            case static::POUND_TROY: return 'lb t';
            case static::POUND_AVOIRDUPOIS: return 'lb av';
            case static::POUND_METRIC: return 'lb';
            case static::QUINTAL: return 'q';
            case static::TON_IMP: return 'ton';
            case static::TON_US: return 'ton (US)';
        }
        return '';
    }


    /**
     * @inheritdoc
     */
    public static function convertToBaseUnit(float $value, string $fromUnit) : float
    {
        switch($fromUnit) {
            case static::GRAM: return $value / 1000;
            case static::DECIGRAM: return $value * pow(10, -4);
            case static::CENTIGRAM: return $value * pow(10, -5);
            case static::MILLIGRAM: return $value * pow(10, -6);
            case static::MICROGRAM: return $value * pow(10, -9);
            case static::NANOGRAM: return $value * pow(10, -12);
            case static::PICOGRAM: return $value * pow(10, -15);
            case static::HECTOGRAM: return $value / 10;
            case static::TONNE: return $value * 1000;
            case static::CARAT: return $value * 200 * pow(10, -6);
            case static::GRAIN: return $value * 64.79891 * pow(10, -6);
            case static::OUNCE_TROY: return $value * 31.1034768 * pow(10, -3);
            case static::OUNCE_AVOIRDUPOIS: return $value * 28.349523125 * pow(10, -3);
            case static::OUNCE_US_LEGAL: return $value * 28 * pow(10, -3);
            case static::POUND_TROY: return $value * .3732417216;
            case static::POUND_AVOIRDUPOIS: return $value * 0.45359237;
            case static::POUND_METRIC: return $value * 0.5;
            case static::QUINTAL: return $value * 100;
            case static::TON_IMP: return $value * 1016.0469088;
            case static::TON_US: return $value * 907.18474;
        }
        return $value;
    }


    /**
     * @inheritdoc
     */
    public static function convertFromBaseUnit(float $value, string $toUnit) : float
    {
        switch($toUnit) {
            case static::GRAM: return $value * 1000;
            case static::DECIGRAM: return $value * pow(10, 4);
            case static::CENTIGRAM: return $value * pow(10, 5);
            case static::MILLIGRAM: return $value * pow(10, 6);
            case static::MICROGRAM: return $value * pow(10, 9);
            case static::NANOGRAM: return $value * pow(10, 12);
            case static::PICOGRAM: return $value * pow(10, 15);
            case static::HECTOGRAM: return $value * 10;
            case static::TONNE: return $value / 1000;
            case static::CARAT: return $value / 200 * pow(10, 6);
            case static::GRAIN: return $value / 64.79891 * pow(10, 6);
            case static::OUNCE_TROY: return $value / 31.1034768 * pow(10, 3);
            case static::OUNCE_AVOIRDUPOIS: return $value / 28.349523125 * pow(10, 3);
            case static::OUNCE_US_LEGAL: return $value / 28 * pow(10, 3);
            case static::POUND_TROY: return $value / .3732417216;
            case static::POUND_AVOIRDUPOIS: return $value / 0.45359237;
            case static::POUND_METRIC: return $value / 0.5;
            case static::QUINTAL: return $value / 100;
            case static::TON_IMP: return $value / 1016.0469088;
            case static::TON_US: return $value / 907.18474;
        }
        return $value;
    }




}