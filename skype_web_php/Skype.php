<?php

namespace skype_web_php;

class Skype {

    const STATUS_ONLINE = 'Online';
    const STATUS_HIDDEN = 'Hidden';

    public $profile;
    public $contacts;

    private $transport;

    public function __construct() {
        $this->transport = new Transport();
    }

    public function login($username, $password) {
        $this->transport->login($username, $password);
        $this->transport->subscribeToResources();
        $this->profile =  $this->transport->loadProfile($username);
        $this->contacts = $this->transport->loadContacts($username);
        $this->transport->createStatusEndpoint();
        $this->transport->setStatus(self::STATUS_ONLINE);
    }

    public function logout() {
        $this->transport->logout();
    }

    public function getContact($username) {
        $contact = array_filter($this->contacts, function($current) use($username){
            return $current->id == $username;
        });

        return reset($contact);
    }

    public function sendTo($message, $contact) {
        $this->transport->send($contact, $message);
    }

    public function onMessage($callback){
        while (true){
            call_user_func_array($callback, [$this->transport->getNewMessages($this->profile->username), $this]);
            sleep(1);
        }
    }
}