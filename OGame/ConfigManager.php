<?php
	class ConfigManager{
		//********//
		// 最好所有會頻繁送http封包的都做syn 不然一個沒做影響到其他的
		// 就都白做了
		// 如果有一天要管理多個帳號
		// 建議 都使用同一個檔案做syn 不然什麼時候會發生問題很難預料
		//		雖然慢但是更穩定安全
		//********//
		public static function getOGameMutexFileName(){
			return dirname(__FILE__). "\\"  ."Defender\\key.txt";
		}

		//!!!!!!!!!!!!!!!!!!!  Warning  !!!!!!!!!!!!!!!!!!!!
		//				千萬不要犯的錯誤
		//				在比較的時候, 一定要確保資料型態一樣
		//				否則怎麼錯的都不知道
		//
		//				6,423 <= 20 -> true
		//					實際上變成 6 <= 20 -> true
		//				6423  <= 20 -> false
		//              ("00:50:10" == 0) -> true
		//				字串與數字比較前 會被轉成數字 轉換失敗就變成0
		//
		//!!!!!!!!!!!!!!!!!!!  Warning  !!!!!!!!!!!!!!!!!!!!
		public static function declareConfigValue(){
			$USER_INFO = AccountManager::declareAccountInfo();
			//Bug
			//找尋派出的艦隊info 只認星球座標 如果同時有船隊從shield回來 
			//		派出空船隊以後 取得船隊資訊會成功 一旦成功 就會以為船隊已經成功被派出去了
			//		就會空轉 實際上找到的ID是錯誤的 該攻擊就有可能成功摧毀剛要回來的船隊
			//		或是 星球上還有船隊 但是被派出去後 withdraw link錯誤 所以withdraw會無效

			$config['USER_ACCOUNT'] = $USER_INFO['USER_ACCOUNT'];
			$config['USER_PASS']    = $USER_INFO['USER_PASS'];
			$config['SHIELD']       = $USER_INFO['SHIELD'];
			$config['ThreatenCount']= $USER_INFO['ThreatenCount'];

			$config['USER_UNIVERSE'] = "s113-tw.ogame.gameforge.com";

			//login info
			//cookie檔案共用的話 同時登入多個帳號會炸掉
			//資訊會錯亂
			//原因: 一個帳號登入的session被另一個帳號蓋掉
			//			這時候如果要取得page 會送http request
			//			然而取得的page其實是另一個帳號的
			//			檢測錯誤檢測不到 因為長度是正常
			//			所以取得的所有資訊其實都是別的帳號的
			$config['COOKIE_FILE'] = dirname(__FILE__). "\\"     .$config['USER_ACCOUNT']  . "\\cookies1.txt";
			$config['SESSION'] = "";
			$config['TimeLog'] = dirname(__FILE__). "\\time.log";
			$config['AttackListPath'] = dirname(__FILE__) . "\\" . $config['USER_ACCOUNT'] . "\\AttackList\\";
			$config['EscapeListPath'] = dirname(__FILE__) . "\\" . $config['USER_ACCOUNT'] . "\\Escape.txt";

			//*********************************************//
			// 因為attack detector是讀取檔案裡面的資料
			// 要保護多個帳號的話 要把檔案路徑 跟帳號id相關聯
			// 不要忘記創Directory
			// 程式只會新增檔案 如果連Directory都找不到 程式會失效
			//*********************************************//

			$config['FailInfo'] = dirname(__FILE__). "\\"   .$config['USER_ACCOUNT']."\\errorlog.txt";
			$config['KEYFILE'] = dirname(__FILE__). "\\"    .$config['USER_ACCOUNT']."\\key.txt";

			//Defender control
			$config['DLastUpt'] = dirname(__FILE__). "\\"   .$config['USER_ACCOUNT']."\\last_update_time.txt";

			//AttackDetector control
			$config['ADOverview'] = dirname(__FILE__). "\\" .$config['USER_ACCOUNT']."\\overview.txt";
			$config['ADEventList'] = dirname(__FILE__). "\\".$config['USER_ACCOUNT']."\\eventlist.txt";

			//User directory
			$config['USER_DIR'] = dirname(__FILE__). "\\"   .$config['USER_ACCOUNT']."\\";
			$config['RESOURCE_DIR'] = $config['USER_DIR'] . "ResourceManager\\";
			$config['UPT_TIME_DIR'] = $config['USER_DIR'] . "PlanetUpdateTime\\";

			//page url 
			$config['OVERVIEW_URL'] = "http://". $config['USER_UNIVERSE'] ."/game/index.php?page=overview";
			$config['RESOURCE_URL'] = "http://". $config['USER_UNIVERSE'] ."/game/index.php?page=resources";
			$config['STATION_URL'] = "http://". $config['USER_UNIVERSE'] ."/game/index.php?page=station";
			$config['TECH_URL'] = "http://". $config['USER_UNIVERSE'] ."/game/index.php?page=research";

			//ajax
			$config['EVENTLIST_ADDR'] = "http://". $config['USER_UNIVERSE'] ."/game/index.php?page=eventList";

			//planets
			$config['PLANETS'] = null;

			//Fleet Commander
			$config['FLEET1'] = "http://". $config['USER_UNIVERSE'] ."/game/index.php?page=fleet1";
			$config['FLEET2'] = "http://". $config['USER_UNIVERSE'] ."/game/index.php?page=fleet2";
			$config['FLEETCHECK'] = "http://". $config['USER_UNIVERSE'] ."/game/index.php?page=fleetcheck";
			$config['FLEET3'] = "http://". $config['USER_UNIVERSE'] ."/game/index.php?page=fleet3";
			$config['MOVEMENT'] = "http://". $config['USER_UNIVERSE'] ."/game/index.php?page=movement";

			//ReactionTime > DetectPeriod (Must)
			//若ReactionTime是30分鐘 DetectPeriod最好是28分鐘以內
			$config['ReactionTime'] = 25 * 60;
			$config['DetectPeriod'] = 22 * 60;

			//若敵人知道attack interval就能破解防禦
			//		只需要每5分鐘攻擊一次
			//		然後在我方船隻被派離後
			//		敵方在幾秒後 馬上派出攻擊主力
			//		防禦機制將會失效
			//		若時間夠接近(1分鐘內)
			//		因為回到家的時間末約是 攻擊結束後的10~30秒
			//		回到家後會有5~10秒緩衝
			//		之後才會再次偵測是否有攻擊發生
			//		我方艦隊將可能被摧毀
			//因此CAttackPvtInterval不能太長
			//但如果太短
			//		1.對方一直連續派艦隊要一直重複逃跑 很耗重氫
			//		2.太短到只有幾秒, 很可能船隊剛回到家就撞到下一波攻擊 來不及閃
			$config['CAttackPvtInterval'] = 3 * 60;
			//****************************************************/
			//	基本上需要注意得事項
			//		1. Reaction Time 跟敵方可能到達我方星球的最短時間有關
			//			假設 
			//				若最多能派10次攻擊
			//				CAttackPvtInterval = 2分鐘
			//			 	ReactionTime = 30分鐘
			//			以下公式必須成立
			//				敵人船最快到達時間 > (10 * 2分鐘 + 30分鐘)
			//****************************************************/

			//若期間有人在別台電腦登入把session搶走
			//目前得到的資訊, 索取page失敗的回傳字串長度為57
			//一般要page成功 通常會是幾千, 目前沒有任何船隊活動的話
			//				 看到的最低長度大約是4百多
			//因此設定一個安全長度, 使索取page失敗時, 可以detect到 並且重新登入後, 再執行一次該動作
			$config['AskPageLeastStrLen'] = 57 + 300;

			//LoginManager
			//$period = rand(30, 120) * 60;
			//Defender
			//$detect_period = rand(5, 210) * 60;
			//AttackDetector
			//$config['CAttackPvtInterval']

			//目前是沒有在耕莘timeDiff的值
			//但可由LoginManager取得
			$config["timeDiff"] = -1;//對方伺服器的時間 在一次測驗得到的結果 比我的慢不到1秒
			//由於會在攻擊到達後10秒撤回船隊
			//+伺服器比我的電腦快 $config["timeDIff"] 秒
			//判斷是否要撤回船隊的迴圈 每2秒才檢測一次 (mod 2就知道誤差多少)
			//計算誤差1~2秒
			//目前測得真正回到星球的時間 末約是 攻擊到達後的 16, 17 秒鐘

			//船隊躲避攻擊回家後 會idle 5秒
			//目前觀察 計算時間 會比較早
			//真的船隊回到家事實上會慢1,2秒
			$config["idlePeriod"] = 5;

			//派船失敗重新嘗試 的sleep長度
			$config["sendFleetFailHaltPeriod"] = 10;

			//fleetcommander 208
			//$f_info["arrivetime"] = $this_arrive_time + $this->Config['timeDiff'] ;

			return $config;
		}
	}
?>