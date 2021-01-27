<?php

namespace Keiwen\Utils\APIRequester;


class SimpleSoap extends AbstractAPIRequester
{


    protected $soapClient;


    /**
     * SimpleSoap constructor.
     *
     * @param string $url
     */
    public function __construct(string $url)
    {
        parent::__construct($url);
        $this->soapClient = new \SoapClient($url, array('trace' => true));
    }


    /**
     * @param string $operation
     * @param array $parameters
     * @param bool $jsonDecode
     * @return array|string|null
     */
    public function query(string $operation = '', array $parameters = array(), bool $jsonDecode = false)
    {
        $soapResult = null;
        $this->error = '';
        if(!empty($parameters)) $this->setParameters($parameters);
        try {
            $soapResult = $this->soapClient->$$operation($parameters);

            $this->parseLastRequest();

            if($jsonDecode) {
                $soapResult = json_decode($soapResult, true);
                if(empty($soapResult)) $soapResult = array();
            }

        } catch (\SoapFault $e) {
            $this->error = $e->getMessage();
        }

        return $soapResult;
    }


    /**
     */
    protected function parseLastRequest()
    {
        $this->lastRequest = $this->soapClient->__getLastRequestHeaders() . '\n' . $this->soapClient->__getLastRequest();
        $this->lastRequestParameters = $this->parameters;
        $this->lastResponseHeaders = $this->soapClient->__getLastResponseHeaders();
        $this->lastResponseBody = $this->soapClient->__getLastResponse();
    }


}
