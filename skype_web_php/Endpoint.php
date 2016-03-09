<?php
namespace skype_web_php;

use GuzzleHttp\Psr7\Request;

/**
 * Class Endpoint
 *
 * @package skype_web_php
 */
class Endpoint
{

    /**
     * @var
     */
    private $method;
    /**
     * @var
     */
    private $uri;
    /**
     * @var array
     */
    private $params;

    /**
     * @var array
     */
    private $requires = [
        'skypeToken' => false,
        'regToken' => false,
    ];

    /**
     * @param $method
     * @param $uri
     * @param array $params
     * @param array $requires
     */
    public function __construct($method, $uri, array $params = [], array $requires = [])
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->params = $params;
        if (!array_key_exists('headers', $this->params)) {
            $this->params['headers'] = [];
        }
        $this->requires = array_merge($this->requires, $requires);
    }

    /**
     * @return $this
     */
    public function needSkypeToken()
    {
        $this->requires['skypeToken'] = true;

        return $this;
    }

    /**
     * @return mixed
     */
    public function skypeToken()
    {
        return $this->requires['skypeToken'];
    }

    /**
     * @return $this
     */
    public function needRegToken()
    {
        $this->requires['regToken'] = true;

        return $this;
    }

    /**
     * @return mixed
     */
    public function regToken()
    {
        return $this->requires['regToken'];
    }

    /**
     * @param $args
     * @return Endpoint
     */
    public function format($args)
    {
        return new Endpoint($this->method, vsprintf($this->uri, $args), $this->params, $this->requires);
    }

    /**
     * @param array $args
     * @return Request|\Psr\Http\Message\MessageInterface
     */
    public function getRequest($args = [])
    {
        $Request = new Request($this->method, $this->uri, $this->params);
        if ($this->requires['skypeToken']) {
            $Request = $Request->withHeader('X-SkypeToken', $args['skypeToken']);
        }
        if ($this->requires['regToken']) {
            $Request = $Request->withHeader('RegistrationToken', $args['regToken']);
        }

        return $Request;
    }
}