<?php
set_time_limit(50);
header('Content-type: application/json');

require_once("./const.php");
require_once("./dbworker.php");
require_once("./tools.php");
require_once("./string_tools.php");

$act = _valExt("act");

if ($act == "search") {
	$q = trim(_valExt("q", ""));
	$rez = array();
	// view-source:http://127.0.0.1/pdfindexing/indexer/webservice.php?act=search&q=customer
	if (strlen($q) >= 3) {
		$db = new \Indexing\DbWorker();
		$wlist = $db->getKeywords($q);
		$jsonarr = array();
		foreach ($wlist as $k => &$v) {					
			$jsonarr[] = Array("id" => $v["id"], "label" => $v["word"], "value" => $v["word"]);
		}				
		$cb = _valExt("callback", "");
		if ($cb != "") {
			print $cb;
		}
		print json_encode($jsonarr);
	}	
} elseif ($act == "details") {
	
	$db = new \Indexing\DbWorker();	
	$wordid = (int)(_valExt("wordid", "0"));
	
	
	$sentrange = $db->getKeywordSentRange($wordid);
	$range = $db->getKeywordParagrRange($wordid);
	$topic = $db->getKeywordTopicRange($wordid);
	$phrase = $db->getWord($wordid);
	
	$yarr = array();
	$filedata = array();
	// $retval = array();
	foreach(array_merge($sentrange, $range, $topic) as $k => $vrez) {
		$fileid = (int)$vrez["fileid"];
		if (!in_array($fileid, $filedata)) {			
			$details = $db->getFileInfo($fileid);
						
			$yarr[] = $details["year"];
			$filedata[$fileid] = $details;
		}
	}		
	$yarr = array_unique($yarr);
	/*
	$retval["sentrange"] = $sentrange;
	$retval["paragrrange"] = $range;
	$retval["topicrange"] = $topic;		
	*/
	// Refact !
	$rawjson = ''
		.'[{'
		.'"id": "dgPubYearNode",'
		.'"text": "Publication Years",'
		.'"state": {"opened": true},'
		.'"children": [';
	$comma1 = '';
	$comma2 = '';
	$yearnodeid = 0;
	$resnodeid = 0;	
	$sectnodeid = 0;
					
	foreach ($yarr as $year) {
		$yearnodeid++;
		$rawjson.= $comma1.'{"text": "'.jsonEscape($year).'",'
			.'"state": {"opened": true},'
			.'"id": "dgYearNode_'.$yearnodeid.'",'
			.'"children": [';
		$comma1 =',';
		$comma2 = '';
		
		foreach($filedata as $fileid => $fd)	
		if ($fd["year"] == $year) {
			$resnodeid++;
			$filename = trim($fd["filename"]);
			if ($filename == "") {								
				$filename = "unknown";				
			}
			$rawjson.= $comma2.'{"text": "'.jsonEscape($filename).'",'
				.'"state": {"opened": true},'
				.'"id": "dgResourceNode_'.$resnodeid.'",'
				.'"children": [';
				
			$comma3 = '';
			foreach($sentrange as $contdescr) 
			if ($contdescr["fileid"] == $fileid) {				
				$sectnodeid++;
				$pagenr = (int)$contdescr["pg_nr"];
				$pagenr = $pagenr < 1 ? $pagenr + 1 : $pagenr;				
						
				// $extractText = "A Slovenian company, in cooperation with pharmacies, <span style='color:red'>pharmaceutical</span> ";				
				// $extractText = str_ireplace($phrase, '$'.$phrase.'$', $extractText);				
				$startpos = (int)$contdescr["startpos"];
				$endpos = (int)$contdescr["endpos"];
				// $extractText = $db->getTextRange($contdescr["fileid"], $startpos, $endpos);				
				
				
				// Sooviti "pseudo" lõike, kus kaks rida ees ja kaks rida taga
				
				$startpos = $startpos - 128;				
				$endpos = $endpos + 256;		
				$startpos = ($startpos < 0 ? 0 : $startpos); 								
				$extractText = $db->getTextRange($contdescr["fileid"], $startpos, $endpos);				
				
				$proctext = buildPseudoParagraph($phrase, $extractText);				
				if ($proctext["paramfound"]) {
					$extractText = $proctext["pseudopg"]; 									
				}
				
				// TODO mb safe replace !
				$extractText = nl2br(str_ireplace($phrase, '<span style="color:red">'.$phrase.'</span>', wordwrap($extractText, 165, "\n", true)));				
				$extractText = trim(str_ireplace("..", "", $extractText));
				$extractText = mb_ereg_replace("~pagenr\d+", "", $extractText);
				// Workaround, ntx tekst algas eelmisel leheküljes, aga lõppes teisel, ning märksõna oli teisel, siis väga imelik oleks kuvada lõiku ala Page 5, 
				// kus otsingusõna pole ja siis Page 6, kus on otsingusõna sees
				if ((getLength($extractText) < 2) || (mb_stripos($extractText , $phrase, 0, "UTF-8") === false)) {
					continue;
				}
				
				
				$rawjson.= $comma3.'{"text": "Page '.jsonEscape($pagenr).'",'
					.'"state": {"opened": true},'
					.'"id": "dgSectionNode'.$sectnodeid.'",'
					.'"children": [';
			
							
				$secttextid++;
				$rawjson.= '{"text": "'.jsonEscape($extractText).'",'
					.'"icon": "img/ui-text.png",'
					.'"state": {"opened": true},'
					.'"id": "dgDocText'.$secttextid.'"'
					.'}';
					
				$rawjson.= ']}';
				$comma3 = ',';
			}
			
			$rawjson.= ']}';
			$comma2 = ',';
		}
		
		$rawjson.= "]}";		
	}
	$rawjson.= "]";
	$rawjson.= '}]';
	
	
	print $rawjson;
	
}
	




/*
elseif ($act == "details") {
	// view-source:http://127.0.0.1/pdfindexing/indexer/webservice.php?act=details&wordid=22
	$db = new \Indexing\DbWorker();	
	$wordid = (int)(_valExt("wordid", "0"));
	$sentrange = $db->getKeywordSentRange($wordid);
	$ranges = $db->getKeywordParagrRange($wordid);
	$topic = $db->getKeywordTopicRange($wordid);
	
	$retval = array();
	$retval["sentrange"] = $sentrange;
	$retval["paragrrange"] = $ranges;
	$retval["topicrange"] = $topic;
	
	print_r($retval);
}
*/

/*
<?php
	
?>
([{"id":"Ficedula hypoleuca","label":"Eurasian Pied Flycatcher","value":"Eurasian Pied Flycatcher"},
{"id":"Muscicapa striata","label":"Spotted Flycatcher","value":"Spotted Flycatcher"},{"id":"Branta canadensis","label":"Greater Canada Goose","value":"Greater Canada Goose"},{"id":"Haematopus ostralegus","label":"Eurasian Oystercatcher","value":"Eurasian Oystercatcher"},{"id":"Aythya marila","label":"Greater Scaup","value":"Greater Scaup"},{"id":"Corvus corone","label":"Carrion Crow","value":"Carrion Crow"},{"id":"Sylvia atricapilla","label":"Blackcap","value":"Blackcap"},{"id":"Hydroprogne caspia","label":"Caspian Tern","value":"Caspian Tern"},{"id":"Bubulcus ibis","label":"Cattle Egret","value":"Cattle Egret"},{"id":"Aythya valisineria","label":"Canvasback","value":"Canvasback"},{"id":"Aythya affinis","label":"Lesser Scaup","value":"Lesser Scaup"},{"id":"Anas falcata","label":"Falcated Duck","value":"Falcated Duck"}]);
*/
	
?>