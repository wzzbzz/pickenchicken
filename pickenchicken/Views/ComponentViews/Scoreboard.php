<?php

namespace pickenchicken\Views\ComponentViews;
use \pickenchicken\Models\User;

class Scoreboard{
    private $data;
    public function __construct( $data ){
        $this->data = $data;
    }
    public function render(){
        $userPicks = $this->data->getUserPicks(app()->currentUser()->id());
        $usersPicks = $this->data->picks();
        $chickenResults = $userResults =  array("win"=>0,"loss"=>0,"push"=>0);
        $allResults = array();
        $allResults['TheChicken']=array("win"=>0,"loss"=>0,"push"=>0);
        foreach($this->data->picks() as $id=>$picks){
            $user = new User(get_user_by('ID',$id));
            $allResults[$user->id()]=array("win"=>0,"loss"=>0,"push"=>0);
        }
        foreach($this->data->getGames() as $i=>$game){
            
            //$allGamesStarted = $allGamesStarted && $game->gameStartedInThePast();
            if($game->status=="Completed"){
                $adjustedScore = ($game->scoreAwayTotal + $game->pointSpread) - $game->scoreHomeTotal;
                
                if($adjustedScore==0){
                    $allResults['TheChicken']['push']++;

                    foreach($usersPicks as $user_id=>$userPick){

                        $allResults[$user_id]['push']++;

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
                    foreach($usersPicks as $user_id=>$userPick){
                        $user = new User(get_user_by("id",$user_id));

                        if($winner == $userPick[$i]){
                            $allResults[$user_id]['win']++;
                        }
                        else{
                            $allResults[$user_id]['loss']++;
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
                    $user = new User(get_user_by("ID",$id));
                    $name = $user->display_name();
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