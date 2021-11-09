<?php
/*
Plugin Name: The Picken' Chicken
*/

namespace pickenchicken;

class PickenChicken {
    public function __construct(){

        // action hooks
        register_activation_hook( __FILE__ , array($this,'activate') );
        register_deactivation_hook( __FILE__ , array($this, 'deactivate' ) );

        add_action('init',array($this,'init'));
        add_action('admin_init',array($this,'admin_init'));
    }

    public function activate(){
        $paths = get_option('root_paths');
        
        if(empty($paths)){
            return false;
        }
        
        if(array_search(dirname(__FILE__),$paths))
        {
            return;
        }
        $paths["pickenchicken"] = dirname(__FILE__);

        update_option("root_paths",$paths);

    }

    public function deactivate(){
        $paths = get_option('root_paths');        
        unset( $paths[ array_search( dirname( __FILE__ ), $paths ) ] );
        update_option( 'root_paths' , $paths );
    }

    public function init(){
        \pickenchicken\Controllers\TeamsController::init();
        \pickenchicken\Controllers\DailyScheduleOfGamesController::init();
        self::rewrites();
        add_action("wp",array(self::class, "setPage"));
    }

    public function admin_init(){
        add_meta_box(
            'pickenchicken_dailygamesbox',                 // Unique ID
            'Today\'s Games',      // Box title
            array('\\pickenchicken\\Views\\AdminViews\\DailyScheduleAdminView','renderDailyScheduleForm'),  // Content callback, must be of type callable
            'daily-schedule'// Post type
        );
        \pickenchicken\Controllers\DailyScheduleOfGamesController::admin_init();
    }

    private function rewrites(){
        add_rewrite_rule("^pickenchicken/?$", "index.php?package=pickenchicken&pagename=home", "top");
        add_rewrite_rule("^actions/dailyPicks/?$", "index.php?package=pickenchicken&action=dailyPicks", "top");
        add_rewrite_rule("^actions/refreshGamesFeed/?$", "index.php?package=pickenchicken&action=refreshGamesFeed", "top");
    }

    public function setPage(){
        $package = get_query_var('package');
        $pagename = get_query_var('pagename');
        if('pickenchicken' !== $package)
            return;
        
        switch ($pagename){
            case 'home':
            default:
                $args = array('numberposts'=>1, 'post_type'=>'daily-schedule');
                $post = get_posts($args)[0];
                $post = get_post(191);
                $schedule = new \pickenchicken\Models\DailyScheduleOfGames( $post );
                $view = new \pickenchicken\Views\PageViews\DailyPicksView( $schedule );              
                app()->setCurrentView($view);
                break;
        }

        return;
    }
}


$pickenchicken = new PickenChicken();

