<?php

namespace Keiwen\Utils\APIRequester;


abstract class AbstractAPIRequester
{

    protected $url;
    protected $error = '';

    protected $parameters = array();

    protected $lastRequest = '';
    protected $lastRequestParameters = array();
    protected $lastResponseHeaders = array();
    protected $lastResponseBody = '';


    /**
     * AbstractAPIRequester constructor.
     *
     * @param string $url
     */
    public function __construct($url)
    {
        $this->url = $url;
    }


    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }


    abstract public function query();


    /**
     * @return bool
     */
    public function hasError()
    {
        return !empty($this->error);
    }


    /**
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }


    /**
     * @param array $parameters
     * @return $this
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }



    /**
     * @return string
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    /**
     * @return array
     */
    public function getLastRequestParameters()
    {
        return $this->lastRequestParameters;
    }

    /**
     * @return array
     */
    public function getLastResponseHeaders()
    {
        return $this->lastResponseHeaders;
    }

    /**
     * @return string
     */
    public function getLastResponseBody()
    {
        return $this->lastResponseBody;
    }



}
