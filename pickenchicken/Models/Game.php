<?php
/*
    encapsulates game data as pulled from
    datafeeds.net free api feed.
*/
namespace pickenchicken\Models;
use bandpress\Models\Model;

class Game extends Model{
    
    private $game_id;
    private $gameUID;
    private $type;
    private $api;
    private $feedID;
    private $speed;
    private $sport;
    private $league;
    private $startDate;
    private $seasonType;
    private $seasonYear;
    private $seasonWeek;
    private $awayTeamAbb;
    private $awayTeamCity;
    private $awayTeamName;
    private $awayTeam;
    private $homeTeamAbb;
    private $homeTeamCity;
    private $homeTeamName;
    private $homeTeam;
    private $description;
    private $venueName;
    private $venueLocation;
    private $isLive;
    private $status;
    private $period;
    private $clock;
    private $scoreAwayTotal;
    private $scoreHomeTotal;
    private $scoreAwayPeriod1;
    private $scoreAwayPeriod2;
    private $scoreAwayPeriod3;
    private $scoreAwayPeriod4;
    private $scoreHomePeriod1;
    private $scoreHomePeriod2;
    private $scoreHomePeriod3;
    private $scoreHomePeriod4;
    private $nextUpdate;
    private $checkedData;


    public function __construct( ){
        
    }

    // setters n getters;
    
    public function setGameId( $id ){
        $this->game_id = $id;

    }
    public function gameId(){
        return $this->game_id;
    }
    
    public function setGameUID( $id ){
        $this->gameUID = $id;
    }

    public function gameUID(){
        return $this->gameUID;
    }
    
    public function setType($type){
        $this->type = $type;
    }

    public function type(){
        return $this->type;
    }

    public function setApi( $api ){
        $this->api = $api;
    }

    public function api(){
        return $this->api;
    }

    public function setFeedId($feed_id){
        $this->feedID = $feed_id;
    }

    public function feedID(){
        return $this->feedID;
    }

    public function setSpeed( $speed ){
        $this->speed = $speed;
    }

    public function speed(){
        return $this->speed;
    }

    public function setSport( $sport ){
        $this->sport = $sport;
    }

    public function sport(){
        return $this->sport();
    }

    public function setLeague( $league ){
        $this->league = $league;
    }

    public function league(){
        return $this->league();
    }

    public function setStartDate( $startDate ){
        // how are we gonna handle this?
        $this->startDate = $startDate;
    }

    public function startDate(){
        return $this->startDate;
    }

    public function setSeasonType( $type ){
        $this->seasonType = $type;
    }
    
    public function seasonType(){
        return $this->seasonType;
    }

    public function setSeasonYear( $year ){
        $this->seasonYear = $year;
    }

    public function seasonYear(){
        return $this->seasonYear;
    }

    public function awayTeamAbb(){
        return $this->awayTeamAbb;
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