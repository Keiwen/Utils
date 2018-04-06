<?php

namespace Keiwen\Utils\Curl;


class SimpleCurlGet extends SimpleCurl
{


    /**
     * SimpleCurlGet constructor.
     *
     * @param string $url
     * @param array  $parameters
     */
    public function __construct(string $url, array $parameters = array())
    {
        parent::__construct($url, $parameters, self::METHOD_GET);
    }


}
