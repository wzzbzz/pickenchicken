<?php

namespace pickenchicken\Views\PageViews;

class EditBulletinView extends \pickenchicken\Views\View{
    
    public function renderBody(){
        $viewClass = "\\bandpress\\Views\\ComponentViews\\".ucfirst($this->data->getMedia()->mediaType())."View";
        $mediaView = new $viewClass($this->data->getMedia());
        ?>
        <div class="container text-center">
            <div class="row">
                <div class="col">
                <h5>Here's Your Bulletin</h5>
                    <div class="col col-lg-4 mx-auto h-100">
                        <div class="">
                            <?php $mediaView->render()?>
                        </div>
                        <div class="mb-3">
                            <?= $this->data->getMessage()?>
                        </div>
                        <div class="mb-3">
                            <?php
                            $scheme = $this->data->getColorScheme();
                            ?>
                            <button class="btn  btn-<?= $scheme['bg']?>" type="button" data-bs-toggle="collapse" data-bs-target="#special" aria-expanded="false" aria-controls="special">
                                SPECIAL MESSAGE
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

}