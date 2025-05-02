<?php

if (@txpinterface == 'public') {
    if (!in_array('aks_header_callback', ob_list_handlers())) {
        ob_start('aks_header_callback');
    }
    if (class_exists('\Textpattern\Tag\Registry')) {
        Txp::get('\Textpattern\Tag\Registry')
            ->register('aks_301')
            ->register('aks_header')
            ->register('aks_header_etag')
        ;
    }
}


/*  // for external plugins
    if( function_exists('aks_filter_add') ){
        aks_filter_add('aks_text_filter', 30);
    }else{
        trigger_error("Unable to include required plugin 'aks_header'", E_USER_ERROR);  // E_USER_WARNING
    }
*/
function aks_filter_add($name, $priority = 50)
{
    global $aks_filter;
    $aks_filter["{$priority}_{$name}"] = $name;
}
