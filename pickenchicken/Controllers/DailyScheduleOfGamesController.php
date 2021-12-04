<?php

namespace pickenchicken\Controllers;

class DailyScheduleOfGamesController{
    public function __construct(){
    }

    public function __destruct(){}

    public function init(){
        self::post_type();
    }

    public function admin_init(){
        // save the post 
        add_action('save_post', array(self::class,'save'));

        // add the meta box
        add_meta_box(
            'pickenchicken_dailygamesbox',                 // Unique ID
            'Today\'s Games',      // Box title
            array('\\pickenchicken\\Views\\AdminViews\\DailyScheduleAdminView','renderDailyScheduleForm'),  // Content callback, must be of type callable
            'daily-schedule'// Post type
        );


    }


    private function post_type(){
        register_post_type(
            'daily-schedule',
            array(
                'labels'                => array(
                    'name'                     => _x( 'Daily Games Schedules', 'post type general name' ),
                    'singular_name'            => _x( 'Schedule', 'post type singular name' ),
                    'add_new'                  => _x( 'Add New', 'Schedule' ),
                    'add_new_item'             => __( 'Add new Schedule' ),
                    'new_item'                 => __( 'New Schedule' ),
                    'edit_item'                => __( 'Edit Schedule' ),
                    'view_item'                => __( 'View Schedule' ),
                    'all_items'                => __( 'All Schedules' ),
                    'search_items'             => __( 'Search Schedules' ),
                    'not_found'                => __( 'No Schedules found.' ),
                    'not_found_in_trash'       => __( 'No Schedules found in Trash.' ),
                    'filter_items_list'        => __( 'Filter Schedules list' ),
                    'items_list_navigation'    => __( 'Schedules list navigation' ),
                    'items_list'               => __( 'Schedules list' ),
                    'item_published'           => __( 'Schedule published.' ),
                    'item_published_privately' => __( 'Schedule published privately.' ),
                    'item_reverted_to_draft'   => __( 'Schedule reverted to draft.' ),
                    'item_scheduled'           => __( 'Schedule scheduled.' ),
                    'item_updated'             => __( 'Schedule updated.' ),
                ),
                'public'                => false,
                'show_ui'               => true,
                'show_in_menu'          => true,
                'rewrite'               => false,
                'supports'              => array(
                    'title',
                    'thumbnail'
                ),
            )
        );

    }

    public function save($post_id){
        if ( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id) )
            return $post_id;
        
		if( !isset( $_REQUEST[ 'post_title' ] ) ){
			return $post_id;
		}

        $pointSpreads = $_REQUEST['point_spread'];
        $chickenPicks = $_REQUEST['chicken_pick'];

        $schedulePost = new \pickenchicken\Models\DailyScheduleOfGames(get_post($post_id));
        $games = $schedulePost->getGames();
        foreach($games as $i=>$game){
            $game->pointSpread = $pointSpreads[$i];
            $game->chickenPick = $chickenPicks[$i];
            $games[$i] = $game;
        }

        $schedulePost->updateGames( $games );
    }
}