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
                <?= $game->awayTeamAbb;?>
                </div>
                <div class="col text-center"><?= $game->awayTeamAbb;?> <?=$game->pointSpread<0?"":"+";?><?= $game->pointSpread;?></div>
                <div class="col text-center" id="home">
                <?= $game->homeTeamAbb;?>
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
            <div class="row">
                <div class="col text-center">
                <button type="submit" >Lock 'em in</button>
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
                $userTeam = ($userPick=="away")?$game->awayTeamAbb:$game->homeTeamAbb;
                $chickenTeam = ($game->chickenPick=="away")?$game->awayTeamAbb:$game->homeTeamAbb;
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
                <?= $game->awayTeamAbb;?>
                </div>
                <div class="col text-center"><?=$game->awayTeamAbb;?> <?=($game->pointSpread>0)?"+":"";?><?= $game->pointSpread;?></div>
                <div class="col text-center" id="home">
                <?= $game->homeTeamAbb;?>
                </div>
            </div>
            
            <div class="row mb-3 justify-content-center">
                <div class="col text-center" id="away">
                
                </div>
                <div class="col text-center">
                <div><?= $game->checkedDate; ?>
                <?php if ($game->status=="Completed"):?>
                <div><?php echo $game->scoreAwayTotal;?> - <?php echo $game->scoreHomeTotal;?></div>
                <?php
                    $adjustedScore = ($game->scoreAwayTotal + $game->pointSpread) - $game->scoreHomeTotal;
                    if($adjustedScore==0){
                        $text="Push";
                        $textClass="text-warning";
                        $userPicks["push"]++;
                    }
                    else{
                        $winner = ($adjustedScore)>0?"away":"home";
                        $loser = ($adjustedScore)<0?"away":"home";
                        $winnerAbbr = $winner."TeamAbb";
                        $loserAbbr = $loser."TeamAbb";
         
                        if($winner == $userPick){
                            $text="You picked {$game->$winnerAbbr}. You Win!";
                            $textClass="text-success";
                        }
                        else{
                            $text="You picked {$game->$loserAbbr}. You lose.";
                            $textClass="text-danger";
                        }
                    }
                ?>
                    <div class="userPickResult <?=$textClass;?>"><?=$text;?></div>
                    <?php
                        if($adjustedScore==0){
                            $textClass="text-warning";
                        }
                        else{
                            $winner = ($adjustedScore)>0?"away":"home";
                            $textClass=($winner == $game->chickenPick)?"text-success":"text-danger";
                        }
                        
                        
                    ?>
                <?php else:?>
                    <div class="userPickResult">You Picked <span class='<?=$textClass;?>'><?=$userTeam?></span></div>
                <?php endif;?>
                    <div class="chickenPickResult">Chicken Picked <span class='<?=$textClass;?>'><?=$chickenTeam?></span></div>
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

     /* This is the static comparing function: */
     static function sortByWin($a, $b)
     {
         
         if ($a['win'] == $b['win']) {
             return 0;
         }
         return ($a['win'] < $b['win']) ? +1 : -1;
     }
}