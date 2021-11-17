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

        add_role("chicken", "The Chicken");
    }

    public function deactivate(){
        $paths = get_option('root_paths');        
        unset( $paths[ array_search( dirname( __FILE__ ), $paths ) ] );
        update_option( 'root_paths' , $paths );
    }

    public function init(){

        self::capabilities();

        // Post types 
        \pickenchicken\Controllers\DailyScheduleOfGamesController::init();
        \pickenchicken\Controllers\BulletinsController::init();

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
        add_rewrite_rule("^pickenchicken/composeBulletin/?$", "index.php?package=pickenchicken&pagename=composebulletin", "top");
        add_rewrite_rule("^pickenchicken/bulletins/([^\/]+)?$", "index.php?package=pickenchicken&pagename=editBulletin&post_id=\$matches[1]", "top");
        add_rewrite_rule("^pickenchicken/actions/dailyPicks/?$", "index.php?package=pickenchicken&action=dailyPicks", "top");
        add_rewrite_rule("^pickenchicken/actions/submitBulletin/?$", "index.php?package=pickenchicken&action=submitBulletin", "top");
        add_rewrite_rule("^pickenchicken/actions/refreshGamesFeed/?$", "index.php?package=pickenchicken&action=refreshGamesFeed", "top");
    }

    private function capabilities(){
        
        $role = get_role("administrator");
        $role->add_cap("send_bulletins");
        $role->add_cap("access_admin");

        add_role("chicken", "The Chicken");
        $role = get_role("chicken");
        $role->add_cap("send_bulletins");

    }
    public function setPage(){

        $package = get_query_var('package');
        $pagename = get_query_var('pagename');
        
        if('pickenchicken' !== $package)
            return;

        switch ($pagename){
            case 'composebulletin':
                if(current_user_can('send_bulletins'))
                    $view = new \pickenchicken\Views\PageViews\ComposeBulletinView();
                else
                    $view = new \bandpress\Views\PageViews\ErrorPageView(404);
                app()->setCurrentView($view);
                break;
            case "editbulletin":
                if(current_user_can('send_bulletins')){
                    $post = get_post(get_query_var("post_id"));
                    $bulletin = new \pickenchicken\Models\Bulletin( $post );
                    $view = new \pickenchicken\Views\PageViews\EditBulletinView( $bulletin );
                }
                else
                    $view = new \bandpress\Views\PageViews\ErrorPageView(404);
                    app()->setCurrentView($view);
                
                break;
            case 'home':
            default:
                $args = array('numberposts'=>1, 'post_type'=>'daily-schedule');
                $post = get_posts($args)[0];
                $schedule = new \pickenchicken\Models\DailyScheduleOfGames( $post );
                $view = new \pickenchicken\Views\PageViews\DailyPicksView( $schedule );
                app()->setCurrentView($view);
                break;
        }

        return;
    }
}


$pickenchicken = new PickenChicken();

