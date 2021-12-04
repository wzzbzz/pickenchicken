<?php

namespace pickenchicken\Models;

class Bonus{

    protected $slug;
    protected $name;
    protected $validDate;

    public function __construct(){

    }

    public function create($args=null){
        if(is_array($args)){
            foreach($args as $key=>$val){
                $this->$key=$val;
            }
        }
        diebug(json_encode($this));
    }
    public function __destruct(){}

    public function claim( $user ){
        $this->do( $user );
        $user->setClaimed( $this->slug );
    }

    public function do( $user ){

    }

    public function setClaimed( $user ){

    }

    public function userHasClaimed ($user){

    }
}