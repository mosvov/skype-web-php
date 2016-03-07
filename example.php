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
$skype->sendTo("Hello: " . date('Y-m-d H:i:s'), $skype->getContact("vomoskal"));
$skype->onMessage(function($message) use ($skype, $username){
   var_dump($message);

   if ($message && isset($message->content)){
      if ($message->imdisplayname != $username){//message not from self

         $skype->sendTo($message->content.".  Response: " . date('Y-m-d H:i:s'), $skype->getContact($message->from));
      }
   }
});
//$skype->logout();