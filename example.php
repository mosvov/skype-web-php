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
$skype->sendTo("Hello: " . time(), $skype->getContact("vomoskal"));
$skype->logout();