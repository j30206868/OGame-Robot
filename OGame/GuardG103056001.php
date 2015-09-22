<?php
/*
	PHP Thread 在執行while後 while內產生的一切東西都會被清除 不管用什麼存放都一樣
	若必須取出 請存入某個file裡面 然後在需要時讀出
*/
	require_once("AttackDetector/g103056001/G103056001AccountInfo.php");
	require_once("ConfigManager.php");
	require_once("GlobalFunc.php");
	require_once("LoginManager.php");
	require_once("AttackDetector.php");
	//used in AttackDetector
	require_once("Planet.php");
	require_once("Defender.php");
	//used in Defender
	require_once("FleetCommander.php");

	date_default_timezone_set('Asia/Taipei');
	//error_reporting(E_DEPRECATED); 連error都沒有
	error_reporting(-1);

	$Config = ConfigManager::declareConfigValue();

	//結束時會取得一次overview page
	$LoginManager = new LoginManager();
	$LoginManager->start();

	//會取得一次overview page
	$PLANETS = Planet::getPlanets();

	echo "\nThreaten Count: ". $Config['ThreatenCount'] . "\n";

	if( $PLANETS ){
		echo "AttackDetector is Constructed, " . sizeof($PLANETS) ." planet(s) is protected.\n";

		for($i = 0 ; $i < sizeof($PLANETS) ; $i++){
			echo "Planet(".$PLANETS[$i]->num."): ". $PLANETS[$i]->name. "[".$PLANETS[$i]->coord[0].":".$PLANETS[$i]->coord[1].":".$PLANETS[$i]->coord[2]."] is detected.\n";
			$_defender = new Defender( $PLANETS[$i]->coord, $Config['SHIELD'], $PLANETS[$i]->href, $PLANETS[$i] );
			$PLANETS[$i]->defender = $_defender;
			$PLANETS[$i]->defender->start();
		}
	}
?>