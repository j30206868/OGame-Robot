<?php
	class TiemSync extends Thread{
		function run(){

				for($count = 1 ; $count <= 5 ; $count++){
					system("cmd.exe /c Syntime".$count.".bat");
					sleep(30);
				}
	
		}
	}
	$Timer = new TiemSync();
	$Timer->start();
?>