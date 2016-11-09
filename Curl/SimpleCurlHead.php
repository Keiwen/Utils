<?php

namespace Keiwen\Utils\Curl;


class SimpleCurlHead extends SimpleCurl
{


    /**
     * SimpleCurlHead constructor.
     *
     * @param string $url
     * @param array  $parameters
     */
    public function __construct(string $url, array $parameters = array())
    {
        parent::__construct($url, $parameters, self::METHOD_HEAD);
        $this->addOption(CURLOPT_HEADER, true);
        $this->addOption(CURLOPT_NOBODY, true);
    }


    /**
     * @param bool $baseOnly
     * @return string
     */
    public function getUrl($baseOnly = false)
    {
        if($baseOnly) return $this->url;
        return $this->url . '?' . $this->buildParameterString();
    }


}
