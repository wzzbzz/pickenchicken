<?php

namespace pickenchicken\Models;

use \vinepress\Models\Post;

class Team extends Post{
    
    public function abbreviation(){
        return $this->get_field('abbreviation');
    }

    public function name(){
        return $this->title();
    }
}