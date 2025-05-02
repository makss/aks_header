<?php
function aks_header($atts) {
	global	$thisarticle, $aks_headers
		, $aks_header_strip
		, $aks_header_nostrip
		, $aks_header_gzip
		, $aks_filter
		, $aks_header_etag;
	$ext=array(				// extension => header
		'txt'	=> 'text/plain',
		'css'	=> 'text/css',
		'js'	=> 'application/x-javascript',
		'xml'	=> 'application/rss+xml'
	);
	extract(lAtts(array(
		'file'		=> '0',
		'nodebug'	=> '0',
		'nocache'	=> '0',
		'name'		=> 'Content-Type',
		'value'		=> '',
		'strip'		=> '0',
		'gzip'		=> '0',
		'filter'	=> '',
		'etag'		=> '',
		'nostrip'	=> 'pre,code,textarea,script,style'
	),$atts));
	$aks_header_nostrip = trim($nostrip);
	$aks_header_etag = ($etag) ? 1 : 0;

	if($filter=='none'){
		$aks_filter = array();
	}elseif($filter){
		$aks_filter = array_merge($aks_filter, preg_split('/\,/', $filter) );
	}

	if($strip){ $aks_header_strip = 1; }
	if($gzip && !in_array('zlib output compression', ob_list_handlers()) ){ $aks_header_gzip = 1; }

	if($value){ $aks_headers[$name] = $value; }
	if($nodebug || $file){ $GLOBALS['production_status'] = 'live'; }

	if($file && preg_match('/^.*\.(.*)$/', $thisarticle['url_title'], $mm) ){
		if($ext[$mm[1]]){ $aks_headers['Content-Type'] = $ext[$mm[1]];}
		if($mm[1] !='xml'){ $aks_headers['Last-Modified'] = safe_strftime('rfc822',$thisarticle['modified']); }
	}
	if($nocache){
		$aks_headers["Cache-Control"] = "no-store, no-cache, must-revalidate, pre-check=0, post-check=0, max-age=0";
		$aks_headers["Expires"] = "Sat, 9 Jun 1990 07:00:00 GMT";
		$aks_headers['Last-Modified'] = safe_strftime('rfc822');
		$aks_headers["Pragma"] = "no-cache";
	}

}

function aks_header_callback_preg($mm) {
	static $c=0; $c+=1;
	global $aks_header_block;
	$key = "@@DONTOUCHMETAG{$c}@@";
	$aks_header_block[$key] = $mm[0];
	return $key;
}

function aks_header_callback($html) {
	global	$aks_headers
		, $aks_header_strip
		, $aks_header_nostrip
		, $aks_header_gzip
		, $aks_header_block
		, $aks_filter
		, $aks_header_etag
		, $production_status;

	if( isset($aks_headers) && is_array($aks_headers) ){
		foreach ($aks_headers as $name => $value){ header("$name: $value"); }
	}

	if( is_array($aks_filter) ){
		ksort($aks_filter);
		foreach( array_unique($aks_filter) as $filter){
			if( function_exists($filter) ){ $html = $filter($html); }
		}
	}

	if( $production_status == 'live' ){					// clear marks aks_	<!--aks_...--><!--/aks_...-->
		$html = preg_replace('/<!--\/?aks_.*?-->/i', '', $html);
	}

	if( $aks_header_strip ){
		$aks_header_block = array();
		$html = preg_replace ("/\015\012|\015|\012/", PHP_EOL, $html);
		if( $aks_header_nostrip ){
			$aa = preg_split('/[, ]+/', $aks_header_nostrip);
			foreach($aa as $a){
				$html = preg_replace_callback("!<(".$a.")[^>]*>.*?</(".$a.")>!isx", 'aks_header_callback_preg', $html);
			}
		}
//		$html = preg_replace('/<!--([^\[]).*?-->/s', '', $html);
		$html = preg_replace('/((?<!\?>)'.PHP_EOL.')[\s]+/m', '\1', $html);
		$html = str_replace(">".PHP_EOL."<", '><', $html);
		$html = str_replace(PHP_EOL, ' ', $html);
		$html = preg_replace('/[ \t]+/', ' ', $html);
		foreach( array_reverse($aks_header_block,true) as $key=>$block ){ $html = str_replace($key,  $block, $html); }
	}

	// Check for buggy versions of Internet Explorer
	if( $aks_header_gzip && !strstr($_SERVER['HTTP_USER_AGENT'], 'Opera') &&
	preg_match('/^Mozilla\/4\.0 \(compatible; MSIE ([0-9]\.[0-9])/i', $_SERVER['HTTP_USER_AGENT'], $matches) ){
		$version = floatval($matches[1]);
		if( $version < 6 || ($version == 6 && !strstr($_SERVER['HTTP_USER_AGENT'], 'SV1')) ){ $aks_header_gzip=0; }
	}

	if( $aks_header_etag && $production_status == 'live' ){
		$etag = ( $aks_header_etag == 1 ) ? '"'.md5($html).'"' : '"'.$aks_header_etag.'"';
		header("ETag: $etag");
		if( $match = serverSet('HTTP_IF_NONE_MATCH') ){		// check etag:  304 Not Modified
			if( in_array($etag, do_list($match)) ){		// TODO:   If-None-Match "*"
				txp_status_header('304 Not Modified');
				return;
			}
		}
	}

	if( $aks_header_gzip && isset($_SERVER['HTTP_ACCEPT_ENCODING']) ){
		$encoding = $_SERVER['HTTP_ACCEPT_ENCODING'];
		if( function_exists('gzencode') && preg_match('/gzip/i', $encoding) ){
			header("Vary: Accept-Encoding");
			header('Content-Encoding: gzip');
			$html = gzencode($html);
		}elseif( function_exists('gzdeflate') && preg_match('/deflate/i', $encoding) ){
			header("Vary: Accept-Encoding");
			header('Content-Encoding: deflate');
			$html = gzdeflate($html);
		}
	}
//	$size=strlen($html);	if($size){ header('Content-Length: ' . $size); }
	return $html;
}
