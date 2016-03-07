<?php

namespace skype_web_php;

class Skype {
    private $username;
    private $isLoggedIn = false;

    private $Transport;
    private $Contacts = [];

    public function __construct() {
        $this->Transport = new Transport($this);
    }

    public function login($username, $password) {
        $this->username = $username;
        $this->isLoggedIn = $this->Transport->login($username, $password);
        $this->load();
        return $this;
    }

    public function logout() {
        $this->Transport->logout();
        return $this;
    }

    public function getContact($username) {
        if (array_key_exists($username, $this->Contacts)) {
            return $this->Contacts[$username];
        }
        return $this->load($username);
    }

    public function getContacts($usernames=null) {
        if (!$usernames) {
            return $this->Contacts;
        }
        $result = [];
        foreach ($usernames as $name) {
            $result[$name] = $this->getContact($name);
        }
        return $result;
    }

    public function sendTo($message, Contact $Contact) {
        $this->Transport->send($Contact->getUsername(), $message);
        return $this;
    }

    public function sendAll($message, $Contacts=null) {
        if (!$Contacts) {
            $Contacts = array_values($this->Contacts);
        }
        foreach ($Contacts as $Contact) {
            $this->sendTo($message, $Contact);
        }
        return $this;
    }

    private function load($username=null) {
        if (!$username) {
            $this->Contacts = [];
            foreach ($this->Transport->loadAllContacts() as $item) {
                $this->Contacts[$item['id']] = new Contact($item);
            }
            return $this->Contacts;
        }
        $this->Contacts[$username] = new Contact($this->Transport->loadContact($username));
        return $this->Contacts[$username];
    }

}
