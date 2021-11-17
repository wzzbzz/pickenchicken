<?php

namespace pickenchicken\Views\PageViews;

use \pickenchicken\Views\ComponentViews\BulletinView;

class EditBulletinView extends \pickenchicken\Views\View{
    
    public function renderBody(){
        $bulletinView = new BulletinView( $this->data );
        ?>
        <div class="container text-center">
            <div class="row">
                <div class="col">
                <h5>Here's Your Bulletin</h5>
                    <div class="col col-lg-4 mx-auto h-100">
                        
                        <?php $bulletinView->render();?>
                        
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

}