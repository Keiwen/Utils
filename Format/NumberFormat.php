<?php

namespace Keiwen\Utils\Format;


class NumberFormat
{

    protected $locale;

    /**
     * NumberFormat constructor.
     * @param string $locale
     */
    public function __construct(string $locale = 'en')
    {
        $this->locale = $locale;
    }

    /**
     * @param int $style
     * @param array $attributes
     * @return \NumberFormatter
     */
    protected function getFormatter(int $style, array $attributes = array())
    {
        $fmt = new \NumberFormatter($this->locale, $style);
        foreach($attributes as $attr => $value) {
            if($value !== null) $fmt->setAttribute($attr, $value);
        }
        return $fmt;
    }

    /**
     * @param float $value
     * @param string $currency
     * @return string
     */
    public function formatCurrency(float $value, string $currency)
    {
        $fmt = $this->getFormatter(\NumberFormatter::CURRENCY);
        return $fmt->formatCurrency($value, $currency);
    }

    /**
     * @param int $style
     * @param float $value
     * @param int|null $maxFractionDigits
     * @param int|null $fractionDigits
     * @return string
     */
    protected function formatFloat(int $style, float $value, int $maxFractionDigits = null, int $fractionDigits = null)
    {
        $fmt = $this->getFormatter($style, array(
            \NumberFormatter::MAX_FRACTION_DIGITS => $maxFractionDigits,
            \NumberFormatter::FRACTION_DIGITS  => $fractionDigits,
        ));
        return $fmt->format($value);
    }

    /**
     * @param float $value
     * @param int|null $maxFractionDigits
     * @param int|null $fractionDigits
     * @return string
     */
    public function formatDecimal(float $value, int $maxFractionDigits = null, int $fractionDigits = null)
    {
        return $this->formatFloat(\NumberFormatter::DECIMAL, $value, $maxFractionDigits, $fractionDigits);
    }

    /**
     * @param float $value
     * @param int|null $maxFractionDigits
     * @param int|null $fractionDigits
     * @return string
     */
    public function formatPercent(float $value, int $maxFractionDigits = null, int $fractionDigits = null)
    {
        return $this->formatFloat(\NumberFormatter::PERCENT, $value, $maxFractionDigits, $fractionDigits);
    }

    /**
     * @param float $value
     * @param int|null $maxFractionDigits
     * @param int|null $fractionDigits
     * @return string
     */
    public function formatScientific(float $value, int $maxFractionDigits = null, int $fractionDigits = null)
    {
        return $this->formatFloat(\NumberFormatter::SCIENTIFIC, $value, $maxFractionDigits, $fractionDigits);
    }

    /**
     * @param float $value
     * @param int $maxFractionDigits
     * @return string
     */
    public function formatSpellout(float $value, int $maxFractionDigits = 0)
    {
        return $this->formatFloat(\NumberFormatter::SPELLOUT, $value, $maxFractionDigits);
    }

    /**
     * @param float $value
     * @return string
     */
    public function formatOrdinal(float $value)
    {
        return $this->formatFloat(\NumberFormatter::ORDINAL, $value);
    }

    /**
     * @param float $value
     * @return string
     */
    public function formatDuration(float $value)
    {
        return $this->formatFloat(\NumberFormatter::DURATION, $value);
    }

}
