<?php

namespace pickenchicken\Models;


class User extends \bandpress\Models\User{
    
    public function setDailyPicks($post_id, $picks){
        $post = get_post($post_id);
        $dailyGames = new DailyScheduleOfGames($post);
        $dailyGames->setUserPicks($this->id(),$picks);
    }
    public function getDailyPicks($post_id){

    }

}