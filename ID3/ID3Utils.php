<?php

namespace Keiwen\Utils\ID3;

class ID3Utils
{

    /**
     * @param int $raw
     * @return int
     * @see https://www.php.net/manual/en/language.operators.bitwise.php
     */
    public static function syncSafeInteger(int $raw): int
    {
        return $raw & 0x0000007F | ($raw & 0x00007F00) >> 1 | ($raw & 0x007F0000) >> 2 | ($raw & 0x7F000000) >> 3;
    }


    /**
     * @param string $string
     * @return string
     */
    public static function unsyncString(string $string): string
    {
        return str_replace("\xFF\x00", "\xFF", $string);
    }

}
