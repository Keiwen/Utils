<?php

namespace Keiwen\Utils\Sanitize;


class StringSanitizer
{

	public const FILTER_DEFAULT = 'default';
	public const FILTER_BOOLEAN = 'boolean';
	public const FILTER_INT = 'int';
	public const FILTER_FLOAT = 'float';
	public const FILTER_ALPHA = 'alpha';
	public const FILTER_SCALAR = 'scalar';
	public const FILTER_MAIL = 'mail';
	public const FILTER_IP = 'ip';
	public const FILTER_MAC = 'mac';
	public const FILTER_URL = 'url';
	public const FILTER_FIRSTLETTER = 'firstLetter';
	public const FILTER_FIRSTTWOLETTER = 'firstTwoLetter';
	public const FILTER_FIRSTTHREELETTER = 'firstThreeLetter';
	public const FILTER_FIRSTFOURLETTER = 'firstFourLetter';
	public const FILTER_FIRSTFIVELETTER = 'firstFiveLetter';
    public const FILTER_SLUG = 'slug';
    public const FILTER_JSON_ARRAY = 'jsonArray';
    public const FILTER_JSON_OBJECT = 'jsonObject';
	public const FILTER_COLORHEXA= 'colorHexa';


    protected static $filters = array(
        self::FILTER_DEFAULT,
        self::FILTER_BOOLEAN,
        self::FILTER_INT,
        self::FILTER_FLOAT,
        self::FILTER_ALPHA,
        self::FILTER_SCALAR,
        self::FILTER_MAIL,
        self::FILTER_IP,
        self::FILTER_MAC,
        self::FILTER_URL,
        self::FILTER_FIRSTLETTER,
        self::FILTER_FIRSTTWOLETTER,
        self::FILTER_FIRSTTHREELETTER,
        self::FILTER_FIRSTFOURLETTER,
        self::FILTER_FIRSTFIVELETTER,
        self::FILTER_SLUG,
        self::FILTER_JSON_ARRAY,
        self::FILTER_JSON_OBJECT,
        self::FILTER_COLORHEXA,
    );
    
    
    public function __construct()
    {
    }


    /**
	 * @param string $rawValue
	 * @param string $filter
	 * @return mixed
	 */
	public function get(string $rawValue,
                        string $filter = self::FILTER_DEFAULT)
    {
		switch($filter) {
			//PHP filter types
			case static::FILTER_BOOLEAN: return filter_var($rawValue, FILTER_VALIDATE_BOOLEAN); break;
			case static::FILTER_INT: return filter_var($rawValue, FILTER_VALIDATE_INT); break;
			case static::FILTER_FLOAT: return filter_var($rawValue, FILTER_VALIDATE_FLOAT); break;
			case static::FILTER_MAIL: return filter_var($rawValue, FILTER_VALIDATE_EMAIL); break;
			case static::FILTER_IP: return filter_var($rawValue, FILTER_VALIDATE_IP); break;
			case static::FILTER_MAC: return filter_var($rawValue, FILTER_VALIDATE_MAC); break;
			case static::FILTER_URL: return filter_var($rawValue, FILTER_VALIDATE_URL); break;

			//Local types
			case static::FILTER_FIRSTLETTER: return $this->getFirstLetters($rawValue, 1); break;
			case static::FILTER_FIRSTTWOLETTER: return $this->getFirstLetters($rawValue, 2); break;
			case static::FILTER_FIRSTTHREELETTER: return $this->getFirstLetters($rawValue, 3); break;
			case static::FILTER_FIRSTFOURLETTER: return $this->getFirstLetters($rawValue, 4); break;
			case static::FILTER_FIRSTFIVELETTER: return $this->getFirstLetters($rawValue, 5); break;
			case static::FILTER_SLUG: return $this->getSlug($rawValue); break;
			case static::FILTER_ALPHA: return $this->getAlpha($rawValue); break;
			case static::FILTER_SCALAR: return $this->getScalar($rawValue); break;
            case static::FILTER_JSON_ARRAY: return $this->getJsonArray($rawValue); break;
            case static::FILTER_JSON_OBJECT: return $this->getJsonObject($rawValue); break;
			case static::FILTER_COLORHEXA: return $this->getColorHexa($rawValue); break;
		}
		return $this->getDefault($rawValue);
	}



    /**
     * @return array
     */
    public static function getFilters()
    {
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
	public function processArray(array $rawValues,
                                 string $filter = self::FILTER_DEFAULT)
    {
		$sanitized = array();
		foreach($rawValues as $rawValue) {
			$sanitizedValue = $this->get($rawValue, $filter);
			$sanitized[] = $sanitizedValue;
		}
		return $sanitized;
	}


    /**
     * sanitize all part of a string, converted to array according to separator
     * @param string $rawValue
     * @param string $separator
     * @param string $filter
     * @return array
     */
	public function getArray(string $rawValue, string $separator, string $filter = self::FILTER_DEFAULT)
    {
        $rawList = explode($separator, $rawValue);
        return $this->processArray($rawList, $filter);
    }


	/**
	 * @param string $rawValue
	 * @return string
	 */
	protected function getDefault(string $rawValue)
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
	protected function sanitizeRawValue(string $rawValue,
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
	protected function getAlpha(string $rawValue)
    {
		return $this->sanitizeRawValue($rawValue, '/^([a-zA-Z]+)(.*)/');
	}

	/**
	 * @param string $rawValue
	 * @return string
	 */
	protected function getScalar(string $rawValue)
    {
		return $this->sanitizeRawValue($rawValue, '/^([a-zA-Z0-9]+)(.*)/');
	}

	/**
	 * @param string $rawValue
	 * @param int $numberMax
	 * @return string
	 */
	protected function getFirstLetters(string $rawValue, int $numberMax = 1)
    {
		return $this->sanitizeRawValue($rawValue, '/^([a-zA-Z]{1,' . $numberMax . '})(.*)/');
	}

	/**
	 * @param string $rawValue
	 * @return string
	 */
	protected function getSlug(string $rawValue)
    {
		return $this->sanitizeRawValue($rawValue, '/^([a-zA-Z0-9_]+)(.*)/');
	}

    /**
     * @param string $rawValue
     * @return array
     */
    protected function getJsonArray(string $rawValue)
    {
        return $this->getJson($rawValue, true);
    }

    /**
     * @param string $rawValue
     * @return object
     */
    protected function getJsonObject(string $rawValue)
    {
        return $this->getJson($rawValue, false);
    }

    /**
     * @param string $rawValue
     * @param bool $assoc
     * @return mixed
     */
    private function getJson(string $rawValue, bool $assoc = true)
    {
        $json = json_decode($rawValue, $assoc);
        if(JSON_ERROR_NONE == json_last_error()) {
            return $json;
        };
        return $assoc ? array() : null;
    }


    /**
     * # char is not mandatory.
     * 'short-code' on 3 chars are allowed, but sanitized on 6 (ie 'FFF' will give '#FFFFFF')
	 * @param string $rawValue
	 * @return string
	 */
	protected function getColorHexa(string $rawValue)
    {
        $sanitized = $this->sanitizeRawValue($rawValue, '/^#?([a-fA-F0-9]{6})(.*)/');
        if(empty($sanitized)) {
            //try with 'short code' with 3 chars only
            $sanitized = $this->sanitizeRawValue($rawValue, '/^#?([a-fA-F0-9]{3})(.*)/');
            if(empty($sanitized)) {
                return '';
            }

            //rebuild full code
            $sanitizedChars = str_split($sanitized);
            $sanitized = '';
            foreach($sanitizedChars as $char) {
                $sanitized .= $char . $char;
            }
        }
        return '#' . $sanitized;
	}

}
