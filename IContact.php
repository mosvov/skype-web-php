<?php

interface IContact {

    public function getUsername();
    public function getData();
    public function send($message);

}
