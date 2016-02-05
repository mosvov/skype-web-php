<?php

class Curl {

    private $curl;
    private $session;
    private $cookie;

    public function __construct($session=null) {
        $curl = curl_init();
        if (!$curl) {
            throw new Exception('Unable to init curl');
        }

        if (!$session) {
            $this->session = time();
            $this->cookie = static::getCookiePath($this->session);
            $fop = fopen($this->cookie, 'wb');
            fclose($fop);
        } else {
            $this->session = $session;
            $this->cookie = static::getCookiePath($session);
        }

        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_COOKIEFILE, $this->cookie);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookie);
        curl_setopt($curl, CURLOPT_AUTOREFERER, TRUE);

        $this->curl = $curl;
    }

    public function get($url, $headers=null) {
        $curl = $this->curl;

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, false);
        curl_setopt($curl, CURLOPT_HTTPGET, true);

        if ($headers) {
            $curl_headers = [];
            foreach ($headers as $header_name => $value)
                $curl_headers[] = "{$header_name}: {$value}";
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $curl_headers);
        }

        $result = curl_exec($curl);
        if (!$result) {
            throw new Exception(curl_error($curl), curl_errno($curl));
        }
        return $result;
    }

    public function getDOM($url, $headers=null) {
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->recover = true;
        $doc->loadHTML($this->get($url, $headers));
        libxml_use_internal_errors(false);
        return $doc;
    }

    public function getJSON($url, $headers=null) {
        return json_decode($this->get($url, $headers), true);
    }

    public function post($url, $post_elements, $headers=null) {
        $curl = $this->curl;

        $flag = false;
        $elements = '';
        foreach ($post_elements as $name => $value) {
            if ($flag)
                $elements .= '&';
            $elements .= "{$name}=" . urlencode($value);
            $flag = true;
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $elements);

        if ($headers) {
            $curl_headers = [];
            foreach ($headers as $header_name => $value)
                $curl_headers[] = "{$header_name}: {$value}";
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $curl_headers);
        }

        $result = curl_exec($curl);
        if (!$result && (curl_error($curl) || curl_errno($curl))) {
            throw new Exception(curl_error($curl), curl_errno($curl));
        }
        return $result;
    }

    public function postDOM($url, $post_elements, $headers=null) {
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->recover = true;
        $doc->loadHTML($this->post($url, $post_elements, $headers));
        libxml_use_internal_errors(false);
        return $doc;
    }


    public static function getCookiePath($sessionId) {
        return $sessionId . '.cookie';
    }

}
