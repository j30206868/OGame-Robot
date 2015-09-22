<?php
	class AttackDetector{
		var $ReactionTime;
		var $PLANET;//只是座標
		var $Config;
					//Galaxy System Position

		function __construct($PLANET){
			$this->Config = ConfigManager::declareConfigValue();
	
			$this->ReactionTime = $this->Config['ReactionTime']; // if the attack will come within 10min, defend the planet
									 // ReactionTime should be longer than the attacks detecting period for the defender

			$this->PLANET = $PLANET;
		}

		function detectAttack(){
			/******************************************/
			// 基本上 此物件裡面使用到的資訊
			// 都會從檔案中讀取 並不會產生http request
			// 不需要做synchronization
			/******************************************/
			$PLANET = $this->PLANET;

			//echo "\n";
			echo "AttackDetector: [". $PLANET[0]. ":".$PLANET[1]. ":". $PLANET[2]."](".$PLANET[3].") ";
			echo "Start detecting attacks.\n";

			$final_arrive_time = "0";
			if($this->hasAttack()){
				
				//Attacked; get event list
				$eList = file_get_contents($this->Config['ADEventList'], true);
				//echo "event list result strlen: ". strlen($eList)."\n";
				if( strlen($eList) < $this->Config['AskPageLeastStrLen'] ){
					$LoginManager = new LoginManager();
					$eList = GlobalFunc::getEventList();
				}

				$event_list_dom = GlobalFunc::loadHtml( "<?xml encoding='UTF-8'>" . $eList);

				//每個tr都是一筆傳對往返的event
				$items = $event_list_dom->getElementsByTagName('tr');

				if( !is_object($items) ){
					echo "Error occur, <tr> tags are not found in event list, return 0.\n";
					return 0;
				}

				$items_num = $items->length;
				//items of fleets action report are sorted by arriving time from closest to farest

				//逐項檢查event list(items)裡面的每筆event(item)
				for($i = 0 ; $i < $items_num ; $i++){
					//船隊的抵達時間
					$arrive_time = 0;

					//取得event(item)下的所有td
					$item = $items->item($i);
					$tds = $item->getElementsByTagName('td');
					$tds_num = $tds->length;

					//取得船隊的到達時間
					echo "Prepare to check arrival time.\n";
					for($tdIdx = 0 ; $tdIdx < $tds_num ; $tdIdx++){
						$tmp_td_item = $tds->item( $tdIdx );
						$tmp_class_value = $tmp_td_item->attributes->getNamedItem('class')->nodeValue;
						if($tmp_class_value == "arrivalTime"){
							$arrive_time_str = $tmp_td_item->textContent;
							//trim chinese code after the time string
							$arrive_time = GlobalFunc::trimArrivalTime($arrive_time_str);
							break;
						}
					}

					if( $arrive_time != "" ){
						//data-arrival-time不為空值
			
						//剩下多少時間會到達
						$left_arrive_time = GlobalFunc::timeFromNowInSecond($arrive_time, true);

						echo "AttackDetector: Arrive_time ". $arrive_time . ".\n";
						echo "AttackDetector: Reaction time ". $this->ReactionTime . " seconds.\n";
						echo "AttackDetector: An Attack arrive after ". $left_arrive_time . " seconds.\n";

						if( $left_arrive_time > $this->ReactionTime ){
							echo "AttackDetector: Attack over reaction time, do nothing. \n";
							//(因為第一個偵測到的攻擊一定是最早到的攻擊)
							//所以第一個都還沒到就可以直接結束偵測了
							//到達時間超過反應時間 不需理會
							break;
						}
					}else{
						echo "AttackDetector: No arrive time , it's not an attack.\n";
						//沒有抵達時間 該event應該不是攻擊
						break;
					}

					//紀錄event狀態的flag
					$isTDAttackEvent = false;
					$isGroupAttackEvent = false;//是聯合攻擊
					$isTDEventOver = true;
					$dest_coord = array(0, 0, 0);
					$dest_type = 0;
					$total_fleets_count = 10000;
					$eventFleetStr = "";

					//查看該event的所有<td>並取得重要內容
					for($j = 0 ; $j < $tds_num ; $j++){
						$td_dom = $tds->item( $j );
						$td_class_value = $td_dom->attributes->getNamedItem('class')->nodeValue;

						if( $td_class_value == "missionFleet"){
							//確認該事件是否為攻擊事件

							//被攻擊的會有這個圖示 以圖片網址來判定
							//<td class="missionFleet">
			        			//<img src="http://gf1.geo.gfsrv.net/cdn9a/cd360bccfc35b10966323c56ca8aac.gif" class="tooltipHTML" title="">
							//</td>
							$img = $td_dom->getElementsByTagName('img')->item(0);
							$icon_source = $img->attributes->getNamedItem('src')->nodeValue;

							if( $icon_source == "http://gf1.geo.gfsrv.net/cdn9a/cd360bccfc35b10966323c56ca8aac.gif" ){
								$isTDAttackEvent = true;
							}else if($icon_source == "http://gf2.geo.gfsrv.net/cdnad/87d615c4fb395b75ec902b66b7757e.gif" ){
								$isTDAttackEvent = true;
								$isGroupAttackEvent = true;
								echo "AttackDetector: This is a group attack event. Withdraw fleets whatever.\n";
							}else{
								echo "AttackDetector: This is not an known attack event. ";
								echo "Check if is attack event. Icon Source = ". $icon_source . "\n";
							}
							
						}else if( $td_class_value == "detailsFleet"){
							$span_dom = $td_dom->getElementsByTagName('span')->item(0);
							$total_fleets_count = $span_dom->textContent;

							//一定要拿掉三位撇 否則會錯
							//"6,643" <= 20 = true (錯誤結果)
							//"6643"  <= 20 = false
							echo "total_fleets_count before trim: ".$total_fleets_count."\n";
							$total_fleets_count = GlobalFunc::strToIntByTakeCommaOff( $total_fleets_count );

							echo "total_fleets_count after trim: ".$total_fleets_count."\n";
							/*if($total_fleets_count <= $this->Config['ThreatenCount']){
								echo "\n================= Ignore Attack ==================\n";
								echo "\nFleets count Total(".$total_fleets_count.") <= Threshold(".$this->Config['ThreatenCount']."), no big deal. Ignore the attack.\n";
								echo "AttackDetector: Attack arrive after ". $left_arrive_time . " seconds.\n";
								echo "\n==================================================\n";
								break;
							}else{
								echo "AttackDetector: This attack is dangerous, hide fleets.\n";
							}*/

						}else if( $td_class_value == "icon_movement"){
							//確認該船隊是否為前往狀態

							//icon_movement存在表示艦隊正在前往
							$isTDEventOver = false;

							//取得eventFleetStr
							$eventFleetStr = EscapeFilter::getEventFleetStr($td_dom);

						}else if( $td_class_value == "destFleet"){
							//找出被攻擊的星球type
							$dest_type = GlobalFunc::getTargetTypeByTdDom($td_dom);

							if($dest_type != $PLANET[3]){
								//打的不是本Planet(type不一樣)
								$isTDAttackEvent = false;
							}

						}else if( $td_class_value == "destCoords"){
							//取得目的地星球座標
							
							//<td class="destCoords">
        						//<a href="http://s113-tw.ogame.gameforge.com/game/index.php?page=galaxy&amp;galaxy=1&amp;system=79" target="_top">
            						//[1:79:10]
            					//</a>
    						//</td>

    						$a_dom = $td_dom->getElementsByTagName('a')->item(0);
    
    						$dest_coord_string = trim( $a_dom->textContent );

    						$dest_coord_string = substr( $dest_coord_string , 1, strlen($dest_coord_string)-2); // trim notation '[]'
    						$dest_coord = explode(":", $dest_coord_string);

    						//echo "dest_coord_string = ".$dest_coord_string;
    						//var_dump($dest_coord);
    						//echo PHP_EOL;
						}
					}

					//取得目標的type
					$dest_coord[3] = $dest_type;

					if( $isTDAttackEvent && (!$isTDEventOver)){
						//確定該星球正在被攻擊 且 攻擊船隊是前往的狀態

						if( GlobalFunc::isCoordinateMatch($dest_coord, $PLANET) ){
							//判斷威脅性 若此event沒有威脅性 則coninue
							$p_coord_text = GlobalFunc::getCoordTextWithType($PLANET);

							//是聯合攻擊的話 不管怎樣直接逃
							if($isGroupAttackEvent==false){
								$isThreaten = EscapeFilter::escapeIfThreatenFilter($total_fleets_count, $eventFleetStr, EscapeFilter::getEscapeList(), $p_coord_text);
								if($isThreaten != true)
								{//不具威脅性
									echo "================================= Ignore Attack =================================\n";
									echo iconv("UTF-8", "big5//IGNORE", "\n此攻擊(arrive_time:$arrive_time)(目標:$p_coord_text) 不具威脅性 忽略.\n");
									echo "\n================================= Ignore Attack =================================\n";
									continue;
								}
							}

							//宣告該星球是被攻擊的星球
							echo "AttackDetector: [$dest_coord[0]:$dest_coord[1]:$dest_coord[2]]($dest_coord[3]):\n";
							echo "final_arrive_time: ";
							var_dump($final_arrive_time);
							if( strcmp($final_arrive_time, "0") == 0){
								$final_arrive_time = $arrive_time;

								echo "Attack arrive time is updated, after,".( $left_arrive_time)." s.\n";
							}else{//之前已經有一個攻擊被偵測到了了
								//之前被偵測到的攻擊 還剩幾秒鐘會到?
								$latest_attack_left_arr_time = GlobalFunc::timeFromNowInSecond($final_arrive_time, true);
								//這次攻擊抵達的時間 一定要 比之前檢查到的晚到(因為對方伺服器給的資料有照時間順序排序)
								//所以如果 attack_interval < 0 表示有error
								$attack_interval = $left_arrive_time - $latest_attack_left_arr_time;
								if( $attack_interval < 0 ){
									echo "\n!!!!!!!!!!!!! error !!!!!!!!!!!!!!\n";
									echo "attack_interval = ". $attack_interval . "\n";
									echo "There must be some error happened.\n";
									echo "\n!!!!!!!!!!!!! error !!!!!!!!!!!!!!\n";
								}

								//確認attack interval是否太近 會造成危險
								//若太接近則視為同一起攻擊事件, 
								//		取最晚到的攻擊做為final_arrive_time
								//但	取最早到的攻擊做為判斷船隻是否需要撤離的條件
								//(只要 $final_arrive_time 不為0, 船隻就會被送走)
								echo "$attack_interval: ".$attack_interval. " CAttackPvtInterval: ".$this->Config['CAttackPvtInterval']."\n";
								if( $attack_interval < ($this->Config['CAttackPvtInterval']) ){
									$final_arrive_time = $arrive_time;

									echo "Second Attack is detected, arrive time is updated, after ".($left_arrive_time)." sec.\n";
								}else{
									echo "Second Attack is detected, it's too far from the preceding ones.\n";
								}
							}
						}
					}
				}
			
			}else{
				echo "AttackDetector: [". $PLANET[0]. ":".$PLANET[1]. ":". $PLANET[2]."](".$PLANET[3].") ";
				echo " No Attack Detected.\n";
			}
			echo "AttackDetector: [". $PLANET[0]. ":".$PLANET[1]. ":". $PLANET[2]."](".$PLANET[3].") ";
			echo "Detecting finished.\n";
			return $final_arrive_time;
		}

		function hasAttack(){
			$isAttacked = false;

			//get overview page
			//$over_page = GlobalFunc::getOverviewPage();
			//read over_page string from file
			$last_update_time = file_get_contents($this->Config['DLastUpt'], true);
			echo "\n===== Detect if has attack file path =====\n";
			echo "AttackDetector: [". $this->PLANET[0]. ":".$this->PLANET[1]. ":". $this->PLANET[2]."](".$this->PLANET[3]."):\n";
			echo "File last updated time: " . date('H:i:s', $last_update_time) . "\n";
			echo "ADOverview Path: " . $this->Config['ADOverview'] . "\n";
			echo "ADEventList Path: " . $this->Config['ADEventList'] . "\n";
			echo "====================================\n";

			$over_page = file_get_contents($this->Config['ADOverview'], true);
			//read over_page string from file

			//echo "over_page result strlen: " . strlen($over_page) . "\n"; 
			if(strlen($over_page) < $this->Config['AskPageLeastStrLen']){
				echo "Request overview page failed, strlen: ".strlen($over_page).". may be attacked.\n";
				return 1; // maybe has attacked but call latter program to check
			}

			$doc = GlobalFunc::loadHTML($over_page);

			//get body
			//$body = $doc->getElementsByTagName('body')->item(0);
		
			//get attack alert dom
			$attack_alert_dom = $doc->getElementById('attack_alert');

			if( !is_object($attack_alert_dom) ){
				echo "Error occur, Attack Alert not found. return 1.\n";
				return 1;
			}

			if( $attack_alert_dom->hasAttribute('class') ){
				$attack_alert_class = $attack_alert_dom->getAttribute('class');

				//目前沒有人攻擊
				if( strstr($attack_alert_class, "noAttack") ){
					$isAttacked = false;
				}else{
					$isAttacked = true;
				}
			}

			return $isAttacked;
		}
	}
?>