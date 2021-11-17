<?php

namespace pickenchicken\Actions;
use \pickenchicken\Models\Bulletin;

class SubmitBulletinAction extends \bandpress\Actions\UploadAction{
    public function __construct(){}
    public function __destruct(){}
    public function do(){

        // check file
        $attachment_id = $this->handleFileUpload();

        if(!is_numeric($attachment_id)){
            diebug("do an error here");
        }
        // create a new post for the bulletin
            // if there's no post, create one.
            
        $post_date = date("Y-m-d h:i:s",time());
   
        $status="draft";
        $args = [
            'post_title'=>$post_date,
            'post_type'=>'bulletin',
            'post_date'=>$post_date,
            'post_name'=>sanitize_title($post_date),
            'post_status'=>$status
        ];

        $post_id = wp_insert_post($args);
        $post = get_post($post_id);
        $bulletin = new Bulletin($post);
        $bulletin->setMedia($attachment_id);
        $bulletin->setMessage($_REQUEST['messageText']);
        $bulletin->setColorScheme($_REQUEST['colorScheme']);
        
        $_SESSION['notifications']['successes']="Bulletin Created.";
        wp_redirect("/pickenchicken/bulletins/{$post_id}");

    }
}