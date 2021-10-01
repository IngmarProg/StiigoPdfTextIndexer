<?php

namespace Indexing;
use \PDO;

class DbWorker {
	protected $db = null;	
		
	/* ------------------------------------------------------ */

	function __construct($charset = "utf8") {
		// $this->db = new PDO("mysql:host=".$servername.";dbname=".$dbname.";charset=utf8",mysql_usr,mysql_pwd);

		$this->db = new PDO("mysql:host=".mysql_host.";dbname=".mysql_db, mysql_usr, mysql_pwd, 
			array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES ".$charset, PDO::ATTR_PERSISTENT => true));
		$this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
    }
	
	/* ------------------------------------------------------ */	
	
	public function resetData() {		
		$stmt = $this->db->prepare("truncate table stiigo_pdf_files");
		$stmt->execute();
				
		$stmt = $this->db->prepare("truncate table stiigo_pdf_rvindex");
		$stmt->execute();
		
		$stmt = $this->db->prepare("truncate table stiigo_pdf_words");
		$stmt->execute();

		$stmt = $this->db->prepare("truncate table stiigo_pdf_files_raw");
		$stmt->execute();
	}
	
	/* ------------------------------------------------------ */
	
	public function addWord($word) {
		$stmt = $this->db->prepare("INSERT INTO stiigo_pdf_words(word) VALUES(LOWER(?)) ON DUPLICATE KEY UPDATE word_id=LAST_INSERT_ID(word_id), word=VALUES(word)");
		if (!$stmt) {
			$this->err = $stmt->errorInfo();
			return -1;
		}
		
		$stmt->bindParam(1, $word, PDO::PARAM_STR, strlen($word));
		$stmt->execute();
		$this->err = $stmt->errorInfo();
		if (intval($this->err[0])) {
			return -1;
		}
						
		return $this->db->lastInsertId(); 
	}
	
	/* ------------------------------------------------------ */
	
	public function writeBufferedWords($bufferedwords) {
		$wordlist = "";
		$sql = " INSERT IGNORE INTO stiigo_pdf_words(word) VALUES ";
		$separator = "";
		foreach($bufferedwords as $kword => $kwordid) {
			$sql.= $separator."(".chr(39).addslashes($kword).chr(39).")";
			$wordlist.= $separator.chr(39).addslashes($kword).chr(39);
			$separator = ",";
		}
		
		if ($wordlist == "") {
			return $bufferedwords;
		}
		
		$stmt = $this->db->prepare($sql);		
		if (!$stmt) {
			$this->err = $stmt->errorInfo();
			return $bufferedwords;
		}
		$stmt->execute();
		
		
		$stmt = $this->db->prepare("SELECT * FROM stiigo_pdf_words WHERE word IN (".$wordlist.")");		
		$this->err = $stmt->errorInfo();
		if (intval($this->err[0])) {
			return $retval;
		}			
		$stmt->execute();
				
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$bufferedwords[$row["word"]] = (int)$row["word_id"];
		}
				
		return $bufferedwords;
	}
	
	/* ------------------------------------------------------ */	

	public function addRvIndex($wdid, $wdfpos, $wdspflags, $wdparaindx, $wdtopicindx, $wdsentindx, $fileid ) {
		$stmt = $this->db->prepare("INSERT INTO stiigo_pdf_rvindex(word_id,word_fpos,word_spflags,word_paragr_indx,word_topic_indx,word_sent_indx,file_id) "
			." VALUES(?,?,?,?,?,?,?) ");
		if (!$stmt) {
			$this->err = $stmt->errorInfo();
			return -1;
		}
		
		$stmt->bindParam(1, $wdid, PDO::PARAM_INT);
		$stmt->bindParam(2, $wdfpos, PDO::PARAM_INT);
		$stmt->bindParam(3, $wdspflags, PDO::PARAM_INT);
		$stmt->bindParam(4, $wdparaindx, PDO::PARAM_INT);
		$stmt->bindParam(5, $wdtopicindx, PDO::PARAM_INT);
		$stmt->bindParam(6, $wdsentindx, PDO::PARAM_INT);
		$stmt->bindParam(7, $fileid, PDO::PARAM_INT);
		$stmt->execute();
		$this->err = $stmt->errorInfo();
		if (intval($this->err[0])) {			
			return -1;
		}
		
		$this->err = $stmt->errorInfo();
		return (intval($this->err[0]));			
	}		
	
	/* ------------------------------------------------------ */
	
	public function writeBufferedRvIndex($bufferedrv) {
		$sql = "INSERT IGNORE INTO stiigo_pdf_rvindex(word_id,word_fpos,word_spflags,word_paragr_indx,word_topic_indx,word_sent_indx,file_id,pg_nr) VALUES";
		$separator = "";
		foreach($bufferedrv as $v) {
			$sql.= $separator
				."(".$v["word_id"].",".$v["startidx"].",".$v["wordflags"].","
				.$v["wordparaindx"].",".$v["wordtopicindx"].","
				.$v["wordsentindx"].",".$v["fileid"].",".$v["pgnr"].")";
			$separator = ",";				
		}
		
		$stmt = $this->db->prepare($sql);
		if (!$stmt) {
			$this->err = $stmt->errorInfo();
			return -1;
		}
		
		$stmt->execute();
		$this->err = $stmt->errorInfo();
		if (intval($this->err[0])) {			
			return -1;
		}
		
		return 1;
	}
	
	/* ------------------------------------------------------ */
	
	public function generateFileDescriptor($filename, $md5, $year, $userid, $author = "", $descr = "") {
		$stmt = $this->db->prepare("INSERT INTO stiigo_pdf_files(filename, year, `md5`, author, descr, user_id) "
			." VALUES(?,?,?,?,?,?) ");		
		$stmt->bindParam(1, $filename, PDO::PARAM_STR, strlen($filename));			
		$stmt->bindParam(2, $year, PDO::PARAM_STR, strlen($year));
		$stmt->bindParam(3, $md5, PDO::PARAM_STR, strlen($md5));
		$stmt->bindParam(4, $author, PDO::PARAM_STR, strlen($author));
		$stmt->bindParam(5, $descr, PDO::PARAM_STR, strlen($descr));
		$stmt->bindParam(6, $userid, PDO::PARAM_INT);		
		$stmt->execute();		
		$this->err = $stmt->errorInfo();
		if (intval($this->err[0])) {			
			return -1;
		}
		
				
		return (int)$this->db->lastInsertId();		
	}
	
	/* ------------------------------------------------------ */
	// $text = ucs2
	public function writeRawFile($fileid, $text) {
				
		$stmt = $this->db->prepare("INSERT INTO stiigo_pdf_files_raw(file_id,raw_text_uc2) "
			." VALUES(?,?) ");
		$stmt->bindParam(1, $fileid, PDO::PARAM_INT);			
		$stmt->bindParam(2, $text, PDO::PARAM_STR, strlen($text));
		$stmt->execute();
		$this->err = $stmt->errorInfo();
		if (intval($this->err[0])) {			
			return -1;
		}
	}
	
	/* ------------------------------------------------------ */
	
	public function getTextRange($fileid, $rangestart, $rangeend) {
		$len = ((int)$rangeend - (int)$rangestart) + 1;
		$sql = ""
			// ." SELECT CONVERT(SUBSTRING(raw_text_uc2 FROM ".$rangestart." FOR ".$len.") USING UTF8)  AS tpart  "
			." SELECT SUBSTRING(raw_text_uc2 FROM ".$rangestart." FOR ".$len.") AS tpart  "
			." FROM stiigo_pdf_files_raw "
			." WHERE `file_id`=".$fileid;
		
		$stmt = $this->db->prepare($sql);
		$stmt->execute();
		$this->err = $stmt->errorInfo();
		
		if (intval($this->err[0])) {			
			return "";			
		}		
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return (!empty($row["tpart"]) ? trim($row["tpart"]) : "");
	}
	
	/* ------------------------------------------------------ */
	
	public function getKeywords($q, $matchtype = 0) {
		$retval = array();
		$stmt = $this->db->prepare("SELECT * FROM stiigo_pdf_words WHERE word LIKE ? LIMIT ".max_search_keywords);		
		$qmatch = $q."%";
		$stmt->bindParam(1, $qmatch, PDO::PARAM_STR, strlen($qmatch));
		$stmt->execute();
		$this->err = $stmt->errorInfo();
		if (intval($this->err[0])) {			
			return $retval;			
		}	
		
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$retval[] = array("id" => $row["word_id"], "word" => $row["word"]);
		}
		
		return $retval;
	}

	/* ------------------------------------------------------ */

	protected function getDiscoverKeywordRange($wordid, $sql) {
		$retval = array();
		$stmt = $this->db->prepare($sql);			
		$stmt->bindParam(1, $wordid, PDO::PARAM_INT);
		$stmt->execute();
		$this->err = $stmt->errorInfo();
		if (intval($this->err[0])) {			
			return $retval;			
		}				
		
		$corrdelta = 64;
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$last = (int)$row["endpos"]; // eelviimase sõna algus !
			$newsetstart = (int)$row["endpos2"];
			if ($newsetstart == 0) {
				$last+= $corrdelta;
			} elseif (($newsetstart - $last) < $corrdelta) {
				$last = $newsetstart - 1;
			}
			
			$retval[] = array("startpos" => $row["startpos"], "endpos" => $last, "fileid" => $row["file_id"], "pg_nr" => $row["pg_nr"]);		
		}
				
		return $retval;				
	}
	
	/* ------------------------------------------------------ */
	
	public function getKeywordParagrRange($wordid) {		
		$sql = ""
			." SELECT (SELECT MIN(w0.word_fpos) FROM stiigo_pdf_rvindex w0 WHERE w0.word_paragr_indx = sub.word_paragr_indx + 1 AND w0.file_id = sub.file_id) AS endpos2,"
			." sub.startpos, sub.endpos, sub.word_paragr_indx, sub.file_id, sub.pg_nr "				
			." FROM ( "
			." SELECT MIN(wi.word_fpos) as startpos, MAX(wi.word_fpos) as endpos, wi.word_paragr_indx, wi.file_id, wi.pg_nr "
			." FROM stiigo_pdf_rvindex wi "
			." INNER JOIN (SELECT DISTINCT r.word_paragr_indx, r.file_id FROM stiigo_pdf_rvindex r WHERE r.word_id = ? AND r.word_paragr_indx > 0) AS sgrp ON "
			." wi.file_id = sgrp.file_id AND wi.word_paragr_indx = sgrp.word_paragr_indx "
			." GROUP BY wi.word_paragr_indx, wi.file_id, wi.pg_nr"
			." ) as sub "
			." LIMIT ".max_searchdata_rows;
						
		return $this->getDiscoverKeywordRange($wordid, $sql);			
	}
	
	/* ------------------------------------------------------ */
	
	public function getKeywordSentRange($wordid) {		
		$sql = ""			
			." SELECT (SELECT MIN(w0.word_fpos) FROM stiigo_pdf_rvindex w0 WHERE w0.word_sent_indx = sub.word_sent_indx + 1 AND w0.file_id = sub.file_id) AS endpos2,"
			." sub.startpos, sub.endpos, sub.word_sent_indx, sub.file_id, sub.pg_nr "
			." FROM ( "
			." SELECT MIN(wi.word_fpos) as startpos, MAX(wi.word_fpos) as endpos, wi.word_sent_indx, wi.file_id, wi.pg_nr "
			." FROM stiigo_pdf_rvindex wi "
			." INNER JOIN (SELECT DISTINCT r.word_sent_indx, r.file_id FROM stiigo_pdf_rvindex r WHERE r.word_id = ? AND r.word_sent_indx > 0) AS sgrp ON "
			." wi.file_id = sgrp.file_id AND wi.word_sent_indx = sgrp.word_sent_indx "
			." GROUP BY wi.word_sent_indx, wi.file_id, wi.pg_nr "
			." ) as sub"
			." LIMIT ".max_searchdata_rows;
	
		return $this->getDiscoverKeywordRange($wordid, $sql);	
	}
	
	/* ------------------------------------------------------ */
	
	public function getKeywordTopicRange($wordid) {
		$retval = array();
		$stmt = $this->db->prepare(""
			." SELECT MIN(wi.word_fpos) as startpos, MAX(wi.word_fpos) as endpos, wi.word_topic_indx, wi.file_id, wi.pg_nr "
			." FROM stiigo_pdf_rvindex wi "
			." INNER JOIN (SELECT DISTINCT r.word_topic_indx, r.file_id FROM stiigo_pdf_rvindex r WHERE r.word_id = ? AND r.word_topic_indx > 0) AS sgrp ON "
			." wi.file_id = sgrp.file_id AND wi.word_topic_indx = sgrp.word_topic_indx "
			." GROUP BY wi.word_topic_indx, wi.file_id, wi.pg_nr"
			." LIMIT ".max_searchdata_rows);
		$stmt->bindParam(1, $wordid, PDO::PARAM_INT);
		$stmt->execute();
		$this->err = $stmt->errorInfo();
		if (intval($this->err[0])) {			
			return $retval;			
		}				
		
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$retval[] = array("startpos" => $row["startpos"], "endpos" => $row["endpos"], "fileid" => $row["file_id"], "pg_nr" => $row["pg_nr"]);		
		}
		
		return $retval;
	}
	
	/* ------------------------------------------------------ */

	public function getFileInfo($fileid) {
		$retval = array();
		$stmt = $this->db->prepare("SELECT filename,year,author,descr FROM stiigo_pdf_files WHERE id=?");		
		$stmt->bindParam(1, $fileid, PDO::PARAM_INT);
		$stmt->execute();
		$this->err = $stmt->errorInfo();
		if (intval($this->err[0])) {			
			return $retval;			
		}				
		
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$retval = array("filename" => $row["filename"], "year" => $row["year"], "author" => $row["author"], "descr" => $row["descr"]);		
				
		return $retval;		
	}
	
	/* ------------------------------------------------------ */
	
	public function getWord($wordid) {		
		$stmt = $this->db->prepare("SELECT word FROM stiigo_pdf_words WHERE word_id=?");		
		$stmt->bindParam(1, $wordid, PDO::PARAM_INT);
		$stmt->execute();
		$this->err = $stmt->errorInfo();
		if (intval($this->err[0])) {			
			return "";			
		}				
		
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return  $row["word"];
	}
	/* ------------------------------------------------------ */
	
	function __destruct() {     
		$this->db=null;		
    }
}

?>