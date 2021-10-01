<?php	
namespace Indexing;
	
class Indexer extends \Indexing\TextProcessor{ 
	const PAGE_NR_MARKER = "~pagenr"; // addWord
	protected $parser;	
	protected $prevtextstart = -1;
	protected $prevtextend = -1;
	protected $paragroupindx = 1;
	protected $topicgroupindx = 1;
	protected $sentencegroupindx = 1;
	
	/* ------------------------------------------------------ */
	
	function __construct($skipwords = array()) {		
		$this->parser = new \Smalot\PdfParser\Parser();
		$this->skipwords = $skipwords;
	}		
				
	/* ------------------------------------------------------ */
			
	protected function analyzeSection($text, $start, $end) {
		$paraend = false;		
		$rebuildtext = mb_trim($this->rebuildLine($text, $start, $end));
		if ($rebuildtext == "") {
			return false;
		}
				
		$rebuildtext = toUCS2($rebuildtext); // convert back to UCS-2; UTF8 operations are very slow ! iconv  is even worse
		$result = $this->isHeadLineOrTopic($rebuildtext);	
		$istopic = isset($result["istopic"]) ? $result["istopic"] : false;
		
		if ($istopic) {			
			$this->paragraphActive = false;			
			// Wrapped topic ?
			if ((!isFirstCharUcase($rebuildtext)) && (!hasAnnotation($rebuildtext)) && ($this->prevtext != "") && (!charIsInt($this->prevtext[0]))) {								
				$this->assignTopicBindIndx($this->prevtextstart, $this->prevtextend, $this->topicgroupindx);
				$this->topicgroupindx++;				
			}
						
			$this->assignTopicBindIndx($start, $end, $this->topicgroupindx);
			if (debug_text) {
				print "<topic>[".($istopic ? $this->topicgroupindx : "")."]".toUTF8($rebuildtext)."</sent>\n";
			}				
			
			$this->topicgroupindx++;
			$this->prevtext = $rebuildtext;
		} else {
			$paragractive = $this->paragraphActive;			
			if ((!$this->paragraphActive) && !hasAnnotation($rebuildtext)) {							
				$this->paragraphActive = hasAnnotation($this->prevtext) && isFirstCharUcase($rebuildtext);												
			}
			
			//	$paraend = $this->isEndOfParagraph($text, $start, $end);		
			//	if (hasAnnotation($this->prevtext) && isFirstCharUcase($rebuildtext)) {
			if ($this->paragraphActive) {	
				if (!$paragractive) {
					$this->paragroupindx++;
				}					
				$this->assignParagrBindIndx($start, $end, $this->paragroupindx);				
				$paraend = $this->isEndOfParagraph($text, $start, $end);							
				if (debug_text) {					
					print "<para>[".$paraend.":".$this->paragroupindx."]".toUTF8($rebuildtext)."</para>\n";
				}
				
				$this->paragraphActive = !$paraend;
									
			} else {		
				if (debug_text) {
					print "<sent>[".$this->sentencegroupindx."]".toUTF8($rebuildtext)."</sent>\n";
			}
		}
		} 
	
		$this->prevtextstart = $start;
		$this->prevtextend = $end;
		$this->prevtext = $rebuildtext;					
		return true;
	}
	
	/* ------------------------------------------------------ */
		
	public function indexContent($pdftext) {			
		$starttime = microtime(true);		
		$word = "";
		$number = "";
		$nextchar = "";		
		$isnrchar = false;
		$issummary = false;
		$sectionstart = (int)0;
		$sectionend = (int)-1;
		$openbracket = false;
		$closebracket = false;
		$analyzedone = false;
		$eolflag = false;
		$httpflag = false;
		$httpsegment = "";
		$hasopenqflag = false;		
		$i = 0;			
		 // mb_convert_encoding($str, 'UTF-8', 'UCS-2LE'); 		 
		 // UCS2
		$pdftext = mb_convert_encoding(textCleanup($pdftext), "UCS-2", "UTF-8");		
		mb_internal_encoding("UCS-2");	
		$pdftextlen = getLength($pdftext);
		
		
		while ($i < $pdftextlen) {
			$dtstart = microtime(true);
			$c = getChar($pdftext, $i);			
			$b = isLineEnd($c);			
							
			$resethttpflag = ($b || isSpace($c));
			if (!$hasopenqflag) {
				$hasopenqflag = isOpenQuat($c);				
			}
			
			
			// URL ends
			if (($httpflag) && $resethttpflag) {
				// wrapped url ?
				//https://een.e c.europa.eu/tools/services/PRO/Profile/Detail/a184819f -d0ee-424d-879a-
				//6f4b4f993ac9?shid=32db25cb -726f-43b0-8b5f-7742d0935799 							
				/* 
				$peekback = getChar($pdftext, $i - 1);				
				$c = getChar($pdftext, $i);								
				$origcharindex = $i;				
				$goforward = $peekback <> "/";						
				while ($goforward) {					
					$c = getChar($pdftext, $i);
					$goforward = isLineEnd($c) || isSpace($c);
					if ($goforward) {						
						$i++;
					}					
				} */
								
				while ($i < $pdftextlen) {
					$c = getChar($pdftext, $i);
					$space = isSpace($c);
					if ((isLineEnd($c)) || $space) {
						
						if (isFirstCharUcase(getChar($pdftext, $i + 1))) {
							break;													
						}						
					} elseif (isQuatMark($c) || ((!plainAscii($c)) && (!in_array($c, Array("=", ":", "%", "&", "/", "?", ".", "-", "#"))))) {
						break;						
					} else {							
						$word.= $this->addAcceptedChar($i, $c);						
					}
				
					// max url 
					if (getLength($word) > allowedurllen) {						
						break;
					}
					
					$i++;					
				}
						
				
				// Heuristics: check paragr end sequence				
				$paraend = ((isSingleLineEnd(getChar($pdftext, $i - 2)) && (isSpace(getChar($pdftext, $i - 1))) && (isSingleLineEnd($c)))
					|| ((isSingleLineEnd(getChar($pdftext, $i - 2)) && (isSingleLineEnd($c)))));

				
				// filter_var($word, FILTER_VALIDATE_URL)
				if (getLength($word) > 6) {
					$sectionstart = $sectionend + 1;
					$sectionend = $this->addWord($word, $i, $this->currpage, flag_as_url) - 1;
					$this->analyzeSection($pdftext, $sectionstart, $sectionend);
					$hasopenqflag = false;
				}		

				if ($paraend) {
					$this->paragraphActive = false;
				}				
				
				$word = "";
				$httpflag = false;
			} elseif ($b) { // Build sentence
				if (!$eolflag) {				
					$sectionstart = $sectionend + 1;
					$sectionend = $this->wdqindex - 1;									
					$this->analyzeSection($pdftext, $sectionstart, $sectionend);
					$this->paragraphflag = false;
					$analyzedone = true;
					$eolflag = true;									
				}				
			} else {				
				$analyzedone = false;
				$eolflag = false;				
			}
			
			if (isWordTerminator($c)) {				
				$i++;
				continue;
			} elseif (isStartingBracket($c)) {
				$openbracket = true;
				$i++;
				continue;				
			} elseif (isEndBracket($c)) {
				$openbracket = false;
				$i++;
				continue;							
			}
			
			$dtstart = microtime(true);				
			$nextchar = getChar($pdftext, $i + 1);							
			$wlen = getLength($word);
			
			// Is it URL			
			if ((!$httpflag) && ($wlen >= 3) && ($wlen <= 5) && (in_array(mb_strtolower($word), toUCS2Arr(Array("http", "https", "ftp"))))) {				
				$httpflag = (($c == toUCS2(":")) || ($nextchar == toUCS2(":")));
			}

			if (!$httpflag) {
				// -15; +25;
				if (isPlusMinus($c) && charIsInt($nextchar)) {
					if ($c == "-") {
						$number.= $c;
					}
				
					$i++;
					continue;
				}
			}

			$dtend = microtime(true);
			$this->debword.= number_format($dtend - $dtstart, 5).PHP_EOL;
			
			// Maybe it's time to write word to queue
			$hasendchar = (($nextchar == "") || (isWordTerminator($nextchar)));
			$eof = (bool)($i == $pdftextlen - 1);
			
			if ($httpflag) {// Skip special flags !
				$word.= $this->addAcceptedChar($i, $c);
								
				if (($eof) && (getLength($word) > 6)) {
					$this->addWord($word, $i, $this->currpage, flag_as_url, 0, 0, $this->sentencegroupindx);
					$hasopenqflag = false;
				}
			} else {				
				if (charIsInt($c) || ($isnrchar && isDecSeparator($c) && charIsInt($nextchar))) {
															
					$isnrchar = true;
					if (($word != "") && ($word != toUCS2(self::PAGE_NR_MARKER))) {											
						$this->addWord($word, $i, $this->currpage, 0, 0, 0, $this->sentencegroupindx);
						$word = "";						
					}
								
								
					$number.= $this->addAcceptedChar($i, $c);
					if ($hasendchar) {						
						if ($word == toUCS2(self::PAGE_NR_MARKER)) {
							$number = $word.$number;							
						}							
						$this->addWord($number, $i, $this->currpage, flag_as_number, 0, 0, $this->sentencegroupindx);
						$isnrchar = false;						
						$number = "";
						$word = "";
					}
														
					$i++;
					continue;
				} elseif ($isnrchar) {
					// 150%								
					$gotonext = (bool)((getLength($number) <= 8) && ($c == "%"));
					if ($gotonext) {					
						$number .= $c;		
					}			
																			
					$this->addWord($number, $i, $this->currpage, flag_as_number, 0, 0, $this->sentencegroupindx);					
					$isnrchar = false;
					$sentence= "";
					$number = "";
					if ($gotonext) {
						$i++;
						continue;
					}																						
				}				

				
				$sentend = isSentenceTerminator($c);							
				// Build word
				// $word.= (!$sentend ? $c : "");
				$word.= $this->addAcceptedChar($i, $c);
			
			
				// Quoted sentence?
				if (($hasopenqflag) && (isCloseQuat($nextchar))) {
					$hasopenqflag = false;
					$i++;					
				}
												
				if (($hasendchar) || $sentend || $eof) {
					// Kuidas originaaltekstis asendused teha ?
					// Split word ?
					// Many skir-
					// mishes
					$currlen = getlength($word);
					if (($hasendchar) && ($currlen > 2) && ($currlen < 36)) {		
						if (toUTF8(getChar($word,$currlen - 1)) == "-") {
							$word = mb_substr($word, 0, $currlen - 1,  "UCS2");							
							$i++;
							continue;
						}
					}
										
					$this->addWord($word, $i, $this->currpage, flag_as_normal, 0, 0, $this->sentencegroupindx);
					$word = "";			
					$dtend = microtime(true);
					// $db.= number_format($dtend - $dtstart, 5).PHP_EOL;
					// print number_format($dtend - $dtstart, 5)."\n";										
				}
				
				if ($sentend) {					
					$this->sentencegroupindx++;										
				}				
			}			
			$i++;	
		}
	
		$sectionstart = $sectionend + 1;
		$sectionend = $this->wdqindex - 1;							
		
		
		$rebuildtext = "";				
		for ($i = $sectionstart; $i <= $sectionend; $i++) {
			$rebuildtext.= $this->wdqueue[$i][0]." ";
		}
		
		
		if ((!$analyzedone) && ($sectionend > 0) && ($sectionstart <= $sectionend)) {							
			$this->analyzeSection($pdftext, $sectionstart, $sectionend);
		}			
		
		$this->doWordQueueCleanup();
		$endtime = microtime(true);
		
		// For callback
		$this->content = $pdftext;
	}	
	
	/* ------------------------------------------------------ */
}

?>