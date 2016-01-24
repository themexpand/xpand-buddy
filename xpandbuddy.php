<?php
/*
Plugin Name: Xpand Buddy
Plugin URI: http://themexpand.com/product/xpand-buddy
Description: Backup and clone your WordPress site securely and easily using Xpand Buddy.
Version: 1.0.0
Author: ThemeXpand
Author URI: http://themexpand.com
*/
$_tmpDir=getcwd();
chdir( ABSPATH );
if( !is_dir( 'wp-backups' ) && mkdir( 'wp-backups', 0755 )===false ){
	$arrError=error_get_last();
	echo 'The plugin could not be activated because of your host server settings. '.@$arrError['message'].'. Please contact your host to get the issue resolved.';
	die();
}
if( !is_file( 'wp-backups'.DIRECTORY_SEPARATOR.".htaccess" ) && @file_put_contents(  'wp-backups'.DIRECTORY_SEPARATOR.".htaccess", "Options -Indexes" )===false ){
	$arrError=error_get_last();
	echo 'The plugin could not be activated because of your host server settings. '.@$arrError['message'].'. Please contact your host to get the issue resolved.';
	die();
}
chdir( $_tmpDir );

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

include_once( pathinfo( __FILE__ , PATHINFO_DIRNAME ).'/library/Plugin.php' );
$pluginClass=new Xpandbuddy();
Xpandbuddy::$pathName=pathinfo( __FILE__, PATHINFO_DIRNAME );
Xpandbuddy::$baseName=trailingslashit( WP_PLUGIN_URL.'/'.dirname( plugin_basename( __FILE__ ) ) );
$pluginClass->pluginRun();