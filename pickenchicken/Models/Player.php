<?php

namespace pickenchicken\Models;


class Player extends \vinepress\Models\User{
    
    public function setDailyPicks($post_id, $picks){
        $post = get_post($post_id);
        $dailyGames = new DailyScheduleOfGames($post);
        $dailyGames->setUserPicks($this->id(),$picks);
    }

    public function getDailyPicks($post_id){

    }

    public function getBalance(){
        return empty($this->get_meta("cluckbucks"))?0:$this->get_meta("cluckbucks");
    }

    public function setBalance( $balance ){
        $this->update_meta("cluckbucks",$balance);
    }

    public function updateBalance( $amount ){
        $balance = $this->getBalance();
        $balance += $amount;
        $this->setBalance($balance);
    }

    public function hasRecievedBonus(){
        
    }

}