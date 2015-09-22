<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>toggle demo</title>
  <script src="https://code.jquery.com/jquery-1.10.2.js"></script>
</head>
<body>

<style>
#button{
	width: 100%;
	height: 500px;
	font-size : 500%;
}
.PlanetDiv {
	  border: solid 1px;
	  padding: 10px;
	  width: 300px;
	  position: relative;
	  left: 0px;
	  float: left;
	  margin: 10px;
	  box-shadow: 5px 5px 2px #ccc;
}
.title {
	  font-weight: bold;
	  text-align: center;
	  padding: 5px;
	  border-bottom: solid 1px;
	  background-color: lightgrey;
}

.smallplanet {
	display:none;
}

.max {
	    padding: 10px;
	    display: block;
}
#shields li {
    width:255px;

	margin-right: 2em;

	line-height:15px;

	margin-left: 0;
	padding-left: 1em;
	display:inline;
}
</style>
<?php
	//印出所有星球圖示
	$img_fname = dirname(__FILE__). "\\OGame\\eternal\\PlanetsImgs.txt";
	$imgs_str = file_get_contents($img_fname);
	echo $imgs_str;
?>

<button id="button">
<?php
	//讀入外掛 控制bit
	//error_reporting(E_DEPRECATED);
	$realstate_fname = dirname(__FILE__). "\\OGame\\OGame\\RealState.txt";

	$real_state = file_get_contents($realstate_fname);

	if( $real_state == 0 ){
		echo "<font color='red'>外掛已關閉</font>";
	}else if( $real_state == 1 ){
		echo "<font color='green'>外掛執行中</font>";
	}else{
		echo "<font color='red'>ERROR CONTACT MANAGER!</font>";
	}
?>
</button>
<div id="dv"></div>
<?php
	$es_str = file_get_contents("OGame/eternal/Escape.txt", true);

	echo $es_str;
?>

<script>
$(document).ready(function() {
	//美化
	$( ".PlanetDiv" ).each(function( index ) {
		var title_str = $(this).attr('title');
		var title_arr = title_str.split(".");
		var coord_text = title_arr[0] + "_" + title_arr[1] + "_" + title_arr[2] + "_" + title_arr[3];
	  	$(this).prepend("<div>逃亡星球:</div>");
	  	
	  	//img title
	  	$("#"+coord_text).addClass('title');
	  	$("#"+coord_text).css("display", "block");
	  	$(this).prepend( $("#"+coord_text) );
	  	//平凡的title
	  	//$(this).prepend( "<div class='title'>星球("+coord_text+")</div>" );
	  	
	});
	$( ".max" ).each(function( index ) {
	  	$(this).text("最多承受 "+($(this).text()-1)+" 艘船攻擊");
	});

	var val = <?php echo $real_state; ?>;
	//按按鈕
	$("#button").click(function() {
	 	if(val==0){
	 		val = 1;
	 	}else if(val==1){
	 		val = 0;
	 	}else{
	 		val = 2;
	 	}

	 	$("#button").text("更新狀態中...").css({color: "gray"});

		$.ajax({
			type:'POST',
			url:'myPhp.php',
			cache: false,
			data: {
				val:val
			},
			dataType: 'json',
			success: function(msg){
				console.log(msg);
				//alert("成功"+msg);
				val = msg[0];
			 	if(val == 0){
			 		$("#button").text("外掛已關閉").css({color: "red"});
			 	}else if(val == 1){
			 		$("#button").text("外掛執行中").css({color: "green"});
			 	}else{
			 		$("#button").text("ERROR CONTACT MANAGER!").css({color: "red"});
			 	}
			}
		});
	});
});
</script>
 
</body>
</html>