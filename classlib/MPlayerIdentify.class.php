<?php

class MPlayerIdentify
{
    var $video_codec = null;
    var $video_bitrate = null;

    var $audio_codec = null;
    var $audio_bitrate = null;
    var $audio_rate = null;
    var $audio_streams = 0;

    var $width = null;
    var $height = null;

    var $fps = null;
    var $aspect = null;

    private static $map = array(
        'ID_VIDEO_CODEC' => 'video_codec',
        'ID_VIDEO_BITRATE' => 'video_bitrate',

        'ID_AUDIO_CODEC' => 'audio_codec',
        'ID_AUDIO_BITRATE' => 'audio_bitrate',
        'ID_AUDIO_RATE' => 'audio_rate',

        'ID_VIDEO_WIDTH' => 'width',
        'ID_VIDEO_HEIGHT' => 'height',

        'ID_VIDEO_FPS' => 'fps',
        'ID_VIDEO_ASPECT' => 'aspect'
    );

    function MPlayerIdentify($filename)
    {
        $identify = shell_exec(
          MPLAYER.' -vo null -ao null -frames 1 -identify "'.$filename.'"|tac');

        preg_match_all('/(ID_[^=]+)=([\S]+)/m', $identify, $matches);
        foreach($matches[1] as $i=>$name){
            if(isset(MPlayerIdentify::$map[$name])){
                $var = MPlayerIdentify::$map[$name];
                if(!$this->$var)
                    $this->$var = $matches[2][$i];
            }
            if($name == 'ID_AUDIO_ID'){
                $this->audio_streams++;
            }
        }

        /* some post processing */
        if ($this->aspect == '1.3333') $this->aspect = '4:3';
        if ($this->aspect == '1.77') $this->aspect = '16:9';
    }
}


