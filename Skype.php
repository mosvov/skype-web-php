<?php

require 'Curl.php';

class Skype {

    private static $urls = [
        'login'    => 'https://login.skype.com/login?client_id=578134&redirect_uri=https%3A%2F%2Fweb.skype.com',
        'logout'   => 'https://login.skype.com/logout?client_id=578134&redirect_uri=https%3A%2F%2Fweb.skype.com&intsrc=client-_-webapp-_-production-_-go-signin',
        'asm'      => 'https://api.asm.skype.com/v1/skypetokenauth',
        'endpoint' => 'https://client-s.gateway.messenger.live.com/v1/users/ME/endpoints',
        'contacts' => 'https://contacts.skype.com/contacts/v1/users/%s/contacts?filter=type%%20eq%%20%%27skype%%27%%20or%%20type%%20eq%%20%%27msn%%27%%20or%%20type%%20eq%%20%%27pstn%%27%%20or%%20type%%20eq%%20%%27agent%%27'
    ];

    private $username;
    private $skypeToken;
    private $Curl;

    private $contacts;

    public function __construct() {
        $this->Curl = new Curl();
    }

    private function postToLogin($username, $password, $captchaData=null) {
        $doc = $this->Curl->getDOM(Skype::$urls['login']);
        $loginForm = $doc->getElementById('loginForm');
        $inputs = $loginForm->getElementsByTagName('input');
        $data = [];
        foreach ($inputs as $input) {
            $data[$input->getAttribute('name')] = $input->getAttribute('value');
        }
        $now = time();
        $data['timezone_field'] = str_replace(':', '|', date('P', $now));
        $data['username'] = $username;
        $data['password'] = $password;
        $data['js_time'] = $now;
        if ($captchaData) {
            $data['hip_solution'] = $captchaData['hip_solution'];
            $data['hip_token'] = $captchaData['hip_token'];
            $data['fid'] = $captchaData['fid'];
            $data['hip_type'] = 'visual';
            $data['captcha_provider'] = 'Hip';
        } else {
            unset($data['hip_solution']);
            unset($data['hip_token']);
            unset($data['fid']);
            unset($data['hip_type']);
            unset($data['captcha_provider']);
        }
        return $this->Curl->postDOM(Skype::$urls['login'], $data);
    }

    private function skypeToken($username, $password, $captchaData=null) {
        $doc = $this->postToLogin($username, $password, $captchaData);
        $xpath = new DOMXPath($doc);
        $inputs = $xpath->query("//input[@name='skypetoken']");
        if ($inputs->length) {
            $this->skypeToken = $inputs->item(0)->attributes->getNamedItem('value')->nodeValue;
            echo $this->skypeToken;
        } else {
            throw new Exception('Unable to get skype token');
        }
        return $this;
    }
    private function asmToken() {
        echo PHP_EOL . $this->Curl->post(Skype::$urls['asm'], ['skypetoken' => $this->skypeToken]) . PHP_EOL;
        return $this;
    }
    private function registerEndpoint() {
        $this->Curl->post(Skype::$urls['endpoint'], [], ['Authentication' => "skypetoken=$this->skypeToken"]);
        return $this;
    }
    private function loadAllContacts() {
        $this->contacts = $this->Curl->getJSON(sprintf(Skype::$urls['contacts'], $this->username), ['X-SkypeToken' => $this->skypeToken]);
        return $this;
    }

    public function login($username, $password, $captchaData=null) {
        $this->username = $username;
        $this
            ->skypeToken($username, $password, $captchaData)
            ->asmToken()
            ->registerEndpoint()
            ->loadAllContacts();
    }

    public function getContacts() {
        return $this->contacts;
    }


}
