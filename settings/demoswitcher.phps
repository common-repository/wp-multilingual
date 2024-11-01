<?php
	echo "<div id='switchlangs'>";
	foreach($GLOBALS['AviableLanguages'] as $_K=>$_I){
		if ($_I['lang_shortcode'] != $language){
			$link = WP_Multilingual::LanguageLink($_SERVER['REQUEST_URI'], $_I['lang_shortcode']);
			echo "<a href='".$link."'><span>".$_I['name']."</span></a>";
		}
	}
	echo "</div>";
?>
