<?php
	// http://127.0.0.1/pdfindexing/keywords.php?callback=jQuery112103620650682366857_1548018582762&term=410100&_=1548018582763
	error_reporting(0);
	require_once("./indexer/tools.php");
	require_once("./indexer/const.php");	
	require_once("./indexer/dbworker.php");
	
	// header('Content-type: application/json');
	
	$cbsid = _valExt("callback", "");
	print $cbsid;	
	
	$value = substr(trim(_valExt("term", "")), 0, 65);
	$db = new \Indexing\DbWorker();			
	$srcrez = $db->getKeywords($value);
	$convert = array();
	foreach($srcrez as $v) {
		$convert[] = array("id" => $v["id"], "label" => $v["word"], "value" => $v["word"]);
	}
	
	$json = "(".json_encode($convert).")";
	print $json; 
	// jQuery112103620650682366857_1548018582762[{"id":"49","label":"minified","value":"minified"}]
	// ([{"id":"Ficedula hypoleuca","label":"Eurasian Pied Flycatcher","value":"Eurasian Pied Flycatcher"},{"id":"Muscicapa striata","label":"Spotted Flycatcher","value":"Spotted Flycatcher"},{"id":"Branta canadensis","label":"Greater Canada Goose","value":"Greater Canada Goose"},{"id":"Haematopus ostralegus","label":"Eurasian Oystercatcher","value":"Eurasian Oystercatcher"},{"id":"Aythya marila","label":"Greater Scaup","value":"Greater Scaup"},{"id":"Corvus corone","label":"Carrion Crow","value":"Carrion Crow"},{"id":"Sylvia atricapilla","label":"Blackcap","value":"Blackcap"},{"id":"Hydroprogne caspia","label":"Caspian Tern","value":"Caspian Tern"},{"id":"Bubulcus ibis","label":"Cattle Egret","value":"Cattle Egret"},{"id":"Aythya valisineria","label":"Canvasback","value":"Canvasback"},{"id":"Aythya affinis","label":"Lesser Scaup","value":"Lesser Scaup"},{"id":"Anas falcata","label":"Falcated Duck","value":"Falcated Duck"}]);	

?>

