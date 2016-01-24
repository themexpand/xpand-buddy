<?php
require_once pathinfo( __FILE__, PATHINFO_DIRNAME )."/dropboxautoload.php";

function Dropbox_Client($_accessToken){
	return new Dropbox\Client($_accessToken, "PHP-Example/1.0");
}

function Dropbox_WriteMode_add(){
	return Dropbox\WriteMode::add();
}

function Dropbox_WebAuthNoRedirect($_key,$_secret){
	$appInfo=Dropbox\AppInfo::loadFromJson( array( "key"=>$_key, "secret"=>$_secret ) );
	return new Dropbox\WebAuthNoRedirect($appInfo, "PHP-Example/1.0");
}