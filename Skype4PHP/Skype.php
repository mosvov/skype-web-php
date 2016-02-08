<?php

namespace Skype4PHP;

class Skype {
    private $transport;
    private $username;
    private $contacts = [];
    private $isLoggedIn = false;

    public function __construct() {
        $this->transport = new Transport($this);
    }

    public function login($username, $password) {
        $this->username = $username;
        $this->isLoggedIn = $this->transport->login($username, $password);
        $this->load();
        return $this;
    }

    public function logout() {
        $this->transport->logout();
        return $this;
    }

    public function getContact($username) {
        if (array_key_exists($username, $this->contacts)) {
            return $this->contacts[$username];
        }
        return $this->load($username);
    }

    public function getContacts($usernames=null) {
        if (!$usernames) {
            return $this->contacts;
        }
        $result = [];
        foreach ($usernames as $name) {
            $result[$name] = $this->getContact($name);
        }
        return $result;
    }

    public function sendTo($message, Contact $user) {
        $this->transport->send($user->getUsername(), $message);
        return $this;
    }

    public function sendAll($message, $users=null) {
        if (!$users) {
            $users = array_values($this->contacts);
        }
        foreach ($users as $item) {
            $this->sendTo($message, $item);
        }
        return $this;
    }

    private function load($username=null) {
        if (!$username) {
            $this->contacts = [];
            foreach ($this->transport->loadAllContacts() as $item) {
                $this->contacts[$item['id']] = new Contact($item);
            }
            return $this->contacts;
        }
        $this->contacts[$username] = new Contact($this->transport->loadContact($username));
        return $this->contacts[$username];
    }

}
