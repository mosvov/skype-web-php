<?php

require 'Skype.php';
require 'Skype2.php';

$username = 'cyberpunk239';
$password = 'pft,fkbvtyz239';

$skype = new Skype2();
$skype->login($username, $password);
var_dump($skype->getContacts());

//$skype = new Skype();
//$skype->login($username, $password);
//var_dump($skype->getContacts());
