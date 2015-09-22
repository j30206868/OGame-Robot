<?php
	class AccountManager{
		public static function declareAccountInfo(){
			//Bug
			//找尋派出的艦隊info 只認星球座標 如果同時有船隊從shield回來 
			//		派出空船隊以後 取得船隊資訊會成功 一旦成功 就會以為船隊已經成功被派出去了
			//		就會空轉 實際上找到的ID是錯誤的 該攻擊就有可能成功摧毀剛要回來的船隊
			//		或是 星球上還有船隊 但是被派出去後 withdraw link錯誤 所以withdraw會無效
			//user data
			$USER_INFO['USER_ACCOUNT']  = "eternal";
			$USER_INFO['USER_PASS']     = "";
			$USER_INFO['SHIELD'] 	    = array(1, 43, 11, 1);
			$USER_INFO['ThreatenCount'] = 3;

			return $USER_INFO;
		}
	}
?>