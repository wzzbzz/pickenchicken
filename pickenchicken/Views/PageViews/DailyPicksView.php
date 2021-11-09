<?php
namespace pickenchicken\Views\PageViews;

use \bandpress\Views\View;
use \pickenchicken\Models\User;
class DailyPicksView extends View{

    public function renderBody(){
        
        $allGamesFinished = $allGamesStarted = false;
        $userPicks = $this->data->getUserPicks(app()->currentUser()->id());

        $this->renderIntro();

        if(empty($userPicks)){
            
            $this->renderPicksForm();
            
        }
        else{
            $this->renderScoreboard();    
            $this->renderGames();
        }
die;
        $chickenResults = $userResults = array("win"=>0,"loss"=>0,"push"=>0);
        foreach($games as $i=>$game){
            
            //$allGamesStarted = $allGamesStarted && $game->gameStartedInThePast();
            $allGamesFinished = $allGamesStarted && $game->gameIsDecided();
            if($game->gameIsDecided()){
                if($game->winningPick()=="push"){
                    $chickenResults['push']++;
                    $userResults['push']++;
                }
                else{
                    if($game->pickIsWinner($game->chickenPick())){
                        $chickenResults['win']++;
                    }
                    else{
                        $chickenResults['loss']++;
                    }
                    if($game->pickIsWinner($userPicks[$i])){
                        $userResults['win']++;
                    }
                    else{
                        $userResults['loss']++;
                    }
                }
            }
        }

        ?>
        
        
        <div class="container mb-5">
            <div class="row">
                <div class="col text-center">
                    <h6>Your results: <?= $userResults['win'] . "-".$userResults['loss']."-".$userResults['push']?></h6>
                </div>
            </div>
            <div class="row">
                <div class="col text-center">
                    <h6>Chicken's results: <?= $chickenResults['win'] . "-".$chickenResults['loss']."-".$chickenResults['push']?></h6>
                </div>
            </div>
            <?php if ($allGamesFinished):
            if($userResults['win']>$chickenResults['win']){
                $text = "You plucked the chicken!";
                $textClass="text-success";
            }
            elseif($userResults['win']<$chickenResults['win']){
                $text = "You got pecked by the chicken!";
                $textClass="text-danger";
            }
            else{
                $text="You equaled a chicken.";
                $textClass="text-danger";
            }
                
            ?>
            <div class="row">
            <div class="col text-center">
                <h6 class="<?=$textClass;?>"><?=$text?></h6>
            </div>
            </div>
            <?php endif;?>
        </div>
        
        <?php
    }

    public function renderIntro(){
        ?>
        <h2 class="d-flex justify-content-center">The Chicken's Pickens!</h2>
        <h6 class="d-flex justify-content-center">Games for <?php echo $this->data->title();?></h6>
        <?php

    }

    public function renderPicksForm(){
        $games = $this->data->getGames();

?>
<div class="container">
            <form action="actions/dailyPicks" method="post">
            <input type="hidden" name="postId" value="<?= $this->data->id();?>"/>
            <?php foreach ($games as $i=>$game): 
?>
            <div class="row justify-content-center">
                <div class="col text-center" id="away">
                <?= $game->awayTeam()->abbreviation();?>
                </div>
                <div class="col text-center"><?= $game->displayPointSpread();?></div>
                <div class="col text-center" id="home">
                <?= $game->homeTeam()->abbreviation();?>
                </div>
            </div>
            <div class="row mb-3 justify-content-center">
                <div class="col text-center" id="away">
                <input type="radio" name="gamePicks[<?=$i?>]" value="away" <?= $disabled; ?> <?= $awaySelected;?> />
                </div>
                <div class="col text-center">
                </div>
                <div class="col text-center" id="home">
                <input type="radio" name="gamePicks[<?=$i?>]" value="home" <?= $disabled;?> <?= $homeSelected;?>/>
                </div>
            </div>

            <?php endforeach;?>
            <?php $disabled = $allGamesStarted?"disabled":"";?>
            <div class="row">
                <div class="col text-center">
                <button type="submit" <?=$disabled;?>>Lock 'em in</button>
                </div>
            </div>
            </form>
        </div>
<?php
    }

    private function renderGames(){
       $userPicks = $this->data->getUserPicks(app()->currentUser()->id());
        ?>
        <div class="container">

            <?php foreach ($this->data->getGames() as $i=>$game): 
                $userPick = $userPicks[$i];
                $textClass="";
                $userTeam = $game->teamFromHomeAway($userPick);
                $chickenTeam = $game->teamFromHomeAway($game->chickenPick());
            ?>
            <div class="row justify-content-center">
                <div class="col text-center">
                    <strong>Away</strong>
                </div>
                <div class="col text-center">
                    
                </div>
                <div class="col text-center">
                    <strong>Home</strong>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col text-center" id="away">
                <?= $game->awayTeam()->abbreviation();?>
                </div>
                <div class="col text-center"><?= $game->displayPointSpread();?></div>
                <div class="col text-center" id="home">
                <?= $game->homeTeam()->abbreviation();?>
                </div>
            </div>
            <div class="row mb-3 justify-content-center">
                <div class="col text-center" id="away">
                
                </div>
                <div class="col text-center">
                <?php if ($game->gameIsDecided()):?>
                <div><?php echo $game->awayScore();?> - <?php echo $game->homeScore();?></div>
                <?php
                    if($game->winningPick()=="push"){
                        $text="Push";
                        $textClass="text-warning";
                        $userPicks["push"]++;
                    }
                    else{
                        if($game->pickIsWinner($userPick)){
                            $text="You picked {$userTeam->abbreviation()}. You Win!";
                            $textClass="text-success";
                        }
                        else{
                            $text="You picked {$userTeam->abbreviation()}. You lose.";
                            $textClass="text-danger";
                        }
                    }
                ?>
                    <div class="userPickResult <?=$textClass;?>"><?=$text;?></div>
                    <?php
                        if($game->winningPick()=="push"){
                            $textClass="text-warning";
                        }
                        else{
                            $textClass=$game->pickIsWinner($game->chickenPick())?"text-success":"text-danger";
                        }
                        
                        
                    ?>
                <?php else:?>
                    <div class="userPickResult">You Picked <span class='<?=$textClass;?>'><?=$userTeam->abbreviation()?></span></div>
                <?php endif;?>
                <div class="chickenPickResult">Chicken Picked <span class='<?=$textClass;?>'><?=$chickenTeam->abbreviation()?></span></div>
                
                </div>
                <div class="col text-center" id="home">
                
                </div>
            </div>

            <?php endforeach;?>
            <?php $disabled = $allGamesStarted?"disabled":"";?>
            
        </div>
        <?php
    }

    public function renderScoreboard(){

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
            
            if($game->gameIsDecided()){
                
                if($game->winningPick()=="push"){
                    $allResults['TheChicken']['push']++;

                    foreach($usersPicks as $user_id=>$userPick){

                        $allResults[$user_id]['push']++;

                    }
                }
                else{
                    if($game->pickIsWinner($game->chickenPick())){
                        $allResults['TheChicken']['win']++;
                    }
                    else{
                        $allResults['TheChicken']['loss']++;
                    }
                    foreach($usersPicks as $user_id=>$userPick){
                        $user = new User(get_user_by("id",$user_id));
                        echo $user->display_name()." picked ".$game->teamFromHomeAway( $userPick[$i] )->abbreviation()."<br>";
                        if($game->pickIsWinner($userPick[$i])){
                            $allResults[$user_id]['win']++;
                        }
                        else{
                            $allResults[$user_id]['loss']++;
                        }
                        
                    }
                   
                }
            }
        }
        die;
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

     /* This is the static comparing function: */
     static function sortByWin($a, $b)
     {
         
         if ($a['win'] == $b['win']) {
             return 0;
         }
         return ($a['win'] < $b['win']) ? +1 : -1;
     }
}