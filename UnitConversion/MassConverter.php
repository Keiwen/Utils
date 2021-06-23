<?php

namespace Keiwen\Utils\UnitConversion;


class MassConverter extends UnitConverter
{

    public const KILOGRAM = 'Kilogram';
    public const GRAM = 'gram';
    public const DECIGRAM = 'Decigram';
    public const CENTIGRAM = 'Centigram';
    public const MILLIGRAM = 'Milligram';
    public const MICROGRAM = 'Microgram';
    public const NANOGRAM = 'Nanogram';
    public const PICOGRAM = 'Picogram';
    public const HECTOGRAM = 'Hectogram';
    public const TONNE = 'Tonne';
    public const CARAT = 'Carat';
    public const GRAIN = 'Grain';
    public const OUNCE_TROY = 'Ounce (troy)';
    public const OUNCE_AVOIRDUPOIS = 'Ounce (avoirdupois)';
    public const OUNCE_US_LEGAL = 'Ounce (US legal)';
    public const POUND_TROY = 'Pound (troy)';
    public const POUND_AVOIRDUPOIS = 'Pound (avoirdupois)';
    public const POUND_METRIC = 'Pound (metric)';
    public const QUINTAL = 'Quintal';
    public const TON_IMP = 'Ton (imp)';
    public const TON_US = 'Ton (us)';

    /**
     * @inheritdoc
     */
    public function getBaseUnit(): string
    {
        return static::KILOGRAM;
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
            static::KILOGRAM => 'kg',
            static::GRAM => 'g',
            static::DECIGRAM => 'dg',
            static::CENTIGRAM => 'cg',
            static::MILLIGRAM => 'mg',
            static::MICROGRAM => 'μg',
            static::NANOGRAM => 'ng',
            static::PICOGRAM => 'pg',
            static::HECTOGRAM => 'hg',
            static::TONNE => 't',
            static::CARAT => 'ct',
            static::GRAIN => 'gr',
            static::OUNCE_TROY => 'oz t',
            static::OUNCE_AVOIRDUPOIS => 'oz av',
            static::OUNCE_US_LEGAL => 'oz',
            static::POUND_TROY => 'lb t',
            static::POUND_AVOIRDUPOIS => 'lb av',
            static::POUND_METRIC => 'lb',
            static::QUINTAL => 'q',
            static::TON_IMP => 'ton',
            static::TON_US => 'ton (US)',
        );
    }


    /**
     * @inheritdoc
     */
    public function convertToBaseUnit(float $value, string $fromUnit) : float
    {
        switch($fromUnit) {
            case static::GRAM: return $value / 1000;
            case static::DECIGRAM: return $value * (10 ** -4);
            case static::CENTIGRAM: return $value * (10 ** -5);
            case static::MILLIGRAM: return $value * (10 ** -6);
            case static::MICROGRAM: return $value * (10 ** -9);
            case static::NANOGRAM: return $value * (10 ** -12);
            case static::PICOGRAM: return $value * (10 ** -15);
            case static::HECTOGRAM: return $value / 10;
            case static::TONNE: return $value * 1000;
            case static::CARAT: return $value * 200 * (10 ** -6);
            case static::GRAIN: return $value * 64.79891 * (10 ** -6);
            case static::OUNCE_TROY: return $value * 31.1034768 * (10 ** -3);
            case static::OUNCE_AVOIRDUPOIS: return $value * 28.349523125 * (10 ** -3);
            case static::OUNCE_US_LEGAL: return $value * 28 * (10 ** -3);
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
    public function convertFromBaseUnit(float $value, string $toUnit) : float
    {
        switch($toUnit) {
            case static::GRAM: return $value * 1000;
            case static::DECIGRAM: return $value * (10 ** 4);
            case static::CENTIGRAM: return $value * (10 ** 5);
            case static::MILLIGRAM: return $value * (10 ** 6);
            case static::MICROGRAM: return $value * (10 ** 9);
            case static::NANOGRAM: return $value * (10 ** 12);
            case static::PICOGRAM: return $value * (10 ** 15);
            case static::HECTOGRAM: return $value * 10;
            case static::TONNE: return $value / 1000;
            case static::CARAT: return $value / 200 * (10 ** 6);
            case static::GRAIN: return $value / 64.79891 * (10 ** 6);
            case static::OUNCE_TROY: return $value / 31.1034768 * (10 ** 3);
            case static::OUNCE_AVOIRDUPOIS: return $value / 28.349523125 * (10 ** 3);
            case static::OUNCE_US_LEGAL: return $value / 28 * (10 ** 3);
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
