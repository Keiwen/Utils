<?php

namespace Keiwen\Utils\Format;


class StringFormat
{


    const PHONE_PATTERN_US = '334';
    const PHONE_PATTERN_CA = '334';
    const PHONE_PATTERN_FR = '22222';
    const PHONE_PATTERN_SP = '3222';
    const PHONE_PATTERN_IT = '244';
    const PHONE_PATTERN_CH = '3322';
    const PHONE_PATTERN_DE = '2323';
    const PHONE_PATTERN_AR = '134';
    const PHONE_PATTERN_BR = '244';
    const PHONE_PATTERN_AU = '333';
    const PHONE_PATTERN_JP = '144';
    const PHONE_PATTERN_CN = '244';

    const PHONE_CODE_US = '+1';
    const PHONE_CODE_CA = '+1';
    const PHONE_CODE_FR = '+33';
    const PHONE_CODE_SP = '+34';
    const PHONE_CODE_IT = '+39';
    const PHONE_CODE_CH = '+41';
    const PHONE_CODE_UK = '+44';
    const PHONE_CODE_DE = '+49';
    const PHONE_CODE_AR = '+54';
    const PHONE_CODE_BR = '+55';
    const PHONE_CODE_AU = '+61';
    const PHONE_CODE_KR = '+82';
    const PHONE_CODE_JP = '+81';
    const PHONE_CODE_CN = '+86';

    const POST_PATTERN_US = '54';
    const POST_PATTERN_CA = '33';
    const POST_PATTERN_UK = '43';
    const POST_PATTERN_FR = '23';
    const POST_PATTERN_SP = '5';
    const POST_PATTERN_IT = '5';
    const POST_PATTERN_CH = '4';
    const POST_PATTERN_DE = '5';
    const POST_PATTERN_AR = '8';
    const POST_PATTERN_BR = '53';
    const POST_PATTERN_AU = '4';
    const POST_PATTERN_JP = '34';
    const POST_PATTERN_CN = '6';
    const POST_PATTERN_KR = '5';


    /**
     * @param string $subject
     * @return string
     */
    public static function formatNbsp(string $subject)
    {
        return str_replace(' ', '&nbsp;', trim($subject));
    }


    /**
     * @param string|int $formattedNumber
     * @param string     $unit
     * @param bool       $unitFirst
     * @param bool       $space
     * @return string
     */
    public static function formatNumberUnit($formattedNumber,
                                     string $unit = 'u',
                                     bool $unitFirst = false,
                                     bool $space = true)
    {
        $addSpace = $space ? ' ' : '';
        if($unitFirst) {
            $number = $unit.$addSpace.$formattedNumber;
        } else {
            $number = $formattedNumber.$addSpace.$unit;
        }
        return $number;

    }


    /**
     * @param string|int $rawCode
     * @param string     $pattern characters from code to group by sequence (ex: 323 will give 3chars/separator/2chars/separator/3chars)
     * @param string     $separator
     * @return string
     */
    public static function groupCharsCode($rawCode, string $pattern = '', string $separator = ' ')
    {
        $rawCode = (string) $rawCode;
        if(empty($pattern)) return $pattern;
        $pattern = str_split($pattern);
        $groups = array();
        //for each digit of the pattern, group this amount from the code from the end
        for($i = count($pattern) - 1; $i >= 0; $i--) {
            $length = (int) $pattern[$i];
            if(strlen($rawCode) < $length) {
                //if digit > rest of the code, take all and end
                $part = $rawCode;
                $rawCode = '';
                $i = -1;
            } else {
                //cut the last {digit} numbers in code
                $part = substr($rawCode, -$length);
                $rawCode = substr($rawCode, 0, strlen($rawCode) - $length);
            }
            $groups[] = $part;
        }
        //if something left after pattern, put everything in it
        if(!empty($rawCode)) $groups[] = $rawCode;
        $groups = array_reverse($groups);
        $groups = implode($separator, $groups);
        return $groups;
    }


    /**
     * @param string|int $rawNumber
     * @param string     $pattern numbers from phone number to group by sequence (ex: 343 will give 3chars/separator/4chars/separator/3chars)
     * @param string     $separator
     * @param string     $callingCode must be defined if present in raw number
     * @return string
     */
    public static function formatPhoneNumber($rawNumber, string $pattern = self::PHONE_PATTERN_US, string $separator = ' ', $callingCode = '')
    {
        $rawNumber = (string) $rawNumber;
        //is calling code included in rawnumber?
        $hasCallingCode = (strpos($rawNumber, '+') !== false);
        if(!empty($callingCode)) {
            //be sure to have calling code with + sign
            $callingCode = '+' . ltrim($callingCode, '+');
        }
        if($hasCallingCode) {
            //remove calling code from raw number before format
            $rawNumber = str_replace($callingCode, '', $rawNumber);
            //if calling code detected but not given, stop method
            //as we cannot retrieve code
            if(empty($callingCode)) return $rawNumber;
        }
        //group numbers
        $numbers = self::groupCharsCode($rawNumber, $pattern, $separator);

        if(!empty($callingCode)) {
            //add calling code (remove the first 0 if needed)
            $numbers = ltrim($numbers, '0');
            $numbers = $callingCode . ' ' . $numbers;
        }
        return $numbers;
    }



    /**
     * @param string|int $rawCode
     * @param string     $pattern postcode chars to group by sequence (ex: 33 will give 3chars/separator/3chars)
     * @param string     $separator
     * @return string
     */
    public static function formatPostalCode($rawCode, string $pattern = self::POST_PATTERN_US, string $separator = ' ')
    {
        return static::groupCharsCode($rawCode, $pattern, $separator);
    }



    /**
     * @param string $subject
     * @param bool   $lcfirst
     * @return string
     */
    public static function formatCamelCase(string $subject, $lcfirst = true)
    {
        //lower, replace _ by space, upper case on all words
        $return = ucwords(str_replace('_', ' ', strtolower($subject)));
        if($lcfirst) {
            //remove first uppercase if needed
            $return = lcfirst($return);
        }
        //remove space
        $return = str_replace(' ', '', $return);
        return $return;
    }


    /***
     * @param string $subject
     * @return string
     */
    public static function formatSnakeCase(string $subject)
    {
        //replace single char isolated with spaces or underscores
        $subject = preg_replace('#(^|[_ ])([a-zA-Z0-9])([ _]|$)#', '$2', $subject);

        preg_match_all('#([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)#', $subject, $matches);
        $words = $matches[0];
        foreach($words as &$match) {
            $match = ($match == strtoupper($match)) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $words);
    }


}
