<?php

namespace Keiwen\Utils\UnitConversion;


class EnergyConverter extends UnitConverter
{

    const JOULE = 'Joule';
    const KILOJOULE = 'Kilojoule';
    const CALORIE = 'Calorie';
    const KILOCALORIE = 'Kilocalorie';
    const WATT_HOUR = 'Watt hour';
    const KILOWATT_HOUR = 'Kilowatt hour';
    const BRITISH_THERMAL = 'British thermal unit';
    const FOOT_POUNDFORCE = 'Foot pound-force';
    const INCH_POUNDFORCE = 'Inch pound-force';
    const BARREL_OF_OIL = 'Barrel of oil equivalent';
    const ELECTRONVOLT = 'Electronvolt';

    protected static $siBaseUnit = self::JOULE;
    protected static $physicalMinimum = 0;
    protected static $physicalMaximum = null;


    /**
     * @inheritdoc
     */
    public static function getUnitSymbol(string $unit) : string
    {
        switch($unit) {
            case static::JOULE: return 'J';
            case static::KILOJOULE: return 'kJ';
            case static::CALORIE: return 'cal';
            case static::KILOCALORIE: return 'kcal';
            case static::WATT_HOUR: return 'Wh';
            case static::KILOWATT_HOUR: return 'kWh';
            case static::BRITISH_THERMAL: return 'BTU';
            case static::FOOT_POUNDFORCE: return 'ft lbf';
            case static::INCH_POUNDFORCE: return 'in lbf';
            case static::BARREL_OF_OIL: return 'boe';
            case static::ELECTRONVOLT: return 'eV';
        }
        return '';
    }


    /**
     * @inheritdoc
     */
    public static function convertToBaseUnit(float $value, string $fromUnit) : float
    {
        switch($fromUnit) {
            case static::KILOJOULE: return $value * 1000;
            case static::CALORIE: return $value * 4.1868;
            case static::KILOCALORIE: return $value * 4.1868 * pow(10, 3);
            case static::WATT_HOUR: return $value * 3.6 * pow(10, 3);
            case static::KILOWATT_HOUR: return $value * 3.6 * pow(10, 6);
            case static::BRITISH_THERMAL: return $value * 1.0545 * pow(10, 3);
            case static::FOOT_POUNDFORCE: return $value * 1.3558179483314004;
            case static::INCH_POUNDFORCE: return $value * 0.1129848290276167;
            case static::BARREL_OF_OIL: return $value * 5.8 * 1.0545 * pow(10, 9); //5.8 BTU
            case static::ELECTRONVOLT: return $value * pow(10, -7);
        }
        return $value;
    }


    /**
     * @inheritdoc
     */
    public static function convertFromBaseUnit(float $value, string $toUnit) : float
    {
        switch($toUnit) {
            case static::KILOJOULE: return $value / 1000;
            case static::CALORIE: return $value / 4.1868;
            case static::KILOCALORIE: return $value / 4.1868 * pow(10, -3);
            case static::WATT_HOUR: return $value / 3.6 * pow(10, -3);
            case static::KILOWATT_HOUR: return $value / 3.6 * pow(10, -6);
            case static::BRITISH_THERMAL: return $value / 1.0545 * pow(10, -3);
            case static::FOOT_POUNDFORCE: return $value / 1.3558179483314004;
            case static::INCH_POUNDFORCE: return $value / 0.1129848290276167;
            case static::BARREL_OF_OIL: return $value / 5.8 / 1.0545 * pow(10, -9);
            case static::ELECTRONVOLT: return $value * pow(10, 7);
        }
        return $value;
    }




}