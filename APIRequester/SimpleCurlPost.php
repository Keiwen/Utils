<?php

namespace Keiwen\Utils\APIRequester;


class SimpleCurlPost extends SimpleCurl
{


    /**
     * SimpleCurlPost constructor.
     *
     * @param string $url
     * @param array  $parameters
     */
    public function __construct(string $url, array $parameters = array())
    {
        parent::__construct($url, $parameters, self::METHOD_POST);
    }

}
