<?php

require('init.inc.php');

$transcode_args = array();
foreach($_POST as $key=>$value){
    if(!empty($value) && $key != 'preset' && $key != 'bitrate'){
        $transcode_args[] = $key.':'.$value;
    }
}
$transcode_url = BASE_URL."/stream.php/".implode(';', $transcode_args);
$transcode_url .= '/'.basename($mediafile);

if($_POST['container']=='ogg' && $mediatype == 'video')
    $transcode_url .= '.ogv';
else
    $transcode_url .= '.'.$_POST['container'];

$smarty->assign('player', $_POST['player']);
$smarty->assign('transcode_url', $transcode_url);

if(!empty($_POST['size'])){
    $size = explode('x', $_POST['size']);
    $smarty->assign('width', $size[0]);
    $smarty->assign('height', $size[1]);
}
else{
    $smarty->assign('width', $mediainfo->width);
    $smarty->assign('height', $mediainfo->height);
}

$smarty->display('player.tpl.html');

