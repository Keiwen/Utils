<?php

namespace Keiwen\Utils\Format;


class DateFormat
{

    /**
     * @var string
     */
    protected $locale;

    /**
     * DateFormat constructor.
     * @param string $locale
     */
    public function __construct(string $locale = 'en')
    {
        $this->locale = $locale;
    }

    /**
     * @param int|null $datetype
     * @param int|null $timetype
     * @return \IntlDateFormatter
     */
    protected function getFormatter(int $datetype = null, int $timetype = null)
    {
        return new \IntlDateFormatter($this->locale, $datetype, $timetype);
    }

    /**
     * @param $value
     * @param int|null $datetype
     * @param int|null $timetype
     * @return string
     */
    public function formatDateAndTime($value, int $datetype = null, int $timetype = null)
    {
        return $this->getFormatter($datetype, $timetype)->format($value);
    }

    /**
     * @param $value
     * @param int|null $datetype
     * @return string
     */
    public function formatDate($value, int $datetype = null)
    {
        return $this->formatDateAndTime($value, $datetype, \IntlDateFormatter::NONE);
    }

    /**
     * @param $value
     * @param int|null $timetype
     * @return string
     */
    public function formatTime($value, int $timetype = null)
    {
        return $this->formatDateAndTime($value, \IntlDateFormatter::NONE, $timetype);
    }

    /**
     * @param $value
     * @return string
     */
    public function formatDateFull($value)
    {
        return $this->formatDate($value, \IntlDateFormatter::FULL);
    }

    /**
     * @param $value
     * @return string
     */
    public function formatDateLong($value)
    {
        return $this->formatDate($value, \IntlDateFormatter::LONG);
    }

    /**
     * @param $value
     * @return string
     */
    public function formatDateMedium($value)
    {
        return $this->formatDate($value, \IntlDateFormatter::MEDIUM);
    }

    /**
     * @param $value
     * @return string
     */
    public function formatDateShort($value)
    {
        return $this->formatDate($value, \IntlDateFormatter::SHORT);
    }

    /**
     * @param $value
     * @return string
     */
    public function formatTimeFull($value)
    {
        return $this->formatTime($value, \IntlDateFormatter::FULL);
    }

    /**
     * @param $value
     * @return string
     */
    public function formatTimeLong($value)
    {
        return $this->formatTime($value, \IntlDateFormatter::LONG);
    }

    /**
     * @param $value
     * @return string
     */
    public function formatTimeMedium($value)
    {
        return $this->formatTime($value, \IntlDateFormatter::MEDIUM);
    }

    /**
     * @param $value
     * @return string
     */
    public function formatTimeShort($value)
    {
        return $this->formatTime($value, \IntlDateFormatter::SHORT);
    }

    /**
     * @param $value
     * @return string
     */
    public function formatDateAndTimeFull($value)
    {
        return $this->formatDateAndTime($value, \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
    }

    /**
     * @param $value
     * @return string
     */
    public function formatDateAndTimeLong($value)
    {
        return $this->formatDateAndTime($value, \IntlDateFormatter::LONG, \IntlDateFormatter::LONG);
    }

    /**
     * @param $value
     * @return string
     */
    public function formatDateAndTimeMedium($value)
    {
        return $this->formatDateAndTime($value, \IntlDateFormatter::MEDIUM, \IntlDateFormatter::MEDIUM);
    }

    /**
     * @param $value
     * @return string
     */
    public function formatDateAndTimeShort($value)
    {
        return $this->formatDateAndTime($value, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT);
    }

}
