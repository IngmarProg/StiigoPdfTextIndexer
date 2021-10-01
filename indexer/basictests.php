<?php
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 0);
assert_options(ASSERT_QUIET_EVAL, 1);

function assertHandler($file, $line, $code, $desc = null) {	
    print "Assertion failed at ".$file.":".$line.":".$code;
    if (!empty($desc)) {
        // print ":".$desc;
    }
    print "\n";
}


function runCommonTests() {	
	assert_options(ASSERT_ACTIVE, true);
	assert_options(ASSERT_BAIL, false); // lõpetab scripti töö, kui aktiivne
	assert_options(ASSERT_WARNING,  false);
	assert_options(ASSERT_QUIET_EVAL, true);
	assert_options(ASSERT_CALLBACK, "assertHandler");
	assert(isFirstCharUcase("Test case 1"), "isFirstCharUcase");
	
	
// removeSpecialChars("”“there") == "there";
// removeSpecialChars("-“never") == "never";
// removeSpecialChars('“wa’al,”') == "wa’al";
// removeSpecialChars('“yes,”') == "yes";
// removeSpecialChars('“thank’ee,') == "thank’ee"
}


/*
		// $pdftext = '1936 96 (92%) 7 (7%) 1 (1%) 0 104 (100%)';
		// $pdftext = '1936 96 (92%) 7 (7%) 1 (1%) 0 104 (100%)'; 
		 
		 $ip = " A "." B ".chr(160)."AAA".chr(160)."BBB".chr(160);    
		
		$pdftext = ' (-100% test %%)RwA#'; 
		$pdftext = '-100.0% aa';		
		
		$pdftext = 'Introduction ..................................................................................................................................................... 11' 
		.PHP_EOL.'Pharma: German company for cell culture media and related services looks for distributors/licensees ...... 12'		
		.PHP_EOL
		.'XX YY ZZZ';
		
		
		$pdftext = '‘Ham and eggs. And onions.’'.PHP_EOL
		.'Too much information!'.PHP_EOL
		.'‘Not at all, Joe.’'.PHP_EOL
		.'which Rob shook'.PHP_EOL
		.'if it ever became public';
		
		
*/		
?>