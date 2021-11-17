<?php

namespace pickenchicken\Views\PageViews;

class ComposeBulletinView extends \pickenchicken\Views\View{
    
    public function renderBody(){
        ?>
        <div class="container text-center">
            <div class="row">
                <div class="col">
                <h5>Compose Bulletin</h5>
                <form action="/pickenchicken/actions/submitBulletin/" method ="POST" enctype="multipart/form-data"> 
                    <input type="hidden" name="action" value="submitBulletin" />
                    <div class="py-5 col col-lg-4 mx-auto h-100">
                        <div class="mb-3">
                            <label class="form-label" for="file">Upload Audio/Video</label>
                            <input type="file" class="form-control" name="file" id="file" />
                        </div>
                        <div class="mb-3">
                        <label class="form-label" for="messageText" style="margin-left: 0px;">Message</label>
                            <textarea class="form-control" id="messageText" rows="4" name="messageText"></textarea>    
                        </div>

                        <div><label class="form-label" for="messageText" style="margin-left: 0px;">Button Color</label></div>
                        <fieldset class="mb-3 d-flex justify-content-between">
                            <div class="px-1">
                                <input type="radio" name="colorScheme" class="form-check-input" id="exampleRadio2" value="primary-light">
                                <label class="form-check-label" for="exampleRadio2" ><span class="badge bg-primary text-light">Blue</span></label>
                            </div>
                            <div class="px-1">
                                <input type="radio" name="colorScheme" class="form-check-input" id="exampleRadio2"  value="success-light">
                                <label class="form-check-label" for="exampleRadio2"><span class="badge bg-success text-light">Green</span></label>
                            </div>
                            <div class="px-1">
                                <input type="radio" name="colorScheme" class="form-check-input" id="exampleRadio2" value="danger-light">
                                <label class="form-check-label" for="exampleRadio2" ><span class="badge bg-danger text-light">Red</span></label>
                            </div>
                            <div class="px-1">
                                <input type="radio" name="colorScheme" class="form-check-input" id="exampleRadio1" value="warning-dark">
                                <label class="form-check-label" for="exampleRadio1"><span class="badge bg-warning text-dark">Yellow</span></label>
                            </div>
                    </fieldset>

                    <div class="mb-3">
                        <label class="form-label" for="buttonText">Button Text (keep it short, obviously)</label>
                        <input type="input" class="form-control" name="buttonText" id="buttonText" />
                    </div>
                


                        <button type="submit" class="btn btn-primary">Submit</button>

                    </div>
                </form>
                </div>
            </div>
        </div>
        <?php
    }

}