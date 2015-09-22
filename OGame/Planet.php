<?php
	class Planet{
		var $name = "";
		var $coord = array(0, 0, 0);
		var $defender = null;
		var $href = "";
		var $num;

		function __construct($_name, $_koord, $_defender, $_href, $num){
			$this->name = $_name;
			$this->coord = $_koord;
			$this->defender = $_defender;
			$this->href = $_href;
			$this->Config = ConfigManager::declareConfigValue();
			$this->num = $num;

			//echo "this name: ". $this->name . "Coord: [". $this->coord[0]. ", ". $this->coord[1]. ", ".$this->coord[2]."] ; href = ".$this->href;
			//var_dump($this->defender);
		}

		public function defence(){
			$this->defender->defendAttack();
		}

		public static function getPlanets(){
			
			$Config = ConfigManager::declareConfigValue();

			//get overview page html
			$overview = GlobalFunc::httpGet( $Config['OVERVIEW_URL'], $Config['COOKIE_FILE'] );
			$ov_dom = GlobalFunc::loadHtml( $overview );
			$planet_list_dom = $ov_dom->getElementById("planetList");

			$pList = $planet_list_dom;

			if( !is_object($pList) ){
				echo "Error occur, Planet List not found, return empty planet list.\n";
				return array();
			}

			//list 下 每個div都是一個星球
				//每個div下都有兩個span
				//<span class="planet-name  ">jones4</span>
				//<span class="planet-koords  ">[1:83:6]</span>

			$planets_dom = $pList->getElementsByTagName('div');

			//echo "There ". $planets_dom->length . " Planet(s).";

			//存放月亮的起始編號(最後一顆星球之後就是月亮)
			$moon_num = $planets_dom->length + 1;

			//create planets
			$planets = array();
			for( $i = 0 ; $i < $planets_dom->length ; $i++){

				//get planet link
				$a_list = $planets_dom->item($i)->getElementsByTagName('a');
				$a_dom = $a_list->item(0);
				//產生星球
				$planet = Planet::createPlanetObj($a_dom, $planets_dom, $i);

				//將星球obj 加入list
				$planets[$i] = $planet;

				//確認是否有月球
				$has_moon = false;
				$moon_href = "";
				$moon_coord = array();
				$a_count = $a_list->length;
				$moon_obj = array();
				Planet::updateMoonInfo($a_list, $a_count, $planet, $has_moon, $moon_href, $moon_coord);
				
				if( $has_moon == true )
				{//有月亮則加到清單最後面
					$moon_obj = new Planet("月亮", $moon_coord, null, $moon_href, $moon_num);

					//將月亮obj 加入list
					//idx為編號-1
					$planets[$moon_num-1] = $moon_obj;

					$moon_num++;//月亮編號+1
				}
				
			}

			return $planets;
		} 

		public static function createPlanetObj($a_dom, $planets_dom, $i){
			$href = $a_dom->attributes->getNamedItem('href')->nodeValue;

			//get span two span doms under planet(div under planet list)
			$span_dom = $planets_dom->item($i)->getElementsByTagName('span');
			
			//get name and coordinates
			$name_span = $span_dom->item(0);
			$koord_span = $span_dom->item(1);

			$pName = $name_span->textContent;
			$pCoord_string = $koord_span->textContent;

			//coordinates format [1:83:6]
			$pCoord_string = substr( $pCoord_string , 1 , strlen($pCoord_string) - 2); // trim notation '[]'

			$pCoord = explode(":",$pCoord_string);
			$pCoord[3] = 1;//表示這顆是普通星球(type = 1)

			//create defender
			//$shield_coord = array(1, 83, 6);
			$_defender = null;

			return new Planet($pName, $pCoord, $_defender, $href, ($i+1));
		}

		public static function updateMoonInfo($a_list, $a_count, $planet, &$has_moon, &$moon_href, &$moon_coord){
			for($a_idx=1 ; $a_idx < $a_count ; $a_idx++){
				$moon_candidate = $a_list->item($a_idx);
				$class_text = $moon_candidate->attributes->getNamedItem('class')->nodeValue;
				$classes = explode(" ", $class_text);
				if(isset($classes[0]) && $classes[0] == "moonlink"){
					echo iconv("UTF-8", "big5", "Planet[".$planet->coord[0].":".$planet->coord[1].":".$planet->coord[2]."]:有一顆月球\n");
					$has_moon = true;
					$moon_href = $moon_candidate->attributes->getNamedItem('href')->nodeValue;

					$moon_coord[0] = $planet->coord[0];
					$moon_coord[1] = $planet->coord[1];
					$moon_coord[2] = $planet->coord[2];
					$moon_coord[3] = 3;//最後一個數字是type 跟母星球不一樣(月亮為3)
					break;
				}
			}
		}
	}

?>