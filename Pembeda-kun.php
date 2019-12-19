<?php
	include_once 'hQuery\hquery.php';
	use duzun\hQuery;
	//include 'library\pdf2text.php';
	include "library/vendor/autoload.php";//pdf parser smalot
	
	ModeHandler();
	//Jenis Nama Doc = 1000 0100 0010 0001
	//ChanceRowObjInTxt(array("term"=>"shory","boolean"=>"0001"));
	function ModeHandler(){
		$mode = 0;
		if((isset($_GET["mode"]))){
			$mode = $_GET["mode"];
			if(isset($_GET["name"])){
				$name = $_GET["name"];
			}
		}else{
			return;
		}
		switch($mode){
			case 1:
				//mode 1 require doc enddoc mode name
				$doc = '';
				if((isset($_GET["doc"]))&&(isset($_GET["enddoc"]))){
					$docNow = $_GET["doc"];
					$enddoc = $_GET["enddoc"];
					for($i=1;$i<=$enddoc;$i++){
						if($i==$docNow){
							$doc=$doc."1";
						}else{
							$doc=$doc."0";
						}
					}
				}else{
					return;
				}
				$kv_texts = GetTextForm($name);
				if($kv_texts !== false) {
					SplitText($name,$doc,$kv_texts,300);
				} else {
					echo "Can't Read that file.";
				}
				break;
			case 2:
				//mode require mode and name
				MakeMatrixTerm($name);
				break;
			case 3:
				if(isset($_GET["row"])){
					CompareWord($_GET["row"]);
				}
				break;
			case 4:
				echo GetS_ValueResult("SValueDB.txt");
				break;
			case 5:
				if (isset($_FILES['files']) && !empty($_FILES['files'])) {
					$no_files = count($_FILES["files"]['name']);
					for ($i = 0; $i < $no_files; $i++) {
						if ($_FILES["files"]["error"][$i] > 0) {
							echo "Error: " . $_FILES["files"]["error"][$i] . "<br>";
						} else {
							move_uploaded_file($_FILES["files"]["tmp_name"][$i], $_FILES["files"]["name"][$i]);
							echo 'File successfully uploaded';
						}
					}
				} else {
					echo 'Please choose at least one file';
				}
				break;
			default:
				echo "die";
		}
	}
	function GetS_ValueResult($filename){
		$myfile = fopen($filename, "r") or dieResfresh();
		$BitsSTr = fread($myfile,filesize($filename));
		fclose($myfile);
		return $BitsSTr;
	}
	function NotBit($bit){
		$newBit = "";
		for($i=0;$i<strlen($bit);$i++){
			if($bit[$i]=='0'){
				$newBit=$newBit.'1';
			}else{
				$newBit=$newBit.'0';
			}
		}
		return $newBit;
	}
	function AndBit($bit1,$bit2){
		$newBit = "";
		for($i=0;$i<strlen($bit1);$i++){
			if(($bit1[$i]==$bit2[$i]) and ($bit1[$i]=='1')){
				$newBit=$newBit.'1';
			}else{
				$newBit=$newBit.'0';
			}
		}
		return $newBit;
	}
	function OrBit($bit1,$bit2){
		$newBit = "";
		for($i=0;$i<strlen($bit1);$i++){
			if(($bit1[$i]=='1') or ($bit2[$i]=='1')){
				$newBit=$newBit.'1';
			}else{
				$newBit=$newBit.'0';
			}
		}
		return $newBit;
	}
	function BitsToArrayNum($DBname,$bits){
		$myfile = fopen($DBname, "a+") or dieResfresh();
		if ((!file_exists($DBname))and(filesize($DBname)==0)) {
			for($i=0;$i<strlen($bits);$i++){
				fwrite($myfile,"0\n");
			}
			echo "The file $filename does not exist";
		}
		$DocS_ValTbl = fread($myfile,filesize($DBname));
		file_put_contents($DBname,"");
		$DocS_Val = explode("\n",$DocS_ValTbl);
		for($i=0;$i<strlen($bits);$i++){
			if($bits[$i]=='1'){
				//echo gettype(strval(((int)$DocS_Val[$i])+1))."<br>";
				fwrite($myfile,strval(((int)$DocS_Val[$i])+1)."\n");
			}else{
				//echo gettype($DocS_Val[$i])."<br>";
				fwrite($myfile,$DocS_Val[$i]."\n");
			}
		}
		fclose($myfile);
	}
	function GetS_Value($DBname,$bit1,$bit2){
		$BitOne = OrBit($bit1,NotBit($bit2));
		$BitTwo = OrBit(NotBit($bit1),$bit2);
		$BitThree = AndBit($bit1,$bit2);
		$Bits = OrBit(AndBit($BitOne,$BitTwo),$BitThree);
		BitsToArrayNum($DBname,$Bits);
		//fwrite($myfile, $strRow);fclose($myfile);
	}
	/* arraiRow(RowObj) = array('term' = > "contoh", 'boolean' = "10010");*/
	function SaveArrayToTxt($arraiRow){//Add new RowTerm in Database(Matrix Bool)
		$strRow = $arraiRow["term"]." ".$arraiRow["boolean"]."\n";
		$myfile = fopen('VocabularyList.txt', "a") or dieResfresh();
		fwrite($myfile, $strRow);fclose($myfile);
	}
	function MakeMatrixTerm($urlFile){//open file and make boolean matrix
		echo $urlFile;
		if ((!file_exists('VocabularyList.txt'))) {
			$myfile = fopen('VocabularyList.txt', "w") or dieResfresh();
			fclose($myfile);
		}
		$myfile = fopen($urlFile, "r") or dieResfresh();
		$str = fread($myfile,filesize($urlFile));
		fclose($myfile);
		$splitStr = explode(" ",$str);
		for($i=0;$i<count($splitStr);$i++){
			if(strlen($splitStr[$i])>2){
				if(ChanceRowObjInTxt(array("term"=>$splitStr[$i],"boolean"=>explode(" ",$urlFile)[0]))==false){
					SaveArrayToTxt(array("term"=>$splitStr[$i],"boolean"=>explode(" ",$urlFile)[0]));
				}
			}
		}
		unlink($urlFile);
	}
	function dieResfresh(){
		die("Unable to open file!");
		//header("Refresh:0");
	}
	function CompareWord($now){//$now is 0 to infinity
		$myfile = fopen('VocabularyList.txt', "r+") or dieResfresh();
		if(filesize('VocabularyList.txt')==0){
			return false;
		}
		$str = fread($myfile,filesize('VocabularyList.txt'));
		fclose($myfile);
		$splitStr = explode("\n",$str);
		if($now>count($splitStr)){
			echo "end";//must
			return false;
		}
		$lenTerm = 0;
		$TermOne = explode(" ",$splitStr[$now]);
		$breakBool = false;
		$pieceHtmlTermOne = GetFormPKcom($TermOne[0]);
		for($i=0;$i<count($splitStr);$i++){
			$Term = explode(" ",$splitStr[$i]);
			if(count($Term)==2){
				$arraySameWord = CariSama($pieceHtmlTermOne,$Term[0]);
				for($o=0;$o<count($arraySameWord);$o++){
					if($arraySameWord[$o]==$Term[0]){
						echo $Term[1]." ".$TermOne[1];
						GetS_Value("SValueDB.txt",$Term[1],$TermOne[1]);
						//do compare in binary matrix
						$breakBool = true;
						break;
					}
				}
			}
			if($breakBool){
				break;
			}
		}
		return false;
	}
	function ChanceRowObjInTxt($arrayRow){
		if ((!file_exists('VocabularyList.txt'))) {
			$myfile = fopen('VocabularyList.txt', "w") or dieResfresh();
			fclose($myfile);
		}
		$myfile = fopen('VocabularyList.txt', "r+") or dieResfresh();
		if(filesize('VocabularyList.txt')==0){
			return false;
		}
		$str = fread($myfile,filesize('VocabularyList.txt'));
		fclose($myfile);
		$splitStr = explode("\n",$str);
		$lenTerm = 0;
		for($i=0;$i<count($splitStr);$i++){
			$Term = explode(" ",$splitStr[$i]);
			if(count($Term)==2){
				if($Term[0]==$arrayRow["term"]){
					$bit = "";
					for($i=0;$i<strlen($arrayRow["boolean"]);$i++){
						if(($arrayRow["boolean"][$i]=='1')||($Term[1][$i]=='1')){
							$bit=$bit.'1';
						}else{
							$bit=$bit.'0';
						}
					}
					$str = substr($str,0,$lenTerm+strlen($arrayRow["term"])+1).$bit.substr($str,$lenTerm+strlen($arrayRow["term"])+1+strlen($arrayRow["boolean"]));
					ChanceText($str);
					return true;
				}
				$lenTerm+=strlen($splitStr[$i])+1;
			}
		}
		return false;
	}
	function ChanceText($str){//chance database vocabulary
		file_put_contents("VocabularyList.txt", $str);
	}
	function CariSama($banners,$str){//get array of the same word
		if ( $banners ) {
			$arrai = array();
			foreach($banners as $pos => $a) {
				if($a->attr('class') == "word_thesaurus"){
					$banners2 = $a->find('a[href]');
					$rowPK = false;
					foreach($banners2 as $pos2 => $b) {
						array_push($arrai,$b->text());
						if($str==$b){
							$rowPK = true;
						}
					}
					if($rowPK){
						return $arrai;
					}
				}
			}
			return $arrai;
		}
		return null;
	}
	function GetFormPKcom($str){//get list same word
		$doc = hQuery::fromHTML(get_redirect_target($str));
		$arai = [];
		$banners = $doc->find('div[class]');
		return $banners;
	}
	function GetTextForm($urlText){//get Text or docx In Server
		$getType = substr($urlText,strrpos($urlText,'.')+1);
		$str = "";
		if($getType=="txt"){//jika content file tidak sesuai dengan akhir nama(.xxx) maka ini error
			$myfile = fopen($urlText, "r+") or die("Unable to open file!");//open every file in dokumen wich get compared
			$str = fgets($myfile);
			fclose($myfile);
		}else if($getType=="docx"){
			$str = kv_read_word($urlText);
		}else if($getType=="doc"){
			$str = kv_read_word($urlText);
		}else if($getType=="pdf"){
			$str = GetPdf($urlText);
		}
		return $str;
	}
	function GetPdf($url){
		$directory = getcwd();
		$file = $url;
		$fullfile = $directory . '/' . $file;
		$content = '';
		$out = '';
		$parser = new \Smalot\PdfParser\Parser();

		$document = $parser->parseFile($fullfile);
		$pages    = $document->getPages();
		$content = "";
		for($i=0;$i<count($pages);$i++){
			$page=$pages[$i];
			$content=$content.($page->getText());
		}
		return $content;
	}
	function get_redirect_target($word){
		//API Url
		$url = 'https://www.persamaankata.com/search.php';
		//Initiate cURL.
		$ch = curl_init($url);
		//The JSON data.
		//Encode the array into JSON.
		//$jsonDataEncoded = json_encode($jsonData);//kalau gk pake json dibawah
		$jsonDataEncoded = "q=".$word."&search.x=60&search.y=37";
		//Tell cURL that we want to send a POST request.
		curl_setopt($ch, CURLOPT_POST, 1);
		//Attach our encoded JSON string to the POST fields.
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
		curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=5308bc3368f5cd831ea65c8125074148; __utmc=7124403; __utmz=7124403.1576044792.1.1.utmcsr=google|utmccn=(organic)|utmcmd=organic|utmctr=(not%20provided); __utma=7124403.890482341.1576044791.1576044791.1576044791.1; __utmt=1; __utmb=7124403.10.10.1576044792');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		//Set the content type to application/json
		curl_setopt($ch, CURLOPT_HTTPHEADER, 
			array(
			'Origin: https://www.persamaankata.com',
			'Upgrade-Insecure-Requests: 1',
			'Content-Type: application/x-www-form-urlencoded',
			'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36',
			'Sec-Fetch-User: ?1',
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3'
			)
		);
		//Execute the request
		$result = curl_exec($ch);
		return $result;
	}
	function kv_read_word($input_file){//read docx
		$kv_strip_texts = ''; 
		$kv_texts = ''; 	
		if(!$input_file || !file_exists($input_file)) return false;	
		$zip = zip_open($input_file);
		if (!$zip || is_numeric($zip)) return false;
		while ($zip_entry = zip_read($zip)) {
			if (zip_entry_open($zip, $zip_entry) == FALSE) continue;
			if (zip_entry_name($zip_entry) != "word/document.xml") continue;
			$kv_texts .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
			zip_entry_close($zip_entry);
		}
		zip_close($zip);
		$kv_texts = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $kv_texts);
		$kv_texts = str_replace('</w:r></w:p>', "\r\n", $kv_texts);
		//$kv_strip_texts = nl2br(strip_tags($kv_texts,''));
		$kv_strip_texts = strip_tags($kv_texts,'');
		return $kv_strip_texts;
	}
	function ArrayToSTr($arrai){
		$str = "";
		foreach($arrai as $pos) {
			$str=$str." ".$pos;
		}
		return $str;
	}
	function Tokenisation($str){
		$arraiTerm = array();
		$splitStr = explode(" ",$str);
		foreach($splitStr as $pos) {
			if(array_search($pos,$arraiTerm,true)==false){
				array_push($arraiTerm,$pos);
			}
		}
		return $arraiTerm;
	}
	//DocNum is Doc1 = 1000 in 4 doc
	function SplitText($name,$DocNum,$str,$lenMax){//split txt in server
		$str = preg_replace("/[^a-zA-Z0-9\s]/", " ", $str);
		$str = preg_replace("/[\n\r]/"," ",$str);
		$str = preg_replace('!\s+!', ' ', $str);
		$str = strtolower($str);
		$arraiTerm = Tokenisation($str);
		$str = ArrayToSTr($arraiTerm);
		$lenStr = strlen($str);
		$lenSisa = $lenStr%$lenMax;
		$len = ($lenStr - $lenSisa)/$lenMax;
		$Inow = 0;
		for($i=0;$i<$len;$i++){
			if(($str[($i+1)*$lenMax-1]!=' ')||($str[($i+1)*$lenMax]!=' ')){
				$akhirSubStr = strpos($str,' ',($i+1)*$lenMax);
				$splitStr = substr($str,$Inow,$akhirSubStr-$Inow);
				$Inow = $akhirSubStr;
			}else{
				$splitStr = substr($str,$Inow,$akhirSubStr-$Inow);
				$Inow = ($i+1)*$lenMax;
			}
			$myfile = fopen($DocNum." ".$name.$i, "w") or die("Unable to open file!");
			fwrite($myfile, $splitStr);
			fclose($myfile);
		}
		$myfile = fopen($DocNum." ".$name.$len, "w") or die("Unable to open file!");
		fwrite($myfile, substr($str,$Inow));
		fclose($myfile);
		unlink($name);
		echo $len;//must, essential to make this piece of Sh*t works
	}
?>