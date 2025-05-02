<?php

function aks_header_etag($atts, $thing = '')
{
    global $aks_header_etag;
    extract(lAtts(array(
        'hash'  => ''
    ), $atts));
    if ($thing) {
        $aks_header_etag = md5($hash . $aks_header_etag . $thing);
        return parse($thing);
    }
}
