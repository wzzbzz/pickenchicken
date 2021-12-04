<?php

namespace pickenchicken\Views\ComponentViews;
use \pickenchicken\Models\Player;

class Scoreboard{
    
    private $data;

    public function __construct( $data ){
        $this->data = $data;
    }

    public function render(){

        $playerPicks = $this->data->getPlayerPicks(app()->currentUser()->id());
        $playersPicks = $this->data->picks();
        
        $chickenResults = $playerResults =  array("win"=>0,"loss"=>0,"push"=>0);
        $allResults = array();
        $allResults['TheChicken']=array("win"=>0,"loss"=>0,"push"=>0);
        foreach($this->data->picks() as $id=>$picks){
            $player = new Player(get_user_by('ID',$id));
            $allResults[$player->id()]=array("win"=>0,"loss"=>0,"push"=>0);
        }
        foreach($this->data->getGames() as $i=>$game){
            
            //$allGamesStarted = $allGamesStarted && $game->gameStartedInThePast();
            if($game->status=="Completed"){
                $adjustedScore = ($game->scoreAwayTotal + $game->pointSpread) - $game->scoreHomeTotal;
                
                if($adjustedScore==0){
                    $allResults['TheChicken']['push']++;

                    foreach($playersPicks as $player_id=>$playerPick){

                        $allResults[$player_id]['push']++;

                    }
                }
                else{
                    $winner = ($adjustedScore)>0?"away":"home";
                    if($winner == $game->chickenPick){
                        $allResults['TheChicken']['win']++;
                    }
                    else{
                        $allResults['TheChicken']['loss']++;
                    }
                    foreach($playersPicks as $player_id=>$playerPick){
                        $player = new Player(get_user_by("id",$player_id));

                        if($winner == $playerPick[$i]){
                            $allResults[$player_id]['win']++;
                        }
                        else{
                            $allResults[$player_id]['loss']++;
                        }
                        
                    }
                   
                }
            }
        }

        uasort($allResults, array("\pickenchicken\Views\PageViews\DailyPicksView", "sortByWin"));
        
        ?>
        
        
        <div class="container mb-5 h-100">

            <?php
            
            foreach($allResults as $id=>$results):
                if($id=="TheChicken"){
                    $name = $id;
                }
                else{
                    $player = new Player(get_user_by("ID",$id));
                    $name = $player->display_name();
                }
            ?>
            <div class="row">
                <div class="col text-center">
                    <h6><?=$name;?>: <?= $results['win'] . "-".$results['loss']."-".$results['push']?></h6>
                </div>
            </div>
            <?php endforeach;?>
        </div>
        <?php
    }
}