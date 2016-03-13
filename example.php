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

$contact_id = $skype->getContact("vomoskal")->id;
$message_id = $skype->sendMessage("Hello: ".date('Y-m-d H:i:s'), $contact_id);
$skype->editMessage("Hello: ".date('Y-m-d H:i:s'), $contact_id, $message_id);

$skype->onMessage(function ($messages, Skype $skype) {

    if (!is_array($messages)) return;

    foreach ($messages as $message){
        if (isset($message->resource->content)) {
            if ($message->resource->imdisplayname != $skype->profile->username) {//message not from self

                $message_from = substr($message->resource->from, strpos($message->resource->from, "8:") + 2);

                $skype->sendMessage($message->resource->content.".  Response: ".date('Y-m-d H:i:s'), $message_from);
            }
        }
    }
});

// Catch Fatal Error
register_shutdown_function(array($skype, 'logout'));
// Catch Ctrl+C, kill and SIGTERM
pcntl_signal(SIGTERM, array($skype, 'logout'));
pcntl_signal(SIGINT, array($skype, 'logout'));