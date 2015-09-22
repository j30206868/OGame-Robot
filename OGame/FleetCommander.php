<?php

	Class FleetCommander{
		var $coord;	//起始星球座標
		var $target_coord; //目標星球座標
		var $href; //切換到出發星球頁面的連結
		var $fleets; //json
		var $rUri = ""; //request uri 因為fleet1 ~ fleet3很多資訊都是雷同 所以用一個全域變數來保存
		var $fleetInfo;//船飛出去以後才會有
		var $duration = 0;
		var $time_before_sent;
		var $Config;

		function __construct( $this_coord, $s_coord, $_href ){
			$this->coord = $this_coord;
			$this->target_coord = $s_coord;
			$this->href = $_href;
			$this->duration = 0;
			$this->fleetInfo = array("test");

			$this->Config = ConfigManager::declareConfigValue();
			//echo "\nFleet commander ".$this->href."\n";
		}

		public function getFleetInfo(){

			//echo "================get fleetInfo ";
			//var_dump($this->duration);
			//var_dump($this->fleetInfo);
			return $this->fleetInfo;
		}

		public function getDuration( $value ){
			return $this->duration;
		}

		public function setDuration( $value ){
			$this->duration = $value;
		}

		public function sendAllFleetsToShield(){
			$MOVEMENT = $this->Config['MOVEMENT'];
			//init
			$this->fleetInfo["id"] = -1;

//應該要做synchronization
//syn start
			//lock file
			echo iconv("UTF-8", "big5","一個派船出航\n"). "\n";
			$movement_page = GlobalFunc::synExecute("Sent fleet to shield", function(){

				echo "FleetCommander: This Planet[". $this->coord[0]. ":".$this->coord[1]. ":". $this->coord[2]."] ";
				//echo "-> Shield Planet[". $this->target_coord[0]. ":".$this->target_coord[1]. ":". $this->target_coord[2]."]\n";
				//切換星球 call global function
				$page = GlobalFunc::changeFocusPlanet( $this->href );

				$this->rUri = "";

				//取得Fleet1頁面
				///game/index.php?page=fleet1&galaxy=1&system=104&position=13&type=1&mission=1
				$f1_page = $this->getFleet1Page();

				//取得所有的戰船數量
				$fleets_uri = $this->getFleetsAmount( $f1_page );
				$this->rUri .= "&speed=1". $fleets_uri;

				//取得Fleet2頁面
				//galaxy=1&system=104&position=13&type=1&mission=1&speed=10&am204=&am205=&am206=&am202=&am203=1&am209=&am210=
				$f2_page = $this->getFleet2Page();

				//$f2 = GlobalFunc::loadHTML($f2_page);

				//取得Escape List
				echo iconv("UTF-8", "big5", "開始找尋適合的shield.\n");
				$my_coord_text = GlobalFunc::getCoordTextWithType($this->coord);
				$ESList = EscapeFilter::getEscapeList();
				if( isset($ESList[$my_coord_text]) ){
					$shields = $ESList[$my_coord_text]["shields"];
					$sh_count = sizeof($shields);

					$fcheck_page = 1;//儲存最終傳回的結果 來確定有沒有一個可行的座標
					for($sh_i = 0 ; $sh_i < $sh_count ; $sh_i++){
						$sh_coord = $shields[$sh_i];
						$sh_text  = GlobalFunc::getCoordTextWithType($sh_coord);
						//嘗試取得fleetcheck頁面
						$fcheck_page = $this->getFleetCheck($sh_coord);

						echo "fcheck_page:".$fcheck_page;
						if($fcheck_page == "0"){
							echo iconv("UTF-8", "big5", "Check通過 決定Shield座標($sh_text).\n");
							$this->target_coord = $sh_coord;
							break;
						}else if($fcheck_page == "1"){
							echo iconv("UTF-8", "big5", "此target座標($sh_text)不存在.\n");
						}else if($fcheck_page == "2"){
							echo iconv("UTF-8", "big5", "此target處於假期模式($sh_text) 無法派遣.\n");
						}else{
							echo iconv("UTF-8", "big5", "fcheck_page:$fcheck_page 不等於任何已知結果\n");
						}
					}
				}else{
					echo iconv("UTF-8", "big5", "沒有Escape List.\n");
					$fcheck_page = $this->getFleetCheck($this->target_coord);
				}
				//加入目的地uri
				$type = 1;//出發星球的type
				if(isset($this->target_coord[3]) && $this->target_coord[3] != NULL){
					$type = $this->target_coord[3];
				}

				$posi_uri = "galaxy=" . $this->target_coord[0] . "&system=" . $this->target_coord[1] . "&position=" . $this->target_coord[2] . "&type=".$type."&mission=3";
				$this->rUri = $posi_uri . $this->rUri;
				echo "this->uri: ".$this->rUri."\n";

				//file_put_contents($this->Config['FailInfo'], $f2_page);

				$f3_page = $this->getFleet3Page();

				//派遣船隊
				$movement_page = $this->sendFleetOut( $f3_page );

				//echo "movement_page result strlen: ". strlen($movement_page) . "\n";
				if(strlen($movement_page) < $this->Config['AskPageLeastStrLen']){
					echo "\n!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n";
					echo "Sent fleet failed, result strlen: ". strlen($movement_page);
					echo "\n!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n";
				}
				return $movement_page;
			});
//syn end
			echo "FleetCommander: movement_page result strlen: ". strlen($movement_page) . "\n";
			if( strlen($movement_page) == 0 ){
				//echo "movement_page strlen: 0!\n";
				echo "Error occur! No fleet info to return! return 0 instead.\n";
				return 0;
			}

			//取得的duration是錯誤的 因為他的計算都在javascript 因此無法取得
			date_default_timezone_set('Asia/Taipei');
			$n_time = time();
			$time_diff = $n_time - $this->time_before_sent;
			$predicted_start_time = $this->time_before_sent + ($time_diff/2); 

			$mp = GlobalFunc::loadHTML($movement_page);
			//找尋船隊的detail資訊
			$inhalt = $mp->getElementById("inhalt");

			if( !is_object($inhalt) ){
				echo "Error occur, inhalt is not an object, no way to find fleet detail\n";
				return 0;
			}

			$this->fleetInfo = $this->getfleetDetails( $inhalt , $this->coord, $this->target_coord );

			//取得船隊的id
			//$this->fleetInfo['id'];

			//取得船隊召回的get url
			//get ?page=movement&return=id
			$this->fleetInfo["withdraw_link"] = $MOVEMENT . "&return=" . $this->fleetInfo["id"];

			if( !isset($this->fleetInfo["id"]) ){
				$this->fleetInfo["id"] = -1;
			}
			if( $this->fleetInfo["id"]==-1 || strlen( trim($this->fleetInfo["id"]) ) == 0 ){
				echo "Can't get fleet id, return 0.\n";
				echo iconv("UTF-8", "big5", '船隊撤退失敗, 請檢查 重氫是否不足\n');
				return 0;
			}

			//取得船隊的抵達時間
			//$this->fleetInfo["arrivetime"];

			//echo "My fleet info in the end of send: duration = ".$this->duration;

			//取得船隊的飛行時間
			//$this->fleetInfo["flighttime"] = $this->duration;

			//取得船隊的出發時間
			//$this->fleetInfo["starttime"] = $this->fleetInfo["arrivetime"] - $this->duration;
			//echo "arrivetime = ".$this->fleetInfo["arrivetime"]." duration = $this->duration | starttime = ".$this->fleetInfo["starttime"]."\n";
			$this->fleetInfo["starttime"] = $predicted_start_time;

			//echo "My fleet info in the end of send: ";
			//var_dump($this->fleetInfo);

			return $this->fleetInfo;
		}

		private function getfleetDetails( $inhalt_dom, $oring_pos, $target_pos ){
			$divs = $inhalt_dom->getElementsByTagName("div");
			$len = $divs->length;
			
	        //取得所有的fleet div(每一筆飛行事件)
	        $fleet_divs = array();
	        $f_idx = 0;
			for($i=0; $i<$len ; $i++){
	            $attrs = $divs->item($i)->attributes;

	            if( $attrs != null){
	                $id = $attrs->getNamedItem('id');
	            }

	            if( $id != null){
	                $id = $id->nodeValue;

	                if( substr($id,0,5) == "fleet" ){
	                    //echo "---------------------------Div found---------------------------";
	                    $fleet_divs[ $f_idx ] = $divs->item($i);
	                    $f_idx++;
	                }
	            }
				//var_dump($attrs);
			}
			$fleet_div_length = $f_idx;

	        //從飛行事件比對 取得相同origin星球且相同target星球的fleet div
	        for($fd_i = ($fleet_div_length-1) ; $fd_i>=0 ; $fd_i--) 
	        {//從後面開始找, 逃出的船都設10%速度 正常來說都在後排
	        	$f_div = $fleet_divs[$fd_i];

	            //比對每筆飛行事件
	            $spans = $f_div->getElementsByTagName("span");
	            $span_num = $spans->length;

	            //找出每筆fleet div下的span 取得需要的資訊
	            $this_origin = null; //class="originData"
	            $this_target = null; //class="destinationData"

	            $this_origin_str;
	            $this_target_str;

	            //紀錄星球的種類
	            $origin_type = 0;
	            $dest_type = 0;

	            $this_id = null;    //ref="1026133" 取ref != null
	            $this_arrive_time = $f_div->attributes->getNamedItem("data-arrival-time")->nodeValue; //div 的 data-arrival-time="1407182351"
	            for($i=0; $i<$span_num ; $i++){
	                $span = $spans->item($i);
	                //prevent null class
	                if($span->attributes->getNamedItem("class")){
	                    $s_class = $span->attributes->getNamedItem("class")->nodeValue;
	                }

	                if( $s_class == "originData" ){
	                    //起始座標的parent span
	                    $child_spans = $span->getElementsByTagName("span");
	                    $child_num = $child_spans->length;

	                    for($j=0 ; $j<$child_num ; $j++){
	                        $child_span = $child_spans->item($j);
	                        //prevent null class
	                        if( $child_span->attributes->getNamedItem("class") ){
	                            $s_class = $child_span->attributes->getNamedItem("class")->nodeValue;
	                        }

	                        if( $s_class == "originCoords tooltip"){
	                            $this_origin_str = $child_span->getElementsByTagName("a")->item(0)->textContent;
	                            $this_origin = GlobalFunc::getCoordArrayByStr( $this_origin_str );
	                        }

	                        if( $s_class == "originPlanet" ){
	                        	$origin_type = GlobalFunc::getTargetTypeByTdDom($child_span);
	                        }
	                    }

	                }else if( $s_class == "destinationData" ){
	                    //目標座標的parent span
	                    $child_spans = $span->getElementsByTagName("span");
	                    $child_num = $child_spans->length;

	                    for($j=0 ; $j<$child_num ; $j++){
	                        $child_span = $child_spans->item($j);
	                        //prevent null class
	                        if( $child_span->attributes->getNamedItem("class") ){
	                            $s_class = $child_span->attributes->getNamedItem("class")->nodeValue;
	                        }

	                        if( $s_class == "destinationCoords tooltip"){
	                            $this_target_str = $child_span->getElementsByTagName("a")->item(0)->textContent;
	                            $this_target = GlobalFunc::getCoordArrayByStr( $this_target_str );
	                        }

	                        $pos = strpos($s_class, "destinationPlanet");
	                        //注意 必須用 === 或 !== 運算元
	                        //因為false = 0(boolean)
	                        //第一個字吻合 = 0(int) 位置為0
	                        //要用全等號===來判斷是否連型態都吻合
	                        if( $pos !== false  )
	                        {//有找到
	                        	//找到後下面可能有很多span, 其中沒有class value的才是正確的
	                        	$in_sp_array = $child_span->getElementsByTagName("span");
	                        	$in_sp_len = $in_sp_array->length; 
	                        	for($inner_idx=0 ; $inner_idx < $in_sp_len ; $inner_idx++){
	                        		$in_sp = $in_sp_array->item($inner_idx);
	                        		$in_class = $in_sp->attributes->getNamedItem("class");

	                        		if( !is_object($in_class ) ){
	                        			$dest_type = GlobalFunc::getTargetTypeByTdDom($in_sp);
	                        		}
	                        	}
	                        }
	                    }

	                }else if( $span->attributes->getNamedItem("ref") != null ){
	                    $this_id = $span->attributes->getNamedItem("ref")->nodeValue;
	                }
	            }

	            //更新星球的type
	            $this_origin[3] = $origin_type;
	            $this_target[3] = $dest_type;
	            //var_dump($this_target);

	            /*echo "This: ";
	            var_dump($this_target);
	            var_dump($this_origin);
	            echo "Compare with: ";
	            var_dump($oring_pos);
	            var_dump($target_pos);*/

	            //目前只比對起始及目的座標 ; 若怕有重複 可考慮比較其他資訊
	            if( GlobalFunc::isCoordinateMatch($this_origin, $oring_pos) &&
	            	GlobalFunc::isCoordinateMatch($this_target, $target_pos) ){

	            	echo "\n====================== Get Fleet Info After Send ========================\n";
	            	echo "Found ID: ". $this_id. "\n"; 
	            	echo "Found similar fleet info Origin:".$this_origin_str . " | Target: ". $this_target_str. "\n";
	            	echo "Right info: Origin[". $oring_pos[0]. ":".$oring_pos[1]. ":". $oring_pos[2]."]($oring_pos[3]) | ";
	            	echo "Target: [". $target_pos[0]. ":".$target_pos[1]. ":". $target_pos[2]."]($target_pos[3])\n";
	            	echo "\n===============================================================+=========\n";

	                //$f_info["arrivetime"] = $this_arrive_time - $this->Config['timeDiff'] ;
	                $f_info["id"] = $this_id;
	                //echo "My fleet info in get detail: ";
					//var_dump($f_info);

	                return $f_info;
	                break;
	            }

	            //echo "============". $this_origin ."============";
	            //echo "============". $this_target ."============";
	            //echo "============". $this_id ."============";
	        }
		}

		private function sendFleetOut( $f3_page ){
			date_default_timezone_set('Asia/Taipei');
			//holdingtime=1&expeditiontime=1&token=835326b1f0586f2bf3035c8f5c49c1b9
			//&galaxy=1&system=104&position=13&type=1&mission=1&union2=0&holdingOrExpTime=0
			//&speed=1&acsValues=-&am203=1&metal=0&crystal=0&deuterium=0
			$COOKIE_FILE = $this->Config['COOKIE_FILE'];;
			$MOVEMENT = $this->Config['MOVEMENT'];;
			$f3 = GlobalFunc::loadHTML($f3_page);

			//get token
			$wrap = $f3->getElementById("wrap");

			if( !is_object($wrap) ){
				echo "Error occur, wrap is not found, return 0\n";
				return 0;
			}

			$inputs = $wrap->getElementsByTagName("input");
			$token = "";
			for ($i=0 ; $i < $inputs->length ; $i++) {
				$input = $inputs->item($i);
				$input_name = $input->attributes->getNamedItem('name')->nodeValue;
				//echo "Input name = ". $input_name;
				if( $input_name=="token" ){

					$token = $input->attributes->getNamedItem('value')->nodeValue;
					break;
				}
			}

			//get total resource
			$resources = GlobalFunc::getResources( $f3_page );

			//get consumption of the fly
			$c_dom = $f3->getElementById("consumption");
			//特例<span id="consumption"> <span></span> </span> 用getElementsByTagName()->item(0)找不到
			$consumption = trim( $c_dom->firstChild->textContent );

			//get total space for resource
			$max_resource = $f3->getElementById("maxresources")->textContent;
			$max_resource = GlobalFunc::strToIntByTakeCommaOff( $max_resource );

			//get time length to arrive target
			//15:34:43 h
			$duration = $f3->getElementById("duration")->textContent;

			$duration = trim( substr( $duration, 0, strlen($duration)-1 ) );//get 15:34:43

			$d_array = explode(":", $duration);
			//explode()輸出的array沒有length 只能用sizeof
			if( sizeof($d_array) == 3){
				//有小時
				$duration = $d_array[0] * 3600 + $d_array[1] * 60 + $d_array[2];
			}else if( sizeof($d_array) == 2){
				$duration = $d_array[0] * 60 + $d_array[1];
			}else{
				$duration =  $d_array[0];
			}
			$this->setDuration( $duration );

			//get off all resources if it's capable of.
			$resources_taken_uri = $this->getAllResourcesCanBeTaken( $max_resource, $resources );
			//$resources_taken_uri = "";
			//$left_space = $max_resource;
			//$resources_taken_uri .= $this->getAllResourcesCanBeTaken( $info, "darkmatter", $resources );
			//$resources_taken_uri .= $this->getAllResourcesCanBeTaken( $info, "deuterium", $resources );
			//$resources_taken_uri .= $this->getAllResourcesCanBeTaken( $info, "crystal", $resources );
			//$resources_taken_uri .= $this->getAllResourcesCanBeTaken( $info, "metal", $resources );

			$this->rUri .= "&holdingtime=1&expeditiontime=1&holdingOrExpTime=0";
			$this->rUri .= "&token=" . $token . $resources_taken_uri;

			//echo "resources_taken_uri: " . $resources_taken_uri . "\n";
			//echo "rUri: " . $this->rUri . "\n";
			//echo "URI : ". $this->rUri." \n";
			//echo "==============================duration ".$this->duration;

			$data = GlobalFunc::uriToJson( $this->rUri );

			$this->time_before_sent = time();
			return GlobalFunc::httpPost( $MOVEMENT, $data, $COOKIE_FILE );
		}
		//var $left_space;
		private function getResourceCanBeTaken( $info_array, $key, $resources ){
			$left_space = $this->left_space;
			if( $resources[ $key ] <= $left_space){
				$resourcesTaken[ $key ] = $resources[ $key ];
				$left_space = $left_space - $resources[ $key ];
			}else{//$left_space < $resources[ $key ]
				$resourcesTaken[ $key ] = $left_space;
				$left_space = 0;
			}

			$uri = "&". $key . "=" . $resourcesTaken[ $key ];

			return $uri;
		}

		function getAllResourcesCanBeTaken( $max_resource, $resources ){			
			//$resource['darkmatter'] = $darkmatter;
			//$resource['deuterium'] = $deuterium;
			//$resource['crystal'] = $crystal;
			//$resource['metal'] = $metal;

			//take resources
			//left_space 等於 還有多少資源可以分配
			$left_space = $max_resource;
			$resourcesTaken;
			$uri = "";

			foreach ($resources as $key => $value) {

				if( $resources[ $key ] <= $left_space){
					$resourcesTaken[ $key ] = $resources[ $key ];
					$left_space = $left_space - $resources[ $key ];
				}else{//$left_space < $resources[ $key ]
					$resourcesTaken[ $key ] = $left_space;
					$left_space = 0;
				}

				$uri .= "&". $key . "=" . $resourcesTaken[ $key ];
			}

			return $uri;
		}

		private function getFleet3Page(){
			//type=1&mission=1&union=0&am203=1&galaxy=1&system=104&position=13&acsValues=-&speed=1
			$COOKIE_FILE = $this->Config['COOKIE_FILE'];
			$FLEET3 = $this->Config['FLEET3'];

			$this->rUri .= "&acsValues=-&union2=0";

			//echo "get fleet3 = ". $this->rUri;

			$uri_attr = explode("&", $this->rUri);

			$data = array();
			foreach ($uri_attr as $key => $value) {
				$keyandvalue = explode("=", $value);
				if( isset($keyandvalue[1]) ){
					$data[ $keyandvalue[0] ] = $keyandvalue[1];
				}
			}
			return GlobalFunc::httpPost( $FLEET3, $data, $COOKIE_FILE );

		}

		private function getFleetCheck($t_coord){
			//POST /game/index.php?page=fleetcheck&ajax=1&espionage=0 HTTP/1.1\r\n
			//galaxy=1&system=104&planet=13&type=1
			$COOKIE_FILE = $this->Config['COOKIE_FILE'];
			$FLEETCHECK = $this->Config['FLEETCHECK'];

			$url = $FLEETCHECK . "&ajax=1&espionage=0";

			$data = "galaxy=".$t_coord[0] . "&"; 
			$data .= "system=".$t_coord[1] . "&"; 
			$data .= "planet=".$t_coord[2] . "&";
			if(isset($t_coord[3]) && $t_coord[3] != NULL){
				$data .= "type=". $t_coord[3];
			}else{
				$data .= "type=1";
			}

			$data = GlobalFunc::uriToJson($data);
			return GlobalFunc::httpPost( $url, $data, $COOKIE_FILE );
		}

		private function getFleet2Page(){
			$COOKIE_FILE = $this->Config['COOKIE_FILE'];
			$FLEET2 = $this->Config['FLEET2'];

			//echo "get fleet2 = ". $this->rUri;

			$uri_attr = explode("&", $this->rUri);

			$data = array();
			foreach ($uri_attr as $key => $value) {
				$keyandvalue = explode("=", $value);
				if( isset($keyandvalue[1]) ){
					$data[ $keyandvalue[0] ] = $keyandvalue[1];
				}
			}

			return GlobalFunc::httpPost( $FLEET2, $data, $COOKIE_FILE );
		}

		private function getFleet1Page(){
			$COOKIE_FILE = $this->Config['COOKIE_FILE'];
			$FLEET1 = $this->Config['FLEET1'];

			

			$url = $FLEET1 . "&" . $this->rUri;// . "&". $SESSION;
			//echo "get fleet1 = ". $url;
			return GlobalFunc::httpGet( $url, $COOKIE_FILE );
		}

		//return a uri for query and update fleets of the commander
		private function getFleetsAmount( $f1_page ){
			$f1 = GlobalFunc::loadHTML($f1_page);


			$military = $f1->getElementById("military");
			$civil = $f1->getElementById("civil");

			$this->fleets = null;

			if( !isset($military) ){
				echo "Error occur, military is not found. return & as uri.\n";
				echo iconv("UTF-8", "big5", "船隊撤退失敗 請檢查星球上是否有可移動船隻\n");
				return '&';
			}
			if( !isset($civil) ){
				echo "Error occur, civil is not found. return & as uri.\n";
				return '&';
			}

			$result_uri = "";
			$result_uri .= $this->getFleetAmountByCategory( $military );
			$result_uri .= $this->getFleetAmountByCategory( $civil );

			echo "Sent fleet result uri = ".$result_uri. "\n";
			//var_dump($this->fleets);
			
			return $result_uri;
		}

		private function getFleetAmountByCategory( $category_dom ){
			$result_uri = "";

			$li_array = $category_dom->getElementsByTagName("li");

			for($i = 0 ; $i < $li_array->length ; $i++){
				$li = $li_array->item($i);

				//check on and off
				$class_value = $li->attributes->getNamedItem('class')->nodeValue;
				if( $class_value == "on" ){

					$a_dom = $li->getElementsByTagName("a")->item(0);
					$onclick = trim($a_dom->attributes->getNamedItem('onclick')->nodeValue);
					//echo "Trim onclick = $onclick";

					$f_array = explode(";", $onclick);
					//onclick="toggleMaxShips("#shipsChosen", 204,300); checkIntInput("#ship_204", 0, 300); checkShips("shipsChosen"); return false;"
					if( substr($f_array[0], 0, 14) == "toggleMaxShips" ){
						$toggle_str = $f_array[0];

						$toggle_str = substr($toggle_str, 15, strlen($toggle_str)-16 );
						$toggle_array = explode(",", $toggle_str);

						$shipName = "am". trim($toggle_array[1]);
						$shipAmount = trim($toggle_array[2]);

						//add in json
						$this->fleets[$shipName] = $shipAmount;

						$result_uri .= "&". $shipName. "=". $shipAmount;
					}else{
						echo "No toggleMaxShips, can't get the name and max number of the ship";
					}
				}
			}

			return $result_uri;
		}
	}

?>