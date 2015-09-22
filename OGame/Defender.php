<?php
	class Defender extends Thread{
		var $shield_coord = null;
		var $p_coord = array(0, 0, 0);
		var $fCommander = null;
		var $href = "";
		var $attackArriveTime = null;
		var $Config;
		var $planet;
		var $fleetInfo = 0;

		function __construct( $this_coord, $s_coord, $_href, $_planet ){
			$this->shield_coord = $s_coord;
			$this->p_coord = $this_coord;
			$this->href = $_href;
			$this->planet = $_planet;

			$this->Config = ConfigManager::declareConfigValue();
			$this->fCommander = new FleetCommander( $this->p_coord, $this->shield_coord, $this->href );
		}

		function defendAttack(){
			//船隊先離開 因為被呼叫時到達時間已經在1分鐘內
			//派出船隊以後才可以開始執行緒 否則多星球執行緒會搶著切換星球頁面
			echo "Defender[".$this->p_coord[0].":".$this->p_coord[1].":".$this->p_coord[2]."]: ";
			echo "sendAllFleetsToShield called.\n";
			return $this->fCommander->sendAllFleetsToShield(); //fleet info 會存在fCommander裡面
		}

		function run(){

			/*
			船隊ID:1026133
			抵達時間 - 飛行長度 = 出發時間 fleetInfo["starttime"]
			現在時間 - 出發時間 = 回來所需時間

			#攻擊到達時間 = 所有針對該星球的攻擊 最近的那個 但如果攻擊之間只相距不到1分鐘 則視為同一個攻擊事件
			#不用定期抓event list 因為defender反應的時候攻擊就已經快要到達了

			現在時間 + 回來所需時間 - 攻擊到達時間 > 10 秒
			召回
			
			*/

			/*
				以下三個重要的動作要做失敗判斷
				1.$AttackDetector->detectAttack();
				2.$this->defendAttack()
				3.Withdraw fleets
			*/

			while(1==1){
			//<These code should be sychronized>
				//echo "Defender[".$this->p_coord[0].":".$this->p_coord[1].":".$this->p_coord[2]."]: ask for key.\n";
				
				$resultlen = GlobalFunc::synExecute("Defender[".$this->p_coord[0].":".$this->p_coord[1].":".$this->p_coord[2]."](".$this->p_coord[3]."):",function(){
					date_default_timezone_set('Asia/Taipei');

					$last_update_time = file_get_contents($this->Config['DLastUpt'], true);
		            echo " last update time: ". date('H:i:s', $last_update_time). "\n";
					$nowTime = time();
					if( ($nowTime - $last_update_time) <= 5 ){
						//5秒內更新過的 可以直接使用
						echo "Last update time: ".date('H:i:s', $last_update_time)."; File is updated within 5 second.\n";
						//確保通知外面 已經拿到想要的東西了
						return $this->Config['AskPageLeastStrLen']+1;
					}else{
						echo "Last update time: ".date('H:i:s', $last_update_time)."; File is not updated ask for overview page\n";
						//更新overview string的內容
						$overview_string = "";
						$overview_string = GlobalFunc::getOverviewPage();

						//echo "\noverview result strlen: " .strlen($overview_string) . "\n"; 
						if(strlen($overview_string) < $this->Config['AskPageLeastStrLen']){
							//access failed;
							echo "Defender ask for overview page failed, return 0\n";
							return 0;
						}

						$event_list = GlobalFunc::getEventList();

						//update overview page and event list info
						$put1 = file_put_contents($this->Config['ADOverview'], $overview_string);
						$put2 = file_put_contents($this->Config['ADEventList'], $event_list);

						//update last update time that other threads can check
						$put3 = file_put_contents($this->Config['DLastUpt'], time());
						echo " Put1=$put1, Put2=$put2, Put3=$put2\n";
					}

					//成功取得頁面
					return strlen($overview_string);
				});
				
				echo "Defend read overview page result length: $resultlen\n";
				if( $resultlen < $this->Config['AskPageLeastStrLen']){
					echo "Defender: Error occur, failed to update overview page. login in again, resltlen: ". $resultlen ."\n";
					$LoginManager = new LoginManager();
					continue; //登入後 重跑回圈
				}

			//</These code should be sychronized>	
				//偵測攻擊
				$AttackDetector = new AttackDetector($this->p_coord);
				$arrivetime = $AttackDetector->detectAttack();

				//$arrivetime == 0表示不需要做任何事
				//$arrivetime == "xx:xx:xx" 表示在這個時間攻擊會到達
				$fleet_already_withdraw = false;
				$withdraw_arr_time = 0;

				//偵測到攻擊則開始抵禦
				//不是數字 就表示有攻擊
				echo "Defender: get returned arrivetime: ";
				var_dump($arrivetime);
				if( strcmp($arrivetime, "0") != 0 ){
					echo "[".$this->p_coord[0].":".$this->p_coord[1].":".$this->p_coord[2]."](".$this->p_coord[3].") start defese.\n";

					$this->fleetInfo = $this->defendAttack(); //會更新fleet info

					if( $this->fleetInfo == 0){
						$halt_period = $this->Config["sendFleetFailHaltPeriod"];
						echo "Defender: fleetInfo return 0, error occur.\n";
						echo "Defender: No withdraw link to withdraw fleets.\n";
						echo "Ignore withdraw command. Sleep ".$halt_period." second.\n";
						if(strlen($halt_period) == 0){
							$halt_period = 10;
						}
						sleep( $halt_period );
						continue;
					}
						
					echo "Fleet Start Time: ". date('H:i:s', $this->fleetInfo["starttime"]) ."\n";
					echo "Fleet Withdraw link: ". $this->fleetInfo["withdraw_link"] ."\n";
					
					while(1==1){
						//算出回來所需時間 每個loop更新一次
						$withdraw_arr_left_sec = time() - $this->fleetInfo["starttime"];

						$attack_arr_left_sec = GlobalFunc::timeFromNowInSecond($arrivetime, false);

						if( (($withdraw_arr_left_sec - $attack_arr_left_sec) >= 60) && 
							($fleet_already_withdraw == false) 
						){//撤退回到星球所需時間 > 攻擊到達剩餘時間
						  //可以撤退船隊了 
//要發送撤回船隊的指令
//Syn start
							$result = GlobalFunc::synExecute("Fleet Withdraw",function(){

								// start time為預估時間 取不到實際值 因此很可能會有誤差
								$result = GlobalFunc::httpGet( $this->fleetInfo["withdraw_link"], $this->Config['COOKIE_FILE'] );
								echo "Withdraw Link: ". $this->fleetInfo["withdraw_link"] . "\n";
								return $result;

							});

							if( strlen($result) < $this->Config['AskPageLeastStrLen']){
								echo "Error occur, Fleet Withdraw result page len < AskPageLeastStrLen, may login in again.\n";
								$LoginManager = new LoginManager();
								continue;
							}
//Syn end
							//標記船隊已經在回程途中
							//取得艦隊會到家的時間
							$fleet_already_withdraw = true;
							$withdraw_arr_time = $withdraw_arr_left_sec + time();
							
							echo "Defender: [".$this->p_coord[0].":".$this->p_coord[1].":".$this->p_coord[2]."](".$this->p_coord[3].") ";
							echo "withdraw fleets. result len: ".strlen($result).".\n";

						}else if( $fleet_already_withdraw == false){

							echo "Defender: [".$this->p_coord[0].":".$this->p_coord[1].":".$this->p_coord[2]."](".$this->p_coord[3].") ";
							echo "if ". ($withdraw_arr_left_sec - $attack_arr_left_sec). " >= 60, withdraw fleets.\n";
		
						}else if( ($withdraw_arr_time - time()) < 0 )
						{//船隊順利回到家
							$idle_sec = $this->Config['idlePeriod'];

							echo "Defender: [".$this->p_coord[0].":".$this->p_coord[1].":".$this->p_coord[2]."](".$this->p_coord[3].") ";
							echo "Fleets got home, lose defending ability for next 5 seconds.\n";
							sleep($idle_sec); // prevent wrong prediction of the time that fleets arrive
							break;
							//break以後馬上會重跑回圈 若無sleep很可能因為誤差 船還沒到家
							//若有下一輪攻擊 很可能造成程式失效
							//因此當抵達的 $idle_sec 秒將沒有抵禦能力
							//(船通常會晚個10~30秒到家,依據目前設定是10秒)
						}else{

							echo "Defender: [".$this->p_coord[0].":".$this->p_coord[1].":".$this->p_coord[2]."](".$this->p_coord[3].") ";
							echo "Fleets will get home after ".($withdraw_arr_time - time())." second.\n";

						}

						sleep(2);
					}
				}else{
					echo "Defender: [".$this->p_coord[0].":".$this->p_coord[1].":".$this->p_coord[2]."](".$this->p_coord[3].") ";
					//$detect_period = $this->Config['DetectPeriod'];
					$detect_period = rand( $this->Config['DetectPeriod']/2,  $this->Config['DetectPeriod']);
					
					echo "sleep ".$detect_period." second.\n";
					sleep( $detect_period );
					//sleep(60);
				}
				

			}
			echo "Defend loop out\n";
			
		}

	}
?>