<?php

namespace Keiwen\Utils\APIRequester;


class SimpleCurlPut extends SimpleCurl
{


    /**
     * SimpleCurlPut constructor.
     *
     * @param string $url
     * @param array  $parameters
     */
    public function __construct(string $url, array $parameters = array())
    {
        parent::__construct($url, $parameters, self::METHOD_PUT);
    }

}
