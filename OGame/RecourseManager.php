<?php
class RecourseManager extends Thread
{
	var $PLANETS;
	var $Config;
	var $R_List;
				//Galaxy System Position
	//要pass值到syn function裡面所用的
	var $resourceUrl;
	var $onclickStr;

	function __construct($PLANETS){
		$this->Config = ConfigManager::declareConfigValue();
		$this->PLANETS = $PLANETS;	
		$R_List = array();
	}

	function run(){
		while(1==1)
		{
			$PLANETS = $this->PLANETS;

			if( !isset( $PLANETS ) ){
				echo "No Planet list! Break.\n";
				break;
			}

			$skipThisSleep = false;

			//掃過每個星球的檔案
			echo "sizeof planets: ".sizeof( $PLANETS )."\n";
			for($i = 0 ; $i < sizeof( $PLANETS ) ; $i++)
			{
				$coor = $PLANETS[$i]->coord;
				$LBlocks = $this->readFirstItem($coor);
				//var_dump($coor);
				//var_dump($LBlocks);
				if( sizeof($LBlocks) == 2 || sizeof($LBlocks) == 3 )
				{//長度為2表示有東西
					echo "ResourceManager: Ready to build page(".$LBlocks[0].") item(".$LBlocks[1].") in Planet[".$coor[0].":".$coor[1].":".$coor[2]."](".$coor[3].")\n";
					
					$buildResult = $this->buildResourceItem($LBlocks, $coor);
					
					echo "ResourceManager: build result ". $buildResult. ".\n";
					if($buildResult == 0)
					{//可能需要更新session
						$NewLoginManager = new LoginManager();
						echo "ResourceManager: Need new session, sleep 15 seconds.\n";
						sleep(15);
						$skipThisSleep = true;
					}else if($buildResult == 1){
						//這個item被成功建造好了
						echo "ResourceManager: isItemBuiled is set to true, this item is builded.\n";
						$isItemBuiled = true;
						$this->removeFirstItem($coor);

						//若蓋成功 全部過程重複做一遍(避免蓋了科技就要多等一輪才能蓋建築)
						sleep(rand(1,3));
						$skipThisSleep = true;
						break;
					}
				}
				$period = rand(1, 3);
				echo "ResourceManager: sleep ".$period." seconds to build next planet.\n";
				sleep($period);
			}

			if($skipThisSleep == false){
				$period = rand(500, 1000);
				//$period = 10;
				echo "ResourceManager: sleep " . $period . " seconds.\n";
				sleep($period);
			}
		}
	}

	//管理存在陣列中的清單
	function addItem($page, $coor, $item){
		$newIdx = sizeof($this->R_List);
		echo "ResourceManager: add new item number " . $newIdx ." coor[".$coor[0].":".$coor[1].":".$coor[2]. "](".$coor[3]."), ". $item . ", page ".$page."\n";
		$this->R_List[$newIdx]['page'] = $page;
		$this->R_List[$newIdx]['coor'] = $coor;
		$this->R_List[$newIdx]['item'] = $item;
	}

	function showAllItems(){
		$List_len = sizeof($this->R_List);
		echo "ResourceManager: showAllItems\n";
		for($i=0 ; $i < $List_len ; $i++){
			$page = $this->R_List[$i]['page'];
			$coor = $this->R_List[$i]['coor'];
			$item = $this->R_List[$i]['item'];
			echo "Item number " . $i ." coor[".$coor[0].":".$coor[1].":".$coor[2]. "](".$coor[3]."), ". $item . ", page ".$page."\n";
		}
	}

	function popItem(){
		$List_len = sizeof($this->R_List);

		if($List_len == 0){
			echo "ResourceManager: Pop item failed, item length 0.\n";
			return "Nothing";
		}

		//儲存第一格的值
		$result = $this->R_List[0];
		//陣列全部item往前移一格 並把第一格蓋掉
		for($i=1 ; $i < $List_len ; $i++){
			$this->R_List[$i-1]['page'] = $this->R_List[$i]['page'];
			$this->R_List[$i-1]['coor'] = $this->R_List[$i]['coor'];
			$this->R_List[$i-1]['item'] = $this->R_List[$i]['item'];
		}
		//把最後一個刪掉
		unset($this->R_List[ $List_len-1 ]);
		//回傳原本的第一個
		echo "ResourceManager: Pop item coor[".$result['coor'][0].":".$result['coor'][1].":".$result['coor'][2]. "](".$coor[3]."), ". $result['item'] . ", page ".$result['page']."\n";
		return $result;
	}

	//管理存在陣列中的清單

	//管理存在檔案中的清單
	function appendItem($page, $coor, $item){
		$dirpath = $this->Config['RESOURCE_DIR'];
		$filepath = $dirpath . $coor[0] . "." . $coor[1] . "." . $coor[2] . "." . $coor[3];
		$line_item = "<".$page . "," . $item . ">\n";
		if( file_exists($filepath) ){
			file_put_contents($filepath, $line_item, FILE_APPEND);
		}else{
			echo "appendItem: Error filepath doesn't exist: ".$filepath."\n";
		}
	}
	function readFirstItem($coor){
		$dirpath = $this->Config['RESOURCE_DIR'];
		$filepath = $dirpath . $coor[0] . "." . $coor[1] . "." . $coor[2] . "." . $coor[3];
		if( file_exists($filepath) ){
			$file_content = file_get_contents($filepath, true);
			$n_pos = strpos($file_content, ">");
			if($n_pos > 0){
				$firstLine = substr($file_content, 1, $n_pos-1);
			}else{
				$firstLine = substr($file_content, 0);
			}
			return explode(",", $firstLine);
		}else{
			echo "readFirstItem: Error filepath doesn't exist: ".$filepath."\n";
			return NULL;
		}
		
		//使用範例
		//$Lblocks = readFirstItem($this->Config['RESOURCE_DIR'], array(1,42,6));
		//if( isset($Lblocks[0]) && isset($Lblocks[1]) ){
		//	echo $Lblocks[0] ." and ". $Lblocks[1]."\n";
		//}
	}

	function removeFirstItem($coor){
		$dirpath = $this->Config['RESOURCE_DIR'];
		$filepath = $dirpath . $coor[0] . "." . $coor[1] . "." . $coor[2] . "." . $coor[3];
		if( file_exists($filepath) ){
			$file_content = file_get_contents($filepath, true);
			$n_pos = strpos($file_content, "<", 1);
			if($n_pos > 0){
				$left_content = substr($file_content, $n_pos);
			}else{
				$left_content = "";
			}
			file_put_contents($filepath, $left_content);
		}else{
			echo "removeFirstItem: Error filepath doesn't exist: ".$filepath."\n";
		}
	}
	//管理存在檔案中的清單

	//PLANETS:星球的List 	tCoor:目標星球座標
	//找不到星球回傳 或 建造不了 回傳 -1
	//可能沒有 session 回傳 0
	//成功回傳 1
	function buildResourceItem($blocks, $tCoor){
		$PLANETS = $this->PLANETS;

		if( !isset( $PLANETS ) ){
			echo "No Planet list! return false.\n";
			return false;
		}

		$page = $blocks[0];

		for($i = 0 ; $i < sizeof( $PLANETS ) ; $i++){
			if( GlobalFunc::isCoordinateMatch( $PLANETS[$i]->coord, $tCoor) ){
				echo "ResourceManager: Planet[".$tCoor[0].":".$tCoor[1].":".$tCoor[2]. "](".$tCoor[3].") isFound.\n";
				//取得切換到該星球的 url
				$pos = strpos($PLANETS[$i]->href, "cp=");
				$cpText = substr($PLANETS[$i]->href, $pos);
				if($page == "R"){//資源
					$resourceUrl = $this->Config['RESOURCE_URL'] . "&" . $cpText;
				}else if($page == "S"){//設施
					$resourceUrl = $this->Config['STATION_URL'] . "&" . $cpText;
				}else if($page == "T"){//科技
					$resourceUrl = $this->Config['TECH_URL'] . "&" . $cpText;
				}else{
					echo "ResourceManager.buildResourceItem(): error, invalid page value: (".$page.") found. return 1 to skip.\n";
					return 1;
				}

				//切換星球 順便取得resources page
				$this->resourceUrl = $resourceUrl;
				$resources_page = GlobalFunc::synExecute("Change focused planet and get resources page",function(){
					echo "resourceUrl: " . $this->resourceUrl;
					return GlobalFunc::changeFocusPlanet( $this->resourceUrl );
				});

				if( strlen($resources_page) < $this->Config['AskPageLeastStrLen']){
					echo "ResourceManager: resources_page len " . strlen($resources_page) . " too few, return 0.\n";
					return 0;//發送的請求可能失敗了
				}

				$resources_dom = GlobalFunc::loadHtml($resources_page);

				//找金屬礦
				//$resource_item_name = "button1";
				//$but1 = $resources_dom->getElementById( $resource_item_name );
				if($page == "R" || $page == "S"){
					$item_id  = $blocks[1];
					$but1     = $this->getResourceStationBtn($resources_dom, $item_id);
				}else if($page == "T"){
					$item_id  = $blocks[1];
					$item_num = $blocks[2] - 1;
					$but1 = $this->getTechnologyBtn($resources_dom, $item_id, $item_num);
				}else{
					echo "Page is not R, S or T, invalid. return 1 to skip this request.";
					return 1;
				}

				//發送 蓋資源 的請求
				return $this->sendBuildRequest($but1, $item_id);
			}
		}
		//找不到該星球
		return -1;
	}

	function getResourceStationBtn($page_dom, $resource_item_name){
		return $page_dom->getElementById( $resource_item_name );
	}

	function getTechnologyBtn($page_dom, $tech_category_name, $item_num){
		$ul_dom = $page_dom->getElementById( $tech_category_name );
		$li_list = $ul_dom->getElementsByTagName('li');
		return $li_list->item($item_num);
	}

	//回傳值意義
	// -1 => 建造失敗
	// 0  => http request失敗
	// 1  => 成功
	function sendBuildRequest($but_dom, $resource_item_name)
	{
		if(isset($but_dom))
		{

			echo "Resource Item: ".$resource_item_name ." is found!\n";
			$but_class = $but_dom->getAttribute('class');
			echo "but_class: ". $but_class . "\n";
			if( strstr($but_class, "disabled") )
			{//不能蓋 (可能是資源不足 或 其他正在建)
				echo iconv("UTF-8", "big5", "不能蓋") . "\n";
				return -1;
			}else if( strstr($but_class, "off") ){
				echo iconv("UTF-8", "big5", "but_class = off, not able to build return 1 to skip.\n") . "\n";
				return 1;
			}
			//可以蓋

			//若正在該的就是此項目 則class為on 仍不為disable
			//要確定是否真的可以蓋 找過所有div 確定沒有class名為 construction 的子div
			$isThisBuilding = false;
			$divs = $but_dom->getElementsByTagName('div');
			for($dIdx=0 ; $dIdx < $divs->length ; $dIdx++){
				$div = $divs->item($dIdx);
				$div_class = $div->getAttribute('class');
				if($div_class == "construction"){
					$isThisBuilding = true;
					break;
				}
			}
			if($isThisBuilding == true){
				echo iconv("UTF-8", "big5", "此項目正在建造中") . "\n";
				return -1;
			}

			echo iconv("UTF-8", "big5", "可以蓋") . "\n";
			
			//找正確的tag_a
			$tag_a = $but_dom->getElementsByTagName('a')->item(0);
			$onclick_str = $tag_a->getAttribute('onclick');

			if( strlen($onclick_str) < 10 ){
				$tag_a = $but_dom->getElementsByTagName('a')->item(1);
				$onclick_str = $tag_a->getAttribute('onclick');
			}

			//從onclick string中找到網址
			//網址應為 'http://....'
			$onclick_str = substr($onclick_str, strpos($onclick_str, "http://"));
			$onclick_str = substr($onclick_str, 0, strpos($onclick_str, "'"));

			echo "onclick_str: ".$onclick_str."\n";

			//送出http request
			$this->onclickStr = $onclick_str;
			$result_page = GlobalFunc::synExecute("Sent build resource request",function(){
				echo "onclick_str: ". $this->onclickStr . "\n";
				$result_page = GlobalFunc::httpGet($this->onclickStr, $this->Config['COOKIE_FILE']);
				return $result_page;
			});
			$resultLen = strlen($result_page);
			if( $resultLen > $this->Config['AskPageLeastStrLen']){
				return 1;
			}else{
				file_put_contents($this->Config['FailInfo'], $result_page);
				echo "ResourceManager: Sent build resource request result len " . $resultLen . " too few, content has written into FailInfo file, return 0.\n";
				return 0;//發送的請求可能失敗了
			}

		}else{
			echo "Resource Item: ".$resource_item_name ." is not found!\n";
			return 0;//發送的請求可能失敗了 所以找不到該item
		}	
	}
}
?>