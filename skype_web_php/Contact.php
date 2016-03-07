<?php

namespace skype_web_php;

class Contact {

    private $raw;

    public function __construct(array $raw) {
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
