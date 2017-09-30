<?php
include_once('Manager.php');
include_once('ImageShared.php');

class ImageCleaner extends Manager{
	
	private $collid;
	private $tidArr = array();

	function __construct() {
		parent::__construct(null,'write');
		$this->verboseMode = 2;
		set_time_limit(2000);
	}

	function __destruct(){
		parent::__destruct();
	}

	//Thumbnail building tools
	public function getReportArr(){
		$retArr = array();
		
		$sql = 'SELECT c.collid, CONCAT_WS("-",c.institutioncode,c.collectioncode) as collcode, c.collectionname, count(DISTINCT i.imgid) AS cnt '. 
			'FROM images i LEFT JOIN omoccurrences o ON i.occid = o.occid '.
			'LEFT JOIN omcollections c ON o.collid = c.collid ';
		if($this->tidArr){
			$sql .= 'INNER JOIN taxaenumtree e ON i.tid = e.tid ';
		}
		$sql .= $this->getSqlWhere().
			'GROUP BY c.collid ORDER BY c.collectionname';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$id = $r->collid;
			$name = $r->collectionname.' ('.$r->collcode.')';
			if(!$id){
				$id = 0;
				$name = 'Field images (not linked to specimens)';
			}
			$retArr[$id]['name'] = $name;
			$retArr[$id]['cnt'] = $r->cnt;
		}
		$rs->free();
		if(array_key_exists(0, $retArr)){
			$tempArr = $retArr[0];
			unset($retArr[0]);
			$retArr[0] = $tempArr;
		}
		return $retArr;
	}

	public function buildThumbnailImages(){
		//Process images linked to collections
		if($this->collid){
			$sql = 'SELECT DISTINCT c.collid, CONCAT_WS("_",c.institutioncode, c.collectioncode) AS code, c.collectionname '.
				'FROM omcollections c '.
				'WHERE c.collid = '.$this->collid;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->logOrEcho('Processing Collection: '.$r->collectionname,0,'div');
				$this->collid = $r->collid;
				$this->buildImages($r->code.'/');
			}
			$rs->free();
		}
		else{
			if($this->tidArr){
				$this->logOrEcho('Processing images for taxon #'.$this->tidArr[0],0,'div');
			}
			else{
				$this->logOrEcho('Processing field images (not linked to specimens)',0,'div');
			}
			$this->buildImages('misc/'.date('Ym').'/');
		}
	}

	private function buildImages($targetPath){
		$imgManager = new ImageShared();
		$sql = 'SELECT DISTINCT i.imgid, i.url, i.originalurl, i.thumbnailurl, i.format ';
		if($this->collid){
			$sql .= ', o.catalognumber FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid ';
		}
		else{
			$sql .= 'FROM images i ';
		}
		if($this->tidArr){
			$sql .= 'INNER JOIN taxaenumtree e ON i.tid = e.tid ';
		}
		$sql .= $this->getSqlWhere().'ORDER BY RAND()';
		//echo $sql; exit;
		$result = $this->conn->query($sql);
		$cnt = 1;
		if($this->verboseMode > 1) echo '<ul style="margin-left:15px;">';
		while($row = $result->fetch_object()){
			$status = true;
			$webIsEmpty = false;
			$imgId = $row->imgid;
			$this->logOrEcho($cnt.': Building thumbnail: <a href="../imgdetails.php?imgid='.$imgId.'" target="_blank">'.$imgId.'</a>...');
			$this->conn->autocommit(false);
			//Tag for updating; needed to ensure two parallel processes are not processing the same image
			$testSql = 'SELECT thumbnailurl, url FROM images WHERE (imgid = '.$imgId.') FOR UPDATE ';
			$textRS = $this->conn->query($testSql);
			if($testR = $textRS->fetch_object()){
				if(!$testR->thumbnailurl || (substr($testR->thumbnailurl,0,10) == 'processing' && $testR->thumbnailurl != 'processing '.date('Y-m-d'))){
					$tagSql = 'UPDATE images SET thumbnailurl = "processing '.date('Y-m-d').'" '.
						'WHERE (imgid = '.$imgId.')';
					$this->conn->query($tagSql);
				}
				elseif($testR->url == 'empty' || (substr($testR->url,0,10) == 'processing' && $testR->url != 'processing '.date('Y-m-d'))){
					$tagSql = 'UPDATE images SET url = "processing '.date('Y-m-d').'" '.
						'WHERE (imgid = '.$imgId.')';
					$this->conn->query($tagSql);
				}
				else{
					//Records already processed by a parallel running process, thus go to next record
					$this->logOrEcho('Already being handled by a parallel running processs',1);
					$textRS->free();
					$this->conn->commit();
					$this->conn->autocommit(true);
					continue;
				}
			}
			$textRS->free();
			$this->conn->commit();
			$this->conn->autocommit(true);

			//Build target path
			$finalPath = $targetPath;
			if($this->collid){
				$catNum = $row->catalognumber;
				if($catNum){
					$catNum = str_replace(array('/','\\',' '), '', $catNum);
					if(preg_match('/^(\D{0,8}\d{4,})/', $catNum, $m)){
						$catPath = substr($m[1], 0, -3);
						if(is_numeric($catPath) && strlen($catPath)<5) $catPath = str_pad($catPath, 5, "0", STR_PAD_LEFT);
						$finalPath .= $catPath.'/';
					}
					else{
						$finalPath .= '00000/';
					}
				}
				else{
					$finalPath .= date('Ym').'/';
				}
			}
			$imgManager->setTargetPath($finalPath);
			
			$imgUrl = trim($row->url);
			if((!$imgUrl || $imgUrl == 'empty') && $row->originalurl){
				$imgUrl = trim($row->originalurl);
				$webIsEmpty = true;
			}
			if($imgManager->parseUrl($imgUrl)){
				//Create thumbnail
				$imgTnUrl = '';
				if(!$row->thumbnailurl || substr($testR->thumbnailurl,0,10) == 'processing'){
					if($imgManager->createNewImage('_tn',$imgManager->getTnPixWidth(),70)){
						$imgTnUrl = $imgManager->getUrlBase().$imgManager->getImgName().'_tn.jpg';
					}
					else{
						$this->errorMessage = 'ERROR building thumbnail: '.$imgManager->getErrStr();
						$errSql = 'UPDATE images SET thumbnailurl = "bad url" WHERE thumbnailurl IS NULL AND imgid = '.$imgId;
						$this->conn->query($errSql);
						$status = false;
					}
				}
				else{
					$imgTnUrl = $row->thumbnailurl;
				}
				
				if($status && $imgTnUrl && $imgManager->uriExists($imgTnUrl)){
					$webFullUrl = '';
					$lgFullUrl = '';
					//If web image is too large, transfer to large image and create new web image
					list($sourceWidth, $sourceHeight) = getimagesize(str_replace(' ', '%20', $imgManager->getSourcePath()));
					if(!$webIsEmpty && !$row->originalurl){
						$fileSize = $imgManager->getSourceFileSize();
						if($fileSize > $imgManager->getWebFileSizeLimit() || $sourceWidth > ($imgManager->getWebPixWidth()*1.2)){
							$lgFullUrl = $imgManager->getSourcePath();
							$webIsEmpty = true;
						}
					}
					if($webIsEmpty){
						if($sourceWidth && $sourceWidth < $imgManager->getWebPixWidth()){
							if(copy($imgManager->getSourcePath(),$imgManager->getTargetPath().$imgManager->getImgName().'_web'.$imgManager->getImgExt())){
								$webFullUrl = $imgManager->getUrlBase().$imgManager->getImgName().'_web'.$imgManager->getImgExt();
							}
						}
						if(!$webFullUrl){
							if($imgManager->createNewImage('_web',$imgManager->getWebPixWidth())){
								$webFullUrl = $imgManager->getUrlBase().$imgManager->getImgName().'_web.jpg';
							}
						}
					}
	
					$sql = 'UPDATE images ti SET ti.thumbnailurl = "'.$imgTnUrl.'" ';
					if($webFullUrl){
						$sql .= ',url = "'.$webFullUrl.'" ';
					}
					if($lgFullUrl){
						$sql .= ',originalurl = "'.$lgFullUrl.'" ';
					}
					if(!$row->format && $imgManager->getFormat()){
						$sql .= ',format = "'.$imgManager->getFormat().'" ';
					}
					$sql .= "WHERE ti.imgid = ".$imgId;
					//echo $sql; 
					if(!$this->conn->query($sql)){
						$this->errorMessage = 'ERROR: thumbnail created but failed to update database: '.$this->conn->error;
						$this->logOrEcho($this->errorMessage,1);
						$status = false;
					}
				}
				$imgManager->reset();
			}
			else{
				$this->errorMessage= 'ERROR: unable to parse source image ('.$imgUrl.')';
				//$this->logOrEcho($this->errorMessage,1);
				$status = false;
			}
			if(!$status) $this->logOrEcho($this->errorMessage,1);
			$cnt++;
		}
		$result->free();
		if($this->verboseMode > 1) echo '</ul>';
	}

	private function getSqlWhere(){
		$sql = 'WHERE ((i.thumbnailurl IS NULL) OR (i.url = "empty")) ';
		if($this->collid) $sql .= 'AND (o.collid = '.$this->collid.') ';
		elseif($this->collid === '0') $sql .= 'AND (i.occid IS NULL) ';
		if($this->tidArr) $sql .= 'AND (e.taxauthid = 1) AND (i.tid IN('.implode(',',$this->tidArr).') OR e.parenttid IN('.implode(',',$this->tidArr).')) ';
		return $sql;
	}

	public function resetProcessing(){
		$sqlTN = 'UPDATE images SET thumbnailurl = NULL '.
			'WHERE (thumbnailurl = "") OR (thumbnailurl = "bad url") OR (thumbnailurl LIKE "processing %" AND thumbnailurl != "processing '.date('Y-m-d').'") ';
		$this->conn->query($sqlTN);
		$sqlWeb = 'UPDATE images SET url = "empty" '.
			'WHERE (url = "") OR (url LIKE "processing %" AND url != "processing '.date('Y-m-d').'") ';
		$this->conn->query($sqlWeb);
	}

	//Test and refresh image thumbnails for remote images
	public function getRemoteImageCnt($postArr){
		$retCnt = 0;
		if($this->collid){
			$sql = 'SELECT COUNT(i.imgid) AS cnt '.$this->getRemoteImageSql($postArr);
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retCnt = $r->cnt;
			}
			$rs->free();
		}
		return $retCnt;
	}

	public function hasRemoteImages(){
		$retBool = false;
		$domain = $_SERVER['HTTP_HOST'];
		$sql = 'SELECT i.imgid '.
			'FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
			'WHERE (o.collid = '.$this->collid.') AND (i.thumbnailurl LIKE "%'.$domain.'/%" OR i.thumbnailurl LIKE "/%") '.
			'AND IFNULL(i.originalurl,url) LIKE "http%" AND (IFNULL(i.originalurl,url) NOT LIKE "%'.$domain.'/%") '.
			'LIMIT 1';
		//echo $sql;
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$retBool = true;
		}
		$rs->free();
		return $retBool;
	}

	public function refreshThumbnails($postArr){
		$imgManager = new ImageShared();
		$sql = 'SELECT o.occid, o.catalognumber, i.imgid, i.url, i.thumbnailurl, i.originalurl '.$this->getRemoteImageSql($postArr);
		//echo $sql.'<br/>'; 
		$rs = $this->conn->query($sql);
		$cnt = 1;
		while($r = $rs->fetch_object()){
			$startTime = time();
			$sourceUrl = '';
			$originalIsBase = true;
			if($r->originalurl){
				$sourceUrl = $r->originalurl;
			}
			else{
				$sourceUrl = $r->url;
				$originalIsBase = false;
			}
			$this->logOrEcho($cnt.'. Rebuilding thumbnail: <a href="../imgdetails.php?imgid='.$r->imgid.'" target="_blank">'.$r->imgid.'</a> [cat#: '.$r->catalognumber.']...',0,'div');
			$tnUrlUpdate = '';
			$webUrlUpdate = '';
			$thumbnailUrl = $r->thumbnailurl;
			if(substr($thumbnailUrl, 0, 4) == 'http'){
				$thumbnailUrl = parse_url($thumbnailUrl, PHP_URL_PATH);
			}
			$tsSource = $this->getRemoteModifiedTime($sourceUrl);
			if(strpos($thumbnailUrl, $GLOBALS['IMAGE_ROOT_URL']) === 0){
				$tnPath = $GLOBALS['IMAGE_ROOT_PATH'].substr($thumbnailUrl,strlen($GLOBALS['IMAGE_ROOT_URL']));
				if($p = strpos($tnPath,'?')) $tnPath = substr($tnPath,0,$p);
				if(is_writable($tnPath)){
					$tsThumbnail = filemtime($tnPath);
					//echo 'orig file modified: '.date ("Y-m-d H:i:s.", $tsSource).'<br/>';
					//echo 'tn file modified: '.date ("Y-m-d H:i:s.", $tsThumbnail).'<br/>';
					if($tsSource > $tsThumbnail){
						if($imgManager->parseUrl($sourceUrl)){
							unlink($tnPath);
							if($imgManager->createNewImage('',$imgManager->getTnPixWidth(),70,$tnPath)){
								$tnUrlUpdate = $r->thumbnailurl;
								$this->logOrEcho('Thumbnail rebuilt and refreshed',1);
							}
							if($originalIsBase){
								$webUrl = $r->url;
								if(substr($webUrl, 0, 4) == 'http'){
									$webUrl = parse_url($webUrl, PHP_URL_PATH);
								}
								if(strpos($webUrl, $GLOBALS['IMAGE_ROOT_URL']) === 0){
									$webPath = $GLOBALS['IMAGE_ROOT_PATH'].substr($webUrl,strlen($GLOBALS['IMAGE_ROOT_URL']));
									if($p = strpos($webPath,'?')) $webPath = substr($webPath,0,$p);
									if(is_writable($webPath)){
										//echo 'web file modified: '.date ("Y-m-d H:i:s.", filemtime($webPath)).'<br/>';
										$tsWeb = filemtime($webPath);
										if($tsSource > $tsWeb){
											unlink($webPath);
											if($imgManager->createNewImage('',$imgManager->getWebPixWidth(),0,$webPath)){
												$webUrlUpdate = $r->url;
												$this->logOrEcho('Basic web image rebuilt and refreshed',1);
											}
										}
									}
									else{
										$this->logOrEcho('Unable to rebuild basic web image: image file not writable',1);
									}
								}
							}
						}
					}
					else{
						$this->logOrEcho('Image derivatives are newer than source file: image rebuild skipped',1);
					}
				}
				else{
					$this->logOrEcho('ERROR rebuilding thumbnail: image file not writable',1);
				}
			}
			if($tnUrlUpdate) $this->updateImageRecord($r->imgid, $tnUrlUpdate, $webUrlUpdate);
			$imgManager->reset();
			$cnt++;
		}
		$rs->free();
		if($cnt == 1) $this->logOrEcho('<b>There are no images that match set criteria</b>',0,'div');
	}

	private function getRemoteImageSql($postArr){
		$domain = $_SERVER['HTTP_HOST'];
		$sql = 'FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
			'WHERE (o.collid = '.$this->collid.') AND (i.thumbnailurl LIKE "%'.$domain.'/%" OR i.thumbnailurl LIKE "/%") '.
			'AND IFNULL(i.originalurl,url) LIKE "http%" AND IFNULL(i.originalurl,url) NOT LIKE "%'.$domain.'/%" ';
		if(array_key_exists('catNumHigh', $postArr) && $postArr['catNumHigh']){
			// Catalog numbers are given as a range
			if(is_numeric($postArr['catNumLow']) && is_numeric($postArr['catNumHigh'])){
				$sql .= 'AND (o.catalognumber BETWEEN '.$postArr['catNumLow'].' AND '.$postArr['catNumHigh'].') ';
			}
			else{
				$sql .= 'AND (o.catalognumber BETWEEN "'.$postArr['catNumLow'].'" AND "'.$postArr['catNumHigh'].'") ';
			}
		}
		elseif(array_key_exists('catNumLow', $postArr) && $postArr['catNumLow']){
			// Catalog numbers are given as a single value
			$sql .= 'AND (o.catalognumber = "'.$postArr['catNumLow'].'") ';
		}
		elseif(array_key_exists('catNumList', $postArr) && $postArr['catNumList']){
			$catNumList = preg_replace('/\s+/','","',str_replace(array("\r\n","\r","\n",','),' ',trim($postArr['catNumList'])));
			if($catNumList) $sql .= 'AND (o.catalognumber IN("'.$catNumList.'")) ';
		}
		return $sql;
	}

	private function getRemoteModifiedTime($filePath){
		$curl = curl_init($filePath);
		//Fetch only the header
		curl_setopt($curl, CURLOPT_NOBODY, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		// attempt to retrieve the modification date
		curl_setopt($curl, CURLOPT_FILETIME, true);
		
		$curlResult = curl_exec($curl);
		
		if($curlResult === false){
			$this->logOrEcho('ERROR retrieving modified date of original image file: '.curl_error($curl),1);
			return false;
		}
		
		$ts = curl_getinfo($curl, CURLINFO_FILETIME);
		if($ts != -1) return $ts;
		return false;
	}

	private function updateImageRecord($imgid, $tnUrl, $webUrl){
		$verTag = date('ymd');
		$sql = 'UPDATE images SET thumbnailurl = "'.$tnUrl.'?ver='.$verTag.'" ';
		if($webUrl) $sql .= ', url = "'.$webUrl.'?ver='.$verTag.'" ';
		$sql .= 'WHERE (imgid = '.$imgid.')';
		if(!$this->conn->query($sql)){
			$this->logOrEcho('ERROR updating image record: '.$this->conn->error);
		}
	}

	//URL testing 
	public function testByCollid(){
		$sql = 'SELECT i.imgid, i.url, i.thumbnailurl, i.originalurl '.
			'FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
			'WHERE o.collid IN('.$this->collid.')';
		return $this->testUrls($sql);
	}
	
	public function testByImgid($imgidStr){
		
		
	}
	
	private function testUrls($sql){
		$status = true;
		$badUrlArr = array();
		if(!$sql){
			$this->errorMessage= 'SQL string is NULL';
			return false;
		}
		$imgManager = new ImageShared();
		$rs = $this->conn->query($sql);
		if($rs){
			while($r = $rs->fetch_object()){
				if(!$imgManager->uriExists($r->url)) $badUrlArr[$r->imgid]['url'] = $r->url;
				if(!$imgManager->uriExists($r->thumbnailurl)) $badUrlArr[$r->imgid]['tn'] = $r->thumbnailurl;
				if(!$imgManager->uriExists($r->originalurl)) $badUrlArr[$r->imgid]['lg'] = $r->originalurl;
			}
			$rs->free();
		}
		else{
			$this->errorMessage= 'Issue with connection or SQL: '.$sql;
			return false;
		}
		//Output results (needs to be extended)
		foreach($badUrlArr as $imgid => $badUrls){
			echo $imgid.', ';
			echo (isset($badUrls['url'])?$badUrls['url']:'').',';
			echo (isset($badUrls['tn'])?$badUrls['tn']:'').',';
			echo (isset($badUrls['lg'])?$badUrls['lg']:'').',';
			echo '<br/>';
		}
		return $status;
	}

	//Setters and getters
	public function setCollid($id){
		if(is_numeric($id)){
			$this->collid = $id;
		}
	}
	
	public function setTid($id){
		if(is_numeric($id)){
			$this->tidArr[] = $id;
			$sql = 'SELECT DISTINCT ts.tid '.
				'FROM taxstatus ts INNER JOIN taxstatus ts2 ON ts.tidaccepted = ts2.tidaccepted '.
				'WHERE (ts.taxauthid = 1) AND (ts2.taxauthid = 1) AND (ts2.tid = '.$id.')';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				if($r->tid != $id) $this->tidArr[] = $r->tid;
			}
			$rs->free();
		}
	}
	
	public function getSciname(){
		$sciname = '';
		if($this->tidArr){
			$sql = 'SELECT sciname FROM taxa WHERE (tid = '.$this->tidArr[0].')';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$sciname = $r->sciname;
			}
			$rs->free();
		}
		return $sciname;
	}
}
?>