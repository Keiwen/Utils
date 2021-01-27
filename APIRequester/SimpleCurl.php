<?php

namespace Keiwen\Utils\APIRequester;


class SimpleCurl extends AbstractAPIRequester
{

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_HEAD = 'HEAD';
    const METHOD_DELETE = 'DELETE';
    const METHOD_PATCH = 'PATCH';
    const METHOD_CONNECT = 'CONNECT';
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_TRACE = 'TRACE';

    protected $method = self::METHOD_GET;

    protected $headers = array();
    protected $cookies = array();
    protected $options = array();

    protected $httpCode = 0;
    protected $headerSize = 0;
    protected $errno = 0;

    protected $urlEncodeStringParameters = true;

    /**
     * SimpleCurl constructor.
     *
     * @param string $url
     * @param array  $parameters
     * @param string $method
     */
    public function __construct(string $url, string $method = self::METHOD_GET)
    {
        parent::__construct($url);
        $this->method = strtoupper($method);
    }


    /**
     * @return string
     */
    protected function buildParameterString()
    {
        $paramString = array();
        foreach($this->parameters as $key => $value) {
            if ($this->urlEncodeStringParameters) $value = urlencode($value);
            $paramString[] = $key . '=' . $value;
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
     * @return $this
     */
    public function addCookie(string $name, $value)
    {
        $this->cookies[$name] = $value;
        return $this;
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
     * @return $this
     */
    public function addOption(int $option, $value)
    {
        $this->options[$option] = $value;
        return $this;
    }

    /**
     * @param array $parameters
     * @param bool  $jsonDecode
     * @return array|string
     */
    public function query(array $parameters = array(), bool $jsonDecode = false)
    {
        if(!empty($parameters)) $this->setParameters($parameters);
        $ch = curl_init($this->getUrl());
        $this->configureCurl($ch);
        $content = curl_exec($ch);

        $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $this->errno = curl_errno($ch);
        $this->error = curl_error($ch);

        $this->parseLastRequest($ch, $content);

        curl_close($ch);

        if($jsonDecode) {
            $content = json_decode($content, true);
            if(empty($content)) $content = array();
        }

        return $content;
    }


    /**
     * @param $ch
     * @param string $response
     */
    protected function parseLastRequest(&$ch, string $response)
    {
        $responseHeaders = array();
        $headerString = substr($response, 0, $this->headerSize);
        foreach (explode("\r\n", $headerString) as $i => $line) {
            if ($i === 0) {
                $responseHeaders['http_code'] = $line;
            } else {
                $exploded = explode(': ', $line);
                $value = empty($exploded[1]) ? '' : $exploded[1];
                if(isset($responseHeaders[$exploded[0]])) {
                    if(is_array($responseHeaders[$exploded[0]])) {
                        $responseHeaders[$exploded[0]][] = $value;
                    } else {
                        $responseHeaders[$exploded[0]] = array($responseHeaders[$exploded[0]], $value);
                    }
                } else {
                    $responseHeaders[$exploded[0]] = $value;
                }
            }
        }

        $this->lastRequest = curl_getinfo($ch, CURLINFO_HEADER_OUT);
        if($this->method == self::METHOD_POST || $this->method == self::METHOD_PUT) {
            $this->lastRequest .= PHP_EOL . $this->generatePostFields();
        }
        $this->lastRequestParameters = $this->parameters;
        $this->lastResponseHeaders = $responseHeaders;
        $this->lastResponseBody = substr($response, $this->headerSize);
    }


    /**
     * @param $ch
     */
    protected function configureCurl(&$ch)
    {
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 100);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeaders());
        $cookies = $this->getCookies();
        if(!empty($cookies)) {
            curl_setopt($ch, CURLOPT_COOKIE, $cookies);
        }
        if($this->method == self::METHOD_POST) {
            $this->addOption(CURLOPT_POST, true);
            $this->addOption(CURLOPT_POSTFIELDS, $this->generatePostFields());
        }
        if($this->method == self::METHOD_PUT) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
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
        $headers = $this->getHeaders(true);
        if($headers['Content-Type'] === 'application/x-www-form-urlencoded') {
            $paramWwwForm = '';
            foreach($this->parameters as $key => $valeur) {
                $paramWwwForm .= '&' . $key . '=' . $valeur;
            }
            $paramWwwForm = trim($paramWwwForm, '&');
            return $paramWwwForm;
        }
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


    /**
     * @return string
     */
    public function getError()
    {
        if($this->hasCurlError()) return $this->getCurlError();
        if($this->httpCode >= 400) return $this->httpCode;
        return '';
    }


    /**
     * @return int
     */
    public function getHeaderSize()
    {
        return $this->headerSize;
    }


    /**
     * @param bool $urlEncodeStringParameters
     * @return $this
     */
    public function setUrlEncodeStringParameters(bool $urlEncodeStringParameters)
    {
        $this->urlEncodeStringParameters = $urlEncodeStringParameters;
        return $this;
    }

}
