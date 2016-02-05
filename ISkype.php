<?php

interface ISkype {
    /**
     * Sign in to skype account
     * @param string $username
     * @param string $password
     * @return boolean is signed in?
     */
    public function login($username, $password);

    /**
     * Is signed in?
     * @return boolean
     */
    public function isLoggedIn();

    /**
     * Sign out from skype account
     * @return boolean is signed out?
     */
    public function logout();

    /**
     * Get all contacts
     * @return [Contact]
     */
    public function getContacts();

    /**
     * Send message to each contact in contacts list
     * @param [Contact] $contacts
     * @param string $message
     * @return boolean
     */
    public function sendAll($contacts, $message);
}
