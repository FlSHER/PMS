<?php

/**
 * Curl类
 * create by Fisher <fisher9389@sina.com>
 */

namespace App\Services\SSO;

use Illuminate\Support\Facades\Auth;

class CurlService
{

    private $resource;
    private $url;
    private $port;

    public function __construct($url = '')
    {
        $this->resource = curl_init();
        if (Auth::check()) {
            $authorization = request()->header('Authorization');
            $defaultHeader = [
                'Authorization' => $authorization,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];
            $this->setHeader($defaultHeader);
        }
    }

    public function get($url)
    {
        $this->init($url);
        curl_setopt($this->resource, CURLOPT_URL, $this->url);
        $response = curl_exec($this->resource);
        if ($response === false) {
            $response = curl_error($this->resource);
        }
        curl_close($this->resource);
        return $this->decodeResponse($response);
    }

    public function sendMessage($url, $message)
    {
        if (is_array($message)) {
            $data = '?' . http_build_query($message);
        } else {
            $data = $message;
        }
        $url .= $data;
        curl_setopt($this->resource, CURLOPT_HTTPGET, 1);
        $response = $this->get($url);
        return $response;
    }

    public function sendMessageByPost($url, $message)
    {
        curl_setopt($this->resource, CURLOPT_POST, true);
        $message = json_encode($message);
        curl_setopt($this->resource, CURLOPT_POSTFIELDS, $message);
        $this->setHeader(['Content-Type:application/json']);
        $response = $this->get($url);
        return $response;
    }

    protected function setUrl($url)
    {
        $this->url = $url;
        $preg = '/:\d+/';
        if (preg_match($preg, $url, $match)) {
            $this->port = (int)substr($match[0], 1);
            $this->url = str_replace($match[0], '', $url);
            curl_setopt($this->resource, CURLOPT_PORT, $this->port);
        }
        return $this;
    }

    private function init($url)
    {
        $this->setUrl($url);
        curl_setopt($this->resource, CURLOPT_HEADER, 0);
        curl_setopt($this->resource, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->resource, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($this->resource, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($this->resource, CURLOPT_REFERER, asset(url()->current()));
        curl_setopt($this->resource, CURLINFO_HEADER_OUT, true);
    }

    public function setHeader($headers)
    {
        $stringifyHeader = [];
        foreach ($headers as $key => $value) {
            array_push($stringifyHeader, $key . ':' . $value);
        }
        curl_setopt($this->resource, CURLOPT_HTTPHEADER, $stringifyHeader);
        return $this;
    }

    private function decodeResponse($response)
    {
        $responseArr = json_decode($response, true);
        if (json_last_error() == JSON_ERROR_NONE) {
            return $responseArr;
        }
        return $response;
    }

}
