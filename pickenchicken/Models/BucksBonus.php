<?php

namespace pickenchicken\Models;

class BucksBonus extends Bonus{

    private $amount;
    private $messageText;
    private $dateRange;

    public function createBonus( $name, $amount, $description = "", $dateRange = null ){
        

    }
    public function do( $user ){
        if(!$this->userHasClaimed( $user )){
            
        }

    }
}