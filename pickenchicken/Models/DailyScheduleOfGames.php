<?php

namespace pickenchicken\Models;
use bandpress\Models\Post;

class DailyScheduleOfGames extends Post{
    public function getGames2(){
        //TBD - create "game" object and return array;
        //tbd rename field to "games"
        $games = [];
        foreach($this->get_field("game") as $game){
            $games[] = new Game($game);
        }
        return $games;
    }

    public function updateGamesFromFeed($data){
        $games = $this->getGames();

        // preserve chicken pick and spread
        foreach($games as $i=>$game){
            $data[$i]->chickenPick = $game->chickenPick;
            $data[$i]->pointSpread = $game->pointSpread;
        }
        $this->update_meta("games",$data);

    }

    public function getGames(){
        return $this->get_meta("games",true);
    }

    public function updateGames( $games ){
        $this->update_meta("games",$games);
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