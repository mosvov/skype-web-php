<?php

namespace skype_web_php;

class Message {

    public $id;
    public $type;
    public $resourceType;
    public $time;
    public $messagetype;
    public $originalarrivaltime;
    public $imdisplayname;
    public $conversationLink;
    public $content;
    public $from;

    public function __construct(array $array) {
        if (count($array) == 0){
            return null;
        }

        if (!isset($array['id'])){//array of objects
            $list = [];
            foreach ($array as $key => $model){
                $list[$key] = $this->loadModel($model);
            }
            return $list;
        }else{
            return $this->loadModel($array);
        }
    }

    private function loadModel(array $array){
        foreach(get_object_vars($this) as $attrName => $attrValue){
            if (isset($array[$attrName])){
                $this->{$attrName} = $array[$attrName];
            }

            if (isset($array['resource'][$attrName])){
                $this->{$attrName} = $array['resource'][$attrName];
            }
        }

        if (isset($array['resource']['from'])){//get user from
            $from = $array['resource']['from'];
            $this->from = substr($from, strpos($from, "8:") + 2);
        }
    }

    public function getDisplayName() {
        return $this->raw['display_name'];
    }

    public function getAvatarUrl() {
        return $this->raw['avatar_url'];
    }

    public function getUsername() {
        return $this->raw['id'];
    }

}
