<?php

namespace Keiwen\Utils\Curl;


class Curl
{

    /**
     * @param string $url
     * @param array $postData
     * @param int $httpCode
     * @param bool $jsonDecode
     * @return mixed
     */
	public static function curlRequest(string $url,
                                       array $postData = array(),
                                       int &$httpCode = 0,
                                       bool $jsonDecode = false)
    {
        $url = str_replace(' ', '%20', $url);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        //dont check SSL certificate
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if(!empty($postData)) {
            //requete post
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        }

        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //$errno = curl_errno($ch);
        //$error = curl_error($ch);
        curl_close($ch);

        if($jsonDecode) {
            $data = json_decode($data, true);
            if(empty($data)) $data = array();
        }

        return $data;
	}

}