<?php

namespace pickenchicken\Models;
use vinepress\Models\Post;
use pickenchicken\Models\Player;

class DailyScheduleOfGames extends Post{
  
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

    public function setPlayerPicks($user_id, $user_picks){
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

    public function getPlayerPicks($user_id){
        return $this->picks()[$user_id];
    }

    public function picks(){
        return $this->get_meta("picks",true);
    }

    public function players(){
        $players = [];
        foreach($this->picks() as $player=>$picks){
            $players[] = new Player(get_user_by("ID",$player));
        }
        return $players;
    }
    public function previousDay(){
        $sql = "SELECT * from wp_posts 
                    WHERE post_date < '{$this->date()}' 
                AND post_status='publish' 
                AND post_type='daily-schedule'
                ORDER BY post_date DESC
                LIMIT 1";
        $results = $this->get_results($sql);
        if(empty($results)){
            return false;
        }
        $post = new DailyScheduleOfGames($results[0]);
        return $post;
    }

    public function gamesHaveStarted(){
        $games = $this->getGames();
        $started=false;
        foreach($games as $game){
            $started = $started || $game->status!='Unplayed';
        }
        return $started;
    }

    
}