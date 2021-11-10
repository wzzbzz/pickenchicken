<?php
namespace pickenchicken\Views\ComponentViews;
use \pickenchicken\Models\User;

class PlayersList{
    private $data;

    public function __construct($data){
        $this->data = $data;
    }

    public function render(){
        foreach($this->data->players() as $player){
            ?>
            <div class="row">
                <div class="col text-center">
                    <h6><?=$player->display_name();?></h6>
                </div>
            </div>
            <?php
        }
    }
}