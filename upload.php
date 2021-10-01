<?php
	DEFINE("MAXFILEMAXSIZE",  8388608); // 8 MB	
	
	ini_set('memory_limit','256M');
	ini_set("upload_max_filesize", "8M");
	set_time_limit(20000);	
	date_default_timezone_set("Europe/Tallinn");
				
	error_reporting(0);
	/* ------------------------------------------------------ */
	// @@DEBUG				
	function error_handler($code, $message, $file, $line) {
		$info = $code." ".$message." ".$file." ".$line.PHP_EOL;
		file_put_contents("/home/stiigo5/public_html/pdfdocs/upload/debug_15541120.txt", $info, FILE_APPEND);
	}
		
	set_error_handler("error_handler");
	
	set_exception_handler(function($e) {
		file_put_contents("/home/stiigo5/public_html/pdfdocs/upload/debug_15541120.txt", $e->getMessage(), FILE_APPEND);
		error_log($e->getMessage());
		exit('Unknown error !');
	});
	
	/* ------------------------------------------------------ */
	
	
	$indexerlibs = dirname(__FILE__).DIRECTORY_SEPARATOR."indexer".DIRECTORY_SEPARATOR;	
	require_once($indexerlibs."lib/vendor/autoload.php");
	require_once($indexerlibs."tools.php");
	require_once($indexerlibs."string_tools.php");
	require_once($indexerlibs."const.php");
	require_once($indexerlibs."dbworker.php");
	require_once($indexerlibs."textprocessor.php");
	require_once($indexerlibs."indexer.php");	
	require_once($indexerlibs."processpdf.php");	
	$userid = 0;
	
	
	try {		
        $files = $_FILES;
        $files = reStructFiles($files);

		if (isset( $files[0]["indexfiles"] )) {
			foreach( $files[0] as $file)      
				for ($k = 0; $k < count($file["name"]); $k++) {
					if (!is_array($file["name"])) {
						header("HTTP/1.1 500 Internal Server Error");
						exit("ERR: File structure invalid !");
					}
										
					if (!isset($file["name"][$k]) || ($file["name"][$k] == "")) {
						continue;
					}

					if (!is_uploaded_file($file["tmp_name"][$k]) || $file["error"][$k] != 0) {
						header("HTTP/1.1 500 Internal Server Error");
						exit("ERR: Upload failed ! ");
					}

					$filesize = (int)$file["size"][$k];
					if ($filesize > MAXFILEMAXSIZE) {
						header("HTTP/1.1 500 Internal Server Error");
						exit("ERR: File is too big !");
					}
					
					$fname = $file["name"][$k];
					$newname = md5(time() * rand()).date("dmY");
					$fileext = mb_strtolower(pathinfo($fname, PATHINFO_EXTENSION));
					// hetkel vaid pdf !
					if (!in_array($fileext, array("pdf"))) {
						header("HTTP/1.1 500 Internal Server Error");
						exit("ERR: File with extension ".$fileext." is not allowed");
					}
					
					$processfile = dirname(__FILE__).DIRECTORY_SEPARATOR."upload".DIRECTORY_SEPARATOR.$newname.".".$fileext;
					if (!move_uploaded_file($file["tmp_name"][$k], $processfile)) {
						header("HTTP/1.1 500 Internal Server Error");
						exit("ERR: Upload failed");
					}
					
					// TODO async processing ?
					$origfilename = trim($fname);
					$rez = processPdf($processfile, $origfilename, $userid);
					if ($rez == 1) {
						echo "OK:1";
					} else {
						echo "ERR:".$rez;
					}
					unlink($processfile);
				}
		}
	} catch (Exception $e) {
		echo "ERR:-1000";
	}
?>