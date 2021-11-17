<?php

namespace pickenchicken\Models;

use bandpress\Models\Post;
use pickenchicken\Models\User;
use bandpress\Models\File;

class Bulletin extends Post
{

    public function setMedia($id)
    {
        $this->update_field("media", $id);
    }
    public function getMedia()
    {
        return new File(get_post($this->get_field("media")));
    }
    public function setMessage($text)
    {
        $this->update_meta("message", $text);
    }
    public function getMessage()
    {
        return $this->get_meta("message", true);
    }
    public function setColorScheme($scheme)
    {
        $this->update_meta("colorScheme", $scheme);
    }
    public function getColorScheme()
    {
        $colorScheme = explode("-", $this->get_meta("colorScheme", true));
        $return["bg"] = $colorScheme[0];
        $return['text'] = $colorScheme[1];
        return $return;
    }

    public function setButtonText($text)
    {
        $this->update_meta("buttonText", $text);
    }

    public function getButtonText()
    {
        return $this->get_meta("buttonText",true);
    }

    public function getMediaView(){
        $viewClass = "\\bandpress\\Views\\ComponentViews\\".ucfirst($this->getMedia()->mediaType())."View";
        $mediaView = new $viewClass($this->getMedia());
        return $mediaView;
    }
}
