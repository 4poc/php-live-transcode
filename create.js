/*
    PHP Live Transcode -- HTML5 and Flash streaming with live transforming.
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
$(document).ready(function(){

    $('#player').change(function(event){
        var player = $(this).val();
        if(player == 'html5'){
            $('#preset').val('libtheora/libvorbis/ogg');
        }
        else if(player == 'flash'){
            $('#preset').val('flv/libmp3lame/flv');
        }
        $('#preset').change();
    });

    $('#expert_toggle a').click(function(event){
        $('#expert').toggle(200);
        $('#expert_toggle a').toggle();
        event.preventDefault();
    });

    $('#preset').change(function(event){
        var preset = $(this).val();
        if (preset == '0') {
            return;
        }
        var preset_settings = preset.split('/');

        $('#vcodec').val(preset_settings[0]);
        $('#acodec').val(preset_settings[1]);
        $('#container').val(preset_settings[2]);
        $('#vcodec').change();
    });

    $('#vb, #ab').keyup(function(event){
        var bitrate = parseInt($(this).val(), 10);
        if (isNaN(bitrate)) {
            bitrate = 0;
        }
        var byterate = bitrate * 1024 / 8 / 1024;
        $(this).next('div').html('('+byterate+' kbyte/s)');
    });
    $('#vb, #ab').keyup();

    $('#bitrate').change(function(event){
        var bitrate = $(this).val();
        if (bitrate == '0') {
            return;
        }
        var bitrate_settings = bitrate.split('/');

        $('#vb').val(bitrate_settings[0]);
        $('#ab').val(bitrate_settings[1]);
        $('#vb, #ab').keyup();
    });

    $('#vcodec').change(function(event){
        var vcodec = $(this).val();
        if(vcodec == 'libx264'){
            $('#vpre_options').show();
        }
        else {
            $('#vpre_options').hide();
        }
    });
    $('#vcodec').change();



});

