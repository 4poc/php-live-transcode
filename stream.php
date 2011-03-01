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

/* script execution settings */
set_time_limit(0);
ignore_user_abort(true); /* do not terminate script execution if disconnect */
header("Connection: close");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Cache-Control: no-cache"); 
header("Pragma: no-cache");

require 'init.inc.php';

/* parse path arguments */
$args = array();
$path_info = explode('/', $_SERVER["PATH_INFO"]);
$arguments = explode(';', $path_info[1]);
foreach($arguments as $argument){
    $key = substr($argument, 0, strpos($argument, ':'));
    $value = substr($argument, strpos($argument, ':')+1);
    $args[$key] = $value;
}

/* validate arguments */
function validation_error(){
    global $smarty;
    $smarty->assign('error', 'Some arguments do not validate.');
    $smarty->display('error.tpl.html');
    exit;
}
if(!empty($args['vb']) && !is_numeric($args['vb'])) validation_error();
if(!empty($args['ab']) && !is_numeric($args['ab'])) validation_error();
if(!empty($args['fps']) && !is_numeric($args['fps'])) validation_error();
if(!empty($args['audio_stream']) && !is_numeric($args['audio_stream'])) validation_error();
if(!empty($args['size']) && !preg_match('/^\d+x\d+$/', $args['size'])) validation_error();
if(!empty($args['vcodec']) && !preg_match('/^[a-zA-Z0-9]+$/', $args['vcodec'])) validation_error();
if(!empty($args['acodec']) && !preg_match('/^[a-zA-Z0-9]+$/', $args['acodec'])) validation_error();
if(!empty($args['container']) && !preg_match('/^[a-zA-Z0-9]+$/', $args['container'])) validation_error();
if(!empty($args['vpre']) && !preg_match('/^[a-zA-Z0-9]+$/', $args['vpre'])) validation_error();
if(!empty($args['vpre2']) && !preg_match('/^[a-zA-Z0-9]+$/', $args['vpre2'])) validation_error();
if(!empty($args['seek']) && !preg_match('/^\d+:\d+:\d+$/', $args['seek'])) validation_error();

/* send mime type */
$mime = '';
if ($args['vcodec'] == 'flv') $mime = 'video/x-flv';
else if ($args['vcodec'] == 'libx264') $mime = 'video/mp4';
else if ($args['vcodec'] == 'libtheora') $mime = 'video/ogg';
else if ($args['vcodec'] == 'libvpx') $mime = 'video/webm';
else if ($args['acodec'] == 'libmp3lame') $mime = 'application/mp3';
else if ($args['acodec'] == 'libvorbis') $mime = 'application/ogg';
else if ($args['acodec'] == 'libfaac') $mime = 'audio/x-aac';
if(!empty($mime)) header('Content-Type: '.$mime);

/* build ffmpeg command */
$cmd = FFMPEG;
if(!empty($args['seek']))
    $cmd .= " -ss ".$args['seek'];
$cmd .= " -y -i \"$mediafile\" ";
if(!empty($args['vcodec']))
    $cmd .= " -vcodec ".$args['vcodec'];
if(!empty($args['acodec']))
    $cmd .= " -acodec ".$args['acodec'];
if(!empty($args['vb']))
    $cmd .= " -vb ".$args['vb']."k";
if(!empty($args['ab']))
    $cmd .= " -ab ".$args['ab']."k -ac 2";
if(!empty($args['fps']))
    $cmd .= " -r ".$args['fps'];
if(!empty($args['aspect']))
    $cmd .= " -aspect ".$args['aspect'];
if(!empty($args['size']))
    $cmd .= " -s ".$args['size'];
if(!empty($args['container']))
    $cmd .= " -f ".$args['container'];

if(!empty($args['audio_stream']))
    $cmd .= " -map 0.0:0.0 -map 0.".$args['audio_stream'].":0.1";

/* special cases */
if($args['vcodec'] == 'libx264') $cmd .= " -vpre ".$args['vpre']."_firstpass -vpre ".$args['vpre2'];
if($args['vcodec'] == 'flv') $cmd .= ' -ar 22050';

$cmd .= " pipe:1"; // ffmpeg should output to stdout (other messages to stderr)

/* execute ffmpeg */
$descriptorspec = array(
   P_STDIN => array("pipe", "r"),  // stdin (we write the process reads)
   P_STDOUT => array("pipe", "w"),  // stdout (we read the process writes)
   P_STDERR => array("pipe", "w")   // stderr (we read the process writes)
);
$process = proc_open("nice -n ".FFMPEG_PRIORITY." ".$cmd, $descriptorspec, $pipes);

dbg("Started FFmpeg process.\nCommand Line: $cmd");

$stdout_size = 0;
if (is_resource($process)) {
    while(!feof($pipes[P_STDOUT])){
        $chunk = fread($pipes[P_STDOUT], CHUNKSIZE);
        $stdout_size += strlen($chunk);

        if ($chunk !== false && !empty($chunk)){
            echo $chunk;

            /* flush output */
            if (ob_get_length()){            
                @ob_flush();
                @flush();
                @ob_end_flush();
            }
            @ob_start();
            //dbg("Chunk sent to browser and flush output buffers");
        }

        if(connection_aborted()){
            dbg("Connection aborted.");
            break;
        }
    }
    dbg("Finished reading from stdout.");
    fclose($pipes[P_STDOUT]);

    if($stdout_size == 0){ /* not read anything from stdout indicates error */
        $stderr = stream_get_contents($pipes[P_STDERR]);
        dbg("An Error Occured. Stderr: ".$stderr);
    }
    fclose($pipes[P_STDERR]);

    /* this should quit the encoding process */
    fwrite($pipes[P_STDIN], "q\r\n");
    fclose($pipes[P_STDIN]);

    $return_value = proc_close($process);

    dbg("Process closed with return value: ".$return_value);
}


