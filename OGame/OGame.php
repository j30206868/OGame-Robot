<?php
	$file = file("./OGame/ServerState.txt");
	if ($file) {
		foreach ($file as $key => $value) {
			if($value==0){
				echo "Server is stopped.";
			}else if($value==1){
				echo "Server is working.";
			}
		}
	}
?>