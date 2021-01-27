<?php

namespace Keiwen\Utils\APIRequester;


class SimpleCurlDelete extends SimpleCurl
{


    /**
     * SimpleCurlDelete constructor.
     *
     * @param string $url
     * @param array  $parameters
     */
    public function __construct(string $url, array $parameters = array())
    {
        parent::__construct($url, $parameters, self::METHOD_DELETE);
    }

}
