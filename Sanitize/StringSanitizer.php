<?php

namespace Keiwen\Utils\Sanitize;


class StringSanitizer
{

	const FILTER_DEFAULT = 'default';
	const FILTER_BOOLEAN = 'boolean';
	const FILTER_INT = 'int';
	const FILTER_FLOAT = 'float';
	const FILTER_ALPHA = 'alpha';
	const FILTER_SCALAR = 'scalar';
	const FILTER_MAIL = 'mail';
	const FILTER_IP = 'ip';
	const FILTER_MAC = 'mac';
	const FILTER_REGEXP = 'regexp';
	const FILTER_URL = 'url';
	const FILTER_FIRSTLETTER = 'firstLetter';
	const FILTER_FIRSTTWOLETTER = 'firstTwoLetter';
	const FILTER_FIRSTTHREELETTER = 'firstThreeLetter';
	const FILTER_FIRSTFOURLETTER = 'firstFourLetter';
	const FILTER_FIRSTFIVELETTER = 'firstFiveLetter';
    const FILTER_SLUG = 'slug';
    const FILTER_JSON_ARRAY = 'jsonArray';
    const FILTER_JSON_OBJECT = 'jsonObject';
	const FILTER_COLORHEXA= 'colorHexa';


    protected static $filters;


	/**
	 * @param string $rawValue
	 * @param string $filter
	 * @return mixed
	 */
	public static function get(string $rawValue,
                               string $filter = self::FILTER_DEFAULT)
    {
		switch($filter) {
			//PHP filter types
			case self::FILTER_BOOLEAN: return filter_var($rawValue, FILTER_VALIDATE_BOOLEAN); break;
			case self::FILTER_INT: return filter_var($rawValue, FILTER_VALIDATE_INT); break;
			case self::FILTER_FLOAT: return filter_var($rawValue, FILTER_VALIDATE_FLOAT); break;
			case self::FILTER_MAIL: return filter_var($rawValue, FILTER_VALIDATE_EMAIL); break;
			case self::FILTER_IP: return filter_var($rawValue, FILTER_VALIDATE_IP); break;
			case self::FILTER_MAC: return filter_var($rawValue, FILTER_VALIDATE_MAC); break;
			case self::FILTER_REGEXP: return filter_var($rawValue, FILTER_VALIDATE_REGEXP); break;
			case self::FILTER_URL: return filter_var($rawValue, FILTER_VALIDATE_URL); break;

			//Local types
			case self::FILTER_FIRSTLETTER: return self::getFirstLetters($rawValue, 1); break;
			case self::FILTER_FIRSTTWOLETTER: return self::getFirstLetters($rawValue, 2); break;
			case self::FILTER_FIRSTTHREELETTER: return self::getFirstLetters($rawValue, 3); break;
			case self::FILTER_FIRSTFOURLETTER: return self::getFirstLetters($rawValue, 4); break;
			case self::FILTER_FIRSTFIVELETTER: return self::getFirstLetters($rawValue, 5); break;
			case self::FILTER_SLUG: return self::getSlug($rawValue); break;
			case self::FILTER_ALPHA: return self::getAlpha($rawValue); break;
			case self::FILTER_SCALAR: return self::getScalar($rawValue); break;
            case self::FILTER_JSON_ARRAY: return self::getJsonArray($rawValue); break;
            case self::FILTER_JSON_OBJECT: return self::getJsonObject($rawValue); break;
			case self::FILTER_COLORHEXA: return self::getColorHexa($rawValue); break;
		}
		return self::getDefault($rawValue);
	}



    /**
     * @return array
     */
    public static function getFilters()
    {
        if(empty(static::$filters)) {
            $rClass = new \ReflectionClass(static::class);
            static::$filters = array_values($rClass->getConstants());
        }
        return static::$filters;
    }


    /**
     * @param string $filter
     * @return bool
     */
    public static function isValidFilter(string $filter)
    {
        return in_array($filter, static::getFilters());
    }

    

    /**
	 * @param array $rawValues
	 * @param string $filter
	 * @return array
	 */
	public static function processArray(array $rawValues,
                                        string $filter = self::FILTER_DEFAULT)
    {
		$sanitized = array();
		foreach($rawValues as $rawValue) {
			$sanitizedValue = self::get($rawValue, $filter);
			$sanitized[] = $sanitizedValue;
		}
		return $sanitized;
	}


	public static function getArray(string $rawValue, string $separator, string $filter = self::FILTER_DEFAULT)
    {
        $rawList = explode($separator, $rawValue);
        $list = static::processArray($rawList, $filter);
        return $list;
    }


	/**
	 * @param string $rawValue
	 * @return string
	 */
	protected static function getDefault(string $rawValue)
    {
		return htmlentities(strip_tags($rawValue));
	}


	/**
	 * @param string $rawValue
	 * @param string $pattern
	 * @param string $replacement
	 * @param int $limit
	 * @param int $count
	 * @return string
	 */
	protected static function sanitizeRawValue(string $rawValue,
                                               string $pattern,
                                               string $replacement = '$1',
                                               int $limit = -1,
                                               int &$count = 0)
    {
		if(preg_match($pattern, $rawValue)) {
			$temp = preg_replace($pattern, $replacement, $rawValue, $limit, $count);
			if(empty($temp)) {
				$temp = '';
			}
			return $temp;
		}
		return '';
	}


	/**
	 * @param string $rawValue
	 * @return string
	 */
	protected static function getAlpha(string $rawValue)
    {
		return self::sanitizeRawValue($rawValue, '/^([a-zA-Z]+)(.*)/');
	}

	/**
	 * @param string $rawValue
	 * @return string
	 */
	protected static function getScalar(string $rawValue)
    {
		return self::sanitizeRawValue($rawValue, '/^([a-zA-Z0-9]+)(.*)/');
	}

	/**
	 * @param string $rawValue
	 * @param int $numberMax
	 * @return string
	 */
	protected static function getFirstLetters(string $rawValue, int $numberMax = 1)
    {
		return self::sanitizeRawValue($rawValue, '/^([a-zA-Z]{1,' . $numberMax . '})(.*)/');
	}

	/**
	 * @param string $rawValue
	 * @return string
	 */
	protected static function getSlug(string $rawValue)
    {
		return self::sanitizeRawValue($rawValue, '/^([a-zA-Z0-9!\/\-\._]+)(.*)/');
	}

    /**
     * @param string $rawValue
     * @return array
     */
    protected static function getJsonArray(string $rawValue)
    {
        return self::getJson($rawValue, true);
    }

    /**
     * @param string $rawValue
     * @return object
     */
    protected static function getJsonObject(string $rawValue)
    {
        return self::getJson($rawValue, false);
    }

    /**
     * @param string $rawValue
     * @param bool $assoc
     * @return mixed
     */
    private static function getJson(string $rawValue, bool $assoc = true)
    {
        $json = json_decode($rawValue, $assoc);
        if(JSON_ERROR_NONE == json_last_error()) {
            return $json;
        };
        return $assoc ? array() : null;
    }


    /**
	 * @param string $rawValue
	 * @return string
	 */
	protected static function getColorHexa(string $rawValue)
    {
		return '#' . self::sanitizeRawValue($rawValue, '/^#?([a-fA-F0-9]{6})(.*)/');
	}

}
