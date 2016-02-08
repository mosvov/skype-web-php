<?php

require 'Skype4PHP/Skype.php';

use Skype4PHP\Skype;

$username = 'cyberpunk239';
$password = 'pft,fkbvtyz239';

$skype = new Skype();
$skype->login($username, $password);
$skype->sendTo("Hello: " . time(), $skype->getContact("kesha_seyfert"));
$skype->logout();
