<?php
namespace skype_web_php;

/**
 * Class Skype
 * @package skype_web_php
 */
class Skype
{

    /**
     *
     */
    const STATUS_ONLINE = 'Online';
    /**
     *
     */
    const STATUS_HIDDEN = 'Hidden';

    /**
     * @var
     */
    public $profile;
    /**
     * @var
     */
    public $contacts;

    /**
     * @var Transport
     */
    private $transport;

    /**
     *
     */
    public function __construct()
    {
        $this->transport = new Transport();
    }

    /**
     * @param $username
     * @param $password
     * @throws \Exception
     */
    public function login($username, $password)
    {
        $this->transport->login($username, $password);
        $this->transport->subscribeToResources();
        $this->profile = $this->transport->loadProfile($username);
        $this->contacts = $this->transport->loadContacts($username);
        $this->transport->createStatusEndpoint();
        $this->transport->setStatus(self::STATUS_ONLINE);
    }

    /**
     *
     */
    public function logout()
    {
        $this->transport->logout();
    }

    /**
     * @param $username
     * @return mixed
     */
    public function getContact($username)
    {
        $contact = array_filter($this->contacts, function ($current) use ($username) {
            return $current->id == $username;
        });

        return reset($contact);
    }

    /**
     * @param $text
     * @param $contact
     * @return bool|float
     */
    public function sendMessage($text, $contact)
    {
        return $this->transport->send($contact, $text);
    }

    /**
     * @param $text
     * @param $contact
     * @param $message_id
     * @return bool|float
     */
    public function editMessage($text, $contact, $message_id)
    {
        return $this->transport->send($contact, $text, $message_id);
    }

    /**
     * @param $callback
     */
    public function onMessage($callback)
    {
        while (true) {
            call_user_func_array($callback, [
                $this->transport->getNewMessages($this->profile->username),
                $this
            ]);

            sleep(1);
        }
    }
}