<?php
/*
* Uninstall plugin completely with all data
*/
include_once( pathinfo( __FILE__ , PATHINFO_DIRNAME ).'/library/Plugin.php' );
$pluginClass=new Xpandbuddy();
$pluginClass->del( __FILE__ );
?>
