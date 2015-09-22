<?php
/*
	PHP Thread 在執行while後 while內產生的一切東西都會被清除 不管用什麼存放都一樣
	若必須取出 請存入某個file裡面 然後在需要時讀出
*/
	require_once("eternal\\EternalAccountInfo.php");
	require_once("ConfigManager.php");
	require_once("GlobalFunc.php");

	//Escape
	require_once("EscapeFilter.php");

	require_once("LoginManager.php");
	require_once("AttackDetector.php");
	require_once("RecourseManager.php");
	//used in AttackDetector
	require_once("Planet.php");
	require_once("Defender.php");
	//used in Defender
	require_once("FleetCommander.php");

	date_default_timezone_set('Asia/Taipei');
	//error_reporting(E_DEPRECATED); 連error都沒有
	//error_reporting(E_ERROR);
	//error_reporting(-1);

	$Config = ConfigManager::declareConfigValue();

	//結束時會取得一次overview page
	$loginManager = new LoginManager();
	

	echo "\nThreaten Count: ". $Config['ThreatenCount'] . "\n";

	//會取得一次overview page
	$PLANETS = Planet::getPlanets();

	

	if( $PLANETS ){
		$loginManager->start();
		$ResourceManager = new RecourseManager($PLANETS);
		$ResourceManager->start();

		if( isset($PLANETS) ){
			echo "AttackDetector is Constructed, " . sizeof($PLANETS) ." planet(s) is protected.\n";
		}else{
			echo "Failed to get planet list, login and get planet list again.\n";
			$loginManager->newSessionGetOverview();
			$PLANETS = Planet::getPlanets();
		}

		for($i = 0 ; $i < sizeof($PLANETS) ; $i++){
			echo iconv("UTF-8", "big5", "Planet(".$PLANETS[$i]->num."): ". $PLANETS[$i]->name. "[".$PLANETS[$i]->coord[0].":".$PLANETS[$i]->coord[1].":".$PLANETS[$i]->coord[2]."](".$PLANETS[$i]->coord[3].") is detected.\n");
			$_defender = new Defender( $PLANETS[$i]->coord, $Config['SHIELD'], $PLANETS[$i]->href, $PLANETS[$i] );
			$PLANETS[$i]->defender = $_defender;
			$PLANETS[$i]->defender->start();
		}
	}else{
		die();
	}
?>