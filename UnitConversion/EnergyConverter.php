<?php

namespace Keiwen\Utils\UnitConversion;


class EnergyConverter extends UnitConverter
{

    public const JOULE = 'Joule';
    public const KILOJOULE = 'Kilojoule';
    public const CALORIE = 'Calorie';
    public const KILOCALORIE = 'Kilocalorie';
    public const WATT_HOUR = 'Watt hour';
    public const KILOWATT_HOUR = 'Kilowatt hour';
    public const BRITISH_THERMAL = 'British thermal unit';
    public const FOOT_POUNDFORCE = 'Foot pound-force';
    public const INCH_POUNDFORCE = 'Inch pound-force';
    public const BARREL_OF_OIL = 'Barrel of oil equivalent';
    public const ELECTRONVOLT = 'Electronvolt';

    /**
     * @inheritdoc
     */
    public function getBaseUnit(): string
    {
        return static::JOULE;
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
            static::JOULE => 'J',
            static::KILOJOULE => 'kJ',
            static::CALORIE => 'cal',
            static::KILOCALORIE => 'kcal',
            static::WATT_HOUR => 'Wh',
            static::KILOWATT_HOUR => 'kWh',
            static::BRITISH_THERMAL => 'BTU',
            static::FOOT_POUNDFORCE => 'ft lbf',
            static::INCH_POUNDFORCE => 'in lbf',
            static::BARREL_OF_OIL => 'boe',
            static::ELECTRONVOLT => 'eV',
        );
    }


    /**
     * @inheritdoc
     */
    public function convertToBaseUnit(float $value, string $fromUnit) : float
    {
        switch($fromUnit) {
            case static::KILOJOULE: return $value * 1000;
            case static::CALORIE: return $value * 4.1868;
            case static::KILOCALORIE: return $value * 4.1868 * (10 ** 3);
            case static::WATT_HOUR: return $value * 3.6 * (10 ** 3);
            case static::KILOWATT_HOUR: return $value * 3.6 * (10 ** 6);
            case static::BRITISH_THERMAL: return $value * 1.0545 * (10 ** 3);
            case static::FOOT_POUNDFORCE: return $value * 1.3558179483314004;
            case static::INCH_POUNDFORCE: return $value * 0.1129848290276167;
            case static::BARREL_OF_OIL: return $value * 5.8 * 1.0545 * (10 ** 9); //5.8 BTU
            case static::ELECTRONVOLT: return $value * (10 ** -7);
        }
        return $value;
    }


    /**
     * @inheritdoc
     */
    public function convertFromBaseUnit(float $value, string $toUnit) : float
    {
        switch($toUnit) {
            case static::KILOJOULE: return $value / 1000;
            case static::CALORIE: return $value / 4.1868;
            case static::KILOCALORIE: return $value / 4.1868 * (10 ** -3);
            case static::WATT_HOUR: return $value / 3.6 * (10 ** -3);
            case static::KILOWATT_HOUR: return $value / 3.6 * (10 ** -6);
            case static::BRITISH_THERMAL: return $value / 1.0545 * (10 ** -3);
            case static::FOOT_POUNDFORCE: return $value / 1.3558179483314004;
            case static::INCH_POUNDFORCE: return $value / 0.1129848290276167;
            case static::BARREL_OF_OIL: return $value / 5.8 / 1.0545 * (10 ** -9);
            case static::ELECTRONVOLT: return $value * (10 ** 7);
        }
        return $value;
    }




}
