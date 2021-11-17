<?php
namespace pickenchicken\Controllers;

class BulletinsController{

    public function __construct(){}

    public function __destruct(){}

    public function init(){
        self::post_type();
    }
    public function admin_init(){
        add_action('save_post', array(self::class,'save'));
    }

    private function post_type(){
        register_post_type(
            'bulletin',
            array(
                'labels'                => array(
                    'name'                     => _x( 'Bulletins', 'post type general name' ),
                    'singular_name'            => _x( 'Bulletin', 'post type singular name' ),
                    'add_new'                  => _x( 'Add New', 'Bulletin' ),
                    'add_new_item'             => __( 'Add new Bulletin' ),
                    'new_item'                 => __( 'New Bulletin' ),
                    'edit_item'                => __( 'Edit Bulletin' ),
                    'view_item'                => __( 'View Bulletin' ),
                    'all_items'                => __( 'All Bulletins' ),
                    'search_items'             => __( 'Search Bulletins' ),
                    'not_found'                => __( 'No Bulletins found.' ),
                    'not_found_in_trash'       => __( 'No Bulletins found in Trash.' ),
                    'filter_items_list'        => __( 'Filter Bulletins list' ),
                    'items_list_navigation'    => __( 'Bulletins list navigation' ),
                    'items_list'               => __( 'Bulletins list' ),
                    'item_published'           => __( 'Bulletin published.' ),
                    'item_published_privately' => __( 'Bulletin published privately.' ),
                    'item_reverted_to_draft'   => __( 'Bulletin reverted to draft.' ),
                    'item_Bulletind'           => __( 'Bulletin Bulletind.' ),
                    'item_updated'             => __( 'Bulletin updated.' ),
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
        
    }

}