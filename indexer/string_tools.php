<?php	
	/* ------------------------------------------------------ */	
	
	function toUTF8($text) {
		return mb_convert_encoding($text, "UTF-8", "UCS-2");		
	}

	/* ------------------------------------------------------ */	
	
	function toUCS2($text) {
		return mb_convert_encoding($text, "UCS-2", "UTF-8");
	}

	/* ------------------------------------------------------ */	

	function toUCS2Arr($arr) {		
		foreach($arr as $k => &$v) {
			$v = toUCS2($v);
		}		
		return $arr;
	}
	
	/* ------------------------------------------------------ */	
	
	function lbByte($c) {
		if (strlen($c) == 2) {
			if (ord($c[0]) == 0) {
				return $c[1];
			} else 
			//if (ord($c[1]) == 0) {
			//	return $c[0];
			//} else {
			{
				return $c;
			}				
		} else {
			return $c;
		}
	}

	/* ------------------------------------------------------ */	

	function isPageNrMarker($word) {
		$regex = ("~pagenr(?<nr>\d.*)");				
		$ret = array();				
		if ((lbByte($word[1]) == "~") && (mb_eregi($regex, toUTF8($word), $match)) && (count($match) == 2)) {
			$ret = array("ispagenr" => true, "nr" => $match[1]);
		}		
		return $ret;
	}

	/* ------------------------------------------------------ */	
	
	function textCleanup($text) {
		//$text = preg_replace('!\s+!', ' ', $text);
		// $text = str_ireplace (chr(0x20).chr(0x0A), chr(0x0A), $text); // faster
		return $text;
	}
	
	/* ------------------------------------------------------ */	
	
	function plainAscii($c) {	
		$c = lbByte($c);
		return  ((ord($c) >= 48) && (ord($c) <= 57)) ||
			((ord($c) >= 65) && (ord($c) <= 90)) || 
			((ord($c) >= 97) && (ord($c) <= 122));
	}
	
	/* ------------------------------------------------------ */	
	
	function isFirstCharUcase($word) {		
		return (bool)(mb_substr($word, 0, 1) != mb_strtolower(mb_substr($word, 0, 1)));
	}
	
	/* ------------------------------------------------------ */	
	
	function getChar($text, $index) {
		//return mb_substr($text, $index, 1, "UTF-8");	sadly dead slow !!!
		return mb_substr($text, $index, 1,  "UCS2");
	}
	
	/* ------------------------------------------------------ */	

	function getLength($text) {
		return mb_strlen($text);
	}
	
	/* ------------------------------------------------------ */	
			
	function hasAnnotation($str, $checkcase = true) {		
		$warr = preg_split("/[\s,".chr(160)."]+/", $str);
		$words = Array();
		for ($i = 0; $i < count($warr); $i++) {
			$word = mb_trim($warr[$i]);	
			if (getLength($word) > 3) {		
				$words[] = $word;
				if (count($words) == 3) {
					break;
				}				
			}
		}		
								
			
		if (count($words) < 1) {
			return false;
		}
		
		$firstword = $words[0];						
		// Classification: Separate into groups or explain the various parts of a topic.
		// Summary:
		$rez = false;
		foreach ($words as $vl) {
			$rez = (bool)(toUTF8(mb_substr($vl, -1)) == ":");						
			if ($rez) {
				break;
			}
		}
		
		if ($rez && $checkcase) {			
			$rez = isFirstCharUcase($firstword); 					
		}		
		return $rez;
	}

	/* ------------------------------------------------------ */	
	
	function removeSpecialChars($word) {
		$wordasuc2 = $word;
		while (true) {
			$lastchar = mb_substr($wordasuc2, -1);						
			if (isSentenceTerminator($lastchar) || isWordTerminator($lastchar) || isStartingBracket($lastchar) 
				|| isEndBracket($lastchar) || isComma($lastchar) || isDash($lastchar) || isSemicolon($lastchar)
				|| isOpenQuat($lastchar) || isCloseQuat($lastchar) || isQuatMark($lastchar)) {			
				$wordasuc2 = mb_substr($wordasuc2, 0, -1);				
				continue;
			}
			
			$firstchar = mb_substr($wordasuc2, 0, 1);	
			if (isSentenceTerminator($firstchar) || isWordTerminator($firstchar) || isStartingBracket($firstchar) 
				|| isEndBracket($firstchar) || isComma($firstchar) || isDash($firstchar) || isSemicolon($firstchar)
				|| isOpenQuat($firstchar) || isCloseQuat($firstchar) || isQuatMark($firstchar)) {			
				$wordasuc2 = mb_substr($wordasuc2, 1);				
				continue;
			}			
			break;
		}		
		return $wordasuc2;
	}	

	/* ------------------------------------------------------ */	

	function isNote($c) {
		return isOpenQuat($c) || isCloseQuat($c);
	}
	
	/* ------------------------------------------------------ */	
	
	function isOpenQuat($c) {	
		$c = lbByte($c);
		$bfr = Array();
		$bfr[] = "“";
		$bfr[] = "‘"; 
		$bfr[] = "«";
		$bfr[] = html_entity_decode("&sbquo;");
		$bfr[] = html_entity_decode("&bdquo;");
		$bfr[] = html_entity_decode("&laquo;");
		return in_array($c, $bfr);
	}
	
	/* ------------------------------------------------------ */	
	
	function isCloseQuat($c) {
		$c = lbByte($c);
		$bfr = Array();
		$bfr[] = "”";
		$bfr[] = "’";
		$bfr[] = "»";
		$bfr[] = html_entity_decode("&lsquo;");
		$bfr[] = html_entity_decode("&ldquo;");
		$bfr[] = html_entity_decode("&raquo;");
		return in_array($c, $bfr);
	}	
	
	/* ------------------------------------------------------ */	
	
	function charIsInt($val) {		
		return (intval(lbByte($val))."" == "".lbByte($val)); // faster then regex
	}
	
	/* ------------------------------------------------------ */	
	
	function mb_trim($string, $trim_chars = '\s'){		
		// Incorrect results with UCS-2 chars !
		return $string; // preg_replace('/^['.$trim_chars.']*(?U)(.*)['.$trim_chars.']*$/u', '\\1',$string);
	}
	
	/* ------------------------------------------------------ */	

	function isDash($c) {
		return (bool)(($c == toUCS2(html_entity_decode("&mdash;"))) || ($c == toUCS2(html_entity_decode("&horbar;"))));
	}
	
	/* ------------------------------------------------------ */	

	function isPlusMinus($c) {
		return in_array(lbByte($c), Array("+", "-"));		
	}
	
	/* ------------------------------------------------------ */	

	function isComma($c) {
		return (bool)(lbByte($c) == ",");
	}
	
	/* ------------------------------------------------------ */	

	function isDecSeparator($c) {
		return in_array(lbByte($c), Array(".", ","));
	}
	
	/* ------------------------------------------------------ */	

	function isStartingBracket($c) {
		return in_array(lbByte($c), Array("[", "(", "{"));
	}
	
	/* ------------------------------------------------------ */	

	function isEndBracket($c) {
		return in_array(lbByte($c), Array("]", ")", "}"));
	}	
	
	/* ------------------------------------------------------ */	
	
	function isSpace($c) {
		return in_array(lbByte($c), Array(chr(32), chr(160)));
	}

	/* ------------------------------------------------------ */	
	
	function isQuatMark($c) {			
		return in_array(lbByte($c), Array(chr(0x22), chr(0x27), chr(0x60), chr(0xB4), chr(0x201C), chr(0x201D), chr(0x2018), chr(0x2019), chr(0x201C), chr(0x201D)));
	}

	/* ------------------------------------------------------ */	
	
	function isSemicolon($c) {
		return in_array(lbByte($c), Array(";", ":"));
	}

	/* ------------------------------------------------------ */	
	// CR	
	function isSingleLineEnd2($c) {		
		return (bool)(lbByte($c) == chr(13));
	}

	/* ------------------------------------------------------ */	
	// LF	
	function isSingleLineEnd($c) {
		return (bool)(lbByte($c) == chr(10));
	}
	
	/* ------------------------------------------------------ */	

	function isLineEnd($c) {
		return in_array(lbByte($c), Array(chr(10), chr(13)));
	}
	
	/* ------------------------------------------------------ */	
	
	function isWordTerminator($c) {
		return isSpace($c) || isQuatMark($c) || (isLineEnd($c));
	}
	
	/* ------------------------------------------------------ */	

	function isSentenceTerminator($c) {
		return in_array(lbByte($c), Array('.', '?', '!'));					
	}	
	
	/* ------------------------------------------------------ */	
	// TODO stemming !
	function normalizeWord($word) {
		// NB UTF8 operatsioonid on  PHP's kordades aeglasemad, kui UCS-2.
		// return (removeSpecialChars(mb_strtolower($word, "UTF-8")));
		return toUTF8(removeSpecialChars(toUCS2(mb_strtolower($word, "UTF-8"))));	
	}
	
	/* ------------------------------------------------------ */		
	// TODO UTF8 check

		function buildPseudoParagraph($phrase, $text) {
		$rez = array("paramfound" => false, "pseudopg" => "");
		$CRLF = chr(13).chr(10);		
		//$exptext = explode(stripos($text, chr(13)) !== false ? chr(13) : chr(10), $text);	
		$exptext = explode(chr(10), $text);	
		
		$idxstart = -1;
		$idxend = -1;
		foreach ($exptext as $vindx => $vva) {		
			if (stripos($vva, $phrase) !== false) {
				$idxstart = $idxstart < 1 ? $vindx : $idxstart;								
				$idxend = $idxend < $vindx ? $vindx : $idxend;
			}							
		}	
	
		if ($idxstart < -1) {
			$rez["pseudopg"] = $text;
			return $rez;		
		}

		$after = "";
		$middle = "";
		$before = "";
		$i = $idxstart - 1;	
		$crlfcnt = 0;
		$stop = false;
		while (($i >= 0) && (!$stop)) {
			$crlfcnt++;
			$text = $exptext[$i];		
			$stop = (($crlfcnt >= 2) && (preg_match('/[.!?]/', $text)));	
			if ($stop) {
				foreach(array(".", "!", "?") as $v1) {
					$pos = stripos($text, $v1);
					if ($pos !== false) {
						$text = substr($text, $pos + 1, strlen($text) - $pos);					
						break;
					}
				}		
			}
			
			$before = trim($text).$CRLF.$before;
			$i--;
		}		
	
		for ($i = $idxstart; $i <= $idxend; $i++) {
			$middle = $middle.($i != $idxstart ? $CRLF : "").trim($exptext[$i]);
		}
	
		$i = $idxend + 1;	
		$crlfcnt = 0;
		$stop = false;
		while (($i <= count($exptext)) && (!$stop)) {
			$crlfcnt++;
			$text = $exptext[$i];		
			$stop = (($crlfcnt >= 2) && (preg_match('/[.!?]/', $text)));
			if ($stop) {			
				foreach(array(".", "!", "?") as $v1) {
					$pos = stripos($text, $v1);
					if ($pos !== false) {
						$text = substr($text, 0, $pos + 1);
						break;
					}
				}
			}
			
			$after = $after.$CRLF.trim($text);		
			$i++;
		}			
	
		$rez["pseudopg"] = $before.$middle.$after;
		$rez["paramfound"] = true;
		return $rez;
	}
	
?>