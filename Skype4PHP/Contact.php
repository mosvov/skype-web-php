<?php

namespace Skype4PHP;

class Contact {

    private $raw;

    public function __construct($raw) {
        $this->raw = $raw;
    }

    public function getDisplayName() {
        return $this->raw['display_name'];
    }

    public function getAvatarUrl() {
        return $this->raw['avatar_url'];
    }

    public function getUsername() {
        return $this->raw['id'];
    }

}
