<?php

namespace pickenchicken\Controllers;

class DailyScheduleOfGamesController{
    public function __construct(){
    }

    public function __destruct(){}

    public function init(){
        self::post_type();
    }

    private function post_type(){
        register_post_type(
            'daily-schedule',
            array(
                'labels'                => array(
                    'name'                     => _x( 'Daly Games Schedules', 'post type general name' ),
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
                //'show_in_rest'          => true,
                //'rest_base'             => 'blocks',
                //'rest_controller_class' => 'WP_REST_Blocks_Controller',
                //'capability_type'       => 'block',
                /*'capabilities'          => array(
                    // You need to be able to edit posts, in order to read blocks in their raw form.
                    'read'                   => 'edit_posts',
                    // You need to be able to publish posts, in order to create blocks.
                    'create_posts'           => 'publish_posts',
                    'edit_posts'             => 'edit_posts',
                    'edit_published_posts'   => 'edit_published_posts',
                    'delete_published_posts' => 'delete_published_posts',
                    'edit_others_posts'      => 'edit_others_posts',
                    'delete_others_posts'    => 'delete_others_posts',
                ),*/
                //'map_meta_cap'          => true,
                'supports'              => array(
                    'title',
                    'thumbnail'
                ),
            )
        );

    }
}