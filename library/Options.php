<?php
/**
 * Options class
 */
class Xpandbuddy_Options {

	private static $_arrSettings=array();

	private static $_optionName='xpandbuddy_options';

	public static function get(){
		if(is_multisite()){
			if( $_options=get_blog_option( BLOG_ID_CURRENT_SITE, self::$_optionName) ){
				return self::$_arrSettings=$_options;
			}
		}else{
			if( $_options=get_option( self::$_optionName) ){
				return self::$_arrSettings=$_options;
			}
		}
		return self::$_arrSettings;
	}

	public static function set( $_options ){
		if(is_multisite()){
			if( !@get_blog_option( BLOG_ID_CURRENT_SITE, self::$_optionName) ){
				add_blog_option( BLOG_ID_CURRENT_SITE, self::$_optionName, $_options);
			} else {
				update_blog_option( BLOG_ID_CURRENT_SITE, self::$_optionName, $_options+@get_blog_option( BLOG_ID_CURRENT_SITE, self::$_optionName ) );
			}
			self::$_arrSettings=@get_blog_option( BLOG_ID_CURRENT_SITE, self::$_optionName );
		}else{
			if( !@get_option( self::$_optionName) ){
				add_option(self::$_optionName, $_options);
			} else {
				update_option( self::$_optionName, $_options+@get_option( self::$_optionName ) );
			}
			self::$_arrSettings=@get_option( self::$_optionName );
		}
	}

	public static function reset(){
		if(is_multisite()){
			delete_blog_option( BLOG_ID_CURRENT_SITE, self::$_optionName );
		}else{
			delete_option( self::$_optionName );
		}
		self::setDefault();
	}

	private static function setDefault(){
		self::$_arrSettings=array();
	}
}
?>