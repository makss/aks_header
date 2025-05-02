<?php
function aks_301($atts) {
    extract(lAtts(array(
        'start'=>'',
        'url'=>'http://'.$_SERVER["SERVER_NAME"].'/',
        'ignore'=>'',
        'black'=>'cltreq\.asp|owssvr\.dll'
    ),$atts));
 
    if( $ignore ){
        $ignor=explode('|',$ignore);
        for( $i = 0; $i < count($ignor); $i++ ){
            if( preg_match("|".$ignor[$i]."|i", $_SERVER["REQUEST_URI"]) ){ return; }
        }
    }
 
    if( $black ){
        $bl=explode('|',$black);
        for( $i = 0; $i < count($bl); $i++ ){
            if( preg_match("|".$bl[$i]."|i", $_SERVER["REQUEST_URI"]) ){ header("HTTP/1.0 404 Not Found"); exit; }
        }
    }

    if( $start ){
        $st=explode('|',$start); $ur=explode('|',$url);
        for( $i = 0; $i < count($st); $i++ ){
            if( preg_match("|^".$st[$i]."|i", $_SERVER["REQUEST_URI"]) ){
                if( !$ur[$i] ){ $ur[$i] = $ur[0]; }
                if( preg_match('/\(/', $st[$i]) ){         // check if regexp
                    $ur[$i] = preg_replace("|^".$st[$i]."|i", $ur[$i], $_SERVER["REQUEST_URI"]);
                }
                header('HTTP/1.1 301 Moved Permanently');
                header("Location: ".$ur[$i]); exit;
            }
        }
    }
}
