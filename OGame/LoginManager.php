<?php

	class LoginManager extends Thread{
		var $overview;

		function __construct(){
//更新login session syn start
			GlobalFunc::synExecute("Login Manager Get Session",function(){
				$this->overview = $this->newSessionGetOverview();
			});
			$this->checkServerTimeDiff($this->overview);
//syn
		}

		function run(){
			//ini_set('date.timezone','UTC');  
			date_default_timezone_set('Asia/Taipei');
			$config = ConfigManager::declareConfigValue();
			$before_wakeup = 0;
			while(1==1){
				$count = 0;
				$period = rand(3000, 9000);

				$one_minute = 600;
				$before_wakeup = $period;
				while($count < $period){
					$count += $one_minute; 
					echo date('l jS \of F Y h:i:s A').": Log Manager Alive. before ". $before_wakeup ." secs to ask session again.\n"; // bat alive reply
					$before_wakeup = $period - $count;
					sleep($one_minute);
				}	
//更新login session syn start
				$overview_string = GlobalFunc::synExecute("Login Manager Get Session",function(){
					return $this->newSessionGetOverview();
				});
//syn end
				$this->checkServerTimeDiff($overview_string);
			}
		}

		function checkServerTimeDiff($overview_string){
			date_default_timezone_set('Asia/Taipei');
			$config = ConfigManager::declareConfigValue();

			$now_time = date('H:i:s', time());
			//每次取得新session時 檢測本機時間 與 伺服器時間的差距
			$ogame_clock = GlobalFunc::getOGameServerTime($overview_string);
			$diff_in_sec = GlobalFunc::timeDiffInSecond($now_time, $ogame_clock);
			echo "\n============================================================================\n";
			echo "ogame_clock : $ogame_clock\n";
			echo "\nLoginManager: This computer is ". $diff_in_sec. " sec different from server.\n";

			//write time log to time.log file
			if($ogame_clock !== false){
				$time_log = iconv("UTF-8", "big5", date('l jS \of F Y h:i:s A') . " | 外掛時間:$now_time | OGame網頁時間:$ogame_clock | 時間差: " . $diff_in_sec . " 秒\n");
				file_put_contents($config['TimeLog'], $time_log, FILE_APPEND | LOCK_EX);
			}else{
				echo "ogame_clock === false, don't write to time.log.\n";
			}

			if( $diff_in_sec > 0 ){
				echo "Our System time is faster than OGame Server.\n";
			}else if( $diff_in_sec < 0 ){
				echo "Our System time is slower than OGame Server.\n";
			}

			if( abs($diff_in_sec) >= 20 )
			{//時間差超過3秒
				echo "Time difference is too large, synchronize host time with server.\n";
				system("cmd.exe /c ./../Timer.bat");
			}

			echo "\n============================================================================\n";

		}

		function newSessionGetOverview(){
			$config = ConfigManager::declareConfigValue();
			$COOKIE_FILE = $config['COOKIE_FILE'];
			$USER_UNIVERSE = $config['USER_UNIVERSE'];
			$USER_ACCOUNT = $config['USER_ACCOUNT'];
			$USER_PASS = $config['USER_PASS'];

			//First to ask for data
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "http://tw.ogame.gameforge.com/main/login");
			curl_setopt($ch, CURLOPT_POST, true); // 啟用POST
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//not output result
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);//Ignore redirection
			curl_setopt($ch, CURLOPT_COOKIESESSION, 1);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $COOKIE_FILE);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $COOKIE_FILE);
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query( array( "kid"=>"",
																		  "uni"	 => $USER_UNIVERSE,
																		  "login"=> $USER_ACCOUNT,
																		  "pass" => $USER_PASS ) )); 
			$login_result = curl_exec($ch); 
			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = substr($login_result, 0, $header_size);
			$body = substr($login_result, $header_size);
			
			curl_close($ch);

			//extract query url and data info
			$location = $this->extractHeader($header);
			//print_r("Out Location = ". $location . PHP_EOL);

			//Ask for new session id
			$login_return = GlobalFunc::httpGet($location, $COOKIE_FILE);

			// modify cookie file for correcting cookie edit mistake (session live time is set to 0)
			$this->editCookieFile($COOKIE_FILE);

			// extract correct session id
			$SESSION = $this->extractSeesion( $login_return );

			//updateConfigInfoByNewSession( $SESSION );
			$over_page = GlobalFunc::httpGet( $config['OVERVIEW_URL'] . "&" . $SESSION , $config['COOKIE_FILE'] );

			if( $SESSION ){
				echo "Login Manager got a new session.\n";
			}else{ 
				echo "Get Session Fail."; 
				return 0;
			}

			return $over_page;
		}

		function newSession(){
			$config = ConfigManager::declareConfigValue();
			$COOKIE_FILE = $config['COOKIE_FILE'];
			$USER_UNIVERSE = $config['USER_UNIVERSE'];
			$USER_ACCOUNT = $config['USER_ACCOUNT'];
			$USER_PASS = $config['USER_PASS'];

			//First to ask for data
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "http://tw.ogame.gameforge.com/main/login");
			curl_setopt($ch, CURLOPT_POST, true); // 啟用POST
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//not output result
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);//Ignore redirection
			curl_setopt($ch, CURLOPT_COOKIESESSION, 1);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $COOKIE_FILE);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $COOKIE_FILE);
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query( array( "kid"=>"",
																		  "uni"	 => $USER_UNIVERSE,
																		  "login"=> $USER_ACCOUNT,
																		  "pass" => $USER_PASS ) )); 
			$login_result = curl_exec($ch); 
			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = substr($login_result, 0, $header_size);
			$body = substr($login_result, $header_size);
			
			curl_close($ch);

			//extract query url and data info
			$location = $this->extractHeader($header);
			//print_r("Out Location = ". $location . PHP_EOL);

			//Ask for new session id
			$login_return = GlobalFunc::httpGet($location, $COOKIE_FILE);

			// modify cookie file for correcting cookie edit mistake (session live time is set to 0)
			$this->editCookieFile($COOKIE_FILE);

			// extract correct session id
			$SESSION = $this->extractSeesion( $login_return );

			//updateConfigInfoByNewSession( $SESSION );
			$over_page = GlobalFunc::httpGet( $config['OVERVIEW_URL'] . "&" . $SESSION , $config['COOKIE_FILE'] );

			if( $SESSION ){
				echo "Login Manager got a new session.\n";
			}else{ 
				echo "Get Session Fail.\n";
				return 0;
			}

			return $SESSION;
		}

		/*function updateConfigInfoByNewSession($SESSION){
			global $OVERVIEW_URL;
			global $RESOURCE_URL;
			global $USER_UNIVERSE;

			$OVERVIEW_URL = "http://". $USER_UNIVERSE ."/game/index.php?page=overview&". $SESSION;
			$RESOURCE_URL = "http://". $USER_UNIVERSE ."/game/index.php?page=resources&". $SESSION;

			//ajax
			$EVENTLIST_ADDR = "http://". $USER_UNIVERSE ."/game/index.php?page=eventList&". $SESSION;
		}*/

		function extractHeader($header){
			$start_key = "http://";
			$end_key = "Vary";
			$data_start_pos = strrpos($header, $start_key, 0);
			$data_end_pos = strrpos($header, $end_key, 0);
			$data_length = $data_end_pos - $data_start_pos;

			/*echo "Header = ". $header;
			echo "start_key = ". $start_key;
			echo "end_key = ". $end_key;
			echo "data_start_pos = ". $data_start_pos;
			echo "data_end_pos = ". $data_end_pos;
			echo "data_length = ". $data_length;*/

			$login_data = substr($header, $data_start_pos, $data_length); 

			//echo "Location = ". $login_data;

			return $login_data;
		}

		function extractSeesion($script){
			$doc = GlobalFunc::loadHtml($script);
			//print_r($doc); 

			$value = $doc->textContent;
			//print_r("Value: ".$value."\n\r"); 

			$s_key = "http://";
			$e_key = "'";
			$s_pos = strrpos($value, $s_key, 0);
			$e_pos = strrpos($value, $e_key, -1);
			$len = $e_pos - $s_pos;
			$location = substr($value, $s_pos, $len);
			//print_r("s_pos: ".$s_pos);
			//print_r("e_pos: ".$e_pos);
			//print_r("len: ".$len);
			//print_r("Location: ".$location);

			$session_s_key = "PHPSESSID=";
			$session_pos = strrpos($location, $session_s_key);
			$session = substr($location, $session_pos);
			//print_r("session: ".$session);

			return $session;
			//return $location;
		}

		function editCookieFile($filepath){
			//read file
			$file = file($filepath);

			//write file later
			$myfile = fopen( $filepath , "w") or die("Unable to open file!");

			$new_file_content = "";
			if ($file) {
				$correct_expire_time = '0';

				foreach ($file as $key => $value) {
					$first_chr = substr($value, 0 , 1);
					$new_line_content = "";
					if($first_chr != "#" && strlen($value) >= 5){ // at least 5 field
						//echo $key.": ".$value."</br>";
						$array = explode("	", $value);
						//var_dump($array);
						$domain_index = 0; //The first
						$time_index = 4; //The fifth
						if( $array[$domain_index] == "s113-tw.ogame.gameforge.com" ){

							if($array[$time_index] != '0'){
								//if the expire time is the longest then update
								if( $array[$time_index] > $correct_expire_time ){
									$correct_expire_time = $array[$time_index];
								}

							}else{

								//correct the wrong one
								$array[$time_index] = $correct_expire_time;

								//write into new line
								$new_line_content .= join("	",$array);
							}
						}
					}

					if($new_line_content == ""){
						$new_line_content .= $value;
					}

					//write cookie file
					$new_file_content .= $new_line_content;
				}
			}
			//echo $new_file_content;
			file_put_contents($filepath, $new_file_content);
		}
}
?>