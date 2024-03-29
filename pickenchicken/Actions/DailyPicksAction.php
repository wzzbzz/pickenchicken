<?php

namespace pickenchicken\Actions;
use \pickenchicken\Models\Player;

class DailyPicksAction{
    private $postId;
    private $gamePicks;
    public function __construct(){
        
        $this->postId = $_REQUEST['postId'];
        $this->gamePicks = $_REQUEST['gamePicks'];

    }
    public function __destruct(){}
    public function do(){
        if(!is_user_logged_in()){
            die("anonymous voting not allowed (yet).  Please register / log in");
        }
        else{
            $user = new Player(wp_get_current_user());
            $user->setDailyPicks($this->postId, $this->gamePicks);
        }
        wp_redirect("/pickenchicken");
        die;
    }

}