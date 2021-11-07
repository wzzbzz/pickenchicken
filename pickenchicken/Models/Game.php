<?php

namespace pickenchicken\Models;
use bandpress\Models\Model;

class Game extends Model{
    private $acf_data;
    public function __construct( $acf_data ){
        $this->acf_data = $acf_data;
    }
    public function homeTeam(){
        return new Team( $this->acf_data['home_team'] );
    }
    public function awayTeam(){
        return new Team( $this->acf_data['away_team']);
    }

    public function displayPointSpread(){
        $spread = $this->pointSpread();
        $teamClass = strtolower($spread['favorite'])."Team";
        $team = $this->$teamClass();
        $plus = $spread['amount']>0?"+":"";
        return $team->abbreviation(). " " . $plus.$spread['amount'];
    }

    public function pointSpread(){
        return $this->acf_data['point_spread'];
    }

    public function chickenPick(){
        return $this->acf_data['chicken_pick'];
    }

    public function chickenPickTeamName(){

    }

    public function gameStartedInThePast(){

        return strtotime($this->acf_data['date_time']) < time();

    }

    public function gameIsDecided(){
        return(!empty($this->homeScore()) && !empty($this->awayScore()));
    }

    // pick will be "home" or "away"
    public function pickIsWinner($pick){
        return $pick == $this->winningPick();
    }

    public function teamFromHomeAway( $homeaway ){

        $teamMethod = $homeaway."Team";
        return $this->$teamMethod();

    }


    // check out this alchemy!  would love to see 
    // how good programmers would do this
    public function winningPick(){
        // get the point spread
        $spread = $this->pointSpread();

        // adjust for error in ACF: TBD fix this
        $favorite = strtolower($spread['favorite']);
        // moethod for retrieving the score
        // probably should juse use $this->acf_data['$favorite']
        // but whatever, we're just using wrappers rather than the fields
        $favoriteScoreMethod=$favorite."Score";

        $otherScoreMethod=$this->otherTeam( $favorite )."Score";

        $adjustedFavoriteScore = $this->$favoriteScoreMethod() + $spread['amount'];
        $otherScore = $this->$otherScoreMethod();
        
        if($adjustedFavoriteScore==$otherScore){
            return "push";
        }

        return($adjustedFavoriteScore>$otherScore)?$favorite:$this->otherTeam($favorite);

    }

    public function otherTeam( $team ){
        return ($team=='away')?'home':'away';
    }
    public function finalScore(){
        return $this->acf_data['final_score'];
    }

    public function awayScore(){
        return $this->acf_data['final_score']['away_score'];
    }

    public function homeScore(){
        return $this->acf_data['final_score']['home_score'];
    }

    public function result(){}
    public function victor(){}
}