<?php
	require_once("j30206868/J30206868AccountInfo.php");
	require_once("ConfigManager.php");

	class TiemSync extends Thread{
		function run(){
			$config = ConfigManager::declareConfigValue();
			$period = 10 * 60;
			while(1==1){

				for($count = 1 ; $count <= 5 ; $count++){
					system("cmd.exe /c Syntime".$count.".bat");
					sleep(30);
				}
				

				echo "Sleep for 10 mins\n";
				sleep($period);
			}
		}
	}
	$Timer = new TiemSync();
	$Timer->start();
?>