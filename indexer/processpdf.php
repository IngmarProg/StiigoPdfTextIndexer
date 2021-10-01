<?php

DEFINE("max_rv_index_queue", 1024);
DEFINE("max_word_queue", 255);

function processPdf($pdffile, $origfilename, $userid = 0, $debugcontent = "") {
	if ($debugcontent != "") {
		$content = $debugcontent;
		$author = "";
		$descr = "";
		$created = date("Y-m-d");		
		$md5 = "";
	} else {
		$md5 = md5_file($pdffile);		
		$parser = new \Smalot\PdfParser\Parser(); 
		$pdf = $parser->parseFile($pdffile);
		$pages = $pdf->getPages();
		$content = "";
		$pagenr = 0;
		foreach ($pages as $page) {
			$pagenr++;				
			$content.= PHP_EOL.\Indexing\Indexer::PAGE_NR_MARKER.$pagenr."".PHP_EOL.$page->getText();
		}
		
		$details =$pdf->getDetails();
		$author = utf8_encode(isset($details["Author"]) ? trim($details["Author"]) : "");
		$descr  = utf8_encode(isset($details["Title"]) ? trim($details["Title"]) : "");
		$created = isset($details["CreationDate"]) ? trim($details["CreationDate"]) : "";	
		if (strlen($created) < 8) {
			$created = date("Y-m-d");
		} else {
			if (($created = strtotime($created)) === false) {
				$created = date("Y-m-d");
			} else {
				$created = date("Y-m-d", $created);
			}
		}			
				
	}		
		
	$buildindex = new \Indexing\Indexer();								
	$buildindex->indexContent($content);	
	
	// ---	
	$wdbuffer = array();
	$dbwriter = new \Indexing\DbWorker();
	// DEBUG
	// $dbwriter->resetData();				
	// $fileid = $dbwriter->generateFileDescriptor(basename($pdffile), $md5, $created, $userid, $author, $descr);	
	$fileid = $dbwriter->generateFileDescriptor(basename($origfilename), $md5, $created, $userid, $author, $descr);	
	if ($fileid < 1) {		
		return -1;
	}
	$dbwriter->writeRawFile($fileid, $content);			
		
	// Võime tagasi tõsta UTF-8 kodeeringu, siis vähem UTF8 operatsioone
	mb_internal_encoding("UTF-8");		
	// Teeme eelpuhverdamise, sest ükshaaval puhul on latency väga aeglane
	// TODO: distributors/licensees sellised sõnad kaheks jaotada
	$buildindex->walkQueue(	
		function($content, $word, $word_start_index, $word_end_index, $word_spflags_index, $word_paragr_index, $word_topic_index, $word_sentence_index, $pagenr) use ($dbwriter, &$wdbuffer, $fileid) {				
				$word = normalizeWord($word);
								
				if (getLength($word) >= minwordlen) {
					if (!array_key_exists($word, $wdbuffer)) {
						$wdbuffer[$word] = -1;
					}				
				}					
			return true;
		});
			
	$tmpcache = array();
	foreach($wdbuffer as $kwkey => &$kwval) {			
		if ($kwval < 0) {
			$tmpcache[$kwkey] = -1;
		}		
		if (count($tmpcache) > max_word_queue) {
			$tmpcache = $dbwriter->writeBufferedWords($tmpcache);
			// kirjutame puhvri sisu tagasi
			foreach($tmpcache as $vbkey => $vbval) {
				$wdbuffer[$vbkey] = $vbval;
			}		
			$tmpcache = array();
		}
	}

	if (count($tmpcache) > 0) {
		$tmpcache = $dbwriter->writeBufferedWords($tmpcache);			
		foreach($tmpcache as $vbkey => $vbval) {
				$wdbuffer[$vbkey] = $vbval;
		}
	}

		
	$bufferedsqlwrites = array();
	$buildindex->walkQueue(
	
		function($content, $word, $word_start_index, $word_end_index, $word_spflags_index, $word_paragr_index, $word_topic_index, $word_sentence_index, $pagenr) use ($dbwriter, &$wdbuffer, $fileid, &$bufferedsqlwrites) {
			// content: UCS-2
			// word: UTF - 8								
			$word = normalizeWord($word);		
			if (getLength($word) >= minwordlen) {
				$starttime = microtime(true);					
				if (array_key_exists($word, $wdbuffer)) {
					$dbwordid = $wdbuffer[$word];
				} else {			
					$dbwordid = $dbwriter->addWord($word);
					if ($dbwordid > 0) {
						$wdbuffer[$word] = $dbwordid; 
					}
				}				
				$endtime = microtime(true);				
				// print "  - ".number_format($endtime - $starttime, 5).":";
							
				$starttime = microtime(true);
				if ($dbwordid > 0) {
					$bufferedsqlwrites[] = array("word_id" => $dbwordid, "startidx" => $word_start_index,
						"wordflags" => $word_spflags_index, "wordparaindx" => $word_paragr_index, 
						"wordtopicindx" => $word_topic_index, "wordsentindx" => $word_sentence_index, 
						"fileid" => $fileid, "pgnr" => $pagenr
					);
	

					if (count($bufferedsqlwrites) >= max_rv_index_queue) {
						$dbwriter->writeBufferedRvIndex($bufferedsqlwrites);						
						$bufferedsqlwrites = array();
					}					

					// $dbwriter->addRvIndex($dbwordid, $word_start_index, $word_spflags_index, $word_paragr_index, $word_topic_index, $word_sentence_index, $fileid);
				}
				
				$endtime = microtime(true);
				// print number_format($endtime - $starttime, 5);				
			}
			
			return true;
		}
	);	


	if (count($bufferedsqlwrites) > 0) {
		$dbwriter->writeBufferedRvIndex($bufferedsqlwrites);
		$bufferedsqlwrites = array();
	}					

	return 1;
}
		
	
?>
