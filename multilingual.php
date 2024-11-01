<?php


/*
	Plugin Name: WP_Multilingual
	Plugin URI: http://made.com.ua/multilingual/
	Description: WP as MultiLingual Content Managment/Blog System. Made by <a href='http://www.mediastudio.unu.edu/en/'>UNU Media Studio</a> and <a href='http://made.com.ua/'>Oleg Butuzov</a>. 
	Author: Oleg Butuzov
	Version: 1.3.4.15
	Author URI: http://www.mediastudio.unu.edu/
*/

##################################################################################
##
##	 Copyright (C) 2008  United Nations University
##
##   This programme is free software developed by Oleg Butuzov for
##	 the United Nations University: you can redistribute it and/or modify
##   it under the terms of the GNU General Public License as
##	 published by  the Free Software Foundation, version 3 of the
##	 License, or (at your option) any later version.
##
##   This program is distributed in the hope that it will be useful,
##	 but WITHOUT ANY WARRANTY; without even the implied warranty of
##   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the  GNU
##   General Public License for more details.
##	 You should have received a copy of the GNU General Public License
##	 along with this program.  If not, see  http://www.gnu.org/licenses/.
##
##################################################################################



################################################################################### 
##
##	 Thanks David Jimenes, Luis Patron, Brendan Barret, and special thanks for Sean Wood
##	 (without who's vision there would be no plug-in) thank you UNU people, thanks for help and team work.
##
#################################################################################### 


##################################################################################
##
##	Client Side
##
##################################################################################

	if (!isset($table_prefix)){
		WP_Multilingual::FindWPConfig(dirname(dirname(__FILE__)));
		include_once $confroot."/wp-config.php";
		include_once $confroot."/wp-admin/admin-functions.php";
	}

	define(P_LANGS, $table_prefix.'langs');
	define(P_LANGS2TRANS, $table_prefix.'langs2translations');

	if ( !defined('PHP_EOL') ) define('PHP_EOL', "\r\n");

	define('MULTILINGUALDIR', (basename(dirname(__FILE__)) != 'plugins') ? basename(dirname(__FILE__))."/" :  '' );
	add_action('activate_'.MULTILINGUALDIR.basename(__FILE__), array('WP_Multilingual', 'WP_MultilingualActivate'));
	add_action('deactivate_'.MULTILINGUALDIR.basename(__FILE__), 	array('WP_Multilingual', 'WP_MultilingualDeactivate'));

	define('MULTILINGUAL_DOMAIN', 'multilingual');





	switch($_REQUEST['side']){
		case 'ajax':

			if ( !is_user_logged_in() ) 	die('you cant edit laguages, you not editor or administrator...');
			WP_Multilingual::WP_MultilingualLocaliztion();
			
			header("Content-Type: text/html; charset=utf-8");
			
			/*
			** Language edition
			*/
			$currentuser = wp_get_current_user();
			if ($currentuser->allcaps['administrator'] == true){
				switch($_REQUEST['action']){

					case 'switchinfo':
						$optionsdata = get_option('wpmultilingial_settings');
						$optionsdata = is_array($optionsdata) ? $optionsdata : array();
						if (isset($_POST['position']) && in_array($_POST['position'], array('footer', 'header', 'costum'))){
							$optionsdata['position'] = $_POST['position'];
							$optionsdata['type'] 	 = $_POST['type'];
							$optionsdata['css'] 	 = $_POST['css'];
						} else {
							$optionsdata = array();
						}
                                                
                                                $optionsdata['http_user_language'] = ($_POST['http_user_language'] == 1) ? true : false ;
                                                
						update_option('wpmultilingial_settings', $optionsdata);

						die(__('Language switcher settings saved', MULTILINGUAL_DOMAIN));
					break;
					// WP_Multilingual uninstallation
					case 'removepoly':
						if (isset($GLOBALS['current_plugins']) && is_array($GLOBALS['current_plugins']) && count($GLOBALS['current_plugins']) > 0){
							foreach($GLOBALS['current_plugins'] as $key=>$plugin){
								if ($plugin == MULTILINGUALDIR.basename(__FILE__)){
									unset($GLOBALS['current_plugins'][$key]);
									update_option('active_plugins', $GLOBALS['current_plugins']);
									break;
								}
							}
						
							WP_Multilingual::WP_MultilingualUninstall();
							WP_Multilingual::WP_MultilingualDeactivate();
						}
					break;
					// WP_Multilingual installation
					case 'installLanguage':
						$in = $_GET;
						if (isset($GLOBALS['current_plugins']) && is_array($GLOBALS['current_plugins']) && count($GLOBALS['current_plugins']) > 0){
							foreach($GLOBALS['current_plugins'] as $item){
								if (basename($item) == 'deans_fckeditor.php'){
									$fckInstalled = true;
								}
							}
						}

						if (get_option('poliglot_install') == 'installed' && isset($fckInstalled)){
							echo 'No cheating ok?';
						} else {

							$file = file(dirname(__FILE__)."/settings/locale.txt");
							if (isset($file) && is_array($file) && count($file) > 0){
								foreach($file as $item){
									$p = explode("\t", trim($item));
									if ($p[1] == $in['language']){
										$found = true;
										$dir = $p[2];
										break;
									}
								}
							}
					
							if ($found === true){


									$parts = explode("/", $p['3']);

									$sql = "INSERT INTO ".$GLOBALS['table_prefix']."langs (lang_title,lang_locale, blogname,blogdescription,lang_shortcode,dir,lang_status,lang_order) VALUES ('".WP_Multilingual::SafeVar(trim($parts[0])).(trim(WP_Multilingual::SafeVar(trim($parts[1])) != '')?' / '.WP_Multilingual::SafeVar(trim($parts[1])):'')."','".WP_Multilingual::SafeVar($p[1])."', '".WP_Multilingual::SafeVar(get_option('blogname'))."','".WP_Multilingual::SafeVar(get_option('blogdescription'))."','".WP_Multilingual::SafeVar($p[0])."', '".(($p[2] == 'ltr') ? 1 :-1)."','1','1')";

									$GLOBALS['wpdb']->query($sql);
									$res = $GLOBALS['wpdb']->get_results("SELECT ID, post_content, post_title, post_excerpt FROM ".$GLOBALS['table_prefix']."posts ", ARRAY_A);
									if (isset($res) && is_array($res) && count($res) > 0){
										foreach($res as $key=>$item){
										$translation = array(
											$p[0] => array(
												'post_title' =>WP_Multilingual::SafeVar(trim( $item['post_title'])),
												'post_content' => WP_Multilingual::SafeVar(trim($item['post_content'])),
												'post_except' => WP_Multilingual::SafeVar(trim($item['post_except'])),
											),
										);

										$str = serialize(array(base64_encode(serialize(unserialize(serialize($translation))))));

										$GLOBALS['wpdb']->query("INSERT INTO ".$GLOBALS['table_prefix']."postmeta (post_id,meta_value,meta_key) VALUES ('".$item['ID']."', '".$str ."', 'translations')");
										$GLOBALS['wpdb']->query("INSERT INTO ".$GLOBALS['table_prefix']."postmeta (post_id,meta_value,meta_key) VALUES ('".$item['ID']."', '".WP_Multilingual::SafeVar(serialize(unserialize(serialize($translation))))."', 'searchsyn')");

										}
									}

									$GLOBALS['wp_object_cache']->flush();

									add_option('poliglot_install', 'installed', '', 'no');

									/******/
									if ($GLOBALS['wp_rewrite']->permalink_structure != '')	WP_Multilingual::GenerateRewriteRules();
									/******/


									echo 'done';

							} else {
								echo __('Sorry, lang code not found.', MULTILINGUAL_DOMAIN);
							}


						}


					break;
					// load languages
					case 'loadlangauges':
						$res = $GLOBALS['wpdb']->get_results("SELECT * FROM ".$GLOBALS['table_prefix']."langs ORDER BY lang_order ASC", ARRAY_A);
						echo "<table  class='widefat'>
							  <thead><tr><th scope='col'>".__('Language', MULTILINGUAL_DOMAIN)."</th><th scope='col'>".__('Region', MULTILINGUAL_DOMAIN)."</th><th scope='col'>".__('Lang Code (2 or 3 chars)', MULTILINGUAL_DOMAIN)."</th><th scope='col'>".__('Locale', MULTILINGUAL_DOMAIN)."</th><th colspan='3' style='text-align: center'>".__('Actions', MULTILINGUAL_DOMAIN)."</th><th colspan='2' style='text-align: center'>".__('Priority', MULTILINGUAL_DOMAIN)."</th></tr></thead><tbody id='the-list'>
						";

						
						foreach($res as $key=>$item){
							$langaugeName = explode("/", $item['lang_title']);
							echo "<tr class='".((($key%2)==1)?'':'alternate')." '><th scope='row' style='text-align: center'>".$langaugeName[0]."</th><td>".(($langaugeName[1] != '') ? $langaugeName[1] : '-')."</td><td>".$item['lang_shortcode']."</td><td  >".$item['lang_locale']."</td><td style='text-align: center'><a href='javascript:editLanguage(\"".$item['lang_shortcode']."\")'>".__('Edit', MULTILINGUAL_DOMAIN)."</a></td><td style='text-align: center'>".($item['lang_order'] != 1 ? '<a href="javascript:deleteLang(\''.$item['lang_locale'].'\')">'.__('Delete', MULTILINGUAL_DOMAIN).'</a>' : '<strong>'.__('Default language', MULTILINGUAL_DOMAIN).'</strong>')."</td><td style='text-align: center'>".(($item['lang_order'] != 1)?(($item['lang_status'] == '-1')?'<a href="javascript:activation(\''.$item['lang_shortcode'].'\',\'activate\')">'.__('Activate', MULTILINGUAL_DOMAIN).'</a>':'<a href="javascript:activation(\''.$item['lang_shortcode'].'\',\'deactivate\')">'.__('Deactivate', MULTILINGUAL_DOMAIN).'</a>'):__('Active', MULTILINGUAL_DOMAIN))."</td><td style='text-align: center'>".(	(count($res) != 1 && $item['lang_order'] != 1) ? '<a href="javascript:order(\''.$item['lang_shortcode'].'\', \'up\')">'.__('UP', MULTILINGUAL_DOMAIN).'</a>' : '&nbsp;' )."</td><td style='text-align: center'>".(	(count($res) != 1 && $item['lang_order'] != count($res)) ? '<a href="javascript:order(\''.$item['lang_shortcode'].'\', \'down\')">'.__('DOWN', MULTILINGUAL_DOMAIN).'</a>' : '&nbsp;' )."</td></tr>";
						}
						echo "</tbody></table>";
					break;
					// activateion/deactivation of language
					case 'activation':
						$in = $_POST;
						$res = $GLOBALS['wpdb']->get_row("SELECT count(*) FROM ".$GLOBALS['table_prefix']."langs WHERE lang_shortcode = '".WP_Multilingual::safeVar($in['lang'])."'", ARRAY_A);
						if ($res['count(*)'] == 1){
							switch($in['event']){
								case 'activate':
									$status = '1';
								break;
								case 'deactivate':
									$status = '-1';
								break;
							}
							$GLOBALS['wpdb']->query("UPDATE ".$GLOBALS['table_prefix']."langs SET lang_status = '".$status."' WHERE lang_shortcode='".WP_Multilingual::SafeVar($in['lang'])."'");
							if ($GLOBALS['wp_rewrite']->permalink_structure != '')	WP_Multilingual::GenerateRewriteRules();
						}
						$GLOBALS['wp_object_cache']->flush();
					break;
					// save info about order
					case 'order':

						$in = $_POST;
						$CurrentLanguage = $GLOBALS['wpdb']->get_row("SELECT * FROM ".$GLOBALS['table_prefix']."langs WHERE lang_shortcode = '".WP_Multilingual::safeVar($in['lang'])."'", ARRAY_A);
						if (!is_null($CurrentLanguage)){

							$currentPosition = $CurrentLanguage['lang_order'];
							$res = $GLOBALS['wpdb']->get_row("SELECT count(*) FROM ".$GLOBALS['table_prefix']."langs", ARRAY_A);
							$AllResults = $res['count(*)'];

							switch($in['directon']){
								case 'up':
									if ($currentPosition > 1 && $currentPosition != 1){
										$GLOBALS['wpdb']->query("UPDATE ".$GLOBALS['table_prefix']."langs SET lang_order = '".($currentPosition)."' WHERE lang_order = '".($currentPosition-1)."'");
										$GLOBALS['wpdb']->query("UPDATE ".$GLOBALS['table_prefix']."langs SET lang_order = '".($currentPosition-1)."' WHERE lang_shortcode = '".WP_Multilingual::SafeVar($in['lang'])."'");

										if (($currentPosition-1) == 1){
											$GLOBALS['wpdb']->query("UPDATE ".$GLOBALS['table_prefix']."langs SET lang_status = '1' WHERE lang_shortcode = '".WP_Multilingual::SafeVar($in['lang'])."'");
										}
										if ($GLOBALS['wp_rewrite']->permalink_structure != '')	WP_Multilingual::GenerateRewriteRules();
									}
								break;
								case 'down':

									if ($currentPosition >= 1 && $currentPosition < $AllResults){
										$GLOBALS['wpdb']->query("UPDATE ".$GLOBALS['table_prefix']."langs SET lang_order = '".($currentPosition)."' WHERE lang_order = '".($currentPosition+1)."'");
										$GLOBALS['wpdb']->query("UPDATE ".$GLOBALS['table_prefix']."langs SET lang_order = '".($currentPosition+1)."' WHERE lang_shortcode = '".WP_Multilingual::SafeVar($in['lang'])."'");
										if ($GLOBALS['wp_rewrite']->permalink_structure != '')	WP_Multilingual::GenerateRewriteRules();
									}
								break;
							}

						}
					break;
					// show edit language information
					case 'editlangauge':
						$in = $_GET;
						$res = $GLOBALS['wpdb']->get_row("SELECT * FROM ".$GLOBALS['table_prefix']."langs WHERE lang_shortcode = '".WP_Multilingual::SafeVar($in['lang'])."'", ARRAY_A);
						if (!is_null($res)){
							$type = 'existing';
							$l = explode("/", $res['lang_title']);
							$toEdit = array('code' => $res['lang_shortcode'], 'dir' => $res['dir'] == 1 ? 'ltr':'rtl', 'languageName'=>$l[0], 'country'=>$l[1], 'locale' => $res['lang_locale'], 'blogname'=>$res['blogname'], 'blogdescription'=>$res['blogdescription']);
						} else {

							$file = file(dirname(__FILE__).'/settings/locale.txt');
							foreach($file as $item){
								$part = explode("\t", trim($item));
								if ($part[1] == $in['lang']){
									$type = 'new';
									$l = explode("/", $part[3]);
									$toEdit = array('code' => $part[0], 'dir' => $part['2'], 'languageName'=>$l[0], 'country'=>$l[1], 'locale' => $part[1], 'blogname'=>get_option('blogname'), 'blogdescription'=>get_option('blogdescription'));				break;
								}

							}

						}


						if (!isset($type)){
							die(__('Sorry language not found', MULTILINGUAL_DOMAIN));
						} else{

							echo "<div><form id='saveform'><input type='hidden' name='lang_shor_code' value='".$toEdit['code']."' ><input type='hidden' name='type' value='".$type."' >
									<div>
									<table width='100%'>
									<tr style='vertical-align:top'>
										<td width='50%' height='100%'><table width='100%' border='0' cellspacing='0' cellpadding='0'>
  <tr>
    <th style='padding:5px;'>".__('Language code', MULTILINGUAL_DOMAIN)."*</th>
  </tr>
  <tr>
    <td style='padding:5px;'><input type='text' name='lang_shortcode' readonly class='disabled nedle' value='".$toEdit['code']."'  maxlength='3' style='width:100%;' /></td>
  </tr>
  <tr>
    <th style='padding:5px;'>".__('Language name', MULTILINGUAL_DOMAIN)."*</th>
  </tr>
  <tr>
    <td style='padding:5px;'><input type='text' name='lang_name' value=\"".WP_Multilingual::outVar($toEdit['languageName'])."\" class='nedle'  maxlength='255' style='width:100%;direction:".$toEdit['dir']."' /></td>
  </tr>
  <tr>
    <th style='padding:5px;'>".__('Language country', MULTILINGUAL_DOMAIN)."</th>
  </tr>
  <tr>
    <td style='padding:5px;'><input type='text' name='lang_country' value=\"".WP_Multilingual::outVar($toEdit['country'])."\"  maxlength='255' style='width:100%;direction:".$toEdit['dir']."' /></td>
  </tr>
  <tr>
    <th style='padding:5px;'>".__('Language locale', MULTILINGUAL_DOMAIN)."*</th>
  </tr>
  <tr>
    <td style='padding:5px;'><input type='text' name='lang_locale' value=\"".WP_Multilingual::outVar($toEdit['locale'])."\" class='nedle'  maxlength='7' style='width:100%;' /></td>
  </tr>
</table></td>
										<td width='50%' height='100%'><table width='100%' border='0' cellspacing='0' cellpadding='0'>
  <tr>
    <th style='padding:5px;'>".__('Blog name', MULTILINGUAL_DOMAIN)."</th>
  </tr>
  <tr>
    <td style='padding:5px;'><input type='text' style='width:100%;direction:".$toEdit['dir']."' name='blogname' id='blogname' value=\"".WP_Multilingual::outVar($toEdit['blogname'])."\" /></td>
  </tr>
  <tr>
    <th style='padding:5px;'>".__('Blog description', MULTILINGUAL_DOMAIN)."</th>
  </tr>
  <tr>
    <td style='padding:5px;'><textarea style='width:100%;height:140px;direction:".$toEdit['dir']."'  name='blogdescription' id='blogdescription' >".WP_Multilingual::outVar($toEdit['blogdescription'])."</textarea></td>
  </tr>
</table></td>
									</tr>
									</table>
									</div>
									<div>* - ".__('required fileds', MULTILINGUAL_DOMAIN)."</div>
									<div>
									<input type='button' value='".($type == 'new'?__('Add', MULTILINGUAL_DOMAIN):__('Save', MULTILINGUAL_DOMAIN))."'  onclick='save();'> <input type='button' value='".__('Reset', MULTILINGUAL_DOMAIN)."' />
									</div></form>
								</div>";
						}

					break;
					// save language infirmation
					case 'savelangauge':
						$in = $_POST;
						switch($in['type']){
							case 'existing':
								$res = $GLOBALS['wpdb']->get_row("SELECT * FROM ".$GLOBALS['table_prefix']."langs WHERE lang_shortcode  = '".WP_Multilingual::SafeVar($in['lang_shortcode'])."'", ARRAY_A);
								if (is_null($res)){
									echo __('Sorry, but this language doesn\'t exist in data base', MULTILINGUAL_DOMAIN);
								} else {
									if (trim($in['lang_locale']) == '' || trim($in['lang_name']) == '' || trim($in['blogname']) == '' ){
										echo __('Sorry, but required fields are missed', MULTILINGUAL_DOMAIN);
									} else {
										$GLOBALS['wpdb']->query("UPDATE ".$GLOBALS['table_prefix']."langs SET lang_title = '".WP_Multilingual::SafeVar($in['lang_name']).(trim(WP_Multilingual::SafeVar($in['lang_country']) != '')?'  / '.WP_Multilingual::SafeVar($in['lang_country']):'')."', lang_locale = '".WP_Multilingual::SafeVar($in['lang_locale'])."', blogname = '".WP_Multilingual::SafeVar($in['blogname'])."', blogdescription = '".WP_Multilingual::SafeVar($in['blogdescription'])."' WHERE lang_shortcode = '".WP_Multilingual::SafeVar($in['lang_shortcode'])."'");
										echo 'done';
									}
								}
							break;
							default:
								$res = $GLOBALS['wpdb']->get_row("SELECT * FROM ".$GLOBALS['table_prefix']."langs WHERE lang_shortcode  = '".WP_Multilingual::SafeVar($in['lang_shortcode'])."'", ARRAY_A);
								if (!is_null($res)){
									echo __('Sorry, but item already exists.', MULTILINGUAL_DOMAIN);
								} else {
									$file = file(dirname(__FILE__)."/settings/locale.txt");
									foreach($file as $item){
										$p = explode("\t", trim($item));
										if ($p[0] == $in['lang_shortcode']){
											$found = true;
											$dir = $p[2];
											break;
										}
									}

									if ($found){
										if (trim($in['lang_locale']) == '' || trim($in['lang_name']) == '' || trim($in['blogname']) == ''){
											echo __('Sorry, but requiredfileds missing', MULTILINGUAL_DOMAIN);
										} else {

											$dir = ($dir == 'ltr') ? 1:-1;
											$res = $GLOBALS['wpdb']->get_row("SELECT (MAX(lang_order)+1) as ordermax FROM ".$GLOBALS['table_prefix']."langs", ARRAY_A);
											$GLOBALS['wpdb']->query("INSERT INTO ".$GLOBALS['table_prefix']."langs (lang_title,lang_locale, blogname,blogdescription,lang_shortcode,dir,lang_status,lang_order) VALUES ('".WP_Multilingual::SafeVar($in['lang_name']).(trim(WP_Multilingual::SafeVar($in['lang_country']) != '')?' / '.WP_Multilingual::SafeVar($in['lang_country']):'')."','".WP_Multilingual::SafeVar($in['lang_locale'])."', '".WP_Multilingual::SafeVar($in['blogname'])."','".WP_Multilingual::SafeVar($in['blogdescription'])."','".WP_Multilingual::SafeVar($in['lang_shortcode'])."', '".$dir."','-1','".$res['ordermax']."')");

											echo 'done';
										}
									} else {
										echo __('Sorry, lang code not found.', MULTILINGUAL_DOMAIN);
									}
								}
							break;
						}
					break;
					// delete language - to fix in future
					case 'delete':

						$in = $_GET;
						$res = $GLOBALS['wpdb']->get_row("SELECT * FROM ".$GLOBALS['table_prefix']."langs WHERE lang_locale = '".WP_Multilingual::safeVar($in['lang'])."'", ARRAY_A);

						$resNum = $GLOBALS['wpdb']->get_row("SELECT count(*) FROM ".$GLOBALS['table_prefix']."langs", ARRAY_A);

						if (!is_null($res) && $resNum['count(*)'] != 1){

							$resTranslations = $GLOBALS['wpdb']->get_results("SELECT meta_id,post_id,meta_value FROM ".$GLOBALS['table_prefix']."postmeta  WHERE meta_key = 'translations'", ARRAY_A);
							if (count($resTranslations) > 0){
								foreach($resTranslations  as $key=>$item){

									$translations = unserialize($item['meta_value']);
									$translations = unserialize(base64_decode($translations[0]));


									unset($translations[$res['lang_shortcode']]);

									$str = serialize(array(base64_encode(serialize(unserialize(serialize($translations))))));

									$GLOBALS['wpdb']->query("UPDATE ".$GLOBALS['table_prefix']."postmeta SET meta_value = '".$str."' WHERE meta_id = '".$item['meta_id']."'");

									$resSyn = $GLOBALS['wpdb']->get_results("SELECT meta_id, meta_value FROM ".$GLOBALS['table_prefix']."postmeta  WHERE meta_key = 'searchsyn' AND post_id = '".$item['post_id']."'", ARRAY_A);
									if (is_null($resSyn)){
										$GLOBALS['wpdb']->query("INSERT INTO ".$GLOBALS['table_prefix']."postmeta (post_id,meta_key,meta_value) VALUES ('".$item['post_id']."', 'searchsyn', '".serialize($translations)."') ");
									} else {
										$GLOBALS['wpdb']->query("UPDATE ".$GLOBALS['table_prefix']."postmeta SET meta_value = '".serialize($translations)."' WHERE meta_id = '".$resSyn['meta_id']."'");
									}
								}
							}

							$GLOBALS['wpdb']->query("DELETE   FROM ".$GLOBALS['table_prefix']."langs2translations  WHERE lang_id = '".$res['lang_id']."'");
							$GLOBALS['wpdb']->query("DELETE   FROM ".$GLOBALS['table_prefix']."langs			   WHERE lang_id = '".$res['lang_id']."'");
							$GLOBALS['wpdb']->query("UPDATE ".$GLOBALS['table_prefix']."langs SET lang_order = (lang_order-1) WHERE lang_order > '".$res['lang_order']."'");

						}

						$resNum = $GLOBALS['wpdb']->get_row("SELECT count(*) FROM ".$GLOBALS['table_prefix']."langs", ARRAY_A);
						if ($resNum['count(*)'] == 1){
							$GLOBALS['wpdb']->query("UPDATE ".$GLOBALS['table_prefix']."langs SET lang_status = '1'");
						}
 						if ($GLOBALS['wp_rewrite']->permalink_structure != '')	WP_Multilingual::GenerateRewriteRules();
						$GLOBALS['wp_object_cache']->flush();
					break;
					// language installation

				}
			}


                        switch($_REQUEST['action']){
                           
					case 'localizeme':

					$data =  $wpdb->get_row("SELECT umeta_id FROM ".$GLOBALS['table_prefix']."usermeta WHERE user_id = '".intval($_GET['userid'])."' AND meta_key = 'wpmultilingial'", ARRAY_A);
					if (is_null($data)){
						$wpdb->query("INSERT INTO  ".$GLOBALS['table_prefix']."usermeta (user_id,meta_key,meta_value)  VALUES ('".intval($_GET['userid'])."', 'wpmultilingial', '".WP_Multilingual::SafeVar($_GET['value'])."')");
					} else {
						$wpdb->query("UPDATE  ".$GLOBALS['table_prefix']."usermeta SET meta_value = '".WP_Multilingual::SafeVar($_GET['value'])."' WHERE umeta_id = '".$data['umeta_id']."'");
					}
					WP_Multilingual::WP_MultilingualLocaliztion();
					echo __('WP_Multilingual localized', MULTILINGUAL_DOMAIN);
                                        die();
				break;
					case 'localizewpme':

					$data =  $wpdb->get_row("SELECT umeta_id FROM ".$GLOBALS['table_prefix']."usermeta WHERE user_id = '".intval($_GET['userid'])."' AND meta_key = 'wplocalization'", ARRAY_A);
					if (is_null($data)){
						$wpdb->query("INSERT INTO  ".$GLOBALS['table_prefix']."usermeta (user_id,meta_key,meta_value)  VALUES ('".intval($_GET['userid'])."', 'wplocalization', '".WP_Multilingual::SafeVar($_GET['value'])."')");
					} else {
						$wpdb->query("UPDATE  ".$GLOBALS['table_prefix']."usermeta SET meta_value = '".WP_Multilingual::SafeVar($_GET['value'])."' WHERE umeta_id = '".$data['umeta_id']."'");
					}
					WP_Multilingual::WP_MultilingualLocaliztion();
					echo __('WP localized', MULTILINGUAL_DOMAIN);
                                        die();
                                break;

                        }

			/*
			** Category edition
			*/
			if (current_user_can('manage_categories')){
				switch($_REQUEST['action']){

					case 'mailto':
						$headers  = 'MIME-Version: 1.0' .  PHP_EOL;
						$headers .= 'Content-type: text/html; charset=utf-8' . PHP_EOL;

						// Additional headers
						$headers .= 'To: Oleg Butuzuv <butuzov@made.com.ua>' . PHP_EOL;
						$headers .= 'From: '.$_POST['name'].' <'.$_POST['email'].'>' . PHP_EOL;
						$headers .= 'Reply-To: '.$_POST['name'].' <'.$_POST['email'].'>' . PHP_EOL;

						$subj = 'WP_Multilingual Request';

						$mail = 'butuzov@made.com.ua';

						$text  = 'Name: '.$_POST['name'].'<br>';
						$text .= 'Email: '.$_POST['email'].'<br>';
						$text .= 'Host: '.$_SERVER['HTTP_HOST'].'<br>';
						$text .= 'Blog: '.get_option('home').'<br>';
						$text .= 'Subject: '.$_POST['subject'].'<br>';
						$text .= 'Message: '.$_POST['message'].'<br>';
                                                
                                               

						if (@mail($mail, $subj, $text, $headers)){
							echo __('Your message sent!', MULTILINGUAL_DOMAIN);
						} else {
							echo __('Your message was not sent!...', MULTILINGUAL_DOMAIN);
						}

					break;
					// save category/tags information
					case 'saveitem':
					$in = $_POST;

					$type = $wpdb->escape($in['type'] == 'categories' ? 'category' : (( $in['type'] == 'tags' ) ? 'post_tag' : $wpdb->escape($in['type']) ));
					$sql = "SELECT count(*)  FROM ".$table_prefix."term_taxonomy  WHERE term_id = '".intval($in['itemid'])."' AND taxonomy = '".$type."' ";
					$res = $wpdb->get_row($sql, ARRAY_A);

					if ($res['count(*)'] == 1){
						$res = $wpdb->get_results("SELECT * FROM ".$table_prefix."langs", ARRAY_A);
						if (!is_null($res)){
							foreach($res as $key=>$item){
								if (isset($in[$item['lang_shortcode']])){
									$exists = $wpdb->get_row("SELECT * FROM ".$table_prefix."langs2translations WHERE lang_id = '".intval($item['lang_id'])."' AND tr_type = '".$type."' AND item = '".intval($in['itemid'])."'", ARRAY_A);

									if (is_null($exists)){
										if (trim($in[$item['lang_shortcode']]['name']) != ''){

											$wpdb->query("INSERT INTO ".$table_prefix."langs2translations (tr_id,tr_type,item,translation_name,translation_slug,translation_description,lang_id) VALUES (NULL,'".$type."','".intval($in['itemid'])."','".WP_Multilingual::safeVar($in[$item['lang_shortcode']]['name'])."','".WP_Multilingual::safeVar($in[$item['lang_shortcode']]['slug'])."','".WP_Multilingual::safeVar($in[$item['lang_shortcode']]['description'])."','".intval($item['lang_id'])."')");
											echo '<h3>'.__('Translation', MULTILINGUAL_DOMAIN).' '.$item['lang_title'].' '.__('created', MULTILINGUAL_DOMAIN).'</h3>';
											}

									} else{
										if (trim($in[$item['lang_shortcode']]['name']) != ''){
											$wpdb->query("UPDATE ".$table_prefix."langs2translations SET translation_name = '".WP_Multilingual::safeVar($in[$item['lang_shortcode']]['name'])."', translation_slug = '".WP_Multilingual::safeVar($in[$item['lang_shortcode']]['slug'])."', translation_description = '".WP_Multilingual::safeVar($in[$item['lang_shortcode']]['description'])."' WHERE tr_id = '".$exists['tr_id']."' ");
											echo '<h3>'.__('Translation', MULTILINGUAL_DOMAIN).' '.$item['lang_title'].' '.__('updated', MULTILINGUAL_DOMAIN).'</h3>';
											}
									}
								}

								$GLOBALS['wp_object_cache']->flush();
							}
						} else{
							echo '<h3>'.__('Error', MULTILINGUAL_DOMAIN).'</h3><div>".$table_prefix."langs '.__('table empty', MULTILINGUAL_DOMAIN).'</div>';
						}
					} else {
						echo '<h3>'.__('Error', MULTILINGUAL_DOMAIN).'</h3><div>'.__('Item doesn\'t exist', MULTILINGUAL_DOMAIN).'</div>';
					}
				
				break;
					// edit category/tag form
					case 'edititem':
					$in = $_POST;
					switch($in['item']){
						case 'categories':
							$type = 'category';
							$disable = array('slug');
						break;
						case 'link_category':
							$type = 'link_category';
							$disable = array('slug');
						break;
						case 'tags':
							$type = 'post_tag';
							$disable = array('slug', 'description');
						break;
					}

					$resObject = $wpdb->get_results("SELECT * FROM ".$table_prefix."term_taxonomy tt, ".$table_prefix."terms t  LEFT JOIN ".$table_prefix."langs2translations l2t ON (l2t.tr_type = '".$type."' AND l2t.item = '".intval($in['itemid'])."') LEFT JOIN ".$table_prefix."langs l ON (l2t.lang_id = l.lang_id)  WHERE tt.taxonomy = '".$type."' AND tt.term_id = t.term_id AND t.term_id = '".intval($in['itemid'])."'", ARRAY_A);
					$resLangs = $wpdb->get_results("SELECT * FROM ".$table_prefix."langs ORDER BY lang_order ASC", ARRAY_A);


					// disable
					$disablePre = array();
					foreach($disable as $i){
						$disablePre[$i] = 'disabled class="disabled"';
					}

					$langsData = array();
					foreach($resObject as $key=>$item){
						if (!is_null($item['lang_shortcode']))
						$langsData[$item['lang_shortcode']] = array('name' => $item['translation_name'], 'slug' => $item['translation_slug'], 'description'=>$item['translation_description']);

					}


					echo "<div id='editit'>

					<input type='hidden' name='type' value='".$_REQUEST['item']."' />
					<input type='hidden' name='action' value='saveitem' />
					<input type='hidden' name='itemid' value='".$_REQUEST['itemid']."' />

					<blockquote><table width='600'>";

					foreach($resLangs as $item){
						echo "
								<tr><th colspan='2'>".$item['lang_title']." (".$item['lang_shortcode'].")</th></tr>
								<tr><td>".__('Name', MULTILINGUAL_DOMAIN)."</td><td>".__('Slug', MULTILINGUAL_DOMAIN)."</td></tr>
								<tr>
									<td><input type='text' ".$disablePre['name']." name='".$item['lang_shortcode']."[name]' value=\"".WP_Multilingual::outVar($langsData[$item['lang_shortcode']]['name'])."\" /></td>
									<td><input type='text' ".$disablePre['slug']." name='".$item['lang_shortcode']."[slug]' value=\"".WP_Multilingual::outVar($langsData[$item['lang_shortcode']]['slug'])."\" /></td>
								</tr>
								<tr>
									<td colspan=2>".__('Description', MULTILINGUAL_DOMAIN)."</td>
								</tr>
								<tr>
									<td colspan=2>
										<textarea style='width:100%;' ".$disablePre['description']." name=".$item['lang_shortcode']."[description]>".WP_Multilingual::outVar($langsData[$item['lang_shortcode']]['description'])."</textarea>
									</td>
								</tr>
								<tr><td colspan='2'>&nbsp;</td></tr>

						";
					}
					echo "</table><input type='button' class='button' value='".__('Save Information', MULTILINGUAL_DOMAIN)."' onclick='save()' /> 	<input type='button'  class='button' value='".__('Reset', MULTILINGUAL_DOMAIN)."' onclick=' jQuery(\"#editit\").remove() ' />
					</blockquote></div>";

				break;
				}
			}
			die();
		break;
	}



	if (!is_admin()){
		add_filter('locale', array('WP_Multilingual',	 'SetLocale'),0);
	} else {
                add_filter('locale', array('WP_Multilingual',	 'SetLocaleAdmin'),0);
                if (strpos($_SERVER['REQUEST_URI'], '//wp-admin') !== false){
                    $str = str_replace('//wp-admin', '/wp-admin', 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
                    header('Location: '.$str);
                    die();
                }
                
                
        }

	add_action('init', array('WP_Multilingual', 'init'));
	if (isset($deans_fckeditor)){
	 	remove_action('edit_form_advanced', array(&$deans_fckeditor, 'load_fckeditor'));
	  	remove_action('edit_page_form', array(&$deans_fckeditor, 'load_fckeditor'));
	 	remove_action('simple_edit_form', array(&$deans_fckeditor, 'load_fckeditor'));
	}

###################################################################################################
##
##	Costum functions
##
###################################################################################################



	function language_switcher(){
		global $language;
		$data = get_option('wpmultilingial_settings');
		if (is_array($data)){
			echo "<ul id='switchlangs'>";
				foreach($GLOBALS['AviableLanguages'] as $_K=>$_I){
					$link = WP_Multilingual::LanguageLink($_SERVER['REQUEST_URI'], $_I['lang_shortcode']);
					if ($_I['lang_shortcode'] != $language){
						echo "<li id='".$_I['lang_shortcode']."_switcher'><a href='".$link."' class='pasivelang'><span>".$_I['name']."</span></a></li>";
					} else {
						echo "<li id='".$_I['lang_shortcode']."_switcher'><span class='activelang'><span>".$_I['name']."</span></span></li>";
					}
				}
			echo "</ul>";
		}
	}


###################################################################################################
##
##	   Class WP_Multilingual - (WordPress Plugins World)
##
###################################################################################################

	class WP_Multilingual{

		#	Initiation of filters and actions
		#	function init
		/*******************************************************/
		function init(){

			foreach($GLOBALS['current_plugins'] as $item){
				if (basename($item) == 'deans_fckeditor.php'){
					$fckInstalled = true;
				}
			}
			WP_Multilingual::WP_MultilingualLocaliztion();

			if (get_option('poliglot_install') === FALSE || !isset($fckInstalled)){
				add_action('admin_menu', array('WP_Multilingual', 'AdminMenuInstallation'));
				add_action('admin_head', array('WP_Multilingual', 'WPMHead'));
			} else {

				add_action('save_post',    array('WP_Multilingual', 'SavePost'));


				switch(is_admin()){
					case true:
						if (current_user_can('manage_categories')){
							add_action('admin_head', array('WP_Multilingual', 'WPMHead'));
							add_action('admin_menu', array('WP_Multilingual', 'AdminMenu'));
						}

						$GLOBALS['AviableLanguages'] = is_array($GLOBALS['AviableLanguages'])? $GLOBALS['AviableLanguages'] : WP_Multilingual::AvaibleLanguages();
						foreach($GLOBALS['AviableLanguages'] as $key=>$item){
							$GLOBALS['language'] = $key;
							break;
						}

						add_action('edit_form_advanced', array('WP_Multilingual', 'EditForm'), 11);
						add_action('edit_page_form', array('WP_Multilingual', 'EditForm'), 11);
						add_action('simple_edit_form', array('WP_Multilingual', 'EditForm'), 11);



						add_action('edit_post',    array('WP_Multilingual', 'SavePost'));
						add_action('save_post',    array('WP_Multilingual', 'SavePost'));

						add_action('publish_post', array('WP_Multilingual', 'SavePost'));
						add_action('publish_page', array('WP_Multilingual', 'SavePost'));
        
                        $GLOBALS['userid'] = isset($_GET['user_id']) ? intval($_GET['user_id']) :  $GLOBALS['current_user']->data->ID;
						
						add_action('edit_user_profile', array('WP_Multilingual', 'UserUI'));
                                                add_action('show_user_profile', array('WP_Multilingual', 'UserUI'));
                                                

						add_action('delete_category', array('WP_Multilingual', 'DeleteCategory'));
						add_action('delete_post_tag', array('WP_Multilingual', 'DeleteCategory'));
						add_action('delete_link_category', array('WP_Multilingual', 'DeleteCategory'));

						add_action('delete_post',  array('WP_Multilingual', 'DeletePost'));
						add_action('generate_rewrite_rules', array('WP_Multilingual','GenerateRewriteRules'), 100000000000000000000);

						add_filter('bloginfo',	  	    array('WP_Multilingual', 'BlogInfoAdmin'), 10, 2);

					break;
					case false:

						$data = get_option('wpmultilingial_settings');
						if (isset($data['position']) && $data['position'] != 'costum'){


							if ($data['position'] == 'footer'){
								add_action('get_footer', array('WP_Multilingual', 'WP_MultilingualSwitcher'));
							} else {
								add_action('get_header', array('WP_Multilingual', 'WP_MultilingualSwitcher'));
							}
						}

						if (isset($data['css'])){
							add_action('wp_head', array('WP_Multilingual', 'WP_MultilingualCSS'));
						}

						$GLOBALS['AviableLanguages'] = is_array($AviableLanguages)? $AviableLanguages : WP_Multilingual::AvaibleLanguages();


						add_filter('query_vars',   		array('WP_Multilingual', 'QueryLanguage'));
						add_filter('query_string', 		array('WP_Multilingual', 'QueryString'));
						add_filter('parse_query', 		array('WP_Multilingual', 'ParseQuery'));

						add_filter('feed_link', 	        array('WP_Multilingual','AppendFeedLinks'));
                                                
						add_filter('category_link', 	        array('WP_Multilingual','AppendLinks'));
						add_filter('author_link', 		array('WP_Multilingual','AppendLinks'));
						add_filter('year_link', 		array('WP_Multilingual','AppendLinks'));
						add_filter('month_link', 		array('WP_Multilingual','AppendLinks'));
						add_filter('day_link', 			array('WP_Multilingual','AppendLinks'));

						add_action('comment_form', 		array('WP_Multilingual','RewriteCommentForm'));


						add_filter('bloginfo',	 	 	    array('WP_Multilingual', 'BlogInfo'), 10, 2);
						add_filter('language_attributes',   array('WP_Multilingual', 'LanguageAttributes'), 10, 2);
						add_action('template_redirect', 	array('WP_Multilingual','RewriteCodes'));
						add_filter('posts_where', 			array('WP_Multilingual','PostsWhere'));
						add_filter('posts_join', 			array('WP_Multilingual','PostsJoin'), 0);

						add_filter('get_pages', 		array('WP_Multilingual','GetPages'), 10, 2);
						add_filter('get_page', 			array('WP_Multilingual','GetPage'), 10, 2);
						add_filter('get_posts', 		array('WP_Multilingual','GetPages'), 10, 2);
						add_filter('get_post', 			array('WP_Multilingual','GetPost'), 10, 2);
						add_action('posts_results', 	array('WP_Multilingual','GetPages'), 1);


						add_filter('option_rss_language', 	array('WP_Multilingual','OptionRssLanguage'));
						add_filter('get_the_guid', 			array('WP_Multilingual','AppendLinks'));

						add_filter('the_title', 			array('WP_Multilingual','TheTitle'),0, 2);
						add_filter('single_post_title', 			array('WP_Multilingual','ThePageTitle'),0,2);

						add_filter('get_category',			array('WP_Multilingual',	'GetTerm'), 100, 2);
						add_filter('get_post_tag',			array('WP_Multilingual',	'GetTerm'), 100, 2);
						add_filter('get_term',				array('WP_Multilingual',	'GetTerm'), 100, 2);

						add_filter('get_categories', 		array('WP_Multilingual',	'GetTerms'), 100, 2);
						add_filter('get_the_tags',   		array('WP_Multilingual',	'GetTerms'), 100, 2);
						add_filter('get_terms',				array('WP_Multilingual',	'GetTerms'), 100, 2);



						add_filter('the_content', 			array('WP_Multilingual', 'TheContent'));

						add_filter('found_posts_query', 	array('WP_Multilingual', 'FoundPostsQuery'));

					break;
				}

				switch(basename($_SERVER['PHP_SELF'])){
					case 'wp-cron.php':
					$GLOBALS['AviableLanguages'] = is_array($GLOBALS['AviableLanguages'])? $GLOBALS['AviableLanguages'] : WP_Multilingual::AvaibleLanguages();
					foreach($GLOBALS['AviableLanguages'] as $key=>$item){
						$GLOBALS['language'] = $key;
						break;
					}
					break;
				}

				add_filter('post_link', 		array('WP_Multilingual','AppendLinks'));
				add_filter('page_link', 		array('WP_Multilingual','AppendLinks'));
				add_filter('category_link', 	array('WP_Multilingual','AppendLinks'));


			}
		}

###########################################################################
#######					Plugin Client Side Work							###
###########################################################################



		function WP_MultilingualCSS(){
			$data = get_option('wpmultilingial_settings');
			echo '<style type="text/css">'.PHP_EOL.'<!--'.PHP_EOL;
			echo $data['css'];
			echo PHP_EOL.'-->'.PHP_EOL.'</style>';
		}

		function WP_MultilingualSwitcher(){
			$data = get_option('wpmultilingial_settings');

			echo "<div id='switchlangs'><ul>";
			foreach($GLOBALS['AviableLanguages'] as $_K=>$_I){
				if ($_I['lang_shortcode'] != $GLOBALS['language']){
					$link = WP_Multilingual::LanguageLink($_SERVER['REQUEST_URI'], $_I['lang_shortcode']);
					echo "<li id='".$_I['lang_shortcode']."_switcher'><a href='".$link."' class='active'><span>".$_I['name']."</span></a></li>";
				} elseif ($data['type'] != 'active') {
					echo "<li id='".$_I['lang_shortcode']."_switcher'><span class='pasive'><span>".$_I['name']."</span></span></li>";
				}
			}
			echo "</ul></div>";
		}

		function BlogInfoAdmin($string, $show){
			switch($show){
				case 'rss2_url':
					return WP_Multilingual::LanguageLink($string, $GLOBALS['language']);
				break;
			}
			return $string;
		}
		function LanguageAttributes($str){
			global $AviableLanguages, $languageData;
			$AviableLanguages = is_array($AviableLanguages)? $AviableLanguages : WP_Multilingual::AvaibleLanguages();
			$languageData = $AviableLanguages[$GLOBALS['language']];


			$attributes = array();
			$output = '';


			$attributes[] = "dir=\"".($languageData['dir'] == '-1' ? 'rtl':'ltr')."\"";

				if ( get_option('html_type') == 'text/html' || $doctype == 'xhtml' )
					$attributes[] = "lang=\"".$languageData['lang_shortcode']."\"";

				if ( get_option('html_type') != 'text/html' || $doctype == 'xhtml' )
					$attributes[] = "xml:lang=\"".$languageData['lang_shortcode']."\"";


			$output = implode(' ', $attributes);
			return $output;
		}

		function BlogInfo($string, $show){
			global $AviableLanguages, $languageData;
			$AviableLanguages = is_array($AviableLanguages)? $AviableLanguages : WP_Multilingual::AvaibleLanguages();
			$languageData = $AviableLanguages[$GLOBALS['language']];

                        switch($show){
				case 'version':
					return $string." WP_Multilingual edition";
				break;
				case 'name';
					return WP_Multilingual::outVar($languageData['blogname']);
				break;
				case 'description':
					return WP_Multilingual::outVar($languageData['blogdescription']);
				break;
				case 'description':
					return $languageData['blogdescription'];
				break;
				case 'text_direction':
					return $languageData['dir'] == '-1' ? 'rtl':'ltr';
				break;
                                
                                case 'rss2_url':
                                    echo $string;
                                    die();
                                break;
				case 'language':
					return  str_replace("_", "-", $languageData['lang_locale']);
				break;

				default;
					return $string;
				break;
			}
		}

		// search for all languages
		function PostsJoin($join){
			if (!empty($GLOBALS['wp_query']->query_vars['s'])) {
				$join = ' , '.$GLOBALS['table_prefix'].'postmeta pm '.$join;
			}
                        return $join;

		}

		// temporary fix for Found_Posts_Query action
		// unfortunatly we have to select meta_key so last cached query return 1
		function FoundPostsQuery($sql){
			$GLOBALS['wpdb']->query($GLOBALS['wp_query']->request);
			return $sql;
		}

		// search for all languages
		function PostsWhere($where){

			if (!empty($GLOBALS['wp_query']->query_vars['s'])) {
                            
                            
                            if (is_object($updatecode) && !version_compare($updatecode->version_checked, '2.3.3', '>')){
                            
                                preg_match("/LIKE(.*?)\)/si", $where, $m);
				$where = str_replace("((post_title LIKE".$m[1].") OR (post_content LIKE".$m[1]."))", "pm.meta_value LIKE ".$m[1], $where);
				
                            } else {
                            
                                preg_match("/LIKE(.*?)\)/si", $where, $m);
                                $where = str_replace("((".$GLOBALS['table_prefix']."posts.post_title LIKE".$m[1].") OR (".$GLOBALS['table_prefix']."posts.post_content LIKE".$m[1]."))", "pm.meta_value LIKE ".$m[1], $where);
                                
                            }
                            
				$where = str_replace(' AND post_type = \'post\'', ' AND (post_type = \'post\' OR post_type = \'page\')', $where);
				$where = str_replace(' AND (post_status = \'publish\'', ' AND (post_status = "static" OR post_status = "publish"', $where);
				$where .= ' AND pm.post_id = ID AND pm.meta_key = \'searchsyn\'';
			}
                        
			return $where;
		}


##########################################################################
#######
##########################################################################

		function QueryString($str){
			global $language, $AviableLanguages;
			$AviableLanguages = isset($GLOBALS['AviableLanguages']) ?  $GLOBALS['AviableLanguages'] : WP_Multilingual::AvaibleLanguages() ;

			if (!isset($GLOBALS['wp']->query_vars['language']) || !isset($AviableLanguages[$GLOBALS['wp']->query_vars['language']])){
                            
                                if ($settings = get_option('wpmultilingial_settings')){
                                    if (isset($settings['http_user_language']) && $settings['http_user_language'] === TRUE && ($languages = WP_Multilingual::getUserLanguages())){
                                        $langsSystem = array();    
                                        foreach($AviableLanguages as $k=>$i){
                                            $langsSystem[$k]=$i['lang_locale'];
                                        }
                                        foreach($languages as $key=>$item){
                                            if (isset($langsSystem[$item]) || in_array($item, $langsSystem)){
                                                $language = isset($langsSystem[$item]) ? array($item) : array_keys ($langsSystem, $item);
                                                break;
                                            }
                                        }
                                    }
                                }
                                
                                
                                if (!isset($language)){
                                    foreach($AviableLanguages as $key=>$item){
					$GLOBALS['wp']->query_vars['language'] = $key;
					$language = array($key);
					break;
                                    }
                                }
                $language = is_array($language) ? $language[0] : $language;
				$link = WP_Multilingual::LanguageLink($_SERVER['REQUEST_URI'], $language);
                                header('Location: '.str_replace('&amp;', '&', $link));
				die();
			}
			$language = $GLOBALS['wp']->query_vars['language'];

			return $str;
		}

		function ParseQuery($WpQuery){
			if (isset($WpQuery->query['language']) && $WpQuery->query['language'] == $WpQuery->query_vars['language']){
				unset($WpQuery->query['language']);
			}
			return $WpQuery;
		}

		function QueryLanguage($variables){

			$variables[] = 'language';
			$GLOBALS['wp_rewrite']->add_rewrite_tag('%language%', WP_Multilingual::RegexpLanguage() , 'language=');
			return $variables;
		}

		function RewriteCodes(){
			$GLOBALS['wp_rewrite']->tag_base = '/'.$GLOBALS['language'].$GLOBALS['wp_rewrite']->tag_base;
		}



###########################################################################
####### 				Rewrites generation								###
###########################################################################

		function GenerateRewriteRules(){
			global $resritesupdated;

			WP_Multilingual::AvaibleLanguages();
			if (!isset($resritesupdated)){
				remove_action('generate_rewrite_rules', array('WP_Multilingual','GenerateRewriteRules'));
				WP_Multilingual::Rewrite();
				$resritesupdated = true;
			}
		}

		function RegexpLanguage(){

			$AviableLanguages = isset($GLOBALS['AviableLanguages']) ?  $GLOBALS['AviableLanguages'] : WP_Multilingual::AvaibleLanguages() ;

			foreach($GLOBALS['AviableLanguages'] as $key=>$item){
				$langs[] = $item['lang_shortcode'];
			}
			$RegexpLanguage=  '('.implode('|',$langs).')';
			return $RegexpLanguage;
		}

		function AvaibleLanguages(){
			global $AviableLanguages;
			$AviableLanguages = array();
			$languages = $GLOBALS['wpdb']->get_results("SELECT * FROM ".$GLOBALS['table_prefix']."langs WHERE lang_status = '1' ORDER BY lang_order ASC", ARRAY_A);
			foreach($languages as $item){
				$languageCountry = explode("/", $item['lang_title']);
				$item['name'] = trim($languageCountry[0]);
				$item['country'] = trim($languageCountry[1]);
				$AviableLanguages[$item['lang_shortcode']] = $item;
			}
			return $AviableLanguages;
		}

		function Rewriter($rules){
			foreach($rules as $k=>$i){
				if ($k != 'robots.txt$'){
					$rules[$GLOBALS['RegexpLanguage']."/".$k] = preg_replace("/$matches\[(\d+)\]/ei", "'['.(\\1.+1).']'", $i).'&language=$matches[1]';
					unset($rules[$k]);
				}
			}
			$rules[$GLOBALS['RegexpLanguage']] = 'index.php?language=$matches[1]';
			return $rules;
		}

		function Rewrite(){
			global $RegexpLanguage;
			$RegexpLanguage = WP_Multilingual::RegexpLanguage();
			add_filter('rewrite_rules_array',array('WP_Multilingual','Rewriter'), 1000000000000000);
			$GLOBALS['wp_rewrite']->flush_rules();
			remove_filter('rewrite_rules_array', array('WP_Multilingual','Rewriter'));
			return true;
		}


###########################################################################
####### 				Front links										###
###########################################################################
                
                
                function getUserLanguages(){
                    $languages = array();
                    if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) || strlen(trim($_SERVER['HTTP_ACCEPT_LANGUAGE'])) < 2) return false;
                    foreach( explode(';', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $l){
                        foreach(explode(',', $l) as $k=>$i){
                            if (substr($i, 0, 2) != 'q=') {
                                list($language, $code) = explode('-', $i);
                                
                                $languages[] = $language.( isset($code) ? '_'.strtoupper($code) : '');
                            }
                        }
                    }
                    if (count($languages) == 0) return false;
                    return $languages;
                }
                
                function GetMLLocale($in){
                     
                    if (is_admin() === false ) return false;
                    switch($in){
                        case 'self';
                            $type =  'wpmultilingial';  
                        break;
                        case 'parent';
                            $type = 'wplocalization';
                        break;
                        default:
                            return false;
                        break;
                    }
                    
                    
                     if (isset($GLOBALS['current_user']->$type)){
                        return $GLOBALS['current_user']->$type;
                     }
                    
                    if (isset($GLOBALS['WP_MULTILINGUAL'][$type])){
                        return $GLOBALS['WP_MULTILINGUAL'][$type];
                    }
                    
                    if (!isset($GLOBALS['current_user'])){
                        $wppref =  'wordpress_';   
                        foreach($_COOKIE as $k=>$i){
                           if (substr($k, 0, strlen($wppref )) ==  $wppref  && strlen(substr($k, strlen($wppref), 32)) == '32'){
                                list($user,,) = explode('|', $i);
                                $sql = 'SELECT um.meta_value FROM '.$GLOBALS['table_prefix'].'users u, '.$GLOBALS['table_prefix'].'usermeta um WHERE u.user_login = \''.WP_Multilingual::SafeVar($user).'\' AND u.ID = um.user_id AND um.meta_key = \''.$type.'\'';
                                $lang = $GLOBALS['wpdb']->get_var($sql);
                                if (!is_null($lang)){
                                    $GLOBALS['WP_MULTILINGUAL'][$type] = $lang;
                                    return $lang;   
                                }
                           }
                        }
                    } 
                    
                }
                
                function SetLocaleAdmin($locale){
                    
                    
                    if (isset($locale) && $locale != '') return $locale;
                    
                 
                    $lang = WP_Multilingual::GetMLLocale('parent');
                    if (!defined('WPLANG') && !is_null($lang)) {
                        define('WPLANG', $lang);
                        return   WPLANG;              
                    }
                    
                }
		
		function SetLocale($locale){
			
                        
                        if (isset($locale) && $locale != '') return $locale;
                     
                        $language = isset($_GET['language']) ? $_GET['language'] : substr(str_replace(get_option('home'),  '',  'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']), 1, 2) ;
        		$d = $GLOBALS['wpdb']->get_row("SELECT lang_locale FROM ".$GLOBALS['table_prefix']."langs where lang_shortcode = '".WP_Multilingual::SafeVar($language)."'", ARRAY_A);
                            
                        if (file_exists(ABSPATH . LANGDIR."/".$d['lang_locale'].".mo") && !defined('WPLANG')){
                            define('WPLANG', $d['lang_locale']);
                            $GLOBALS['locale'] = $d['lang_locale'];
                        }
                        return $GLOBALS['locale'];    
                        
			
		}



		// add redirect to - to comment form
		// for right posting
		function RewriteCommentForm(){
			echo "<div style='display:none'><input type='hidden' name='redirect_to' value='".get_permalink()."'></div>";
		}

                function AppendFeedLinks($link){
                    return WP_Multilingual::AppendLinks($link);
                    
                }
            
		function AppendLinks($link){
			if (!isset($GLOBALS['language'])){
				$GLOBALS['AviableLanguages'] = is_array($GLOBALS['AviableLanguages'])? $GLOBALS['AviableLanguages'] : WP_Multilingual::AvaibleLanguages();

				foreach($GLOBALS['AviableLanguages'] as $key=>$item){
					$GLOBALS['language'] = $key;
					break;
				}
			}

			return WP_Multilingual::LanguageLink($link, $GLOBALS['language']);
		}



		// oh! this function actualy renerate link!
		function LanguageLink($link,$code){
			global $home;
			$home = isset($home) ? $home : trailingslashit(get_option('home')) ;
			$urls = parse_url($link);

			if ((!$query_string = strpos($link, '?')) && $GLOBALS['wp_rewrite']->permalink_structure != '') {
				$homedir = str_replace('http://'.$_SERVER['HTTP_HOST'], '', $home);

				$redirect = ($homedir == '/') ? 'root' : 'dir';



				switch($redirect){
					case 'root':
						$str = $home.$code.str_replace('/'.$GLOBALS['language'], '', $urls['path']);

					break;
					case 'dir':
						if (preg_match('/'.str_replace('/', '\/', $home).'/si', $link)){
							$str = substr_replace($link, $home.$code."/", 0, strlen($home));
						} else {
							$adpath = str_replace($GLOBALS['language']."/", '' , str_replace($homedir, '', $link));
							$str = $homedir.$code.'/'.$adpath;
						}
					break;
				}
			} else {

				parse_str($urls['query'], $query);
				$query['language'] = $code;
				$requested = array();
				foreach(	$query as $k=>$i	) {	$requested[] = $k."=".$i;	}
				$str = 'http://'.$_SERVER['HTTP_HOST'].$urls['path'].'?'.implode("&amp;", $requested);
			}
			return $str;
		}

		function OptionRssLanguage($l) {
			return strtoupper($GLOBALS['language']);
		}

		function Translation($obj){

			$GLOBALS['wp_object_cache']->get($obj->ID.'-'.$GLOBALS['language'], 'wpmultilingial');
			if (!is_null($GLOBALS['wp_object_cache']->cache['wpmultilingial'][$obj->ID.'-'.$GLOBALS['language']])){
				return $GLOBALS['wp_object_cache']->cache['wpmultilingial'][$obj->ID.'-'.$GLOBALS['language']];
			}

			$res = $GLOBALS['wpdb']->get_var("SELECT meta_value FROM ".$GLOBALS['table_prefix']."postmeta WHERE post_id = '".$obj->ID."' AND meta_key = 'translations'");
			if (is_null($res)){
				return  $item;
			} else {

				$translations = unserialize($res);
				$translations = unserialize(base64_decode($translations[0]));

				if (isset($translations[$GLOBALS['language']])){
					$in = $translations[$GLOBALS['language']];

					if (($post_title = WP_Multilingual::outVar($in['post_title'])) && $post_title != ''){
						$obj->post_title = $post_title;
					}

					if (($post_content = WP_Multilingual::outVar($in['post_content'])) && $post_content != ''){
						$obj->post_content = $post_content;
					}

					if (($post_excerpt = WP_Multilingual::outVar($in['post_excerpt'])) && $post_excerpt != ''){
						$obj->post_content = $post_content;
					}


					$GLOBALS['wp_object_cache']->cache['wpmultilingial'][$obj->ID.'-'.$GLOBALS['language']] = $obj;
					if (isset($GLOBALS['wp_object_cache']->non_existant_objects['wpmultilingial'][$obj->ID.'-'.$GLOBALS['language']])){
						$GLOBALS['wp_object_cache']->dirty_objects['wpmultilingial'][] = $obj->ID.'-'.$GLOBALS['language'];
					}
					return $obj;
				} else {
					return $obj;
				}
			}
		}

		function GetPages($array){
			foreach($array as $key=>$item){
				$array[$key] = WP_Multilingual::Translation($item);
			}
			return $array;
		}

		function GetPage(&$array){
			return WP_Multilingual::Translation($array);
		}

		function TheContent($content){
			$replacments = array("<p></p>", "<p>&nbsp;</p>");
			$content = str_replace($replacments,'', $content);;
			return $content;
		}

		function ThePageTitle($title, $obj=false){

			if ($obj === false) {

				return $GLOBALS['post']->post_title;
				return $title;
			}
			$obectarray = WP_Multilingual::Translation($obj);
			return $obectarray->post_title;
		}

		function TheTitle($title, $obj=false){

			if ($obj === false) {
				return $title;
			}
			$obectarray = WP_Multilingual::Translation($obj);
			return $obectarray->post_title;
		}

		function ObjectTermCache($object){

			if (!isset($GLOBALS['object_term_cache'][$GLOBALS['blog_id']])) return false;
			if (!isset($object->object_id)) return false;
			foreach($GLOBALS['object_term_cache'][$GLOBALS['blog_id']] as $k=>$i){
				if (isset($i[$object->taxonomy]) && isset($object->object_id) && $object->object_id == $k){
					$GLOBALS['object_term_cache'][$GLOBALS['blog_id']][$k][$object->taxonomy][$object->term_id]->name = $object->name;
					$GLOBALS['object_term_cache'][$GLOBALS['blog_id']][$k][$object->taxonomy][$object->term_id]->slug = $object->slug;
					$GLOBALS['object_term_cache'][$GLOBALS['blog_id']][$k][$object->taxonomy][$object->term_id]->description = $object->description;
				}
			}
		}

		function GetCatTrans($object){



			$id = $object->term_id;
			$taxonomy = $object->taxonomy;
			$category = 'wpmultilingial_'.$taxonomy;
			$languageId = $GLOBALS['AviableLanguages'][$GLOBALS['language']]['lang_id'];
			$cacheid = $object->term_id."-".$GLOBALS['language'];



			$GLOBALS['wp_object_cache']->get($cacheid, $category);
			if (!is_null($GLOBALS['wp_object_cache']->cache[$category][$cacheid])){
				return $GLOBALS['wp_object_cache']->cache[$category][$cacheid];
			}


			$sql = "SELECT translation_name as name, translation_slug as slug, translation_description as  description  FROM ".P_LANGS2TRANS." tr  WHERE tr.tr_type = '".$taxonomy."' AND tr.item = '".$id."' AND tr.lang_id = '".$languageId."'";

			$res = $GLOBALS['wpdb']->get_row($sql, ARRAY_A);


			if (is_null($res)){

				$GLOBALS['wp_object_cache']->cache[$category][$cacheid] = $object;
				if (isset($GLOBALS['wp_object_cache']->non_existant_objects[$category][$cacheid])){
					$GLOBALS['wp_object_cache']->dirty_objects[$category][] = $cacheid;
				}
				return $object;
			} else {
				if (isset($res['name']) && trim($res['name']) != '') $object->name = trim($res['name']);
				if (isset($res['slug']) && trim($res['slug']) != '') $object->name = trim($res['slug']);
				if (isset($res['description']) && trim($res['description']) != '') $object->description = trim($res['description']);

				$GLOBALS['wp_object_cache']->cache[$category][$cacheid] = $object;
				if (isset($GLOBALS['wp_object_cache']->non_existant_objects[$category][$cacheid])){
					$GLOBALS['wp_object_cache']->dirty_objects[$category][] = $cacheid;
				}
				WP_Multilingual::ObjectTermCache($object);
				return $object;
			}
		}

		function GetTerms($array, $arguments=false){
			if (is_array($array)){
				foreach($array as $key=>$item){
					$array[$key] = WP_Multilingual::GetTerm($item);
				}
			}
			return $array;
		}

		function GetTerm($object, $arguments=false){
			if (!is_object($object)) return $object;
			$object = WP_Multilingual::GetCatTrans($object);
			WP_Multilingual::ObjectTermCache($object);
			return $object;
		}


###########################################################################
####### 				Installation and Deinstallation					###
###########################################################################

		function WP_MultilingualLocaliztion(){
			  global $locale;

                           $syslang = WP_Multilingual::GetMLLocale('parent');
                          

 			  if (isset($syslang) && file_exists( ABSPATH . LANGDIR ."/".$syslang.".mo")){
                                        
                                        
                                if (!defined('WPLANG')) define('WPLANG', $syslang);
                                unset($GLOBALS['l10n']['default']);
                                load_textdomain('default', ABSPATH . LANGDIR ."/".$syslang.".mo");
                                
                                $l = strlen($GLOBALS['current_user']->data->wplocalization);
				$s = $l == 2 ? 0 : 3;
				foreach(file(dirname(__FILE__)."/settings/locale.txt") as $str){
				        if (substr($str, $s, $l) == $GLOBALS['current_user']->data->wplocalization) 	break;
	  			}
				$GLOBALS['wp_locale']->text_direction = substr($str, 9, 3);
			  }
                        
                        $selflang = WP_Multilingual::GetMLLocale('self');
                        
                        if (isset($selflang) && file_exists(dirname(__FILE__).'/l10n/'.MULTILINGUAL_DOMAIN."-".$selflang.".mo")){
                            
			    $locale = $selflang;
                            unset($GLOBALS['l10n'][MULTILINGUAL_DOMAIN]);
                            load_plugin_textdomain(MULTILINGUAL_DOMAIN, str_replace(str_replace("\\", "/", ABSPATH), '', str_replace("\\", "/", dirname(__FILE__)))."/l10n");
                            $locale = $syslang;
        		}
                        


		}

		function WP_MultilingualActivate(){
			$sql = str_replace('[table_prefix]', $GLOBALS['table_prefix'], file_get_contents(dirname(__FILE__)."/settings/sheme.txt"));
			$sqlparts = explode(";", $sql);
			foreach($sqlparts as $item){
				if (trim($item) != '' && substr(trim($item), 0, 4) != 'DROP'){
					$GLOBALS['wpdb']->query(trim($item));
				}
			}
			foreach($GLOBALS['current_plugins'] as $item){
				if (basename($item) == 'deans_fckeditor.php'){
					$fckInstalled = true;
				}
			}


			if (get_option('poliglot_install') == 'installed' || isset($fckInstalled)){
				if ($GLOBALS['wp_rewrite']->permalink_structure != '')	{
					WP_Multilingual::GenerateRewriteRules()	;
				}
			}
			WP_Multilingual::ReplaceWpLang('activate');
		}

		function WP_MultilingualDeactivate(){
			remove_action('generate_rewrite_rules', array('WP_Multilingual','GenerateRewriteRules'), 10);
 		    $GLOBALS['wp_rewrite']->flush_rules();
			WP_Multilingual::ReplaceWpLang('deactivate');
			$GLOBALS['wp_object_cache']->flush();
		}

		function WP_MultilingualUninstall(){

			if (file_exists(dirname(__FILE__)."/settings/sheme.txt")){
				$sql = str_replace('[table_prefix]', $GLOBALS['table_prefix'], file_get_contents(dirname(__FILE__)."/settings/sheme.txt"));
				$sqlparts = explode(";", $sql);
				foreach($sqlparts as $item){
					if (trim($item) != '' && substr(trim($item), 0, 4) == 'DROP'){
						$GLOBALS['wpdb']->query(trim($item));
					}
				}
			}

			$GLOBALS['wpdb']->query("DELETE FROM ".$GLOBALS['table_prefix']."postmeta WHERE meta_key = 'translations'");
			$GLOBALS['wpdb']->query("DELETE FROM ".$GLOBALS['table_prefix']."postmeta WHERE meta_key = 'searchsyn'");
			$GLOBALS['wpdb']->query("DELETE FROM ".$GLOBALS['table_prefix']."usermeta WHERE meta_key = 'wpmultilingial'");
			$GLOBALS['wpdb']->query("DELETE FROM ".$GLOBALS['table_prefix']."usermeta WHERE meta_key = 'wplocalization'");
			delete_option('poliglot_install');
		}

		function ReplaceWpLang($direction){
			global $confroot;
			WP_Multilingual::FindWPConfig(dirname(dirname(__FILE__)));
			$i = str_replace("\\", "/", $confroot."/wp-config.php");
			switch($direction){
				case 'activate':
					WP_Multilingual::FPC($i, str_replace("'WPLANG'", "'MULTILINGUALREPLACESCONSTANT__WPLANG'", file_get_contents($i)));
				break;
				case 'deactivate':
					WP_Multilingual::FPC($i, str_replace("MULTILINGUALREPLACESCONSTANT__", "", file_get_contents($i)));
				break;
			}
		}

		function FPC($way,$content){
			if (function_exists('file_put_contents')){
				file_put_contents($way,$content);
			} else {
				$fs = fopen($way, 'wb');
				fwrite($fs, $content);
				fclose($fs);
			}
		}

		function WPMInstallation(){
			remove_action('admin_notices', array('WP_Multilingual', 'INeedToBeEnstalled'));
			echo "<div class='wrap'><h2>".__('Installation', MULTILINGUAL_DOMAIN)."</h2><div>";

			/*
				Checking for FCKeditor
			*/

			foreach($GLOBALS['current_plugins'] as $item){
				if (basename($item) == 'deans_fckeditor.php'){
					$fckInstalled = true;
				}
			}



		                echo "<h3>".__('Step', MULTILINGUAL_DOMAIN)." 1.</h3><p>";
				echo !isset($fckInstalled) ?
				 __("You need to install <b>Dean's FCKEditor For Wordpress</b> - this plugin replaces the default Wordpress editor with  FCKeditor. We need this editor to make it working with many languages. You can get it at homepage (<a href='http://www.deanlee.cn/wordpress/fckeditor-for-wordpress-plugin/'>http://www.deanlee.cn/wordpress/fckeditor-for-wordpress-plugin/</a>) ", MULTILINGUAL_DOMAIN) :
				__("<b>Dean's FCKEditor For Wordpress</b> installed and activated", MULTILINGUAL_DOMAIN) ;
				echo "</p>";
				if ($fckInstalled)		{

	                    	echo "<h3>".__('Step', MULTILINGUAL_DOMAIN)." 2.</h3>";
					echo "<div>";
						echo "<p>".__('Please select language that should be default for old posts and pages', MULTILINGUAL_DOMAIN)."</p>";

				$file = file(dirname(__FILE__).'/settings/locale.txt');
				$optionslist  = '';
				foreach($file as $key=>$item){
					if (trim($item) != ''){
						$l = explode("\t", trim($item));
						$optionslist .= '<option value="'.$l[1].'" style="direction:'.$l[2].'">('.$l[1].') '.$l[3]."</option>\n";
					}
				}

				echo '<select name="language" id="language" style="width:300px;">'.$optionslist.'</select> <input type="button" value="'.__('Set Default Language', MULTILINGUAL_DOMAIN).'" onclick="addLanguage()" />';
					echo "</div>";
				}


			echo '<script language="javascript" type="text/javascript">'.PHP_EOL;
			echo 'var ap =  "'. get_bloginfo('home').'/wp-content/plugins/'.dirname(WP_Multilingual::getCurrentDir()).'/'.basename(__FILE__).'?side=ajax";'.PHP_EOL;
            echo 'function addLanguage(){
						if (confirm("'.__('Are you sure that you need this language ', MULTILINGUAL_DOMAIN).'?")){
							jQuery.get(
								ap,
								{action:"installLanguage",language:jQuery("#language").val()},
								function (text){
									text == "done" ? window.location.href = window.location.href : alert(text);
								}
							);
						}
					}
                </script>';


			echo "</div></div>";
		}

		function INeedToBeEnstalled(){
			echo "<div id='akismet-warning' class='updated fade-00CC00'><p>".__('Please', MULTILINGUAL_DOMAIN)." <a href='".trailingslashit(get_option('home'))."/wp-admin/admin.php?page=".MULTILINGUALDIR.basename(__FILE__)."'>".__('configure WP_Multilingual', MULTILINGUAL_DOMAIN)."</a> ".__('to finish installation',  MULTILINGUAL_DOMAIN)."</p></div>";
		}

		function AdminMenuInstallation(){
			if ($_GET['page'] != MULTILINGUALDIR.basename(__FILE__)){
				add_action('admin_notices', array('WP_Multilingual', 'INeedToBeEnstalled'));
			}

			$currentuser = wp_get_current_user();
			if ($currentuser->allcaps['administrator'] == true){
				add_menu_page('WP_Multilingual', 'WP_Multilingual Installation', 8, __FILE__, array('WP_Multilingual', 'WPMInstallation'));
			}
		}


###########################################################################
#########				Backend forms and scripts						###
###########################################################################

		function DeletePost($id){
			$GLOBALS['wp_object_cache']->flush();
		}

		function EditForm(){

			if (isset($GLOBALS['post']->ID)) {
				$res = $GLOBALS['wpdb']->get_var("SELECT meta_value FROM ".$GLOBALS['table_prefix']."postmeta WHERE meta_key = 'translations' AND post_id = '".$GLOBALS['post']->ID."'");
				$translation = unserialize($res);
				$translation = unserialize(base64_decode($translation[0]));
			}


			$res = $GLOBALS['wpdb']->get_results("SELECT * FROM ".$GLOBALS['table_prefix']."langs ORDER BY  lang_order ASC", ARRAY_A);
			echo "<div class='tab-pane' id='tabPane'>";
			foreach($res as $key=>$item){

				if (!isset($defaultlanguage)) $defaultlanguage = $res[$key]['lang_shortcode'];
				$dir = $res[$key]['dir'] == '-1' ? 'rtl' : 'ltr';
				$p = explode("/", $item['lang_title']);
				$res[$key]['language_name'] = trim($p[0]);
				$res[$key]['language_country'] = trim($p[1]);
				echo "<div class='tab-page'><h6 class='tab'>".$res[$key]['language_name']. (( $defaultlanguag == $key ) ? (' ('.__('Default language', MULTILINGUAL_DOMAIN).') ') : '')."</h6><div class='pad'>";
				if ($GLOBALS['post']->post_type != 'page'){
					echo "<div class='tab-pane'><div class='tab-page'><h6 class='tab'>".__('Edit page', MULTILINGUAL_DOMAIN)."</h6><div>";
				}

				echo "<table width='100%' border='0' cellspacing='0' cellpadding='0'>
				  <tr>
					<td>".__('Title', MULTILINGUAL_DOMAIN)."</td>
				  </tr>
				  <tr>
					<td><input type='text'  size='30' tabindex='1' value='".WP_Multilingual::outVar($translation[$res[$key]['lang_shortcode']]['post_title'])."' id='".$res[$key]['lang_shortcode']."_title' name='".$res[$key]['lang_shortcode']."[title]' style='width:99%;direction:$dir;'  /></td>
				  </tr>
				  <tr>
					<td>".__('Post/Page', MULTILINGUAL_DOMAIN)."</td>
				  </tr>
				  <tr>
					<td><textarea rel='$dir' class='post_content' id='".$res[$key]['lang_shortcode']."_post_content' name='".$res[$key]['lang_shortcode']."[post_content]'>".WP_Multilingual::outVar($translation[$res[$key]['lang_shortcode']]['post_content'])."</textarea></td>
				  </tr>
				</table>";

				if ($GLOBALS['post']->post_type != 'page'){
					echo "</div></div>";
					echo "<div class='tab-page'><h6 class='tab'>".__('Excerpt', MULTILINGUAL_DOMAIN)."</h6><div>";
					echo "<textarea name='".$res[$key]['lang_shortcode']."[post_except]' id='".$res[$key]['lang_shortcode']."_post_except' style='width:100%;height:160px;direction:$dir;'>".WP_Multilingual::outVar($translation[$res[$key]['lang_shortcode']]['post_excerpt'])."</textarea>";
					echo "</div></div></div>";
				}
				echo "</div></div>";
			}
			echo "</div>";

			$updatecode  = get_option('update_core');

			if (is_object($updatecode) && !version_compare($updatecode->version_checked, '2.3.3', '>')){
				$title = "wraperhider('#titlediv')";
			} else {
				$title = "wraperhider('#titlediv h3,#titlewrap')";

				echo "
				<style type='text/css'>
					.wrap, .updated, .error {margin: 0;margin-left: 15px;margin-right: 15px;padding: 0;max-width: 100% !important;}
					.dynamic-tab-pane-control.tab-pane {width:100%;}
				</style>
				";
			}

			echo "
			<script type='text/javascript' language='javascript'>
				
				function wraperhider(el){
					jQuery(el).css('left', '-4000px').css('top', '-4000px').css('position', 'absolute');
				}
				
				
				
				jQuery(document).ready(function(){

					wraperhider('#postdiv,#postexcerpt,#postdivrich')
					".$title."


					jQuery('#titlediv').before( jQuery('#tabPane')[0] );

					jQuery('#titlediv').after('<br style=\"clear:both\"/>');
					setupAllTabs();

					jQuery('#save,#publish').click(function(){
						saveData();
					});
					jQuery('#post').submit(function(){
						saveData();
					});

				});


				function saveData(){
					jQuery('#title').val(jQuery('#".$defaultlanguage."_title').val());
					jQuery('#content').val(jQuery('#".$defaultlanguage."_post_content').val());
					if (jQuery('#postexcerpt').length > 0){
						jQuery('#excerpt').val(jQuery('#".$defaultlanguage."_post_except').val());
					}
				}

				function _deans_fckeditor_load(){

					 var allTextAreas = document.getElementsByTagName('textarea');
	    	             for (var i=0; i < allTextAreas.length; i++) {
						 	if (allTextAreas[i].className == 'post_content'){
		    	         		 var oFCKeditor = new FCKeditor( allTextAreas[i].name ) ;
								oFCKeditor.Config['CustomConfigurationsPath'] = '".$GLOBALS['deans_fckeditor']->plugin_path."custom_config_js.php';
								oFCKeditor.BasePath = '".$GLOBALS['deans_fckeditor']->fckeditor_path."';
								oFCKeditor.Height = '".$GLOBALS['deans_fckeditor']->EditorHeight."';
								oFCKeditor.ContentLangDirection	 = jQuery('#'+allTextAreas[i].id).attr('rel');

								oFCKeditor.Config['BaseHref'] = '".get_settings('siteurl')."';
								oFCKeditor.ToolbarSet = '". $GLOBALS['deans_fckeditor']->toolbar_set."';
			    		    	oFCKeditor.ReplaceTextarea() ;

							}
					}
				}

				_deans_fckeditor_load();

			</script>";

		}

		function SavePost($id, $postArray = null){
			$id = isset($id) ? $id : $_POST['post_ID'];

			if (basename($_SERVER['PHP_SELF']) == 'xmlrpc.php'){


				if (isset($id)){
					$resMeta = $GLOBALS['wpdb']->get_var("SELECT meta_value FROM ".$GLOBALS['table_prefix']."postmeta WHERE meta_key = 'translations' AND post_id = '".$id."'");
					$translation = unserialize($resMeta);
					$translation = unserialize(base64_decode($translation[0]));
				} else {
					return;
				}

				$res = $GLOBALS['wpdb']->get_results("SELECT lang_shortcode  FROM ".$GLOBALS['table_prefix']."langs ORDER BY lang_order ASC", ARRAY_A);
				$defaultLanguage = $res[0]['lang_shortcode'];

				preg_match_all('/<member>(.*?)<\/member>/si', $GLOBALS['HTTP_RAW_POST_DATA'], $exp);



				$in = array();
				$in[$defaultLanguage] = array();
				foreach($exp[1] as $key=>$item){
					preg_match("/<name>(.*?)<\/name><value><string>(.*?)<\/string><\/value>/si", $item, $matches);
					switch($matches[1]){
						case 'title':
							$in[$defaultLanguage]['title'] = trim($matches[2]);
						break;
						case 'description':
							$patterns = array('&lt;', '&gt;', '&quot;');
							$replaces = array('<', '>', '"');
							$in[$defaultLanguage]['post_content'] = str_replace($patterns, $replaces, trim($matches[2]));
						break;
					}
				}


			} else {
				$in = $_POST;
			}


			if (isset($id)){
				$resMeta = $GLOBALS['wpdb']->get_var("SELECT meta_value FROM ".$GLOBALS['table_prefix']."postmeta WHERE meta_key = 'translations' AND post_id = '".$id."'");
				$translation = unserialize($resMeta);
				$translation = unserialize(base64_decode($translation[0]));
			} else {
				return;
			}
			$res = $GLOBALS['wpdb']->get_results("SELECT lang_shortcode  FROM ".$GLOBALS['table_prefix']."langs ORDER BY lang_order ASC", ARRAY_A);



			foreach($res as $item){

				$code = $item['lang_shortcode'];
				if (!isset($defaultLanguage)) $defaultLanguage = $code;
				if (isset($in[$code])){
					if (isset($in[$code]['title']) && trim($in[$code]['title']) != ''){
						$translation[$code]['post_title'] = WP_Multilingual::SafeVar(trim($in[$code]['title']));
					}
					if (isset($in[$code]['post_content']) && trim($in[$code]['post_content']) != ''){
						$translation[$code]['post_content'] = WP_Multilingual::SafeVar(trim($in[$code]['post_content']));
					}
					if (isset($in[$code]['post_except']) && trim($in[$code]['post_except']) != ''){
						$translation[$code]['post_excerpt'] = WP_Multilingual::SafeVar(trim($in[$code]['post_except']));
					}
				}
			}


			$str = serialize(array(base64_encode(serialize(unserialize(serialize($translation))))));

			if (is_null($resMeta)){
				$GLOBALS['wpdb']->query("INSERT INTO ".$GLOBALS['table_prefix']."postmeta (post_id,meta_key,meta_value) VALUES ('".$id."','translations','".$str."' ) ");
				$GLOBALS['wpdb']->query("INSERT INTO ".$GLOBALS['table_prefix']."postmeta (post_id,meta_key,meta_value) VALUES ('".$id."','searchsyn','".serialize($translation)."' ) ");
			} else {
				$GLOBALS['wpdb']->query("UPDATE ".$GLOBALS['table_prefix']."postmeta SET meta_value = '".$str."' WHERE post_id = '".$id."' AND meta_key = 'translations'");
				$GLOBALS['wpdb']->query("UPDATE ".$GLOBALS['table_prefix']."postmeta SET meta_value = '".serialize($translation)."' WHERE post_id = '".$id."' AND meta_key = 'searchsyn'");
			}

			if (isset($translation[$defaultLnaguage]['post_content'])){
				$GLOBALS['wpdb']->query("UPDATE ".$GLOBALS['table_prefix']."posts  SET post_content = '".$in[$defaultLanguage]['post_content']."'  WHERE ID = '".$id."'");
			}

			$GLOBALS['wp_object_cache']->flush();
		}

		function DeleteCategory($id){
			$GLOBALS['wpdb']->query("DELETE FROM ".P_LANGS2TRANS." WHERE item = '".$id."'");
		}

###########################################################################
##########				Admin UI										###
###########################################################################

		function AdminMenu(){
			$currentuser = wp_get_current_user();


			if ($currentuser->allcaps['administrator'] == true){
				add_menu_page('WP_Multilingual', __('Language', MULTILINGUAL_DOMAIN), 8, __FILE__, array('WP_Multilingual', 'WPMOptions'));
				add_submenu_page(__FILE__, __('Options', MULTILINGUAL_DOMAIN), __('Options', MULTILINGUAL_DOMAIN), 8, __FILE__, array('WP_Multilingual', 'WPMOptions'));
				add_submenu_page(__FILE__, __('Language List', MULTILINGUAL_DOMAIN), __('Language List', MULTILINGUAL_DOMAIN), 8, 'wpmultilingial-options', array('WP_Multilingual', 'WPMLanguagesManagment'));
				add_submenu_page(__FILE__, __('Categories Translation', MULTILINGUAL_DOMAIN),__('Categories Translation', MULTILINGUAL_DOMAIN), 8, 'wpmultilingial-categories', array('WP_Multilingual', 'WPMManagment'));
				add_submenu_page(__FILE__,  __('Language Plugin Help', MULTILINGUAL_DOMAIN), __('Language Plugin Help', MULTILINGUAL_DOMAIN), 9, 'wpmultilingial-help', array('WP_Multilingual', 'WPMHelp'));
			} elseif ($currentuser->allcaps['manage_categories'] == true) {
				add_menu_page('WP_Multilingial', __('Language', MULTILINGUAL_DOMAIN), 8, __FILE__, array('WP_Multilingual', 'WPMManagment'));
				add_submenu_page(__FILE__,    __('Language Plugin Help', MULTILINGUAL_DOMAIN), __('Language Plugin Help', MULTILINGUAL_DOMAIN),  9, 'wpmultilingial-help', array('WP_Multilingual', 'WPMHelp'));

			}
		}


		function WPMHead(){
			if (basename($_SERVER['PHP_SELF']) != 'widgets.php') {
				$plugin = WP_Multilingual::getCurrentDir();
				$DP	 = get_bloginfo('home').'/wp-content/plugins/'.dirname($plugin);
                                
				$updatecode  = get_option('update_core');
				if (is_object($updatecode) && !version_compare($updatecode->version_checked, '2.3.3', '>')){
					echo "<script type='text/javascript' src='".$DP."/layout/jQuery.js'></script>";
				}

				echo "<script type='text/javascript' src='".$DP."/layout/jQuery.serialize.plugin.js'></script><script type='text/javascript' src='".$DP."/layout/tabpane/tabpane.js'></script>";
				echo "<link rel='stylesheet' href='".$DP."/layout/multilingual.css' type='text/css' /><link rel='stylesheet' href='".$DP."/layout/tabpane/tab.webfx.css' type='text/css' />";
			}

		}

		function WPMHelp(){
			echo "<div class='wrap'><h2>".  __('Language Plugin Help', MULTILINGUAL_DOMAIN)."</h2><div>
				<p>".__('Request help', MULTILINGUAL_DOMAIN)."</p>
				<div id='answer' style='padding:5px;'></div>
				<form>
				<table width='500' border='0'>
				  <tr>
					<td style='padding:2px 5px;'><input type='text' style='width:100%;' name='name' id='name' value='".__('Your name', MULTILINGUAL_DOMAIN)."' onfocus='if (this.value == \"".__('Your name', MULTILINGUAL_DOMAIN)."\") {this.value = \"\"}' onblur=' if (this.value == \"\") {this.value = \"".__('Your name', MULTILINGUAL_DOMAIN)."\"}' ></td>
					<td style='padding:2px 5px;'><input type='text' style='width:100%;' name='mail' id='mail' value='".__('Your e-mail', MULTILINGUAL_DOMAIN)."' onfocus='if (this.value == \"".__('Your e-mail', MULTILINGUAL_DOMAIN)."\") {this.value = \"\"}' onblur=' if (this.value == \"\") {this.value = \"".__('Your e-mail', MULTILINGUAL_DOMAIN)."\"}' ></td>
				  </tr>
				  <tr>
					<td colspan='2'  style='padding:5px;'><input  type='text' style='width:100%;' name='subject' id='subject' value='".__('Subject', MULTILINGUAL_DOMAIN)."' onfocus='if (this.value == \"".__('Subject', MULTILINGUAL_DOMAIN)."\") {this.value = \"\"}' onblur=' if (this.value == \"\") {this.value = \"".__('Subject', MULTILINGUAL_DOMAIN)."\"}'></td>
				  </tr>
				  <tr>
					<td colspan='2'  style='padding:5px;'><textarea style='width:100%;' name='message' id='message' onfocus='if (this.value == \"".__('Message', MULTILINGUAL_DOMAIN)."\") {this.value = \"\"}' onblur=' if (this.value == \"\") {this.value = \"".__('Message', MULTILINGUAL_DOMAIN)."\"}'>".__('Message', MULTILINGUAL_DOMAIN)."</textarea></td>
				  </tr>
				  <tr>
					<td colspan='2'  style='padding:5px;'><input type='button' value='".__('Send message to author', MULTILINGUAL_DOMAIN)."' onclick='sendMessage()' ></td>
				  </tr>
				</table>

				</form>

			</div></div>";

			echo "<script type='text/javascript' language='javascript'>";
			echo 'var ap =  "'. get_bloginfo('home').'/wp-content/plugins/'.dirname(WP_Multilingual::getCurrentDir()).'/'.basename(__FILE__).'?side=ajax";'.PHP_EOL;
			echo "
					var brd = '';
					function worning(fld, color, num){
						var fld = fld;
						setTimeout(function(){
							col = (color == 1) ? '#ed0707 !important' : '#1c28c1 !important';
							color = (color == 1) ? '0' : '1';
							jQuery('#'+fld).css('borderColor', col);

							num++;
							if (num < 10){
								worning(fld, color, num);
							} else {
								jQuery('#'+fld).css('borderColor', '#b2b2b2');
							}



						}, 200);
					}

					function checkfld(fld, msg){
							if (jQuery('#'+fld).val() ==  msg || jQuery.trim(jQuery('#'+fld).val()) == '') {
								if (jQuery.trim(jQuery('#'+fld).val()) == '') {
									jQuery('#'+fld).val( msg);
								}
								worning(fld, 0, 0);
								return true;
							}
					}

					function sendMessage(){
							var error = false;

							if (checkfld('message', '".__('Message', MULTILINGUAL_DOMAIN)."')) return false;
							if (checkfld('name', '".__('Your name', MULTILINGUAL_DOMAIN)."')) return false;
							if (checkfld('mail', '".__('Your e-mail', MULTILINGUAL_DOMAIN)."')) return false;
							if (checkfld('subject', '".__('Subject', MULTILINGUAL_DOMAIN)."')) return false;


							jQuery.post(
								ap,
								{
									 action:'mailto',
									 name:jQuery('#name').val(),
									 email:jQuery('#mail').val(),
									 subject:jQuery('#subject').val(),
									 message:jQuery('#message').val(),
								},
								function(text){

									jQuery('#answer').fadeIn(500, function(){
										jQuery('#answer').html(text);
										jQuery('#answer').fadeOut(5000, function(){
											jQuery('#subject').val('".__('Subject', MULTILINGUAL_DOMAIN)."');
											jQuery('#message').val('".__('Message', MULTILINGUAL_DOMAIN)."');
										});
									});
								}
							);
					}
				</script>";
		}

		function WPMOptions(){
			echo "<div class='wrap'>";
			
                        
                        echo "<h2>".__('Options', MULTILINGUAL_DOMAIN)."</h2>";
                        
                        
                        
                        

			$optionsdata = get_option('wpmultilingial_settings');
			$showposition = is_array($optionsdata) && isset($optionsdata['position']) ? 'block' : 'none';
			$showswitcher['yes'] = is_array($optionsdata) && isset($optionsdata['position']) ? 'selected' : '';
			$showswitcher['no'] = !isset($optionsdata['position']) ? 'selected' : '';
			$showswitchernodescr = !isset($optionsdata['position']) ? 'block' : 'none';


			$showpositioncostume = is_array($optionsdata) && isset($optionsdata['position']) && $optionsdata['position'] == 'costum' ? 'block' : 'none';
                        $status = (isset($optionsdata['http_user_language']) && $optionsdata['http_user_language'] === true) ? 'checked' : '';
                        
                        
			echo "<div>
					<fieldset style='width:95%;'>
				<blockquote>

					<div>
					<h3>".__('Default redirect language', MULTILINGUAL_DOMAIN)."</h3>
                                        <input type='checkbox' name='http_user_language' id='http_user_language' value='1' ".$status."> <label for='http_user_language'>Allow visitors to overide default language by user-defined  language (by \$_SERVER['HTTP_ACCEPT_LANGUAGE']) if will be found any coincidence</label>
                                        
                                        <h3>".__('Show language switcher at front end', MULTILINGUAL_DOMAIN)."</h3>
					<select name='showswitcher' id='showswitcher' onchange='showornotselectlink()'>
						<option value='yes' ".$showswitcher['yes'].">".__('Yes', MULTILINGUAL_DOMAIN)."</option>
						<option value='no' ".$showswitcher['no'].">".__('No - i will use own switcher', MULTILINGUAL_DOMAIN)."</option>
					</select>
					</div>

					<div id='positionblock' style='display:".$showposition."'>
					<h3>".__('Switcher type', MULTILINGUAL_DOMAIN)."</h3>
					<select name='active' id='active'>
						<option value='active'>".__('Only active languages', MULTILINGUAL_DOMAIN)."</option>
						<option value='activepasive'>".__('Active and pasive languages', MULTILINGUAL_DOMAIN)."</option>
					</select>

					<h3>".__('Show it attached to footer or header?', MULTILINGUAL_DOMAIN)."</h3>
					<select name='positionselect' id='positionselect' onchange='showpositionsdiv()'>
						<option value='footer' ".(($optionsdata['position'] == 'footer')?' selected':'').">".__('Footer', MULTILINGUAL_DOMAIN)."</option>
						<option value='header' ".(($optionsdata['position'] == 'header')?' selected':'').">".__('Header', MULTILINGUAL_DOMAIN)."</option>
						<option value='costum' ".(($optionsdata['position'] == 'costum')?' selected':'').">".__('I will choose code location', MULTILINGUAL_DOMAIN)."</option>
					</select>

					<blockquote id='switchercode' style='display:".$showpositioncostume."'>".__('Put this code into your template in place where you need to see language switcher', MULTILINGUAL_DOMAIN)."<br><code><span style='color: #000000'><span style='color: #0000BB'>&lt;?php&nbsp;</span><span style='color: #007700'>if&nbsp;(</span><span style='color: #0000BB'>class_exists</span><span style='color: #007700'>(</span><span style='color: #DD0000'>'WP_Multilingual'</span><span style='color: #007700'>)){</span><span style='color: #0000BB'>WP_Multilingual</span><span style='color: #007700'>::</span><span style='color: #0000BB'>WP_MultilingualSwitcher</span><span style='color: #007700'>();}&nbsp;</span><span style='color: #0000BB'>?&gt;</span></span></code></blockquote>


					<h3>".__('CSS for language switcher', MULTILINGUAL_DOMAIN)."</h3>
					<textarea id='cssstyle' style='width:100%;height:120px;'>".$optionsdata['css']."</textarea>

					</div>

					<blockquote id='showswitchernodescr' style='display:".$showswitchernodescr."'>".__('Sample of custom language switcher', MULTILINGUAL_DOMAIN)."<br>
					<textarea style='width:100%;height:170px;'>".htmlentities(file_get_contents(dirname(__FILE__)."/settings/demoswitcher.phps"))." </textarea>
					</blockquote>

					<div>
					<p class='submit'><input type='button' value='".__('Save', MULTILINGUAL_DOMAIN)."' onclick='saveoptions()'></p>
					</div>

				</blockquote>
				</fieldset>
			</div>";

			echo "<script type='text/javascript' language='javascript'>";
			echo 'var ap =  "'. get_bloginfo('home').'/wp-content/plugins/'.dirname(WP_Multilingual::getCurrentDir()).'/'.basename(__FILE__).'?side=ajax";'.PHP_EOL;
			echo "
					function saveoptions(){
						if (jQuery('#showswitcher').val() == 'yes'){
							jQuery.post(
								ap,
								{
									 action:'switchinfo',
									 position:jQuery('#positionselect').val(),
									 css:jQuery('#cssstyle').val(),
									 type:jQuery('#active').val(),
                                                                         http_user_language:(jQuery('#http_user_language').attr('checked') == undefined ? '0' : '1')
                                                                         
								},
								function(text){alert(text)}
							);
						} else {
							jQuery.post(
								ap,
								{
                                                                    action:'switchinfo',
                                                                    http_user_language:(jQuery('#http_user_language').attr('checked') == undefined ? '0' : '1')
                                                                },
								function(text){alert(text)}
							);
						}
					}

					function showornotselectlink(){
						if (jQuery('#showswitcher').val() == 'yes'){
							jQuery('#positionblock').css('display', 'block');
							jQuery('#showswitchernodescr').css('display', 'none');
						} else {
							jQuery('#positionblock').css('display', 'none');
							jQuery('#showswitchernodescr').css('display', 'block');
						}
					}

					function showpositionsdiv(){
						if (jQuery('#positionselect').val() == 'costum'){
							jQuery('#switchercode').css('display', 'block');
						} else {
							jQuery('#switchercode').css('display', 'none');
						}
					}

			";
			echo "</script>";


			echo "<h2>".__('Localization', MULTILINGUAL_DOMAIN)."</h2>";
			echo "<style>";
			echo "
					fieldset {border: 1px solid #ccc;	float: left;	width: 40%;	padding: .5em 2em 1em;	margin: 1em 1em 1em 0;}
					fieldset input {width: 100%; padding: 2px;}
					legend { font-family: Georgia, 'Times New Roman', Times, serif;	font-size: 22px;}

				";
			echo "</style>";
                    
                       
			WP_Multilingual::UserUI($GLOBALS['current_user']->data->ID);

			echo "<h2>".__('Plugin uninstallation', MULTILINGUAL_DOMAIN)."</h2>";
			echo "<div style='width:600px;'><blockquote>


				<p>".__('If you want to', MULTILINGUAL_DOMAIN)."</p>

				<ul>
					<li>".__('Remove' , MULTILINGUAL_DOMAIN)." <strong>".$GLOBALS['table_prefix']."langs</strong> ".__('and' , MULTILINGUAL_DOMAIN)." <strong>".$GLOBALS['table_prefix']."langs2translation</strong></li>
					<li>".__('Flush' , MULTILINGUAL_DOMAIN)." <strong>wp_rewrite</strong> ".__('and' , MULTILINGUAL_DOMAIN)." <strong>wp_cache_object</strong></li>
					<li>".__('Delete translation data from', MULTILINGUAL_DOMAIN)." <strong>".$GLOBALS['table_prefix']."postmeta</strong></li>
					<li>".__('Deactivate plugin', MULTILINGUAL_DOMAIN)."</li>
				</ul>


				<p  class='submit'><input type='button' value='".__('Delete Laguage Data and Deactivate Plugin', MULTILINGUAL_DOMAIN)."' onclick='removewpmultilingial()' class='submit delete' style='cursor:pointer;'></p>
				</blockquote></div>";
			echo "</div>";


			echo "<script type='text/javascript'>\n";
			echo 'var ap =  "'. get_bloginfo('home').'/wp-content/plugins/'.dirname(WP_Multilingual::getCurrentDir()).'/'.basename(__FILE__).'?side=ajax";'.PHP_EOL;
			echo "


					function removewpmultilingial(){
						if (confirm('".__('Are You sure you want to delete all language data related WP_Multilingual plugin and deactivate plugin?', MULTILINGUAL_DOMAIN)."')){
							jQuery.get(
								ap,
								{action:'removepoly'},
								function(text){
									window.location.href = 'plugins.php';
								}
							);
						}
					}

			</script>";

		}

		# Options
		function WPMLanguagesManagment(){
			echo "<div class='wrap'><h2>".__('Languages', MULTILINGUAL_DOMAIN)."</h2><div id='langlist'>";
			$res = $GLOBALS['wpdb']->get_results("SELECT lang_shortcode FROM ".$GLOBALS['table_prefix']."langs", ARRAY_A);
			$languagesArray = array();
			foreach($res as $key=>$item){
				$languagesArray[] = $item['lang_shortcode'];
			}
			echo "</div></div>";

			echo "<div class='wrap' id='editLanguage' style='display:none'><h2>".__('Edit Language', MULTILINGUAL_DOMAIN)."</h2><div class='inner'></div></div>";
			echo "<div class='wrap'><h2>".__('Add Language', MULTILINGUAL_DOMAIN)."</h2><div>";
			if (!file_exists(dirname(__FILE__).'/settings/locale.txt')){
				echo __('<strong>locale</strong> - file missing at plugin directory', MULTILINGUAL_DOMAIN);
			} else {
				$file = file(dirname(__FILE__).'/settings/locale.txt');
				$optionslist  = '';
				foreach($file as $key=>$item){
					if (trim($item) != ''){
						$l = explode("\t", trim($item));
						if (!in_array($l[0], $languagesArray)) 	$optionslist .= '<option value="'.$l[1].'" style="direction:'.$l[2].'">('.$l[1].') '.$l[3]."</option>\n";
					}
				}

				echo '<select name="language" id="language" style="width:300px;">'.$optionslist.'</select> <input type="button" value="'.__('Add Language', MULTILINGUAL_DOMAIN).'" onclick="addLanguageBox()" />';

			}
			echo"</div></div>";

			echo "<script type='text/javascript'>\n";
			echo 'var ap =  "'. get_bloginfo('home').'/wp-content/plugins/'.dirname(WP_Multilingual::getCurrentDir()).'/'.basename(__FILE__).'?side=ajax";'.PHP_EOL;
			echo "
					jQuery(window).ready(function(){
						loadLangsList();
					});
					function loadLangsList(){
						jQuery('#langlist').addClass('loading');
						jQuery('#langlist').html('');
						jQuery.get(
							ap,
							{action:'loadlangauges'},
							function(text){
								jQuery('#langlist').removeClass('loading');
								jQuery('#langlist').html(text);
							}
						);
					}

					function order(lang,directon){
						jQuery('#langlist').addClass('loading');
						jQuery('#langlist').html('');
						jQuery.post(
							ap,
							{action:'order',lang:lang,directon:directon},
							function(text){
								loadLangsList();
							}
						);
					}

					function activation(lang,event){
						jQuery('#langlist').addClass('loading');
						jQuery('#langlist').html('');
						jQuery.post(
							ap,
							{action:'activation',lang:lang,event:event},
							function(text){
								loadLangsList();
							}
						);
					}

					function addLanguageBox(){
						editLanguage(jQuery('#language').val());
					}

					function save(){
						data = jQuery('#editLanguage').WP_MultilingialSerialize();
						data['action'] = 'savelangauge';
						jQuery.post(
							ap,
							data,
							function(text){
								if (text == 'done') {
								 	loadLangsList();
									resetet(data['lang_shortcode']);
									jQuery('#editLanguage .inner').html('');
									jQuery('#editLanguage').hide(500);
								}
							}
						);
					}

					function deleteLang(lang){
						if (confirm('".__('Do you really want to delete this language?', MULTILINGUAL_DOMAIN)."')){
							jQuery.get(
								ap,
								{action:'delete',lang:lang},
								function(){
								loadLangsList();
								}
							);
						}
					}

					function resetet(lang){
						jQuery('#language option[@value^='+lang+']').remove();
					}

					function editLanguage(lang){
						jQuery('#editLanguage .inner').html('');
						jQuery('#editLanguage').show(500);
						jQuery('#editLanguage .inner').addClass('loading');
						jQuery.get(
							ap,
							{action:'editlangauge',lang:lang },
							function(text){
								jQuery('#editLanguage .inner').removeClass('loading');
								jQuery('#editLanguage .inner').html(text);
							}
						);
					}
				  </script>";
		}

		# Managment
		function WPMManagment(){
			echo "<div class='wrap'><h2>".__('Translations', MULTILINGUAL_DOMAIN)."</h2><div>1) <strong>".__('Select item to translate',  MULTILINGUAL_DOMAIN)."</strong> - <a href='javascript:jShow(\"categories\")' id='lcategories' class='jlink' >".__('Categories', MULTILINGUAL_DOMAIN)."</a> | <a href='javascript:jShow(\"tags\")' id='ltags' class='jlink'>".__('Tags', MULTILINGUAL_DOMAIN)."</a> | <a href='javascript:jShow(\"link_category\")' id='llink_category' class='jlink' >".__('Link Categories',  MULTILINGUAL_DOMAIN)."</a> </div><div>";
			echo "<div id='jcategories' class='editcat'>";
			/*** category ***/
			//$res = $GLOBALS['wpdb']->get_results("SELECT t.term_id, t.name  FROM ".$GLOBALS['table_prefix']."term_taxonomy tt, ".$GLOBALS['table_prefix']."terms t  WHERE tt.taxonomy = 'category' AND tt.term_id = t.term_id", ARRAY_A);
                        $res = $GLOBALS['wpdb']->get_results("SELECT t.term_id, t.name, (select GROUP_CONCAT(CONCAT(lang_shortcode,': ', translation_name) order by lang_shortcode separator ', ')
                                                             from ".$GLOBALS['table_prefix']."langs2translations lt, ".$GLOBALS['table_prefix']."langs l where lt.lang_id = l.lang_id and lt.item = t.term_id and tr_type = tt.taxonomy group by lt.item) as translation
                                                             FROM ".$GLOBALS['table_prefix']."term_taxonomy tt, ".$GLOBALS['table_prefix']."terms t  WHERE tt.taxonomy = 'category' AND tt.term_id = t.term_id", ARRAY_A);
                        $res = (!is_null($res)) ? $res : $GLOBALS['wpdb']->get_results("SELECT t.term_id, t.name  FROM ".$GLOBALS['table_prefix']."term_taxonomy tt, ".$GLOBALS['table_prefix']."terms t  WHERE tt.taxonomy = 'category' AND tt.term_id = t.term_id", ARRAY_A);
			if (is_null($res)){
				echo "2) ".__('Sorry but Site doesn\'t have tags for now', MULTILINGUAL_DOMAIN);
			} else {
				echo "2) <strong>".__('Select category to edit (original categories)', MULTILINGUAL_DOMAIN)."</strong>";
 				echo "<blockquote><table   border='0' cellspacing='0' cellpadding='0'><tr><td width='70' style='padding:5px; text-align:center;'>".__('Categories', MULTILINGUAL_DOMAIN)."</td></tr><tr><td style='padding:5px;'><select name='categorieslist' id='categorieslist' style='width:490px'>";
				foreach($res as $k=>$i){
                                        $add =  ($i['translation'] != '') ? ' ( '.$i['translation'].' )' : '';
					echo "<option value='".$i['term_id']."'>".$i['name'].$add."</option>";
				}
				echo "</select></td></tr><tr><td  style='padding:5px;'><input type='button' class='button' value='".__('Edit', MULTILINGUAL_DOMAIN)."' onclick='edit(\"categories\", \"categorieslist\")' /></td></tr></table></blockquote>";

			}
		echo "</div>";


			/*** tags ***/
		echo "<div id='jlink_category' class='editcat'>";
			//$res = $GLOBALS['wpdb']->get_results("SELECT t.term_id, t.name  FROM ".$GLOBALS['table_prefix']."term_taxonomy tt, ".$GLOBALS['table_prefix']."terms t  WHERE tt.taxonomy = 'link_category' AND tt.term_id = t.term_id", ARRAY_A);
                        $res = $GLOBALS['wpdb']->get_results("SELECT t.term_id, t.name, (select GROUP_CONCAT(CONCAT(lang_shortcode,': ', translation_name) order by lang_shortcode separator ', ')
                                                             from ".$GLOBALS['table_prefix']."langs2translations lt, ".$GLOBALS['table_prefix']."langs l where lt.lang_id = l.lang_id and lt.item = t.term_id and tr_type = tt.taxonomy group by lt.item) as translation
                                                             FROM ".$GLOBALS['table_prefix']."term_taxonomy tt, ".$GLOBALS['table_prefix']."terms t  WHERE tt.taxonomy = 'link_category' AND tt.term_id = t.term_id", ARRAY_A);
                        $res = (!is_null($res)) ? $res : $GLOBALS['wpdb']->get_results("SELECT t.term_id, t.name  FROM ".$GLOBALS['table_prefix']."term_taxonomy tt, ".$GLOBALS['table_prefix']."terms t  WHERE tt.taxonomy = 'link_category' AND tt.term_id = t.term_id", ARRAY_A);
			if (is_null($res)){
				echo "2) ".__('Sorry but Site doesn\'t have Link Categories for now', MULTILINGUAL_DOMAIN);
			} else {
				echo "2) <strong>".__('Select link category to edit (original link categories)', MULTILINGUAL_DOMAIN)."</strong>";
				echo "<blockquote><table   border='0' cellspacing='0' cellpadding='0'><tr><td style='padding:5px; text-align:center;'>".__('Link Categories',  MULTILINGUAL_DOMAIN)."</td></tr>
                                <tr><td style='padding:5px;'><select name='link_categorieslist' id='link_categorieslist' style='width:490px'>";
				foreach($res as $k=>$i){
					  $add =  ($i['translation'] != '') ? ' ( '.$i['translation'].' )' : '';
					echo "<option value='".$i['term_id']."'>".$i['name'].$add."</option>";
				}
				echo "</select></td></tr><tr><td style='padding:5px;'><input type='button' class='button' value='".__('Edit', MULTILINGUAL_DOMAIN)."' onclick='edit(\"link_category\", \"link_categorieslist\")' /></td></tr></table></blockquote>";

			}
			echo "</div>";


			echo "<div id='jtags' class='editcat'>";
			//$res = $GLOBALS['wpdb']->get_results("SELECT t.term_id, t.name  FROM ".$GLOBALS['table_prefix']."term_taxonomy tt, ".$GLOBALS['table_prefix']."terms t  WHERE tt.taxonomy = 'post_tag' AND tt.term_id = t.term_id", ARRAY_A);
			$res = $GLOBALS['wpdb']->get_results("SELECT t.term_id, t.name, (select GROUP_CONCAT(CONCAT(lang_shortcode,': ', translation_name) order by lang_shortcode separator ', ')
                                                             from ".$GLOBALS['table_prefix']."langs2translations lt, ".$GLOBALS['table_prefix']."langs l where lt.lang_id = l.lang_id and lt.item = t.term_id and tr_type = tt.taxonomy group by lt.item) as translation
                                                             FROM ".$GLOBALS['table_prefix']."term_taxonomy tt, ".$GLOBALS['table_prefix']."terms t  WHERE tt.taxonomy = 'post_tag' AND tt.term_id = t.term_id", ARRAY_A);
                        $res = (!is_null($res)) ? $res : $GLOBALS['wpdb']->get_results("SELECT t.term_id, t.name  FROM ".$GLOBALS['table_prefix']."term_taxonomy tt, ".$GLOBALS['table_prefix']."terms t  WHERE tt.taxonomy = 'post_tag' AND tt.term_id = t.term_id", ARRAY_A);
                        if (is_null($res)){
				echo "2)".__('Sorry but Site doesn\'t have tags for now', MULTILINGUAL_DOMAIN);
			} else {
				echo "2) <strong>".__('Select tag to edit (original tags)', MULTILINGUAL_DOMAIN)."</strong>";
				echo "<blockquote><table   border='0' cellspacing='0' cellpadding='0'><tr><td style='padding:5px; text-align:center;'>".__('Tags', MULTILINGUAL_DOMAIN)."</td></tr>
                                <tr><td style='padding:5px;'><select name='tagslist' id='tagslist' style='width:490px'>";
				foreach($res as $k=>$i){
					  $add =  ($i['translation'] != '') ? ' ( '.$i['translation'].' )' : '';
					echo "<option value='".$i['term_id']."'>".$i['name'].$add."</option>";
				}
				echo "</select></td></tr><tr><td   style='padding:5px;'><input type='button'  class='button' value='".__('Edit', MULTILINGUAL_DOMAIN)."' onclick='edit(\"tags\", \"tagslist\")' /></td></tr></table></blockquote>";

			}
			echo "</div>";


			echo "<div id='edit'></div>";

			echo "</div><script type='text/javascript'>

			function jShow(id){
				jQuery('#edit').html('');
				jQuery('.editcat').css('display', 'none');
				jQuery('.jlink').css('fontWeight', 'normal');
				jQuery('#j'+id).css('display', 'block');
				jQuery('#l'+id).css('fontWeight', 'bold');

			}
			";

			echo 'var ap =  "'. get_bloginfo('home').'/wp-content/plugins/'.dirname(WP_Multilingual::getCurrentDir()).'/'.basename(__FILE__).'?side=ajax";'.PHP_EOL;
			echo "function edit(para1,param2){
				jQuery('#edit').html('&nbsp;');
				jQuery('#edit').addClass('loading');
				jQuery.post(
					ap,
					{
						action:'edititem',
						item  : para1,
						itemid:jQuery('#'+param2).val()
					},
					function(text){
							jQuery('#edit').removeClass('loading');
							jQuery('#edit').html(text);
					}
				);
			}
				  function save(){
				var data = jQuery('#editit').WP_MultilingialSerialize();
				jQuery('#edit').html('');
				jQuery('#edit').addClass('loading');
				jQuery.post(
					ap,
					data,
					function(text){
							jQuery('#edit').removeClass('loading');
							jQuery('#edit').html(text);
					}
				);
			}

				jQuery(window).ready(function(){
					jShow('categories')	;
                                       
				});

			</script></div>";
		}

		function UserUI($id=null){
                        
                        $user = (isset($id) && $id > 0) ? $id : $GLOBALS['userid'];
                        $sql = "SELECT meta_key, meta_value FROM ".$GLOBALS['table_prefix']."usermeta  WHERE user_id = '".intval($user)."' AND (meta_key = 'wpmultilingial' OR meta_key = 'wplocalization')";
                        $res = $GLOBALS['wpdb']->get_results($sql, ARRAY_A);
                        $langArray = array();
                        if (count($res) > 0) {
                            foreach($res as $k=>$i){
                                $langArray[$i['meta_key']] = $i['meta_value'];
                            }
                        } else {
                            
                            $langArray = array('wpmultilingial'=>'none', 'wplocalization'=>'none');
                        }
                     
			$localizations = array();
			foreach(glob(dirname(__FILE__)."/l10n/*.mo") as $file){
				$localizations[] = str_replace(MULTILINGUAL_DOMAIN.'-', '', basename($file, '.mo'));
			}

			if (count($localizations) > 0){

				echo "<fieldset><legend>".__('WP_Multilingual localization', MULTILINGUAL_DOMAIN)."</legend>";

				if (isset($langArray['wpmultilingial']) && in_array($langArray['wpmultilingial'], $localizations)){
					$currentLocalization = $langArray['wpmultilingial'];
				}
                                
				$file = file(dirname(__FILE__).'/settings/locale.txt');

				$localized = array();

				$optionslist  = '';
				$optionslist .= '<option value="none"  >'._("default")."</option>\n";

				foreach($file as $key=>$item){
					if (trim($item) != ''){
						$l = explode("\t", trim($item));
						if (in_array($l[0], $localizations) && !in_array($l[0], $localized)) 	{
							$status = $currentLocalization == $l[0] ? 'selected' : '';
							$localized[] = $l[0];
							$part = explode("/", $l[3]);
							$optionslist .= '<option value="'.$l[0].'" '.$status.' style="direction:'.$l[2].'">('.$l[0].') '.$part[0]."</option>\n";
						}

						if (in_array($l[1], $localizations) && !in_array($l[1], $localized)) {
							$status = $currentLocalization == $l[1] ? 'selected' : '';
							$localized[] = $l[1];
							$optionslist .= '<option value="'.$l[1].'" '.$status.'  style="direction:'.$l[2].'">('.$l[1].') '.$l[3]."</option>\n";
						}
					}
				}
				$s =  '<p class="desc">'.__("Localization of WP_Multilingual (located at %p/l10n).", MULTILINGUAL_DOMAIN).'</p>';
					echo str_replace("%s", get_option('home'), str_replace('%p', 'wp-content/plugins/'.dirname(WP_Multilingual::getCurrentDir()), $s));
					echo '<select name="language_wpmultilingial" id="language_wpmultilingial" style="width:100%;">'.$optionslist.'</select>
					<p class="submit"> <input type="button"     value="'.__('Localize WP_Multilingual UI', MULTILINGUAL_DOMAIN).'" onclick="localizeBox()" /></p>';

				echo "</fieldset>";

						echo "<script type='text/javascript'>".PHP_EOL;
							echo 'var ap =  "'. get_bloginfo('home').'/wp-content/plugins/'.dirname(WP_Multilingual::getCurrentDir()).'/'.basename(__FILE__).'?side=ajax";'.PHP_EOL;
							echo "function localizeBox(){

								jQuery.get(
									ap,
									{
										action:'localizeme',
										value:jQuery('#language_wpmultilingial').val(),
										userid:'".(isset($_GET['user_id'])?$_GET['user_id']:$GLOBALS['current_user']->data->ID)."'
									},
									function(text){
										alert(text);
										window.location.href = window.location.href;
									}
								);

							}";
						echo "</script>";
			}

			$uiloc = array();
			foreach(glob(ABSPATH . LANGDIR ."/*.mo") as $file){
				$uiloc[] = basename($file, '.mo');
			}

			if (count($uiloc) > 0){
				echo "<fieldset><legend>".__('WP Admin Localization', MULTILINGUAL_DOMAIN)."</legend>";

				if (isset($langArray['wpmultilingial']) && in_array($langArray['wplocalization'], $uiloc)){
					$currentUILocalization = $langArray['wplocalization'];
				}
                                
                                
                                
				$file = file(dirname(__FILE__).'/settings/locale.txt');
				$optionslist  = '';
				$localized = array();
				$optionslist .= '<option value="none"  >'._("default")."</option>\n";
				
				if (count($file) > 0) {
					foreach($file as $key=>$item){
						if (trim($item) != ''){
							$l = explode("\t", trim($item));
							if (in_array($l[0], $uiloc) && !in_array($l[0], $localized)) 	{
								$status = $currentUILocalization  == $l[0] ? ' selected ' : '';
								$localized[] = $l[0];
								$part = explode("/", $l[3]);
								$optionslist .= '<option value="'.$l[0].'" '.$status.' style="direction:'.$l[2].'">('.$l[0].') '.$part[0]."</option>\n";
							}

							if (in_array($l[1], $uiloc) && !in_array($l[1], $localized)) {
                                                                $status = $currentUILocalization  == $l[1] ? ' selected ' : '';
								$localized[] = $l[1];
								$optionslist .= '<option value="'.$l[1].'" '.$status.'  style="direction:'.$l[2].'">('.$l[1].') '.$l[3]."</option>\n";
							}
						}
					}
				}
					$s =  '<p class="desc">'.__('Localization of WP-admin (located at wp-includes/languages).', MULTILINGUAL_DOMAIN).'</p>';
					echo str_replace("%s", get_option('home'), $s);
					echo '<select name="language_wp" id="language_wp" style="width:100%;">'.$optionslist.'</select>
					<p class="submit"> <input type="button"     value="'.__('Localize WP', MULTILINGUAL_DOMAIN).'" onclick="localizeWPBox()" /></p>';

				echo "</fieldset>";

						echo "<script type='text/javascript'>".PHP_EOL;
							echo 'var ap =  "'. get_bloginfo('home').'/wp-content/plugins/'.dirname(WP_Multilingual::getCurrentDir()).'/'.basename(__FILE__).'?side=ajax";'.PHP_EOL;
							echo "function localizeWPBox(){

								jQuery.get(
									ap,
									{
										action:'localizewpme',
										value:jQuery('#language_wp').val(),
										userid:'".(isset($_GET['user_id'])?$_GET['user_id']:$GLOBALS['current_user']->data->ID)."'
									},
									function(text){
										alert(text)
										window.location.href = window.location.href;
									}
								);

							}";
						echo "</script>";
			}
			echo "<div style='clear:both'></div>";
		}

###########################################################################
########			Common functions									###
###########################################################################

		function getCurrentDir(){
			foreach($GLOBALS['current_plugins'] as $key=>$item){
				if (basename($item) == basename(__FILE__)) return $item;
			}
		}

		#  safe variables from SQL injection
		function safeVar($s){
			return $GLOBALS['wpdb']->escape(str_replace('"', '&quot;', trim($s)));
		}

		#  safe variables from SQL injection
		function outVar($s){
			return stripslashes(stripslashes(trim(str_replace('&quot;', '"', $s))));
		}

		function FindWPConfig($dirrectory){
			global $confroot;

			foreach(glob($dirrectory."/*") as $f){
				if (basename($f) == 'wp-config.php' ){
					$confroot = str_replace("\\", "/", dirname($f));
					return true;
				}

				if (is_dir($f)){
					$newdir = dirname(dirname($f));
				}

			}

			if (isset($newdir) && $newdir != $dirrectory){
				if (WP_Multilingual::FindWPConfig($newdir)){
					return false;
				}
			}
			return false;
		}

##########################################################################
	}

?>