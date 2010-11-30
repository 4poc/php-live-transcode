<?php
/*
    PHP Live Transcode -- HTML5 and Flash streaming with live transcoding.
    Copyright (C) 2010  Matthias -apoc- Hecker <http://apoc.cc>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/* Please read
 http://sixserv.org/2010/11/30/live-transcoding-for-video-and-audio-streaming/ 
   for more information. */

require 'config.inc.php';

/* just for internal use */
define('P_STDIN', 0);
define('P_STDOUT', 1);
define('P_STDERR', 2);

/* initialize smarty template library */
require SMARTY_LIBRARY;
$smarty = new Smarty();
$smarty->setTemplateDir(SMARTY_TEMPLATE);
$smarty->setCompileDir(SMARTY_COMPILE);

/* define some modifiers for bitrate conversion */
function kbyte($bits){
    return round($bits/8/1024,2);
}
function kbit($bits){
    return round($bits/1000,2);
}
$smarty->register_modifier("kbyte", "kbyte");
$smarty->register_modifier("kbit", "kbit");

/* just a very simple debug logger */
function dbg($message)
{
    file_put_contents(DEBUG_LOG, time().": $message\n", FILE_APPEND);
}

/* test for ffmpeg and mplayer */
if(!preg_match('/MPlayer (\S+)/',shell_exec(MPLAYER),$mplayer_version)){
    $smarty->assign('error', 'MPlayer not found. ('.MPLAYER.')');
    $smarty->display('error.tpl.html');
    exit;
}
$smarty->assign('mplayer_version', $mplayer_version[1]);

if(!preg_match('/FFmpeg (\S+)/',shell_exec(FFMPEG.' -version'),$ffmpeg_version)){
    $smarty->assign('error', 'FFmpeg not found. ('.FFMPEG.')');
    $smarty->display('error.tpl.html');
    exit;
}
$smarty->assign('ffmpeg_version', $ffmpeg_version[1]);

/* check running ffmpeg instances */
$instances = `ps -A | grep ffmpeg | wc -l`;
$instances = (int)(trim($instances));
if ($instances > FFMPEG_MAX_INSTANCES){
    $smarty->assign('error', 'There too many running ffmpeg instances. ('.$instances.'/'.FFMPEG_MAX_INSTANCES.')');
    $smarty->display('error.tpl.html');
    exit;
}

/* base path/url for js resources */
$smarty->assign('base_url', BASE_URL);

/* gather mediafile from path */
if(empty($_SERVER["PATH_INFO"])){
    $smarty->assign('error', 'You must provide a mediafile.');
    $smarty->display('error.tpl.html');
    exit;
}
$mediafile = MEDIA_PATH . '/' . basename($_SERVER["PATH_INFO"]);
if(strstr($mediafile, '..') !== false){ /* directory traversal */
    $smarty->assign('error', 'Illegal characters.');
    $smarty->display('error.tpl.html');
    exit;
}
$mediafilename = basename($mediafile);
preg_match('/\.([^\.]+)$/', $mediafilename, $match);
$mediaext = $match[1];
if(!file_exists($mediafile)){
    $orig_mediafile = $mediafile;

    /* look for existing mediafile without last extension */
    $mediafile = preg_replace('/\.[^\.]+$/','',$mediafile);
    if(!file_exists($mediafile)){
        $smarty->assign('error', 'Mediafile does not exist. ('.htmlentities($orig_mediafile).')');
        $smarty->display('error.tpl.html');
        exit;
    } /* continue in flow */
}

/* identify the mediafile with mplayer */
require 'classlib/MPlayerIdentify.class.php';
$mediainfo = new MPlayerIdentify($mediafile);
if(empty($mediainfo->audio_codec)){
    $smarty->assign('error', 'MPlayer has not identified the file you provided. ('.htmlentities($mediafile).')');
    $smarty->display('error.tpl.html');
    exit;
}

/* assign mediafile information */
$smarty->assign('filename', basename($mediafile));
$smarty->assign('path', dirname($mediafile));
$smarty->assign('size', round(filesize($mediafile) / 1024 / 1024, 2));
$smarty->assign('mediainfo', $mediainfo);

if(empty($mediainfo->video_codec))
    $mediatype = 'audio';
else
    $mediatype = 'video';
$smarty->assign('mediatype', $mediatype);


