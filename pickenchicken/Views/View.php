<?php

namespace pickenchicken\Views;

class View extends \bandpress\Views\View{

    public function __construct( $data = null){

        parent::__construct( $data );
        
        $package_links = [];
        // set navbar links;
        if(current_user_can("send_bulletins")){
            $package_links['Send Bulletin'] = "/pickenchicken/composeBulletin";
        }

        $this->nav->setSectionLinks("package_links",$package_links);

    }
}