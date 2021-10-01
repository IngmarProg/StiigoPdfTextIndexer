<?php

function error_handler($code, $message, $file, $line)
{
    print $code." ".$message." ".$file." ".$line."\n";
    // throw new ErrorException($message, 0, $code, $file, $line);
}

function exception_handler($e)
{    
    print "Exception ".$e->getMessage()."\n";
}	
	
	// phpinfo();
	
	date_default_timezone_set('Europe/Tallinn');
	error_reporting(E_ALL);
	set_error_handler("error_handler");
	set_exception_handler("exception_handler");
	set_time_limit(20000);
	$indexerlibs = dirname(__FILE__).DIRECTORY_SEPARATOR."indexer".DIRECTORY_SEPARATOR;	
	
	
	require_once($indexerlibs."lib/vendor/autoload.php");
	require_once($indexerlibs."tools.php");
	require_once($indexerlibs."string_tools.php");
	require_once($indexerlibs."const.php");
	require_once($indexerlibs."dbworker.php");
	require_once($indexerlibs."textprocessor.php");
	require_once($indexerlibs."indexer.php");	
	require_once($indexerlibs."processpdf.php");	
	
	
	$starttime = microtime(true);
	// function processPdf($pdffile, $origfilename, $userid = 0, $debugcontent = "")	
	// processPdf("C:/Temp/pdf/demo4.pdf", "demo2.pdf");
	
	
	// $content = file_get_contents("/home/stiigo5/public_html/dev/digin/paragraph.txt");	
	$content = file_get_contents("C:/Temp/raw_text_large.txt");
	// $content = file_get_contents("C:/Temp/raw_text.txt");
	// $content = file_get_contents("C:/Temp/wordsplit.txt");
	
	// processPdf("C:/Temp/pdf/demo1.pdf", "demo2.pdf", 0, $content);
	processPdf("C:/Temp/pdf/demo2.pdf", "demo2.pdf", 0, $content);
	$endtime = microtime(true);
	print "OK:korras \n";
	print "Aegakulus ".number_format($endtime - $starttime, 5);			
	
?>