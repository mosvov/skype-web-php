<?php

namespace Skype4PHP;

use Exception;
use DOMDocument;
use DOMXPath;
use GuzzleHttp\Client;

class Transport {

    private static $Endpoints = null;
    private static function init() {
        if (!static::$Endpoints) {
            static::$Endpoints = [
                'login_get'    => new Endpoint('GET',
                    'https://login.skype.com/login?client_id=578134&redirect_uri=https%3A%2F%2Fweb.skype.com'),

                'login_post'   => new Endpoint('POST',
                    'https://login.skype.com/login?client_id=578134&redirect_uri=https%3A%2F%2Fweb.skype.com'),

                'asm'          => new Endpoint('POST',
                    'https://api.asm.skype.com/v1/skypetokenauth'),

                'endpoint'     => (new Endpoint('POST',
                    'https://client-s.gateway.messenger.live.com/v1/users/ME/endpoints'))
                    ->needSkypeToken(),

                'contacts'     => (new Endpoint('GET',
                    'https://contacts.skype.com/contacts/v1/users/%s/contacts?filter=type%%20eq%%20%%27skype%%27%%20or%%20type%%20eq%%20%%27msn%%27%%20or%%20type%%20eq%%20%%27pstn%%27%%20or%%20type%%20eq%%20%%27agent%%27'))
                    ->needSkypeToken(),

                'contact_info' => (new Endpoint('POST',
                    "https://api.skype.com/users/self/contacts/profiles"))
                    ->needSkypeToken(),

                'send_message' => (new Endpoint('POST',
                    'https://%sclient-s.gateway.messenger.live.com/v1/users/ME/conversations/%s/messages'))
                    ->needRegToken(),

                'logout'       => (new Endpoint('Get',
                    'https://login.skype.com/logout?client_id=578134&redirect_uri=https%3A%2F%2Fweb.skype.com&intsrc=client-_-webapp-_-production-_-go-signin')),
            ];
        }
    }

    private $username;
    private $skypeToken;
    private $regToken;
    private $cloud;

    private $Client;
    public function __construct() {
        static::init();

        $stack = new \GuzzleHttp\HandlerStack();
        $stack->setHandler(new \GuzzleHttp\Handler\CurlHandler());

        /**
         * Здесь ставим ловушку, чтобы с помощью редиректов
         *   определить адрес сервера, который сможет отсылать сообщения
         */
        $stack->push(\GuzzleHttp\Middleware::mapResponse(function (\Psr\Http\Message\ResponseInterface $response) {
            $code = $response->getStatusCode();
            if (($code >= 301 && $code <= 303) || $code == 307 || $code == 308) {
                $location = $response->getHeader('Location');
                preg_match('/https?://([^-]*-)client-s/', $location, $matches);
                if (array_key_exists(1, $matches)) {
                    $this->cloud = $matches[1];
                }
            }
            return $response;
        }));

        /**
         * Ловушка для отлова хедера Set-RegistrationToken
         * Тоже нужен для отправки сообщений
         */
        $stack->push(\GuzzleHttp\Middleware::mapResponse(function (\Psr\Http\Message\ResponseInterface $response) {
            $h = $response->getHeader("Set-RegistrationToken");
            if (count($h) > 0) {
                $this->regToken = trim(explode(';', $h[0])[0]);
            }
            return $response;
        }));

        $this->Client = new Client([
            'handler' => $stack,
            'cookies' => true,
        ]);

    }

    /**
     * Выполнить реквест по имени endpoint из статического массива
     * @param string $endpoint
     * @param array $params
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function request($endpoint, $params=[]) {
        $Endpoint = static::$Endpoints[$endpoint];
        if (array_key_exists("format", $params)) {
            $format = $params['format'];
            unset($params['format']);
            $Endpoint = $Endpoint->format($format);
        }
        $Request = $Endpoint->getRequest([
            'skypeToken' => $this->skypeToken,
            'regToken'   => $this->regToken,
        ]);

        $res = $this->Client->send($Request, $params);
        return $res;
    }

    /**
     * Выполнить реквест по имени endpoint из статического массива
     * и вернуть DOMDocument построенный на body ответа
     * @param string $endpoint
     * @param array $params
     * @return DOMDocument
     */
    private function requestDOM($endpoint, $params=[]) {
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->recover = true;
        $body = $this->request($endpoint, $params)->getBody();
        $doc->loadHTML((string) $body);
        libxml_use_internal_errors(false);
        return $doc;
    }

    /**
     * Выполнить реквест по имени endpoint из статического массива
     * и преобразовать JSON-ответ в array
     * @param string $endpoint
     * @param array $params
     * @return array
     */
    private function requestJSON($endpoint, $params=[]) {
        return json_decode($this->request($endpoint, $params)->getBody(), true);
    }

    /**
     * Запрос для входа.
     * @param string $username
     * @param string $password
     * @param null $captchaData Можем передать массив с решением капчи
     * @return DOMDocument
     */
    private function postToLogin($username, $password, $captchaData=null) {
        $doc = $this->requestDOM('login_get');
        $loginForm = $doc->getElementById('loginForm');
        $inputs = $loginForm->getElementsByTagName('input');
        $data = [];
        foreach ($inputs as $input) {
            $data[$input->getAttribute('name')] = $input->getAttribute('value');
        }
        $now = time();
        $data['timezone_field'] = str_replace(':', '|', date('P', $now));
        $data['username'] = $username;
        $data['password'] = $password;
        $data['js_time'] = $now;
        if ($captchaData) {
            $data['hip_solution'] = $captchaData['hip_solution'];
            $data['hip_token'] = $captchaData['hip_token'];
            $data['fid'] = $captchaData['fid'];
            $data['hip_type'] = 'visual';
            $data['captcha_provider'] = 'Hip';
        } else {
            unset($data['hip_solution']);
            unset($data['hip_token']);
            unset($data['fid']);
            unset($data['hip_type']);
            unset($data['captcha_provider']);
        }

        return $this->requestDOM('login_post', [
            'form_params' => $data
        ]);
    }

    /**
     * Выполняем запрос для входа, ловим из ответа skypeToken
     * Проверяем не спросили ли у нас капчу и не возникло ли другой ошибки
     * Если всё плохо, то бросим исключение, иначе вернём true
     * @param $username
     * @param $password
     * @param null $captchaData
     * @return bool
     * @throws Exception
     */
    public function login($username, $password, $captchaData=null) {
        $this->username = $username;

        $doc = $this->postToLogin($username, $password, $captchaData);
        $xpath = new DOMXPath($doc);
        $inputs = $xpath->query("//input[@name='skypetoken']");
        if ($inputs->length) {
            $this->skypeToken = $inputs->item(0)->attributes->getNamedItem('value')->nodeValue;
            $this->request('asm', [
                'form_params' => [
                    'skypetoken' => $this->skypeToken,
                ],
            ]);
            $this->request('endpoint', [
                'headers' => [
                    'Authentication' => "skypetoken=$this->skypeToken"
                ],
                'json' => [
                    'skypetoken' => $this->skypeToken
                ]
            ]);
            return true;
        }

        $captcha = $doc->getElementById("captchaContainer");
        if ($captcha) {
            // Вот здесь определяем данные капчи
            $scripts = $captcha->getElementsByTagName('script');
            if ($scripts->length > 0) {
                $script = "";
                foreach ($scripts as $item) {
                    $script .= $item->textContent;
                }
                preg_match_all("/skypeHipUrl = \"(.*)\"/", $script, $matches);
                $url = $matches[1][0];
                $rawjs = $this->Client->get($url)->getBody();
                $captchaData = $this->processCaptcha($rawjs);
                // Если решение получено, пытаемся ещё раз залогиниться, но уже с решением капчи
                if ($this->login($username, $password, $captchaData)) {
                    return true;
                } else {
                    throw new Exception("Captcha error: $url");
                }
            }
        }
        $errors = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " message_error ")]');
        if ($errors->length) {
            $s = '';
            foreach ($errors as $error) {
                $s = $s . PHP_EOL . $error->textContent;
            }
            throw new Exception($s);
        }
        throw new Exception("Unable to find skype token");
    }

    /**
     * Выход
     * @return bool
     */
    public function logout() {
        $this->request('logout');
        return true;
    }

    /**
     * Заглушка для ввода капчи. Сейчас просто выводит в консоли урл картинки
     * и ждёт ввода с клавиатуры решения
     * @param $script
     * @return array
     */
    private function processCaptcha($script) {
        preg_match_all("/imageurl:'([^']*)'/", $script, $matches);
        $imgurl = $matches[1][0];
        preg_match_all("/hid=([^&]*)/", $imgurl, $matches);
        $hid = $matches[1][0];
        preg_match_all("/fid=([^&]*)/", $imgurl, $matches);
        $fid = $matches[1][0];
        print_r(PHP_EOL . "url: " . $imgurl . PHP_EOL);
        return [
            'hip_solution' => trim(readline()),
            'hip_token'    => $hid,
            'fid'          => $fid,
        ];
    }

    /**
     * Отправляем текстовое сообщение юзеру
     * @param $username
     * @param $message
     * @return bool
     */
    public function send($username, $message) {
        $ms = microtime();
        $response = $this->requestJSON('send_message', [
            'json' => [
                'content' => $message,
                'messagetype' => 'RichText',
                'contenttype' => 'text',
                'clientmessageid' => "$ms",
            ],
            'format' => [$this->cloud, "8:$username"]
        ]);
        return array_key_exists("OriginalArrivalTime", $response);
    }

    /**
     * Скачиваем список всех контактов и информацию о них для залогиненного юзера
     * @return null
     */
    public function loadAllContacts() {
        $result = $this->requestJSON('contacts', [
            'format' => [$this->username],
        ]);
        if (array_key_exists('contacts', $result)) {
            return $result['contacts'];
        }
        return null;
    }

    /**
     * Скачиваем информацию о конкретном контакте, только если его нет в кеше
     * @param $username
     * @return array
     */
    public function loadContact($username) {
        $result = $this->requestJSON('contact_info', [
            'form_params' => [
                'contacts' => [$username]
            ]
        ]);
        return $result;
    }

}
