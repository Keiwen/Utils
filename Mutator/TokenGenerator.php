<?php

namespace Keiwen\Utils\Mutator;


class TokenGenerator
{

    protected $secret;
    protected $cipherAlgo;
    protected $iv;

    /**
     * @param string $secret secret key
     * @param string $cipherAlgo openSSL cipher method
     * @param string $opensslIV for openssl encryption, we need an initialisation vector (exactly 16 chars long) or we will have a warning raised
     * @see openssl_get_cipher_methods
     */
    public function __construct(string $secret, string $cipherAlgo = 'aes-128-cbc', string $opensslIV = '')
    {
        $this->secret = $secret;
        $this->cipherAlgo = $cipherAlgo;
        if (strlen($opensslIV) !== 16) {
            $opensslIV = '1234567890123456';
        }
        $this->iv = $opensslIV;
    }


    /**
     * @param array $data
     * @return string hashed token
     */
    public function generateToken(array $data = []): string
    {
        return base64_encode(hash_hmac('sha256', json_encode($data), $this->secret, true));
    }


    /**
     * @param array $data
     * @return string hashed token
     */
    public function generateTimedToken(array $data = []): string
    {
        $timeData = [
            'token_time' => date('Y-m-d H:i:s'),
        ];
        $data = array_merge($data, $timeData);
        return $this->generateToken($data);
    }


    /**
     * @param array $data
     * @return string encrypted token
     */
    public function encryptToken(array $data = []): string
    {
        return openssl_encrypt(json_encode($data), $this->cipherAlgo, $this->secret, 0, $this->iv);
    }

    /**
     * @param string $token encrypted token
     * @return array decrypted data
     */
    public function decryptToken(string $token): array
    {
        $jsonData = openssl_decrypt($token, $this->cipherAlgo, $this->secret, 0, $this->iv);
        $data = json_decode($jsonData, true);
        if (empty($data)) $data = array();
        return $data;
    }


}
