<?php

function jsonEscape($value) {
    $escapers = array("\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c");
    $replacements = array("\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b");
    $result = str_replace($escapers, $replacements, $value);
    return $result;
}

function currentHttpPath() {
	$path = $_SERVER["PHP_SELF"];
	return (stripos($_SERVER["SERVER_PROTOCOL"], "https") === true ? "https://" : "http://"). $_SERVER["HTTP_HOST"].str_ireplace(basename($path), "", $path);
}	

function reStructFiles(&$file_post) {
    $file_ary = array();   
	$multiple = ((isset($file_post['name'])) && (is_array($file_post['name'])));
	
    $file_count = $multiple ? count($file_post['name']) : 1;
    $file_keys = array_keys($file_post);

    for ($i=0; $i<$file_count; $i++)
    {
        foreach ($file_keys as $key)
        {
            $file_ary[$i][$key] = $multiple ? $file_post[$key][$i] : $file_post[$key];
        }
    }

    return $file_ary;
}

function _valExt($valname, $defaultval = "") {
	if (isset($_GET[$valname])) {
		return $_GET[$valname];
	} else if (isset($_POST[$valname])) {
		return $_POST[$valname];
	}
	return $defaultval;
}

?>