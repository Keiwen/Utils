<?php

namespace Keiwen\Utils\APIRequester;


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

}
