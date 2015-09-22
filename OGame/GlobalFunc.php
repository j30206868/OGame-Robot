<?php
	class GlobalFunc{
		public static function getEventList(){
			$Config = ConfigManager::declareConfigValue();
			$EVENTLIST_ADDR = $Config['EVENTLIST_ADDR'];
			$COOKIE_FILE = $Config['COOKIE_FILE'];

			return GlobalFunc::httpGet($EVENTLIST_ADDR, $COOKIE_FILE);
		}

		public static function fwrite_stream($fp, $string) {
		    for ($written = 0; $written < strlen($string); $written += $fwrite) {
		        $fwrite = fwrite($fp, substr($string, $written));
		        if ($fwrite === false) {
		            return $written;
		        }
		    }
		    return $written;
		}

		public static function getOverviewPage(){
			$Config = ConfigManager::declareConfigValue();
			$OVERVIEW_URL = $Config['OVERVIEW_URL'];
			$COOKIE_FILE = $Config['COOKIE_FILE'];

			return GlobalFunc::httpGet($OVERVIEW_URL, $COOKIE_FILE);
		}

		public static function changeFocusPlanet( $href ){
			$Config = ConfigManager::declareConfigValue();
			$COOKIE_FILE = $Config['COOKIE_FILE'];

			return GlobalFunc::httpGet( $href, $COOKIE_FILE );
		}

		public static function uriToJson( $uri ){
			$uri_attr = explode("&", $uri);
			$data;
			foreach ($uri_attr as $key => $value) {
				$keyandvalue = explode("=", $value);
				if( isset($keyandvalue[0]) && isset($keyandvalue[1]) ){
					$data[ $keyandvalue[0] ] = $keyandvalue [1];
				}
			}

			return $data;
		}

		public static function strToIntByTakeCommaOff( $str ){
			$result = "";
			for($i=0 ; $i<strlen($str) ; $i++){
				if( $str[$i] != ','){
					$result .= $str[$i];
				}
			}
			return $result;
		}

		public static function getResources( $any_page ){ // feed html str not dom element
			$page = GlobalFunc::loadHtml( $any_page );

			$resource;

			$d_dom = $page->getElementById("resources_deuterium");
			$c_dom = $page->getElementById("resources_crystal");
			$m_dom = $page->getElementById("resources_metal");

			if( !is_object($d_dom) ){
				echo "Error occur, getResources {resources_deuterium, resources_crystal, resources_metal} not found, return &.\n";
				return "&";
			}
			
			$deuterium = $d_dom->textContent;
			$crystal = $c_dom->textContent;
			$metal = $m_dom->textContent;
			
			$resource['deuterium'] = GlobalFunc::strToIntByTakeCommaOff( trim($deuterium) );
			$resource['crystal'] = GlobalFunc::strToIntByTakeCommaOff( trim($crystal) );
			$resource['metal'] = GlobalFunc::strToIntByTakeCommaOff( trim($metal) );

			return $resource;
		}

		public static function getCoordArrayByStr( $str ){
			$str = trim($str);
			$len = strlen($str);

			$str = substr($str, 1, $len-2);
			return explode(":", $str);
		}

		public static function loadHtml( $html_str ){
			$dom = new DOMDocument();
			//with @ to avoid charater convertion problem
			//refer to answers at website
			//http://stackoverflow.com/questions/1685277/warning-domdocumentloadhtml-htmlparseentityref-expecting-in-entity
			@$dom->loadHTML($html_str);
			return $dom;
		}

		public static function getDomAttributeValue( $dom, $attr ){
			return $dom->attributes->getNamedItem( $attr )->nodeValue;
		}

		public static function httpPost($_url, $data, $cookie_file){

			//First to ask for data
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $_url);
			curl_setopt($ch, CURLOPT_POST, true); // 啟用POST
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//not output result
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);//Ignore redirection
			curl_setopt($ch, CURLOPT_COOKIESESSION, 1);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query( $data )); 
			$login_result = curl_exec($ch); 
			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = substr($login_result, 0, $header_size);
			$body = substr($login_result, $header_size);
			
			curl_close($ch);

			return $body;
		}

		public static function httpGet($_url, $cookie_file){
			$_process = curl_init();
			
			$headers = array( 
		        //"GET ".$_url." HTTP/1.1", 
		        "Connection: keep-alive",
		        "Cache-Control: max-age=0",
		        "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8", 
		        "Accept-Language: zh-TW,zh;q=0.8,en-US;q=0.6,en;q=0.4",
		       // "Referer: http://tw.ogame.gameforge.com/"
		    ); 

		    $referer = "http://tw.ogame.gameforge.com/";
		    $userAgent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.125 Safari/537.36';
		    $encode = "gzip,deflate,sdch";

		    curl_setopt($_process, CURLOPT_URL, $_url);
			curl_setopt($_process, CURLOPT_RETURNTRANSFER, true);//not output result
			curl_setopt($_process, CURLOPT_FOLLOWLOCATION, 0);//Ignore redirection
			curl_setopt($_process, CURLOPT_HTTPHEADER, $headers); 
			//curl_setopt($_process, CURLOPT_AUTOREFERER , true);
			//curl_setopt($_process, CURLOPT_REFERER , $referer);
			curl_setopt($_process, CURLOPT_USERAGENT, $userAgent);
			curl_setopt($_process, CURLOPT_ENCODING, $encode);
			curl_setopt($_process, CURLOPT_COOKIESESSION, 1);
			curl_setopt($_process, CURLOPT_COOKIEFILE, $cookie_file);
			//$cc = "PHPSESSID=fc66cbe958248abf7b937dc4893fe332583188d2; prsess_105099=fdf238bd19f03659085d3554640d232f; login_105099=U_tw113%3Awenjuchen%3A609ce395db1f24893ca19e65ea52e5bd; ";
			//curl_setopt($_process, CURLOPT_COOKIE, $cc);
			curl_setopt($_process, CURLOPT_COOKIEJAR,  $cookie_file);
			curl_setopt($_process, CURLOPT_HEADER, true);


			$result = curl_exec($_process); 

			$header_size = curl_getinfo($_process, CURLINFO_HEADER_SIZE);
			$header = substr($result, 0, $header_size);
			$body = substr($result, $header_size);
			//print_r("Header = ". $header . "\n\r");
			curl_close($_process);

			return $body;
		}

		//取得伺服器現在時間
		public static function getOGameServerTime($overview_str)
		{//若找不到的話 回傳值會是0
			$overview_dom = GlobalFunc::loadHTML( $overview_str );
			$div_dom = $overview_dom->getElementById("bar");

			if( !is_object($div_dom) ){
				echo "\nGlobalFunc::getOGameServerTime(): error, ogame_clock not found!!\n";
				return false;
			}

			$li_doms = $div_dom->getELementsByTagName('li');

			$li_doms_len = $li_doms->length;

			$ogame_clock = 0;
			for($liIdx=0 ; $liIdx < $li_doms_len ; $liIdx++){
				$li_dom = $li_doms->item( $liIdx );

				$class_text = "";
				
				$li_dom_class = $li_dom->attributes->getNamedItem('class');
				// to avoid notice message
				if( isset($li_dom_class)){
					$class_text = $li_dom_class->nodeValue;
				}
				
				if($class_text == "OGameClock")
				{
					$span_dom = $li_dom->getELementsByTagName('span')->item(0);
					$ogame_clock = $span_dom->textContent;
					break;
				}
			}

			return $ogame_clock;
		}

		//用於計算攻擊到達時間
		public static function trimArrivalTime($arr_time){
			$endpos = strpos($arr_time, ' ');
			$result = substr($arr_time, 0, $endpos);
			return $result;
		}


		public static function timeDiffInSecond($time1, $time2)
		{//使用time1的時間 減 time2的時間
			$time1 = explode(":", $time1);
			$time2 = explode(":", $time2);

			$time_diff = 0;
			//if check before use, can avoid notice message
			if(isset($time1[0]) && isset($time2[0]))
				$time_diff = 			  ((int)$time1[0] - (int)$time2[0]) * 3600; //Hours
			if(isset($time1[1]) && isset($time2[1]))
				$time_diff = $time_diff + ((int)$time1[1] - (int)$time2[1]) * 60;  //Minutes
			if(isset($time1[2]) && isset($time2[2]))
				$time_diff = $time_diff + ((int)$time1[2] - (int)$time2[2]); 	   //Seconds

			return $time_diff;
		}

		public static function timeFromNowInSecond($arr_time_str, $arrtimeMustLarger)
		{//若 $arrtimeMustLarger 為 true  
		 //則arrivetime < nowtime時, 自動判定為arrivetime指的是明天
		 //所以arrivet_time[0](hour)自動+=24

			//輸入類似 "11:24:50" 的到達時間
			date_default_timezone_set('Asia/Taipei');
			$arr_time = explode(":", $arr_time_str);
			$now_time = explode(":", date('H:i:s', time()));

			//注意 若是紀錄hour的 arrive_time[0] < now_time[0] 的現在時間
			//表示很可能 攻擊是隔天到達 因此arr_time[0]要+=24
			$arr_hour = (int)$arr_time[0];
			$now_hour = (int)$now_time[0];
			if( $arrtimeMustLarger && $now_hour > $arr_hour ){
				if(isset($arr_time[0])){
					$arr_time[0] = $arr_time[0] + 24;
				}
			}
			if(isset($arr_time[0]) && isset($now_time[0]))
				$time_diff = 			  ($arr_time[0] - $now_time[0]) * 3600; //Hours
			if(isset($arr_time[1]) && isset($now_time[1]))
				$time_diff = $time_diff + ($arr_time[1] - $now_time[1]) * 60  ; //Minutes
			if(isset($arr_time[2]) && isset($now_time[2]))
				$time_diff = $time_diff + ($arr_time[2] - $now_time[2])	      ; //Seconds

			return $time_diff;
		}

		//靠特定檔案做全部的Synchronization
		public static function synExecute($hint_str, $critialFunc) {
		    //lock file
			$handle = fopen( ConfigManager::getOGameMutexFileName(), "r+");
			while(!flock($handle, LOCK_EX)){ 
				sleep(1); 
			}
			echo "\n== " . $hint_str . " Syn Start ==\n\n";

			//critical section
			$result = $critialFunc();

			echo "\n==Syn end==\n";
			flock($handle, LOCK_UN);
			fclose($handle);

			return $result;
		}

		public static function getTargetTypeByTdDom($td_dom)
		{//用途: 在eventlist中每個event都有兩個項目originFleet跟destFleet, 傳入這兩項的td dom元件 可以得到此星球的種類(星球or廢墟or月球)
			if(!isset($td_dom)){
				return NULL;
			}

			$figure_dom = $td_dom->getElementsByTagName('figure')->item(0);

			if(!isset($figure_dom)){
				return NULL;
			}

			$class_text = $figure_dom->attributes->getNamedItem('class')->nodeValue;
			$class_array = explode(" ", $class_text);
			$class_length = sizeof($class_array);

			$dest_type = 0;//紀錄被攻擊的星球type(星球是1 月球是3)
			for($i=0 ; $i < $class_length ; $i++){
				$last_class_name = $class_array[$i];
				if( strcmp($last_class_name, "moon") == 0 ){
					$dest_type = 3;//月球
					break;
				}else if( strcmp($last_class_name, "planet") == 0 ){
					$dest_type = 1;//星球
					break;
				}else if( strcmp($last_class_name, "tf") == 0 ){
					$dest_type = 2;//廢墟
					break;
				}
			}
			if($dest_type == 0){
				echo "Error getTargetTypeByTdDom(): obj is not planet, tf or moon, undefined thing shows up.\n";
			}
			return $dest_type;
		}		

		public static function isCoordinateMatch($coorArr, $rightCoorArr){
			//避免不合法輸入
			if(!isset($coorArr[0]))
				return false;
			if(!isset($coorArr[1]))
				return false;
			if(!isset($coorArr[2]))
				return false;
			if(!isset($coorArr[3]))
				return false;
			if(!isset($rightCoorArr[0]))
				return false;
			if(!isset($rightCoorArr[1]))
				return false;
			if(!isset($rightCoorArr[2]))
				return false;
			if(!isset($rightCoorArr[3]))
				return false;

			$result = true;
			if($coorArr[0] != $rightCoorArr[0]){
				$result = false;
			}
			if($coorArr[1] != $rightCoorArr[1]){
				$result = false;
			}
			if($coorArr[2] != $rightCoorArr[2]){
				$result = false;
			}
			if($coorArr[3] != $rightCoorArr[3]){
				$result = false;
			}
			return $result;
		}

		public static function getCoordTextWithType($coord){
			return $coord[0] . "." . $coord[1] . "." . $coord[2] . "." .$coord[3];
		}
	}
	
?>