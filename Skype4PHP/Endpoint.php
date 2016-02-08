<?php

namespace Skype4PHP;

use GuzzleHttp\Psr7\Request;

/**
 * Класс для запросов на апи скайпа
 * Class Endpoint
 * @package Skype4PHP
 */
class Endpoint {
    /** Метод запроса */
    private $method;

    /** URI */
    private $uri;

    /** Доп параметры */
    private $params;

    /** Необходимы ли для этого запроса соответствующие токены */
    private $requires = [
        'skypeToken' => false,
        'regToken'   => false,
    ];

    public function __construct($method, $uri, array $params=[], array $requires=[]) {
        $this->method = $method;
        $this->uri = $uri;
        $this->params = $params;
        if (!array_key_exists('headers', $this->params)) {
            $this->params['headers'] = [];
        }
        $this->requires = array_merge($this->requires, $requires);
    }

    public function needSkypeToken() {
        $this->requires['skypeToken'] = true;
        return $this;
    }
    public function skypeToken() {
        return $this->requires['skypeToken'];
    }

    public function needRegToken() {
        $this->requires['regToken'] = true;
        return $this;
    }
    public function regToken() {
        return $this->requires['regToken'];
    }

    /**
     * Есть запросы с шаблонным uri, который нужно переформатировать перед выполнением
     * Этот метод создаст новый Endpoint подставив в текущий запрос переданные параметры для uri
     */
    public function format($args) {
        return new Endpoint($this->method, vsprintf($this->uri, $args), $this->params, $this->requires);
    }

    /**
     * Получаем Guzzle-овский реквест
     * @param array $args
     * @return Request|\Psr\Http\Message\MessageInterface
     */
    public function getRequest($args=[]) {
        $req = new Request($this->method, $this->uri, $this->params);
        if ($this->requires['skypeToken']) {
            $req = $req->withHeader('X-SkypeToken', $args['skypeToken']);
        }
        if ($this->requires['regToken']) {
            $req = $req->withHeader('RegistrationToken', $args['regToken']);
        }
        echo $req->getUri() . PHP_EOL;
        return $req;
    }

}
