<?php

namespace Curl;


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
    protected $errno = 0;
    protected $error = '';

    /**
     * SimpleCurl constructor.
     *
     * @param string $url
     * @param array  $parameters
     * @param string $method
     */
    public function __construct(string $url, array $parameters = array(), $method = self::METHOD_GET)
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
            $paramString[] = $key . '=' . $value;
        }
        return implode('&', $paramString);
    }


    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
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
    public function getHeaders($associative = false)
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
    public function getCookies($associative = false)
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
    public function query($jsonDecode = false)
    {
        $ch = curl_init($this->getUrl());
        $this->configureCurl($ch);
        $content = curl_exec($ch);

        $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
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
        //additional option
        foreach($this->options as $option => $value) {
            curl_setopt($ch, $option, $value);
        }
    }


    /**
     * @return bool
     */
    public function hasError()
    {
        if($this->errno > 0) return true;
        return $this->httpCode >= 400;
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