<?php
namespace Indexing;

class TextProcessor {	
	protected $debword = "";
	protected $content = "";
	protected $wdqindex = 0; // queue index
	protected $wdqueue = Array();	
	protected $wdlastindex = 0;
	protected $wdlist = Array();
	protected $prevtext = "";	
	protected $paragraphActive = false;	
	protected $currpage = 0;
	protected $acceptedcharpos = -1;	
	// @@Conf
	public $paraCrlfCntBefore = minparacrlfcntbefore;
	public $paraCrlfCntAfter = minparacrlfcntafter;	
	public $skipwords = Array();
	public $textlang = defaultlang;
		
	/* ------------------------------------------------------ */
	
	protected function resolveWord($arrindex) {
		$word = array_search ($arrindex, $this->wdlist);
		if ($word !== false) {
			return $word;
		} else {			
			return "";
		}
	}
	
	/* ------------------------------------------------------ */
	
	protected function assignTopicBindIndx($start, $end, $topicindex) {
		for ($i = $start; $i <= $end; $i++) {
			$this->wdqueue[$i][wd_topic_index] = $topicindex;
		}
	}

	/* ------------------------------------------------------ */

	protected function assignParagrBindIndx($start, $end, $paragrindex) {
		for ($i = $start; $i <= $end; $i++) {
			$this->wdqueue[$i][wd_paragr_index] = $paragrindex;
		}
	}
	
	/* ------------------------------------------------------ */
	
	protected function addAcceptedChar($index, $c) {
		if ($this->acceptedcharpos < 0) {
			$this->acceptedcharpos = $index;
		}
		return $c;
	}	
	
	/* ------------------------------------------------------ */
		
	protected function addWord($word, $endindex, $resolvedpagenr, $specialflags = flag_as_normal, $parabindindex = 0, $topicbindindex = 0, $sentencerangeindex = 0) {		
		// Update page number, if new marker !
		$pg = isPageNrMarker($word);	
		if (isset($pg["ispagenr"]) && ($pg["ispagenr"])) {
			$this->currpage = (int)$pg["nr"];
			return 0;
		}
		
		$resolvedpagenr = $resolvedpagenr < 1 ? 1 : $resolvedpagenr;		
		$dtstart = microtime(true);			
		if ($word == "") {
			$this->acceptedcharpos = -1;
			return -1;
		} 
		
		$word = trim(html_entity_decode(toUTF8($word))); // Preserve case !!	
		if (debug_wdlist) {
			print "[".$this->wdqindex."]".$word." \n";			
		}

		// print $word."\n";		
		// array_search is much slower !			
		if (!array_key_exists($word, $this->wdlist)) {
				$index = $this->wdlastindex;
				$this->wdlist[$word] = (int)$index;				
				$this->wdlastindex++;				
		} else {
				$index = (int)$this->wdlist[$word];
		}		
			
		$this->wdqueue[$this->wdqindex] = Array(
			wd_index => $index, 
			wd_start_index => $this->acceptedcharpos,
			wd_end_index => $endindex,
			wd_spflags_index => $specialflags, 
			wd_paragr_index => $parabindindex, 
			wd_topic_index => $topicbindindex,
			wd_sentence_index => $sentencerangeindex,
			wd_page_nr => $resolvedpagenr);
		$this->wdqindex++;		
		$dtend = microtime(true);		
		$this->acceptedcharpos = -1;
		// $this->debword.= number_format($dtend - $dtstart, 5).PHP_EOL;
		return $this->wdqindex;
	}
	
	/* ------------------------------------------------------ */	
	// Text is UC2, word list UTF8 to preserve memory
	public function doWordQueueCleanup() {				
		foreach($this->wdlist as $word => $index) {
			
			$wordasuc2 = toUCS2($word); 
			$bflen  = getLength($wordasuc2);
			$wordasuc2 = removeSpecialChars($wordasuc2);
			$aflen  = getLength($wordasuc2);						
			if ($bflen != $aflen) { // Do cleanup								
			
				// After cleanup, empty entry
				if ($aflen < 1) {
					$emptyentry = array();
					$entryindx = $this->wdlist[$word];
					foreach ($this->wdqueue as $k => &$v) {
						if ($v[wd_index] == $entryindx) {							
							$emptyentry[] = $k;
						}
					}
					
					foreach($emptyentry as $k) {
						unset($this->wdqueue[$k]);
					}					
					
					unset($this->wdlist[$word]);					
					continue;
				}
				
				unset($this->wdlist[$word]);				
				$word = toUTF8($wordasuc2);
				$currindex = $index;					
				if (!array_key_exists($word, $this->wdlist)) {					
					$newentry = array($word => $currindex);
					$this->wdlist = $newentry +$this->wdlist;					
				} else  { // Optimize: cleaned word already exists !
					$entryindx = $this->wdlist[$word];														
					foreach ($this->wdqueue as $k => &$v) {
						if ($v[wd_index] == $currindex) {							
							$v[wd_index] = $entryindx;
							break;
						}
					}
				}				
			}			
		}
	}
	
	/* ------------------------------------------------------ */
	//PHP is deadslow with UTF8 !
	protected function rebuildLine($text, $start, $end) {
		$rtext = "";
		for ($i = $start; $i <= $end; $i++) {
			$flags = (int)$this->wdqueue[$i][wd_spflags_index];				
			$word = ($this->resolveWord($this->wdqueue[$i][wd_index])).($flags != flag_as_url ? chr(32): "");
			$rtext.= $word;	
		}				
		return $rtext;
	}
	
	/* ------------------------------------------------------ */
	// Sometext.......1
	protected function isHeadLineOrTopic($text) {
		if (getLength($text) < 4) {
			return false;
		} 
		
		$nextchar = "";
		$i = $intcnt = 0;		
		// 1. Test			
		while ($i < getLength($text)) {
			if (charIsInt(getChar($text, $i))) {
				$intcnt++;				
			// } elseif (($intcnt > 0) && (lbByte(getChar($text, $i)) == ".") && (isSpace(getChar($text, $i + 1)))) {						
			} elseif (($intcnt > 0) && (lbByte(getChar($text, $i + 1)) == ".") && (isSpace(getChar($text, $i + 2)))) {				
				return Array("istopic" => true, "type" => 1);
			} else {
				break;
			}
			$i++;
		}
				
		// Pharma:  Click-mass spectrometry of lipids.  .........  28
		$intcnt = 0;
		$i = getLength(trim($text)) - 1;
		while ($i > 0) {
			if (charIsInt(getChar($text, $i))) {
				$intcnt++;
			}  elseif ($intcnt > 0) {
				while (isSpace(getChar($text, $i))) {
					$i--;
				}
				$b = (bool)((lbByte(getChar($text, $i)).lbByte(getChar($text, $i - 1)).lbByte(getChar($text, $i - 2)) == "..."));
				return Array("istopic" => $b, "type" => 2);
			} else {
				break;
			}
			$i--;
		}
		
		return false;
	}

	/* ------------------------------------------------------ */
		
	protected function isEndOfParagraph($text, $start, $end) {
		$rez = false;
		if ($this->wdqueue[$start][0] == "") {
			return $rez;
		}
				
		if (($start >= 0) && ($end > 0) && ($end - $start) < 2048) {
			$fqword = toUCS2($this->resolveWord($this->wdqueue[$start][wd_index]));
			$crlfbefore = (int)$this->wdqueue[$start][wd_end_index] - getLength($fqword) - 1;
			$beforecrlfcnt = 0; // 4f6
			if ($crlfbefore >= 0)  {
				$i = $crlfbefore;
				while ($i > 0) {
					if (!isSpace(getChar($text, $i))) {											
						if (isSingleLineEnd(getChar($text, $i))) {
							$beforecrlfcnt++;
						} 				
					} elseif (!isSingleLineEnd2($text[$i])) {												
						break;
					}
					$i--;
				}
			}		
				
			if ($beforecrlfcnt >= $this->paraCrlfCntBefore)  {
				$crlfafter = (int)$this->wdqueue[$end][wd_end_index];
				$isend = (bool)($this->wdqueue[$end][wd_end_index] == getLength($text));											
				$aftercrlfcnt = 0;
				$i = $crlfafter;
				
				
				
				// PDF lib konverteerib teksti topeltread järgnevalt
				// 32 13 10 32 13 10 32				
				$spacecount = 0;				
				while (($i > 0) && ($i < getLength($text))) {										
					$c = getChar($text, $i);
					if (!isSpace($c)) {						
						if (isSingleLineEnd($c)) {
							$aftercrlfcnt++;							
							if ($aftercrlfcnt >= $this->paraCrlfCntAfter) {
								break;
							}
						} elseif (!isSingleLineEnd2($c)) {
							break;
						}
					}
					$i++;
				}
				
				$rez = (bool)(($beforecrlfcnt >= $this->paraCrlfCntBefore) && (($aftercrlfcnt >= $this->paraCrlfCntAfter) || $isend));				
			}
		}
		return $rez;
	}
	
	/* ------------------------------------------------------ */	
	
	public function extractSentenceRanges() {		
		$range = Array();		
		$currsentgroup = 0;
		$start = -1;
		$end = -1;
						
		for ($i = 0; $i < count($this->wdqueue); $i++) {
			$sentgroup = $this->wdqueue[$i][wd_sentence_index];				
			if ($currsentgroup == 0) {				
				$currsentgroup = $this->wdqueue[$i][wd_sentence_index];
				$start = 0; // $this->wdqueue[$i][wd_end_index];
			} elseif ($currsentgroup != $sentgroup) {
				$range[$currsentgroup] = Array($start, $end);
				$start = $this->wdqueue[$i][wd_end_index];
				$currsentgroup = $sentgroup;
			} else {
				$end = $this->wdqueue[$i][wd_end_index];
			//	print $start."\n";
			}
			
			if ($i == count($this->wdqueue) - 1) {
				$range[$currsentgroup] = Array($start, $end);
			}
		}
		return $range;
	}
	
	/* ------------------------------------------------------ */
	
	public function walkQueue($cb) {
		if (is_callable ($cb)) {
			foreach ($this->wdqueue as $indx => $data) {				
				$word = $this->resolveWord($data[wd_index]);
				$word_start_index = $data[wd_start_index];
				$word_end_index = $data[wd_end_index];
				$word_spflags_index = $data[wd_spflags_index]; 
				$word_paragr_index = $data[wd_paragr_index]; 
				$word_topic_index =$data[wd_paragr_index]; 
				$word_sentence_index = $data[wd_sentence_index];
				$pagenr = $data[wd_page_nr];
				if (!$cb($this->content, $word, $word_start_index, $word_end_index, $word_spflags_index, $word_paragr_index, $word_topic_index, $word_sentence_index, $pagenr)) {
					break;
				}
			}
		}
	}
	
	/* ------------------------------------------------------ */
}

?>