<?php

namespace pickenchicken\Controllers;

class TeamsController{
    public function __construct(){
    }

    public function __destruct(){}

    public function init(){
        self::post_type();
    }

    private function post_type(){
        register_post_type(
            'team',
            array(
                'labels'                => array(
                    'name'                     => _x( 'Teams', 'post type general name' ),
                    'singular_name'            => _x( 'Team', 'post type singular name' ),
                    'add_new'                  => _x( 'Add New', 'Team' ),
                    'add_new_item'             => __( 'Add new Team' ),
                    'new_item'                 => __( 'New Team' ),
                    'edit_item'                => __( 'Edit Team' ),
                    'view_item'                => __( 'View Team' ),
                    'all_items'                => __( 'All Teams' ),
                    'search_items'             => __( 'Search Teams' ),
                    'not_found'                => __( 'No Teams found.' ),
                    'not_found_in_trash'       => __( 'No Teams found in Trash.' ),
                    'filter_items_list'        => __( 'Filter Teams list' ),
                    'items_list_navigation'    => __( 'Teams list navigation' ),
                    'items_list'               => __( 'Teams list' ),
                    'item_published'           => __( 'Team published.' ),
                    'item_published_privately' => __( 'Team published privately.' ),
                    'item_reverted_to_draft'   => __( 'Team reverted to draft.' ),
                    'item_scheduled'           => __( 'Team scheduled.' ),
                    'item_updated'             => __( 'Team updated.' ),
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