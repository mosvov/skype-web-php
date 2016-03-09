<?php
/* @var $loader \Composer\Autoload\ClassLoader */
$loader = require 'vendor/autoload.php';
$loader->setUseIncludePath(__DIR__.'/skype_web_php/');
$loader->register();

use skype_web_php\Skype;

$username = '';
$password = '';


$skype = new Skype();
$skype->login($username, $password);
$skype->sendTo("Hello: ".date('Y-m-d H:i:s'), $skype->getContact("vomoskal")->id);
$skype->onMessage(function ($messages, Skype $skype) {

    if (!is_array($messages)) return;

    foreach ($messages as $message){
        if (isset($message->resource->content)) {
            if ($message->resource->imdisplayname != $skype->profile->username) {//message not from self

                $message_from = substr($message->resource->from, strpos($message->resource->from, "8:") + 2);

                $skype->sendTo($message->resource->content.".  Response: ".date('Y-m-d H:i:s'), $message_from);
            }
        }
    }
});
//$skype->logout();