<?php
	class EscapeFilter{
		public static $Config;
		public static $ESList;
		//取得該帳號的Escape List
		public static function getEscapeShields($es_planet_dom){
			$Config = self::getConfig();

			$shield_span = $es_planet_dom->getElementsByTagName('span')->item(0);

			if(!is_object($shield_span)){
				echo "Error getEscapeShields(): no shield_span item. reutrn NULL local shields\n";
				return NULL;
			}				

			$lis = $shield_span->getElementsByTagName("li");
			$count = $lis->length;
			$shields = array();
			$i = 0;
			for($i=0 ; $i<$count ; $i++){
				$li = $lis->item($i);
				$text = trim($li->textContent);
				$shields[$i] = explode(".", $text);
			}
			//shield list中最後一個為 global 的 shield
			$shields[$i] = $Config['SHIELD'];
			return $shields;
		}

		public static function getEscapeMax($es_planet_dom){
			$max_span = $es_planet_dom->getElementsByTagName('span')->item(1);
			if(!is_object($max_span)){
				echo "Error getEscapeMax(): no max_span item. reutrn NULL local max\n";
				return NULL;
			}	
			return (int)($max_span->textContent);
		}

		public static function getEscapeDetails($es_planet_dom){
			$detail_span = $es_planet_dom->getElementsByTagName('span')->item(2);

			if(!is_object($detail_span)){
				echo "Error getEscapeDetails(): no detail_span item. reutrn NULL local detail\n";
				return NULL;
			}	

			$lis = $detail_span->getElementsByTagName("li");
			$count = $lis->length;
			$details = array();
			for($i=0 ; $i<$count ; $i++){
				$li = $lis->item($i);
				$text = trim($li->textContent);

				//pair[0] = 船名
				//pair[1] = 數量
				$pair = explode(",", $text);
				$pair[0] = trim($pair[0]);
				$pair[1] = (int)trim($pair[1]);

				$details[ $pair[0] ] = $pair[1];
			}
			return $details;
		}
		public static function getEscapeList(){
			if( !isset(self::$ESList) ){
				$Config = self::getConfig();
				//如果 某星球 沒有設定escape 的話 就使用global的撤退規則
				$escape_str = file_get_contents($Config['EscapeListPath'], true);
				$escape_html = GlobalFunc::loadHtml( $escape_str );

				$escape_planets = $escape_html->getElementsByTagName('div');
				$ps_count = $escape_planets->length;

				$escape_list = array();
				//處理每個root div
				for($i = 0 ; $i < $ps_count ; $i++){
					$planet = $escape_planets->item($i);
					$coord  = $planet->attributes->getNamedItem('title')->nodeValue;

					//使用星球座標 作為index
					//存入整個 html 元件
					$escape_list[$coord] = array();
					$escape_list[$coord]["shields"] = EscapeFilter::getEscapeShields($planet);
					$escape_list[$coord]["max"]     = EscapeFilter::getEscapeMax($planet);
					$escape_list[$coord]["details"] = EscapeFilter::getEscapeDetails($planet);
				}
				self::$ESList = $escape_list;
			}
			return self::$ESList;
		}

		public static function escapeIfThreatenFilter($total_fleets_count, $event_fleets_str, $es_list, $coord_text){
			$Config = self::getConfig();
			$isThreaten = false;

			//若存在該星球的Escape清單 則過濾細節 
			if( isset( $es_list[$coord_text] ) && $event_fleets_str != "" )
			{//1.該星球必須有escape list 2.有得到event_fleets_str	
				echo iconv("UTF-8", "big5//IGNORE", "使用Local的Escape規則: \n");
				//先過濾總數量 如果總數量超過就不需要過濾細節了
				if( isset($es_list[$coord_text]["max"]) ){
					if($total_fleets_count >= $es_list[$coord_text]["max"])
					{//大於等於 船隊總數限制 有威脅
						$isThreaten = true;
						echo iconv("UTF-8", "big5//IGNORE", "船隊總數($total_fleets_count) >= Max(".$es_list[$coord_text]["max"].") 判定為有威脅\n");
					}else{
						echo iconv("UTF-8", "big5//IGNORE", "船隊總數($total_fleets_count) < Max(".$es_list[$coord_text]["max"].") 無威脅性\n");
					}
				}
				//過濾船隊細節
				//傳入$isThreaten的pointer
				if( $isThreaten===false && isset($es_list[$coord_text]["details"]) ){
					$th_list = $es_list[$coord_text]["details"];
					EscapeFilter::filterAttackByEventFleetStr($event_fleets_str, $th_list, $isThreaten);
					if($isThreaten === true){
						echo iconv("UTF-8", "big5//IGNORE", "某特定船隻超出數量限制 判定為有威脅\n");
					}else{
						echo iconv("UTF-8", "big5//IGNORE", "各種船隻數量皆在安全範圍內 無威脅可能\n");
					}
				}
			}else{
				//echo "No Escape Details Use Global ThreatenCount to check.\n";
				//	否則使用global的 threaten count
				echo iconv("UTF-8", "big5//IGNORE", "使用全域的ThreatenCount規則: ");
				if($total_fleets_count < $Config['ThreatenCount']){
					echo "escapeIfThreatenFilter: Ignore attack, total_fleets_count($total_fleets_count) < ThreatenCount(".$Config['ThreatenCount'].")\n";
				}else{
					echo "escapeIfThreatenFilter: This attack is dangerous, hide fleets.\n";
					$isThreaten = true;
				}
			}

			return $isThreaten;
		}

		//<getEventFleetStr, filterAttackByEventFleetStr>
		//用途: 比對攻擊船隊的細節
		public static function getEventFleetStr($td_dom)
		{//傳入class=icon_movement的<td>物件
			if( !is_object($td_dom) ){
				echo "Error, icon_movement td is null return 0.\n";
				return 0;
			}

			$span_dom = $td_dom->getElementsByTagName('span')->item(0);

			if( !is_object($span_dom) ){
				echo "Error, span under icon_movement td is null return 0.\n";
				return 0;
			}

			return $span_dom->attributes->getNamedItem('title')->nodeValue;
		}

		public static function filterAttackByEventFleetStr($event_fleets_str, $th_list, &$isThreaten)//$isThreaten is call by reference
		{//傳入Event Fleet String
			if( (!isset($event_fleets_str)) || $event_fleets_str == ""){
				echo "Error, filterAttackByEventFleetStr(): event_fleets_str empty, return 0\n";
				return 0;
			}

			$fleet_dom = GlobalFunc::loadHtml('<?xml encoding="UTF-8">' . $event_fleets_str);
			$fleet_tds = $fleet_dom->getElementsByTagName('td');
			$fleet_td_count = $fleet_tds->length;

			echo "event_fleets_str len: ".strlen($event_fleets_str)."\n";
			echo "filterAttackByEventFleetStr(): fleet_td_count = $fleet_td_count\n";

			for($f_idx=0 ; $f_idx < $fleet_td_count ; $f_idx++){
				//取得UTF-8編碼下的characters
				$text_got = $fleet_tds->item($f_idx)->textContent;
				$text_got = trim($text_got, ":");

				//echo "[$f_idx]: " . $text_got . "\n";

				if( isset($th_list[$text_got]) ){
					//若是船艦 idx+1 即為船艦數量
					$ship_count = $fleet_tds->item($f_idx+1)->textContent;
					$ship_count = GlobalFunc::strToIntByTakeCommaOff( $ship_count );
					
					//跳躍讀取船艦數量 idx要自動加1
					$f_idx++;
				}else{
					//不在過濾的船艦清單中
					echo iconv("UTF-8", "big5//IGNORE", "[$f_idx]$text_got 此項目不在清單中\n");
					continue;
				}
				//echo iconv("UTF-8", "big5//IGNORE", "$text_got 數量:$ship_count\n");
				//比對清單 看是否有威脅性
				echo iconv("UTF-8", "big5//IGNORE", "[$f_idx]text_got 門檻:".$th_list[$text_got]." 判斷運算子(<=) 攻擊數量:$ship_count\n");
				if( $th_list[$text_got] <= $ship_count ){
					//有威脅性
					echo iconv("UTF-8", "big5//IGNORE", "此項目已超過門檻 具有威脅性.\n");
					$isThreaten = true;
					//不用再偵測了 已經確定此工及具有威脅性
					break;
				}
			}
			return 1;
		}

		public static function getConfig(){
			if( isset(self::$Config) ){
				return self::$Config;
			}else{
				self::$Config = ConfigManager::declareConfigValue();
				return self::$Config;
			}
		}
	}
	EscapeFilter::$Config = ConfigManager::declareConfigValue();
	EscapeFilter::$ESList = EscapeFilter::getEscapeList();
?>