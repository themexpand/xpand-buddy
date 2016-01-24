<?php
/**
 * Crofile class
 */
if( !class_exists('Xpandbuddy_Logger') ){
class Xpandbuddy_Logger {

	public $stepProject=array();
	public $logDate='';
	public $logName='';
	private $_logFileName='';

   function __construct( $_logName='' ) {
		$this->logName=$_logName;
   }
	
	private function logFileDateActivate(){
		$_pluginDir=WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'xpandbuddy'.DIRECTORY_SEPARATOR;
		if( !is_dir( $_pluginDir.'temp' ) ){
			mkdir( $_pluginDir.'temp' );
		}
		$this->_logFileName=$_pluginDir.'temp'.DIRECTORY_SEPARATOR.$this->logName.'_'.$this->logDate.'.log';
	}
	
	public function deleteStepLog(){
		if( empty( $this->logDate ) ){
			return;
		}
		$this->logFileDateActivate();
		if( is_file( $this->_logFileName ) ){
			unlink( $this->_logFileName );
		}
	}

	public function setStepLog(){
		if( empty( $this->logDate ) ){
			return;
		}
		$this->logFileDateActivate();
		$return=false;
		if( $_logFileHandle=fopen( $this->_logFileName, "w" ) ){
			$return=fwrite( $_logFileHandle, base64_encode( serialize( $this->stepProject ) ) );
			fclose( $_logFileHandle );
		}
		return $return;
	}

	public function getStepLog(){
		if( empty( $this->logDate ) ){
			return;
		}
		$this->logFileDateActivate();
		if( !is_file( $this->_logFileName ) ){
			return;
		}
		if( $_logFileHandle=fopen( $this->_logFileName, "r" ) ){
			$_strBase=fread( $_logFileHandle, filesize( $this->_logFileName ) );
			if( !empty( $_strBase ) ){
				$this->stepProject=unserialize( base64_decode( $_strBase ) );
			}
			fclose( $_logFileHandle );
		}
	}
	
}}
?>