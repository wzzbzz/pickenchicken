<?php

namespace pickenchicken\Models;
use \bandpress\Models\Model;

class Bulletins extends Model{

    public function __construct(){}
    public function __destruct(){}
    public function getLatest(){

        $sql = "SELECT * from wp_posts WHERE post_type='bulletin' AND DATE(post_date) = DATE(NOW()) ORDER BY post_date DESC LIMIT 1";
        $results = $this->get_results($sql);        
        if(empty($results)){
            return false;
        }
        else{
            return new Bulletin($results[0]);
        }

    }
}