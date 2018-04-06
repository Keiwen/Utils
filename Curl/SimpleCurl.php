<?php

namespace Keiwen\Utils\Curl;


class SimpleCurl
{


    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_HEAD = 'HEAD';

    protected $url;
    protected $parameters = array();
    protected $headers = array();
    protected $cookies = array();
    protected $options = array();
    protected $method = self::METHOD_GET;

    protected $httpCode = 0;
    protected $headerSize = 0;
    protected $errno = 0;
    protected $error = '';

    /**
     * SimpleCurl constructor.
     *
     * @param string $url
     * @param array  $parameters
     * @param string $method
     */
    public function __construct(string $url, array $parameters = array(), string $method = self::METHOD_GET)
    {
        $this->url = $url;
        $this->parameters = $parameters;
        $this->method = strtoupper($method);
    }


    /**
     * @return string
     */
    protected function buildParameterString()
    {
        $paramString = array();
        foreach($this->parameters as $key => $value) {
            $paramString[] = $key . '=' . urlencode($value);
        }
        return implode('&', $paramString);
    }


    /**
     * @param bool $baseOnly
     * @return string
     */
    public function getUrl(bool $baseOnly = false)
    {
        if($baseOnly) return $this->url;
        if($this->method != self::METHOD_GET) return $this->url;
        return $this->url . '?' . $this->buildParameterString();
    }

    /**
     * @param string     $name
     * @param string|int $value
     */
    public function addHeader(string $name, $value)
    {
        $this->headers[$name] = $value;
    }

    /**
     * @param bool $associative
     * @return array
     */
    public function getHeaders(bool $associative = false)
    {
        if($associative) return $this->headers;
        $headers = array();
        foreach($this->headers as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }
        return $headers;
    }

    /**
     * @param string     $name
     * @param string|int $value
     */
    public function addCookie(string $name, $value)
    {
        $this->cookies[$name] = $value;
    }

    /**
     * @param bool $associative
     * @return string|array
     */
    public function getCookies(bool $associative = false)
    {
        if($associative) return $this->cookies;
        $cookies = array();
        foreach($this->cookies as $name => $value) {
            $cookies[] = $name . '=' . $value;
        }
        return implode(';', $cookies);
    }


    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }


    /**
     * use CURLOPT_* constants
     * @param int   $option
     * @param mixed $value
     */
    public function addOption(int $option, $value)
    {
        $this->options[$option] = $value;
    }

    /**
     * @param bool $jsonDecode
     * @return array|string
     */
    public function query(bool $jsonDecode = false)
    {
        $ch = curl_init($this->getUrl());
        $this->configureCurl($ch);
        $content = curl_exec($ch);

        $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $this->errno = curl_errno($ch);
        $this->error = curl_error($ch);
        curl_close($ch);

        if($jsonDecode) {
            $content = json_decode($content, true);
            if(empty($content)) $content = array();
        }

        return $content;
    }


    /**
     * @param $ch
     */
    protected function configureCurl(&$ch)
    {
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 100);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeaders());
        curl_setopt($ch, CURLOPT_COOKIE, $this->getCookies());
        if($this->method == self::METHOD_POST) {
            $this->addOption(CURLOPT_POST, true);
            $this->addOption(CURLOPT_POSTFIELDS, $this->generatePostFields());
        }
        //additional option
        foreach($this->options as $option => $value) {
            curl_setopt($ch, $option, $value);
        }
    }


    /**
     * @return string
     */
    protected function generatePostFields()
    {
        return json_encode($this->parameters);
    }


    /**
     * @return bool
     */
    public function hasError()
    {
        if($this->hasCurlError()) return true;
        return $this->httpCode >= 400;
    }

    /**
     * @return bool
     */
    public function hasCurlError()
    {
        return ($this->errno > 0);
    }

    /**
     * @return int|string
     */
    public function getHttpCode()
    {
        return $this->httpCode;
    }


    /**
     * @return string
     */
    public function getCurlError()
    {
        return $this->errno . ': ' . $this->error;
    }

}
