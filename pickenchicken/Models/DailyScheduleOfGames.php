<?php

namespace pickenchicken\Models;
use bandpress\Models\Post;

class DailyScheduleOfGames extends Post{
    public function getGames(){
        //TBD - create "game" object and return array;
        //tbd rename field to "games"
        $games = [];
        foreach($this->get_field("game") as $game){
            $games[] = new Game($game);
        }
        return $games;
    }

    public function setUserPicks($user_id, $user_picks){
        $picks = $this->picks();
        if(!is_array($picks)){
            $picks=array();
        }
        if(isset($picks[$user_id])){
            //diebug("picks already set.  can't chang 'em now!");
        }
        $picks[$user_id] = $user_picks;
        $this->update_meta("picks", $picks);
    }

    public function getUserPicks($user_id){
        return $this->picks()[$user_id];
    }

    public function picks(){
        return $this->get_meta("picks",true);
    }

    
}