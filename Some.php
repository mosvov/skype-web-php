<?php

class Some {

    private $curl;
    private $session;
    private $cookies;
    private $cookiePath;
    private $skypeToken;
    private $username;
    private $password;

    private function getCookiePath($session) {
        return $session . '.cookie';
    }

    private function init($session = false) {
        $curl = curl_init();
        if (!$curl) {
            throw new Exception('Unable to init curl');
        }

        if (!$session) {
            $this->session = time();
            $this->cookiePath = $this->getCookiePath($this->session);
            $fop = fopen($this->cookiePath, 'wb');
            fclose($fop);
        } else {
            $this->session = $session;
            $this->cookiePath = $this->getCookiePath($session);
        }

        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_COOKIEFILE, $this->cookiePath);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookiePath);
        curl_setopt($curl, CURLOPT_AUTOREFERER, TRUE);

        $this->curl = $curl;
    }

    public function __construct() {
        $this->init();
    }






}

(new Some())->login('cyberpunk239', 'pft,fkbvtyz239');
