<?php

namespace pickenchicken\Views\AdminViews;

class DailyScheduleAdminView{
    public function __construct(){}
    public function __destruct(){}

    public function renderDailyScheduleForm(){
        global $post;
        $schedule = new \pickenchicken\Models\DailyScheduleOfGames( $post );
        ?>
        <table class="widefat">
            <thead>
                <tr>
                    <th><strong>Away</strong></th>   
                    <th><strong>Home</strong></th>    
                    <th><strong>Point Spread</strong></th>
                    <th><strong>Chicken Pick</strong></th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th><strong>Away</strong></th>   
                    <th><strong>Home</strong></th>    
                    <th><strong>Point Spread</strong></th>
                    <th><strong>Chicken Pick</strong></th>
                </tr>
            </tfoot>
            <tbody>
            <?php
            foreach($schedule->getGames() as $i=>$game):
                $game->pointSpread=(empty($game->pointSpread))?0:$game->pointSpread;
                if(empty($game->chickenPick)){
                    $homePickChecked="";
                    $awayPickChecked="";
                }
                else{
                    $homePickChecked=($game->chickenPick=="home")?"checked":"";
                    $awayPickChecked=($game->chickenPick=="away")?"checked":"";
                }
            ?>
            <tr>
                <td><?php echo $game->awayTeam; ?></td>
                <td><?php echo $game->homeTeam; ?></td>
                <td><?php echo $game->awayTeamAbb;?> <input type="text" name="point_spread[<?=$i?>]" value="<?=$game->pointSpread;?>"></td>
                <td>
                    <input type="radio" name="chicken_pick[<?=$i?>]" value="away" id="chickenpick" <?=$awayPickChecked;?>/><label for="chickenpick"><?=$game->awayTeamAbb;?></label><br>
                    <input type="radio" name="chicken_pick[<?=$i?>]" value="home" id="chickenpick" <?=$homePickChecked;?>/><label for="chickenpick"><?=$game->homeTeamAbb;?></label>
                </td>
            </tr>
            <?php endforeach;?>
            </tbody>
</table>

        <?php
    }
}