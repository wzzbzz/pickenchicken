<?php
namespace pickenchicken\Views\PageViews;

use \bandpress\Views\View;

class DailyPicksView extends View{

    public function renderBody(){
        $games = $this->data->getGames();
        $allGamesFinished = $allGamesStarted = false;
        $userPicks = $this->data->getUserPicks(app()->currentUser()->id());
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
        
        <h2 class="d-flex justify-content-center">The Chicken's Pickens!</h2>
        <h6 class="d-flex justify-content-center">Games for <?php echo $this->data->title();?></h6>
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
            <?php if ($allGamesDecided):
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
        <div class="container">
            <form action="actions/dailyPicks" method="post">
            <input type="hidden" name="postId" value="<?= $this->data->id();?>"/>
            <?php foreach ($games as $i=>$game): 
                $disabled = "";//$game->gameStartedInThePast()?"disabled":"";
                $userPick = array_shift($userPicks); 

                $homeSelected = ($userPick=="home")?"checked":"";
                $awaySelected = ($userPick=="away")?"checked":"";


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
                            $text="You Win!";
                            $textClass="text-success";
                        }
                        else{
                            $text="You lose.";
                            $textClass="text-danger";
                        }
                    }
                ?>
                    <div class="userPickResult <?=$textClass;?>"><?=$text;?></div>
                    <?php
                        $team = $game->teamFromHomeAway($game->chickenPick());
                        if($game->winningPick()=="push"){
                            $textClass="text-warning";
                        }
                        else{
                            $textClass=$game->pickIsWinner($game->chickenPick())?"text-success":"text-danger";
                        }
                        $text = "Chicken Picked <span class='{$textClass}'>{$team->abbreviation()}</span>";
                        
                    ?>
                    <div class="chickenPickResult"><?=$text;?></div>
                <?php endif;?>
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
}