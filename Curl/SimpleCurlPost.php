<?php

namespace Keiwen\Utils\Curl;


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
        $this->addOption(CURLOPT_POST, true);
        $this->addOption(CURLOPT_POSTFIELDS, json_encode($this->parameters));
    }

}
