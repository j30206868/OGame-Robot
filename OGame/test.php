<?php
	require_once("AttackDetector/eternal/EternalAccountInfo.php");
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
	//error_reporting(E_ERROR);
	error_reporting(-1);

	$Config = ConfigManager::declareConfigValue();
	//結束時會取得一次overview page
	$LoginManager = new LoginManager();
	$LoginManager->start();

	$myCommander = new FleetCommander(array(1,42,8), array(1,42,6), "");

	while(1==1)
	{
		if(file_get_contents($Config["KEYFILE"], true) == 1)
		{//撤船
			$myCommander->sendAllFleetsToShield();
		}
	}
?>