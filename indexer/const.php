<?php
	DEFINE("debug_wdlist", 0);
	DEFINE("debug_text", 0);
	DEFINE("minparacrlfcntbefore", 1);
	DEFINE("minparacrlfcntafter", 2);
	DEFINE("defaultlang", "en");
	DEFINE("allowedurllen", 255);
	
	DEFINE("minwordlen", 2);
	DEFINE("max_searchdata_rows", 255);
	DEFINE("max_search_keywords", 200);
	
	DEFINE("wd_index", 0);
	DEFINE("wd_start_index", 1);
	DEFINE("wd_end_index", 2);
	DEFINE("wd_spflags_index", 3);
	DEFINE("wd_paragr_index", 4);
	DEFINE("wd_topic_index", 5);
	DEFINE("wd_sentence_index", 6);
	DEFINE("wd_page_nr", 7);
	
	
	// Detected word flags
	DEFINE("flag_as_normal", 0);
	DEFINE("flag_as_title", 1);
	DEFINE("flag_as_number", 2);
	DEFINE("flag_as_url", 3);

	// Php parser round bullet char seq
	DEFINE("phpbulletchar", "â€¢");
	
	DEFINE('mysql_host', 'www.stiigo.com');
	DEFINE('mysql_db', '');
	DEFINE('mysql_usr', '');
	DEFINE('mysql_pwd', '');
?>