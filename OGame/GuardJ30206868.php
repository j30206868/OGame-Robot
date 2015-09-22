<?php
/*
	PHP Thread 在執行while後 while內產生的一切東西都會被清除 不管用什麼存放都一樣
	若必須取出 請存入某個file裡面 然後在需要時讀出
*/
	require_once("j30206868/J30206868AccountInfo.php");
	require_once("ConfigManager.php");
	require_once("GlobalFunc.php");
	require_once("LoginManager.php");
	require_once("FleetCommander.php");
	require_once("AttackDetector.php");
	require_once("RecourseManager.php");
	//used in AttackDetector
	require_once("Planet.php");
	require_once("Defender.php");
	//used in Defender
	require_once("FleetCommander.php");


	date_default_timezone_set('Asia/Taipei');
	//c(E_DEPRECATED);// 連error都沒有
	//error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
	//error_reporting(E_ERROR);
	//error_reporting(-1);

	$Config = ConfigManager::declareConfigValue();

	//結束時會取得一次overview page
	$LoginManager = new LoginManager();
	$LoginManager->start();

	echo "\nThreaten Count: ". $Config['ThreatenCount'] . "\n";

	//會取得一次overview page
	$PLANETS = Planet::getPlanets();

	$ResourceManager = new RecourseManager($PLANETS);
	$ResourceManager->start();

	/*使用檔案管理
	$ResourceManager = new RecourseManager($PLANETS);
	var_dump( sizeof($ResourceManager->readFirstItem(array(1,43,11))) );
	$ResourceManager->appendItem("S", array(1,43,11), "button0");
	$ResourceManager->appendItem("R", array(1,42,6), "button2");

	$coor = array(1,42,6);
	$LBlocks = $ResourceManager->readFirstItem($coor);
	if( sizeof($LBlocks) == 2 )
	{//長度為2表示有東西
		echo "Ready to build page(".$LBlocks[0].") item(".$LBlocks[1].") in Planet[".$coor[0].":".$coor[1].":".$coor[2]."]\n";
		$buildResult = $ResourceManager->buildResourceItem($LBlocks[0], $LBlocks[1], $coor);
		echo "build result ". $buildResult. ".\n";
		if($buildResult == 0)
		{//可能需要更新session
			$NewLoginManager = new LoginManager();
			echo "sleep 60 seconds.\n";
			sleep(60);
			continue;
		}else if($buildResult == 1){
			//這個item被成功建造好了
			echo "isItemBuiled is set to true, this item is builded.\n";
			$isItemBuiled = true;
			$ResourceManager->removeFirstItem($coor);
		}
	}*/

	/*使用清單管理
	$ResourceManager = new RecourseManager($PLANETS);
	$ResourceManager->addItem("S", array(1,43,11), "button0");
	$ResourceManager->addItem("R", array(1,42,6), "button2");

	$popResult = array();
	$isItemBuiled = true;
	while(1==1){
		if($isItemBuiled == true)
		{//需要pop新的item了
			$popResult = $ResourceManager->popItem();
			$isItemBuiled = false;
		}

		if($popResult != "Nothing")
		{//有東西
			echo "Ready to buildResourceItem.\n";
			$buildResult = $ResourceManager->buildResourceItem($popResult['page'], $popResult['item'], $popResult['coor']);
			echo "build result ". $buildResult. ".\n";
			if($buildResult == 0)
			{//可能需要更新session
				$NewLoginManager = new LoginManager();
				echo "sleep 60 seconds.\n";
				sleep(60);
				continue;
			}else if($buildResult == 1){
				//這個item被成功建造好了
				echo "isItemBuiled is set to true, this item is builded.\n";
				$isItemBuiled = true;
			}
		}else{
			echo "List is empty, end program.\n";
			break;
		}

		$period = rand(1000, 2500);
		//$period = 10;
		echo "sleep " . $period . " seconds.\n";
		sleep($period);
	}*/
	//$ResourceManager->buildResourceItem("button4", array(1,42,11));

	if( $PLANETS ){
		echo "AttackDetector is Constructed, " . sizeof($PLANETS) ." planet(s) is protected.\n";

		for($i = 0 ; $i < sizeof($PLANETS) ; $i++){
			echo iconv("UTF-8", "big5", "Planet(".$PLANETS[$i]->num."): ". $PLANETS[$i]->name. "[".$PLANETS[$i]->coord[0].":".$PLANETS[$i]->coord[1].":".$PLANETS[$i]->coord[2]."] is detected.\n");
			//防衛
			$_defender = new Defender( $PLANETS[$i]->coord, $Config['SHIELD'], $PLANETS[$i]->href, $PLANETS[$i] );
			$PLANETS[$i]->defender = $_defender;
			$PLANETS[$i]->defender->start();
		}
	}


?>