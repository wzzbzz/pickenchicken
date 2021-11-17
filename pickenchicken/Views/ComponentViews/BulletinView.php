<?php

namespace pickenchicken\Views\ComponentViews;

class BulletinView{
    private $data;
    public function __construct( $data ){
        $this->data = $data;
    }

    public function render(){
        ?>
        <div class="container text-center">
            <p>
            <button class="btn  btn-<?= $this->data->getColorScheme()['bg'];?>" type="button" data-bs-toggle="collapse" data-bs-target="#bulletin" aria-expanded="false" aria-controls="bulletin">
                <?= $this->data->getButtonText();?>
            </button>
            </p>
            <div class="collapse pb-3" id="bulletin">
                <?php if($this->data->hasMedia()) $this->data->getMediaView()->render();?>
                <div class="text-center">
                <?= $this->data->getMessage();?>
                 </div>
            </div>
        </div>
        
        <?php
    }
}