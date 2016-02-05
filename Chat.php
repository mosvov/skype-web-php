<?php

class Chat {

    private $Skype;
    private $name;

    public function __construct($Skype, $name) {
        $this->Skype = $Skype;
        $this->name = $name;
        $this->load();
    }

    private function load() {

    }

}
