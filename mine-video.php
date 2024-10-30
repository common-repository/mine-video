<?php
/*
Plugin Name: Mine Video Player
Plugin URI: https://www.zwtt8.com/wordpress-plugin-mine-video/
Description: 支持视频列表/多组来源/阿里云视频点播/腾讯云点播/m3u8/mp4/直播等，也可将主流视频站的视频通过解析程序播放。
Version: 2.8.11
Author: mine27
Author URI: https://www.zwtt8.com/
*/
if(!defined('ABSPATH'))exit;

define('MINEVIDEO_VERSION', '2.8.11');
define('MINEVIDEO_URL', plugins_url('', __FILE__));
define('MINEVIDEO_PATH', dirname(__FILE__));
define('MINEVIDEO_ADMINURL', admin_url());
define('MINEVIDEO_SETTINGS', get_option('minevideo_mvp_setting'));

require_once MINEVIDEO_PATH . '/autoload.php';
require_once MINEVIDEO_PATH . '/Mine_Video.class.php';
require_once MINEVIDEO_PATH . '/mine-video-dplayer.php';
require_once MINEVIDEO_PATH.'/inc/options.php';

if (class_exists('Mine_Video')) {
	new MineVideo\Ability\Plugin();

	$minevideo = new Mine_Video();
	register_activation_hook(__FILE__,	array($minevideo, 'register_minevideo_init'));
}

?>