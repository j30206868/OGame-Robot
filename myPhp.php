<?php
	error_reporting(E_DEPRECATED);
	$control_fname = "C:\Program Files (x86)\Web\www\OGame\OGame\Control.txt";
	$realstate_fname = "C:\Program Files (x86)\Web\www\OGame\OGame\RealState.txt";

	$arr = array('a' => 2);
	if( isset($_POST["val"]) ){
		if( $_POST["val"]==0 || $_POST["val"]==1){
			file_put_contents($control_fname, $_POST["val"]);

			while( trim($real_state) != trim($_POST["val"])){
				$real_state = file_get_contents($realstate_fname);
				sleep(1);
			}
			//sleep(3);
			$real_state = file_get_contents($realstate_fname);
			$arr[0] = trim($real_state);
		}
	}
	echo json_encode($arr);
?>