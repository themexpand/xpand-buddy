<?php
/**
 * Plugin class
 */
if( !class_exists('Xpandbuddy_Twig') ){
error_reporting(E_ERROR);
ini_set('display_errors', 0);
class Xpandbuddy_Twig{

	public static $arrOptions=array();
	public static $flgType=1;
	public static $error='';
	public static $ds=DIRECTORY_SEPARATOR;
	public static $logger=false;
	public static $logString='';

	private static $_pluginPath='';
	private static $_arrChanges=array();
	private static $_stepStart=false;
	private static $_arrSteps=array();
	private static $_logCode=0;
	private static $_filesCounter=0;
	private static $_allFilesCounter=0;

	private static function _setErr( $str='' ){
		self::$error=$str;
		return false;
	}
	
	public static function archiveName( $_backupUrl, $_id, $_date ){
		if( !isset( $_backupUrl ) || empty( $_backupUrl ) ){
			$_backupUrl=home_url();
		}
		if( !isset( $_id ) || empty( $_id ) ){
			$_project='-backup';
		}else{
			$_project="-prj".$_id;
		}
		if( !isset( $_date ) || empty( $_date ) ){
			$_date=time();
		}
		$_rare='';
		if( isset( $_POST['user_run'] ) ){
			$_rare='-custom';
		}
		return str_replace( array( "http://", "https://", ".", "/" ), array( "","","_","_" ), $_backupUrl ).$_project.'-'.date( "Y-m-d-H", $_date ).$_rare;
	}
	
	private static function getFiles( &$arrFiles, $elementName, $dirStart, $openDirs=true ){
		if( $_handle=opendir( $elementName ) ){
			while( false!==( $_file=readdir( $_handle ) ) ){
				if( $_file!='.' 
					&& $_file!='..'
					&& strpos( $_file, 'wp-backups' )===false 
					&& strpos( $elementName.self::$ds.$_file, 'xpandbuddy'.self::$ds.'temp'.self::$ds )===false 
					&& ( strpos( $_file, 'pclzip-' )===false || strpos( $_file, 'pclzip-' )!=0 )
					&& strpos( $elementName.self::$ds.$_file, self::$ds.'.svn'.self::$ds )===false
				){
					if( is_file( $elementName.self::$ds.$_file ) ){
						$arrFiles[] = str_replace( $dirStart.self::$ds, '', $elementName.self::$ds.$_file );
					}elseif( $openDirs && is_dir( $elementName.self::$ds.$_file ) ){
						$arrFiles[] = str_replace( $dirStart.self::$ds, '', $elementName.self::$ds.$_file );
						self::getFiles( $arrFiles, $elementName.self::$ds.$_file, $dirStart );
					}
				}
			}
		}
	}

	private static function setReplaceUnit( $file, $parse, $replace, $item, $check, $flg_bufer=false ){
		if( $check != $item ){
			if( $flg_bufer ){
				$_preStr=md5( $parse );
				self::$_arrChanges[$file]['string_b'][]=$parse;
				self::$_arrChanges[$file]['replace_b'][]=$replace;
				self::$_arrChanges[$file]['bufer_a'][]=$_preStr;
				self::$_arrChanges[$file]['bufer_b'][]='/'.$_preStr.'/';
			}else{
				self::$_arrChanges[$file]['string'][]=$parse;
				self::$_arrChanges[$file]['replace'][]=$replace;
			}
		}
	}

	private static function setReplace(){
		self::setReplaceUnit( 'wp-config.php', '/(DB_NAME.*[\'"]{1})(.*)([\'"]{1})/i', "\${1}".self::$arrOptions['blog']['db_name'].'$3', self::$arrOptions['blog']['db_name'], DB_NAME );
		self::setReplaceUnit( 'wp-config.php', '/(DB_USER.*[\'"]{1})(.*)([\'"]{1})/i', "\${1}".self::$arrOptions['blog']['db_username'].'$3', self::$arrOptions['blog']['db_username'], DB_USER );
		self::setReplaceUnit( 'wp-config.php', '/(DB_PASSWORD.*[\'"]{1})(.*)([\'"]{1})/i', "\${1}".self::$arrOptions['blog']['db_password'].'$3', self::$arrOptions['blog']['db_password'], DB_PASSWORD );
		self::setReplaceUnit( 'wp-config.php', '/(DB_HOST.*[\'"]{1})(.*)([\'"]{1})/i', "\${1}".self::$arrOptions['blog']['db_host'].'$3', self::$arrOptions['blog']['db_host'], DB_HOST );
		global $table_prefix;
		self::setReplaceUnit( 'wp-config.php', '/(\$table_prefix.*[\'"]{1})(.*)([\'"]{1})/i', "\${1}".self::$arrOptions['blog']['db_tableprefix'].'$3', self::$arrOptions['blog']['db_tableprefix'], $table_prefix );
		$_oldUrl=parse_url( get_option('siteurl') );
		$_newUrl=parse_url( self::$arrOptions['blog']['url'] );
		$_newPath=$_oldPath='';
		if( isset( $_oldUrl['path'] ) ){
			$_oldPath=$_oldUrl['path'];
		}
		if( isset( $_newUrl['path'] ) ){
			$_newPath=$_newUrl['path'];
		}
		self::setReplaceUnit( '.htaccess', '#(RewriteBase .*)('.addslashes( $_oldPath ).'.*)([/]{1}.*)#i', "\$1".$_newPath.'$3', $_newUrl, $_oldUrl );
		self::setReplaceUnit( '.htaccess', '#(RewriteRule [\\.|.]{1} .*)('.addslashes( $_oldPath ).'.*)(/index.php.*)#i', "\$1".$_newPath.'$3', $_newUrl, $_oldUrl );
		self::setReplaceUnit( '##all_files##', "#".addslashes( substr(ABSPATH,0,-1) )."#i", self::$arrOptions['blog']['path'], self::$arrOptions['blog']['path'], addslashes( substr(ABSPATH,0,-1) ), true );
		self::setReplaceUnit( '##all_files##', "#".addslashes( home_url() )."#i", addslashes( self::$arrOptions['blog']['url'] ), self::$arrOptions['blog']['url'], home_url(), true );
		$_oldPathExlode=explode( '/', substr(ABSPATH,0,-1) );
		if( count( $_oldPathExlode ) > 1 ){
			$_oldPathExlode[1]='home';
			$_oldPathExlode=implode( '/' , $_oldPathExlode );
			self::setReplaceUnit( '##all_files##', "#".addslashes( $_oldPathExlode )."#i", self::$arrOptions['blog']['path'], self::$arrOptions['blog']['path'], addslashes( $_oldPathExlode ), true );
		}
		if( $_oldUrl != $_newUrl && isset( $_oldUrl['host'] ) && isset( $_newUrl['host'] ) ){
			self::setReplaceUnit( '##all_files##', "#".addslashes( $_oldUrl['host'].$_oldPath )."#i", addslashes( $_newUrl['host'].$_newPath ), $_newUrl['host'].$_newPath, $_oldUrl['host'].$_oldPath, true );
		}
	}

	public static function setOptions( $arrOptions=array(), $_type=1 ){
		if( !empty( $arrOptions ) ){
			self::$arrOptions=$arrOptions;
			self::setReplace();
		}
		self::$flgType=$_type;
	}

	public static function checkSize( $arrOptions=array() ){
		set_time_limit(0);
		ignore_user_abort(true);
		error_reporting( E_ALL );
		ob_implicit_flush();
		include_once Xpandbuddy::$pathName.'/library/Backup.php';
		include_once Xpandbuddy::$pathName.'/library/Logger.php';
		self::$logger=new Xpandbuddy_Logger( 'step_log_file' );
		if( isset( $arrOptions['settings']['type_clone'] ) ){
			$arrOptions['settings']['user_data']=$_POST['arrProject']['settings']['type_clone'];
			unset( $arrOptions['settings']['type_clone'] );
		}
		if( isset($arrOptions['settings']['type_backup'] ) ){
			$arrOptions['settings']['user_data']=$_POST['arrProject']['settings']['type_backup'];
			unset( $arrOptions['settings']['type_backup'] );
		}
		self::$logger->stepProject=$arrOptions;
		self::setOptions( $arrOptions['settings'] );
		if( ( $arrOptions['settings']['user_data']['only_settings']==1 && $arrOptions['settings']['user_data']['database']==1 ) || $arrOptions['settings']['user_data']['only_settings']==0 ){
			if( !self::createStepBackup() ){
				return self::$error;
			}
		}
		$_dirStart=substr(ABSPATH,0,-1);
		$_arrFiles=array();
		$_wpContentDir=substr( WP_CONTENT_DIR, strlen( ABSPATH ) );
		if( $arrOptions['settings']['user_data']['only_settings']==1 ){
			if( $arrOptions['settings']['user_data']['database']==1 ){
				$_arrFiles[]='wp-backups'.self::$ds.'dump.sql';
			}
			if( $arrOptions['settings']['user_data']['plugins']==1 ){
				self::getFiles( $_arrFiles, $_dirStart.self::$ds.$_wpContentDir.self::$ds.'plugins', $_dirStart );
			}
			if( $arrOptions['settings']['user_data']['themes']==1 ){
				self::getFiles( $_arrFiles, $_dirStart.self::$ds.$_wpContentDir.self::$ds.'themes', $_dirStart );
			}
			if( $arrOptions['settings']['user_data']['uploads']==1 ){
				self::getFiles( $_arrFiles, $_dirStart.self::$ds.$_wpContentDir.self::$ds.'uploads', $_dirStart );
			}
		}else{
			self::getFiles( $_arrFiles, $_dirStart, $_dirStart, false );
			self::getFiles( $_arrFiles, $_dirStart.self::$ds.$_wpContentDir, $_dirStart );
			self::getFiles( $_arrFiles, $_dirStart.self::$ds.'wp-admin', $_dirStart );
			self::getFiles( $_arrFiles, $_dirStart.self::$ds.'wp-includes', $_dirStart );
			$_arrFiles[]='wp-backups'.self::$ds.'dump.sql';
		}
		$_summ=0;
		$_clearDirs=array();
		if( isset(self::$logger->stepProject['settings']['user_data']['files_exclude']) && self::$logger->stepProject['settings']['user_data']['files_exclude'] == 1 ){
			if( isset( $arrOptions['settings']['user_data']['arr_execute_dirs'] ) ){
				foreach( $arrOptions['settings']['user_data']['arr_execute_dirs'] as $_dirs ){
					if( $_dirs['value']==1 ){
						$_clearDirs[]=stripslashes( $_dirs['name'] );
					}
				}
			}
			foreach( $_arrFiles as $_file ){
				$_flgCheck=true;
				if( !empty( $_clearDirs ) ){
					foreach( $_clearDirs as $_needle ){
						if( strpos( $_dirStart.self::$ds.$_file, $_needle ) === 0 ){
							$_flgCheck=false;
						}
					}
				}
				if( ( $_flgCheck && ( !isset( $arrOptions['settings']['files_exclude_filter'] ) || empty( $arrOptions['settings']['files_exclude_filter'] ) ) )
					|| ( $_flgCheck && !empty( $arrOptions['settings']['files_exclude_filter'] ) && @fnmatch( $arrOptions['settings']['files_exclude_filter'], $_dirStart.self::$ds.$_file ) )
				){
					$_summ+=@filesize( $_dirStart.self::$ds.$_file );
				}
			}
			exit;
		}else{
			foreach( $_arrFiles as $_file ){
				$_summ+=@filesize( $_dirStart.self::$ds.$_file );
			}
		}
		$_flgFunctions=false;
		if(
			function_exists('function_exists')
			&& function_exists('fopen')
			&& function_exists('fgets')
			&& function_exists('fwrite')
			&& function_exists('fclose')
			&& function_exists('opendir')
			&& function_exists('readdir')
			&& function_exists('mkdir')
			&& function_exists('chmod')
		){
			$_flgFunctions=true;
		}
		$_flgAccess=false;
		$_tmpDir=getcwd();
		chdir( ABSPATH );
		if( is_dir( 'wp-backups' ) ){
			$_stats=stat( 'wp-backups' );
			$_flgAccess=decoct( $_stats['mode'] );
			if( isset( $_stats['mode'] ) && decoct( $_stats['mode'] ) >= 040755 ){
				$_flgAccess=true;
			}
		}
		chdir( $_tmpDir );
		$_flgTimelimit=false;
		if( function_exists('ini_get') && ( ini_get('max_execution_time')==0 || ini_get('max_execution_time') > 5 ) ){
			$_flgTimelimit=true;
		}
		return json_encode( array( 'backup_size'=>$_summ, 'flg_function_exist'=> $_flgFunctions, 'flg_access'=> $_flgAccess, 'flg_timelimite'=> $_flgTimelimit  ) );
	}
	
	public static function stepByStep( $logDate=false, &$arrProject=array(), $_flgForceCreateStepFile=false ){
		set_time_limit( 0 );
		ignore_user_abort( true );
		error_reporting( E_ALL );
		ob_implicit_flush();
		
		include_once Xpandbuddy::$pathName.'/library/Logger.php';
		self::$logger=new Xpandbuddy_Logger( 'step_log_file' );
		if( empty( $logDate ) && !empty( $arrProject ) ){
			self::$logger->logDate=time();
			$arrProject['step']=0;
			self::$logger->stepProject=$arrProject;
			self::$logger->setStepLog();
		}else{
			self::$logger->logDate=$logDate;
			self::$logger->getStepLog();
		}
		if( empty( self::$logger->stepProject ) && !empty( $logDate ) && !empty( $arrProject ) && $_flgForceCreateStepFile ){
			self::$logger->logDate=$logDate;
			$arrProject['step']=0;
			self::$logger->stepProject=$arrProject;
			self::$logger->setStepLog();
		}
		if( empty( self::$logger->stepProject ) ){
			self::$logger->deleteStepLog();
			return self::_setErr( 'No project settings!' );
		}
		if( !isset( self::$logger->stepProject['settings']['user_data']['only_settings'] ) ){
			$_thisType=1;
			if( isset( self::$logger->stepProject['flg_type'] ) && is_array( self::$logger->stepProject['flg_type'] ) ){
				foreach( self::$logger->stepProject['flg_type'] as $_key=>$_value ){
					if( $_key==$_value ){
						$_thisType=$_value;
						break;
					}
				}
			}
			self::$logger->stepProject['settings']['user_data']=self::$logger->stepProject['settings']['type_'.$_thisType];
		}
		if( self::$logger->stepProject['step']<1 ){ // Create SQL backup
			if( ( ( self::$logger->stepProject['settings']['user_data']['only_settings']==1 && self::$logger->stepProject['settings']['user_data']['database']==1 ) || self::$logger->stepProject['settings']['user_data']['only_settings']==0 ) && !self::createStepBackup() ){
				self::$logger->setStepLog();
				return self::_setErr( 'Can\'t create SQL backup file!' );
			}
			self::$logString.='Created SQL backup file successfully.<br/>';
			self::$logger->stepProject['step']++;
			if( self::$logger->setStepLog() === false ){
				return self::_setErr( 'Can\'t save data to temp file!<br/>' );
			}
			return true;
		}
		$_dirStart=substr(ABSPATH,0,-1);
		$_pluginDir=WP_CONTENT_DIR.self::$ds.'plugins'.self::$ds.'xpandbuddy'.self::$ds;
		$_wpContentDir=substr( WP_CONTENT_DIR, strlen( ABSPATH ) );
		$_tmpDir=getcwd();
		chdir( ABSPATH );
		if( self::$logger->stepProject['step']<2 ){ // Get file list
			self::$logger->stepProject['arrFiles']=array();
			if( @self::$logger->stepProject['settings']['user_data']['only_settings']==1 ){
				if( @self::$logger->stepProject['settings']['user_data']['database'] == 1 ){
					self::$logger->stepProject['arrFiles'][]='wp-backups'.self::$ds.'dump_'.self::$logger->logDate.'.sql';
				}
				if( @self::$logger->stepProject['settings']['user_data']['plugins']==1 ){
					self::getFiles( self::$logger->stepProject['arrFiles'], $_dirStart.self::$ds.$_wpContentDir.self::$ds.'plugins', $_dirStart );
				}
				if( @self::$logger->stepProject['settings']['user_data']['themes']==1 ){
					self::getFiles( self::$logger->stepProject['arrFiles'], $_dirStart.self::$ds.$_wpContentDir.self::$ds.'themes', $_dirStart );
				}
				if( @self::$logger->stepProject['settings']['user_data']['uploads']==1 && is_dir( $_dirStart.self::$ds.$_wpContentDir.self::$ds.'uploads'.self::_subdirCreation() ) ){
					self::getFiles( self::$logger->stepProject['arrFiles'], $_dirStart.self::$ds.$_wpContentDir.self::$ds.'uploads'.self::_subdirCreation(), $_dirStart );
				}
			}else{
				if( @self::$logger->stepProject['settings']['blog']['flg_mu2single']==1 ){
					self::getFiles( self::$logger->stepProject['arrFiles'], $_dirStart, $_dirStart, false );
					foreach( self::$logger->stepProject['arrFiles'] as $_id=>$_file ){
						if( $_file == '.htaccess' ){
							unset( self::$logger->stepProject['arrFiles'][$_id] );
							continue;
						}
					}
					self::getFiles( self::$logger->stepProject['arrFiles'], $_dirStart.self::$ds.'wp-admin', $_dirStart );
					self::getFiles( self::$logger->stepProject['arrFiles'], $_dirStart.self::$ds.'wp-includes', $_dirStart );
					self::getFiles( self::$logger->stepProject['arrFiles'], $_dirStart.self::$ds.$_wpContentDir.self::$ds.'languages', $_dirStart );
					self::getFiles( self::$logger->stepProject['arrFiles'], $_dirStart.self::$ds.$_wpContentDir.self::$ds.'plugins', $_dirStart );
					self::getFiles( self::$logger->stepProject['arrFiles'], $_dirStart.self::$ds.$_wpContentDir.self::$ds.'themes', $_dirStart );
					if( is_dir( $_dirStart.self::$ds.$_wpContentDir.self::$ds.'uploads'.self::$ds.'sites'.self::_subdirCreation() ) ){
						self::getFiles( self::$logger->stepProject['arrFiles'], $_dirStart.self::$ds.$_wpContentDir.self::$ds.'uploads'.self::$ds.'sites'.self::_subdirCreation(), $_dirStart );
					}else{
						self::$logger->stepProject['arrFiles'][]=$_wpContentDir.self::$ds.'uploads'.self::$ds.'sites'.self::$ds.'index.php';
					}
				}else{
					self::getFiles( self::$logger->stepProject['arrFiles'], $_dirStart, $_dirStart );
				}
			}
			$_clearDirs=array();
			$_saveFiles=self::$logger->stepProject['arrFiles'];
			if( isset(self::$logger->stepProject['settings']['user_data']['arr_execute_dirs']) && count( self::$logger->stepProject['settings']['user_data']['arr_execute_dirs'] ) > 0 &&
			isset(self::$logger->stepProject['settings']['user_data']['files_exclude']) && self::$logger->stepProject['settings']['user_data']['files_exclude'] == 1 ){
				foreach( @self::$logger->stepProject['settings']['user_data']['arr_execute_dirs'] as $_dirs ){
					if( $_dirs['value']==1 ){
						$_clearDirs[]=stripslashes( $_dirs['name'] );
					}
				}
				$_saveFiles=array();
				foreach( self::$logger->stepProject['arrFiles'] as $_file ){
					$_flgCheck=true;
					foreach( $_clearDirs as $_needle ){
						if( strpos( $_dirStart.self::$ds.$_file, $_needle ) === 0 ){
							$_flgCheck=false;
						}
					}
					if( $_flgCheck && empty( self::$logger->stepProject['settings']['files_exclude_filter'] ) 
						|| ( $_flgCheck && !empty( self::$logger->stepProject['settings']['files_exclude_filter'] ) && @fnmatch( self::$logger->stepProject['settings']['files_exclude_filter'], $_dirStart.self::$ds.$_file ) )
					){
						$_saveFiles[]=$_file;
					}
				}
			}
			self::$logger->stepProject['arrAllFilesCount']=count($_saveFiles);
			self::$logger->stepProject['arrFiles']=$_saveFiles;
			self::$logString.='Found '.self::$logger->stepProject['arrAllFilesCount'].' files & dirs.<br/>';
			self::$logger->stepProject['step']++;
			self::$logger->setStepLog();
			return true;
		}
		if( @self::$logger->stepProject['settings']['blog']['flg_mu2single']==1 ){
			self::setReplaceUnit( 'wp-config.php', '/(WP_ALLOW_MULTISITE.*[,]{1})(.*)([\)]{1})/i', "\${1}false\${3}", 1, 0 );
			self::setReplaceUnit( 'wp-config.php', '/(MULTISITE.*[,]{1})(.*)([\)]{1})/i', "\${1}false\${3}", 1, 0 );
			self::setReplaceUnit( 'wp-config.php', '/(\$table_prefix.*[\'"]{1})(.*)([\'"]{1})/i', "\${1}".self::$logger->stepProject['settings']['blog']['db_tableprefix'].'$3', 1, 0 );
			self::setReplaceUnit( '##all_files##', "#".addslashes( $_wpContentDir.self::$ds.'uploads'.self::$ds.'sites'.self::_subdirCreation() )."#i", addslashes( $_wpContentDir.self::$ds.'uploads' ), 1, 0 );
			self::setReplaceUnit( '##all_files##', "#".addslashes( str_replace( self::$ds, '/', $_wpContentDir.self::$ds.'uploads'.self::$ds.'sites'.self::_subdirCreation() ) )."#i", addslashes( str_replace( self::$ds, '/', $_wpContentDir.self::$ds.'uploads'.self::$ds ) ), 1, 0 );
		}
		$_backupFile='backup-prj'.self::$logger->stepProject['id'].'-'.date( "Y-m-d-H", self::$logger->logDate );
		if( !isset( self::$logger->stepProject['flg_type'] ) ){
			self::$logger->stepProject['flg_type']=array('1'=>'1');
		}
		self::setOptions( self::$logger->stepProject['settings'], self::$logger->stepProject['flg_type'][1] );
		if( self::$logger->stepProject['step']<3 ){ // Move all files to archive
			$_flgStep3FirstRun = self::$logger->stepProject['arrAllFilesCount'] == count( self::$logger->stepProject['arrFiles'] );
			if( @self::$logger->stepProject['settings']['user_data']['only_settings']==1 ){
				$archivator=self::activateStepArchiveClass( false, 'wp-backups'.self::_subdirCreation(), $_flgStep3FirstRun);
			}else{
				$archivator=self::activateStepArchiveClass( $_backupFile, substr(ABSPATH,0,-1).self::$ds.'wp-backups'.self::$ds, $_flgStep3FirstRun );
			}
			$_summFileSize=0;
			$_inStepCounter=0;
			$_memoryLimit=ini_get('memory_limit');
			switch (substr($_memoryLimit, -1)){
				case 'M': case 'm': $_memoryLimit=(int)$_memoryLimit*1048576;break;
				case 'K': case 'k': $_memoryLimit=(int)$_memoryLimit*1024;break;
				case 'G': case 'g': $_memoryLimit=(int)$_memoryLimit*1073741824;break;
			}
			if( count( self::$logger->stepProject['arrFiles'] ) > 0 ){
				self::$logString.='Archiving:<br/>';
				foreach( self::$logger->stepProject['arrFiles'] as $_fileKey=>$_file ){
					$_fileSize=@filesize( $_dirStart.self::$ds.$_file );
					if( $_summFileSize>1024*1024 && $_inStepCounter > 1 ){
						self::$logString.='Proceeding to next step.<br/>';
						break;
					}
					$_summFileSize+=$_fileSize;
					if( is_file( $_dirStart.self::$ds.$_file ) ){
						if( memory_get_usage()+2*$_fileSize < $_memoryLimit-4194304 ){
							// change only php ini html htaccess files sql
							if( in_array( substr(strrchr($_dirStart.self::$ds.$_file, '.'), 1), array( 'php', 'ini', 'html', 'htaccess', 'sql' ) ) ){
								if( isset( self::$_arrChanges['##all_files##'] ) ){
									foreach( self::$_arrChanges['##all_files##'] as $_keyName=>$_keyValues ){
										foreach( $_keyValues as $_oneValue ){
											if( !isset( self::$_arrChanges[$_file] ) ){
												self::$_arrChanges[$_file]=array();
											}
											self::$_arrChanges[$_file][$_keyName][]=$_oneValue;
										}
									}
								}
								$handleFile=fopen( $_dirStart.self::$ds.$_file, "r" );
								$_copyFileName=basename( $_dirStart.self::$ds.$_file );
								$_copyDirName=substr($_file, 0, -strlen( basename( $_dirStart.self::$ds.$_file ) )-1 );
								$_copyFileTemp=$_pluginDir.'temp'.self::$ds.$_copyFileName;
								$copyFile=fopen( $_copyFileTemp, "w" );
								if( $handleFile===false || $copyFile === false ){
									$arrError=error_get_last();
									self::$logger->setStepLog();
									return self::_setErr( 'The plugin could not be activated because of your host server settings. '.@$arrError['message'].'. Please contact your host to get the issue resolved.' );
								}
								while( ( $buffer=fgets( $handleFile ) )!==false ){
									if( $buffer === false){
										$arrError=error_get_last();
										self::$logString.=$arrError.'<br/>';
										self::$logger->setStepLog();
										return self::_setErr( 'The plugin could not be activated because of your host server settings. '.@$arrError['message'].'. Please contact your host to get the issue resolved.' );
									}
									if( isset( self::$_arrChanges[$_file] ) ){
										if(isset( self::$_arrChanges[$_file]['string_b'] ) ){
											$buffer=preg_replace( self::$_arrChanges[$_file]['bufer_b'], self::$_arrChanges[$_file]['replace_b'], preg_replace( self::$_arrChanges[$_file]['string_b'], self::$_arrChanges[$_file]['bufer_a'], $buffer ) );
										}
										if(isset( self::$_arrChanges[$_file]['string'] ) ){
											$buffer=preg_replace( self::$_arrChanges[$_file]['string'], self::$_arrChanges[$_file]['replace'], $buffer );
										}
									}
									if( fwrite( $copyFile, $buffer ) === false){
										$arrError=error_get_last();
										self::$logString.=$arrError.'<br/>';
										self::$logger->setStepLog();
										return self::_setErr( 'The plugin could not be activated because of your host server settings. '.@$arrError['message'].'. Please contact your host to get the issue resolved.' );
									}
								}
								if (!feof($handleFile)) {
									$arrError=error_get_last();
									self::$logger->setStepLog();
									return self::_setErr( 'The plugin could not be activated because of your host server settings. '.@$arrError['message'].'. Please contact your host to get the issue resolved.' );
								}
								fclose( $handleFile );
								fclose( $copyFile );
								if( !self::haveErrors( $archivator, $archivator->add(
									$_copyFileTemp,
									PCLZIP_OPT_ADD_PATH, $_copyDirName,
									PCLZIP_OPT_REMOVE_PATH, $_pluginDir.'temp'.self::$ds, 
									PCLZIP_OPT_TEMP_FILE_THRESHOLD, 1024*1024*10,
									PCLZIP_OPT_NO_COMPRESSION,
									PCLZIP_OPT_TEMP_FILE_ON
								) ) ){
									@unlink( $_copyFileTemp );
									self::$logString.='Can\'t archiving data from '.$_file.'<br/>'.self::$error.'<br/>';
									self::$logger->setStepLog();
									return self::_setErr( 'Can\'t archiving data from '.$_file.'!' );
								}
								@unlink( $_copyFileTemp );
								self::$logString.=$_file.'<br/>';
							}else{
								$_removePath=$_dirStart.self::$ds;
								$_addPath='';
								if( @self::$logger->stepProject['settings']['blog']['flg_mu2single']==1 && strpos($_dirStart.self::$ds.$_file, $_wpContentDir.self::$ds.'uploads') !== false ){
									$_removePath=$_wpContentDir.self::$ds.'uploads'.self::$ds.'sites'.self::_subdirCreation();
									$_addPath=$_wpContentDir.self::$ds.'uploads';
								}
								if( !self::haveErrors( $archivator, $archivator->add(
									$_dirStart.self::$ds.$_file,
									PCLZIP_OPT_ADD_PATH, $_addPath,
									PCLZIP_OPT_REMOVE_PATH, $_removePath, 
									PCLZIP_OPT_TEMP_FILE_THRESHOLD, 1024*1024*10,
									PCLZIP_OPT_NO_COMPRESSION,
									PCLZIP_OPT_TEMP_FILE_ON
								) ) ){
									self::$logString.='Can\'t archiving data from '.$_file.'<br/>'.self::$error.'<br/>';
									self::$logger->setStepLog();
									return self::_setErr( 'Can\'t archiving data from '.$_file.'!' );
								}
								self::$logString.=$_file.'<br/>';
							}
							self::$_filesCounter++;
							$_inStepCounter++;
						}else{
							self::$logString.='No archiving data from '.$_file.' - memory limit is end!<br/>Saving after restart!<br/>';
							break;
						}
					}elseif( is_dir( $_dirStart.self::$ds.$_file ) && count( scandir( $_dirStart.self::$ds.$_file ) )<3 ){
						if( !self::haveErrors( $archivator, $archivator->add(
							$_dirStart.self::$ds.$_file,
							PCLZIP_OPT_REMOVE_PATH, $_dirStart.self::$ds,
							PCLZIP_OPT_ADD_PATH, '',
							PCLZIP_OPT_NO_COMPRESSION,
							PCLZIP_OPT_TEMP_FILE_ON
						) ) ){
							self::$logString.='Can\'t archiving data dir '.$_file.'<br/>'.self::$error.'<br/>';
							self::$logger->setStepLog();
							return self::_setErr( 'Can\'t archiving dir '.$_file.'!' );
						}
						self::$_filesCounter++;
						self::$logString.=$_file.'<br/>';
					}
					unset( self::$logger->stepProject['arrFiles'][ $_fileKey ] );
				}
				self::$logString.='Package of files saved.<br/>';
				self::$logger->setStepLog();
				return true;
			}else{
				self::$logger->stepProject['step']++;
				self::$logger->setStepLog();
				return true;
			}
		}
		if( @self::$logger->stepProject['settings']['user_data']['only_settings']!=1 ){
			if( self::$logger->stepProject['step']<4 ){
				$archivator=self::activateStepArchiveClass( $_backupFile, substr(ABSPATH,0,-1).self::$ds.'wp-backups'.self::$ds );
				if( !self::haveErrors( $archivator, $archivator->add( 
						$_dirStart.self::$ds.'wp-backups'.self::$ds.'dump_'.self::$logger->logDate.'.sql',
						PCLZIP_OPT_ADD_PATH, '', 
						PCLZIP_OPT_REMOVE_PATH, $_dirStart.self::$ds, 
						PCLZIP_OPT_TEMP_FILE_THRESHOLD, 1024*1024*10,
						PCLZIP_OPT_NO_COMPRESSION,
						PCLZIP_OPT_TEMP_FILE_ON
					) ) ){
					self::$logString.=self::$error.'<br/>';
					self::$logger->setStepLog();
					return self::_setErr( 'Can\'t archiving SQL dump file!' );
				}
				self::$logString.='Archiving SQL dump file.<br/>';
				self::$logger->stepProject['step']++;
				self::$logger->setStepLog();
				return true;
			}
			if( self::$logger->stepProject['step']<5 ){
				$archivator=self::activateStepArchiveClass(false,'wp-backups'.self::_subdirCreation(), true );
				if( !self::haveErrors( $archivator, $archivator->add( 
					substr(ABSPATH,0,-1).self::$ds.'wp-backups'.self::$ds.$_backupFile.self::$logger->stepProject['settings']['archive_type'], 
					PCLZIP_OPT_ADD_PATH, '', 
					PCLZIP_OPT_REMOVE_PATH, $_dirStart.self::$ds.'wp-backups'.self::$ds, 
					PCLZIP_OPT_TEMP_FILE_THRESHOLD, 1024*1024*10,
					PCLZIP_OPT_NO_COMPRESSION,
					PCLZIP_OPT_TEMP_FILE_ON
				) ) ){
					self::$logString.=self::$error.'<br/>';
					self::$logger->setStepLog();
					return self::_setErr( 'Can\'t compare data file!' );
				}
				if( is_file( 'wp-backups'.self::$ds.$_backupFile.self::$logger->stepProject['settings']['archive_type'] ) ){
					unlink( 'wp-backups'.self::$ds.$_backupFile.self::$logger->stepProject['settings']['archive_type'] );
				}
				self::$logString.='Compare data file.<br/>';
				self::$logger->stepProject['step']++;
				self::$logger->setStepLog();
				return true;
			}
			if( self::$logger->stepProject['step']<6 ){
				$archivator=self::activateStepArchiveClass(true,'wp-backups'.self::_subdirCreation() );
				$_indexFile=$_pluginDir.'temp'.self::$ds.'index.php';
				$_archiveClassName=( self::$logger->stepProject['settings']['archive_type'] == '.zip' )?'PclZip':'TarArchive';
				if( false === @file_put_contents($_indexFile,
'<?php
set_time_limit(0);
ignore_user_abort(true);
if( function_exists("version_compare")&&version_compare( phpversion(), "5.2", "<") ){
	echo "Low version PHP, try to manually extract!";
	die();
}
include_once( "'.$_archiveClassName.'.php" );
$archivator=new '.$_archiveClassName.'("'.$_backupFile.self::$logger->stepProject['settings']['archive_type'].'");
@unlink("index.php");
$archivator->extract( PCLZIP_OPT_REPLACE_NEWER, PCLZIP_OPT_SET_CHMOD, ( substr( php_sapi_name(), 0, 3 )=="cgi"? 0644:0777 ) );
if( $archivator->error_code == 0 && empty( $archivator->error ) ){
	$link=mysql_connect(\''.self::$logger->stepProject['settings']['blog']['db_host'].'\', \''.self::$logger->stepProject['settings']['blog']['db_username'].'\', \''.self::$logger->stepProject['settings']['blog']['db_password'].'\');
	if(!$link){
		die( mysql_error() );
	}
	if(!mysql_select_db(\''.self::$logger->stepProject['settings']['blog']['db_name'].'\', $link)){
		die( mysql_error() );
	}
	mysql_set_charset("utf8",$link);
	$handle=@fopen( ".".DIRECTORY_SEPARATOR."wp-backups".DIRECTORY_SEPARATOR."dump_'.self::$logger->logDate.'.sql", "r" );
	$arrError=array();
	if($handle){
		global $table_prefix;
		while( $buffer=fgets( $handle ) ){
			$buffer=trim( $buffer );
			if( empty( $buffer ) || ord($buffer{0})==35 ){
				continue;
			}
			if( !empty( $buffer ) ){
				str_replace( "##new_table_prefix##", $table_prefix, $buffer );
				if( !mysql_query( $buffer, $link ) ){
					$arrError[]=mysql_query("SHOW ENGINE INNODB STATUS;")."\n\n".mysql_error()."\n\n".$buffer;
				}
			}
		}
		fclose($handle);
	}else{
		$arrError=error_get_last();
		die( "The plugin could not be activated because of your host server settings. ".@$arrError["message"].". Please contact your host to get the issue resolved." );
	}
	mysql_close($link);
	@unlink("'.$_backupFile.self::$logger->stepProject['settings']['archive_type'].'");
	@unlink("'.$_archiveClassName.'.php");
	if( empty( $arrError ) ){
		header("Refresh: 1;");
	}
}else{
	echo( "Error: ".$archivator->errorInfo( true ) );
}
?>')
				){
					self::$logger->setStepLog();
					return self::_setErr( 'Can\'t create action file!' );
				}
				if( !self::haveErrors( $archivator, $archivator->add( 
					$_indexFile, 
					PCLZIP_OPT_ADD_PATH, '', 
					PCLZIP_OPT_REMOVE_PATH, $_pluginDir.'temp'.self::$ds, 
					PCLZIP_OPT_TEMP_FILE_THRESHOLD, 1024*1024*10,
					PCLZIP_OPT_NO_COMPRESSION,
					PCLZIP_OPT_TEMP_FILE_ON
				) ) ){
					self::$logString.=self::$error.'<br/>';
					self::$logger->setStepLog();
					return self::_setErr( 'Can\'t compare action file!' );
				}
				if( is_file( $_indexFile ) ){
					unlink( $_indexFile );
				}
				self::$logString.='Compare action file.<br/>';
				self::$logger->stepProject['step']++;
				self::$logger->setStepLog();
				return true;
			}
			if( self::$logger->stepProject['step']<7 ){
				$archivator=self::activateStepArchiveClass(true,'wp-backups'.self::_subdirCreation() );
				if( self::$logger->stepProject['settings']['archive_type'] == '.zip' ){
					if( !self::haveErrors( $archivator, $archivator->add( 
						$_pluginDir."library".self::$ds."PclZip.php", 
						PCLZIP_OPT_ADD_PATH, '', 
						PCLZIP_OPT_REMOVE_PATH, $_pluginDir."library".self::$ds, 
						PCLZIP_OPT_TEMP_FILE_THRESHOLD, 1024*1024*10,
						PCLZIP_OPT_NO_COMPRESSION,
						PCLZIP_OPT_TEMP_FILE_ON
					) ) ){
						self::$logString.=self::$error.'<br/>';
						self::$logger->setStepLog();
						return self::_setErr( 'Can\'t compare class file!' );
					}
				}else{
					if( !self::haveErrors( $archivator, $archivator->add( 
						$_pluginDir."library".self::$ds."TarArchive.php", 
						PCLZIP_OPT_ADD_PATH, '', 
						PCLZIP_OPT_REMOVE_PATH, $_pluginDir."library".self::$ds, 
						PCLZIP_OPT_TEMP_FILE_THRESHOLD, 1024*1024*10,
						PCLZIP_OPT_NO_COMPRESSION,
						PCLZIP_OPT_TEMP_FILE_ON
					) ) ){
						self::$logString.=self::$error.'<br/>';
						self::$logger->setStepLog();
						return self::_setErr( 'Can\'t compare class file!' );
					}
				}
				self::$logString.='Compare class file.<br/>';
				self::$logger->stepProject['step']++;
				self::$logger->setStepLog();
				return true;
			}
		}
		if( is_file( $_dirStart.self::$ds.'wp-backups'.self::$ds.'dump_'.self::$logger->logDate.'.sql' ) ){
			unlink( $_dirStart.self::$ds.'wp-backups'.self::$ds.'dump_'.self::$logger->logDate.'.sql' );
		}
		self::$logger->deleteStepLog();
		self::$logger->stepProject['settings']['file_loader']=self::$logger->stepProject['settings']['file_name'];
		self::$logString.='Process completed!';
		chdir( $_tmpDir );
		return true;
	}
	
	private static function createStepBackup(){
		$_tmpDir=getcwd();
		chdir( ABSPATH );
		include_once Xpandbuddy::$pathName.'/library/Backup.php';
		$_backup=new Xpandbuddy_Backup();
		if( !$_backup->setInfo( self::$logger->stepProject['settings'] ) ){
			return self::_setErr( 'Empty options' );
		}
		if( !$_backup->b_create_dump( 'wp-backups'.self::$ds.'dump_'.self::$logger->logDate.'.sql' ) ){
			return self::_setErr( 'Empty database. Can`t create sql backup.' );
		}
		chdir( $_tmpDir );
		return true;
	}
	
	private static function activateStepArchiveClass( $_fileName=false, $_dir='', $_flgRemoveOld=false ){
		if( $_fileName === false ){
			$_fileName=self::archiveName(self::$logger->stepProject['settings']['blog']['url'], self::$logger->stepProject['id'], time() );
		}
		if( $_flgRemoveOld && $handle=opendir( $_dir ) ){
			$_readyToRemove=array();
			while( false !== ( $entry = readdir( $handle ) ) ){
				if( $entry != "." && $entry != ".." 
					&& ( strpos( $entry, substr($_fileName, 0, -(strlen( $_fileName )-stripos( $_fileName, '-20' ) ) ) ) === 0 || strpos( $entry, $_fileName ) === 0 ) 
					&& strpos( $entry, '-custom' ) === false
				){
					$_readyToRemove[filemtime( $_dir.$entry )]=$_dir.$entry;
				}
			}
			closedir( $handle );
			foreach( $_readyToRemove as $_k=>$_n ){
				if( ( count( $_readyToRemove )>(int)self::$logger->stepProject['settings']['updates_number'] && self::$logger->stepProject['flg_type'][1]==1 )
					|| ( count( $_readyToRemove )>=1 && self::$logger->stepProject['flg_type'][1]!=1 )
					|| strpos( $_n, $_fileName ) !== false
				){
					if( unlink( $_n ) ){
						unset( $_readyToRemove[$_k] );
					}
				}
			}
			
			
//exit;
			
		}
		if( $_fileName === true ){
			if( !isset( self::$logger->stepProject['settings']['archive_type'] ) || self::$logger->stepProject['settings']['archive_type'] == '.zip' ){
				$archivator=new PclZip( $_dir.self::$logger->stepProject['settings']['file_name'] );
			}else{
				$archivator=new TarArchive( $_dir.self::$logger->stepProject['settings']['file_name'] );
			}
			return $archivator;
		}
		if( !isset( self::$logger->stepProject['settings']['archive_type'] ) || self::$logger->stepProject['settings']['archive_type'] == '.zip' ){
			self::$logger->stepProject['settings']['file_name']=$_fileName.'.zip';
			$archivator=new PclZip( $_dir.self::$logger->stepProject['settings']['file_name'] );
		}else{
			self::$logger->stepProject['settings']['file_name']=$_fileName.'.tar.gz';
			$archivator=new TarArchive( $_dir.self::$logger->stepProject['settings']['file_name'] );
		}
		return $archivator;
	}
	
	public static function _subdirCreation( $ds=DIRECTORY_SEPARATOR ){
		$_subdir=$ds;
		if( is_multisite() ){
			$_subdir=$ds.get_current_blog_id().$ds;
			if( !is_dir( substr(ABSPATH,0,-1).self::$ds.'wp-backups'.self::$ds.get_current_blog_id() ) ){
				mkdir( substr(ABSPATH,0,-1).self::$ds.'wp-backups'.self::$ds.get_current_blog_id() );
			}
		}
		return $_subdir;
	}

	public static function haveErrors( $archivator, $_arr ){
		if( $_arr == 0 ){
			return self::_setErr( $archivator->errorInfo(true) );
		}
		if( is_array( $_arr ) ){
			foreach( $_arr as $_file ){
				if( $_file['status'] != 'ok' ){
					return self::_setErr( $archivator->errorInfo(true) );
				}
			}
		}
		return true;
	}

	public static function zipUpload( $filePosition ){
		$_tmpDir=getcwd();
		chdir( ABSPATH );
		set_time_limit(0);
		ignore_user_abort(true);
		error_reporting( E_ALL );
		if( is_array( $filePosition ) && isset( $filePosition['name'] ) && isset( $filePosition['tmp_name'] ) ){
			if( substr( $filePosition['name'], strrpos($filePosition['name'], ".") ) == '.gz' ){
				$archivator=new TarArchive( $filePosition['tmp_name'] );
			}else{
				$archivator=new PclZip( $filePosition['tmp_name'] );	
			}
			$filePosition=$filePosition['name'];
		}elseif( is_string( $filePosition ) && is_file( $filePosition ) ){
			if( substr( $filePosition, strrpos($filePosition, ".") ) == '.gz' ){
				$archivator=new TarArchive( $filePosition );
			}else{
				$archivator=new PclZip( $filePosition );	
			}
		}else{
			return self::_setErr( "Can't get archive file" );
		}
		$_arrList=$archivator->listContent();
		if( $_arrList===false ){
			return self::_setErr( "Can't read archive file" );
		}
		$_flgBackup=$_flgClone=false;
		foreach( $_arrList as $_item){
			if( strpos( $_item['filename'], 'index.php' ) !== false || strpos( $_item['filename'], 'backup_' ) !== false ){
				$_flgClone=true;
			}
			if( !(stripos($_item['filename'],'wp-backups')===false) ){
				$_flgBackup=true;
			}
		}
		if( !$_flgBackup && !$_flgClone ){
			return self::_setErr( "Can't identify archive file" );
		}
		if( $_flgBackup ){
			$archivator->extract( PCLZIP_OPT_REPLACE_NEWER, PCLZIP_OPT_PATH, substr(ABSPATH,0,-1) );
		}
		if( $_flgClone ){
			$archivator->extract( PCLZIP_OPT_REPLACE_NEWER, PCLZIP_OPT_PATH, substr(ABSPATH,0,-1).self::$ds );
			if( substr( $filePosition, strrpos($filePosition, ".") ) == '.gz' ){
				$archivator=new TarArchive( substr(ABSPATH,0,-1).self::$ds.'backup.tar.gz' );
			}else{
				$archivator=new PclZip( substr(ABSPATH,0,-1).self::$ds.'backup.zip' );
			}
			$archivator->extract( PCLZIP_OPT_REPLACE_NEWER, PCLZIP_OPT_PATH, substr(ABSPATH,0,-1) );
		}
		include_once Xpandbuddy::$pathName.'/library/Backup.php';
		$_backup=new Xpandbuddy_Backup();
		if( !$_backup->b_set_dump( '.'.self::$ds.'wp-backups'.self::$ds.'dump.sql' ) ){
			return self::_setErr( 'Can`t open dump file' );
		}
		chdir( $_tmpDir );
		return true;
	}
	
	private static function _removeOldSteps(){
		$_tempDir=WP_CONTENT_DIR.self::$ds.'plugins'.self::$ds.'xpandbuddy'.self::$ds.'temp';
		while( false !== ($_logOldFile = readdir($handle)) ){
			if( strpos( "log_file_", $_logOldFile ) !== false && filemtime( $_logOldFile )-time() > 5*60 ){
				unlink( $_logOldFile );
			}
		}
	}
	
	private static function _removeStepFile(){
		if( empty( self::$_logCode ) ){
			return true;
		}
		$_pluginDir=WP_CONTENT_DIR.self::$ds.'plugins'.self::$ds.'xpandbuddy'.self::$ds;
		$_logFileName=$_pluginDir.'temp'.self::$ds.'log_file_'.self::$_logCode.'.log';
		unlink( $_logFileName );
	}
	
	private static function _setStepData( $_stepName, $_data=false, $lastMessge='', $forceMessage=false ){
		if( empty( self::$_logCode ) ){
			return true;
		}
		$_pluginDir=WP_CONTENT_DIR.self::$ds.'plugins'.self::$ds.'xpandbuddy'.self::$ds;
		if( !is_dir( $_pluginDir.'temp' ) ){
			mkdir( $_pluginDir.'temp' );
		}
		$_logFileName=$_pluginDir.'temp'.self::$ds.'log_file_'.self::$_logCode.'.log';
		if( $_logFileLink=fopen( $_logFileName, "w" ) ){
			if( !in_array( $_stepName, self::$_arrSteps ) ){
				self::$_arrSteps[]=$_stepName;
			}
			$_countersData=array(
				'all'=>self::$_allFilesCounter,
				'now'=>self::$_filesCounter,
			);
			$_return = fwrite( $_logFileLink, serialize( array( 'step'=>$_stepName, 'old_steps'=>self::$_arrSteps, 'data'=>$_data, 'counters'=>$_countersData ) ) );
			fclose( $_logFileLink );
			if( microtime(true) - self::$_stepStart > 5 || $forceMessage ){
				echo json_encode( array( 'message'=>$lastMessge, 'next_link'=>self::$_logCode, 'time'=>microtime(true) - self::$_stepStart, 'counters'=>$_countersData ) );
				self::$logString='';
				exit;
			}
			return $_return;
		}
	}
	
	private static function _getStepData( $_stepName ){
		if( empty( self::$_logCode ) ){
			return 'NOT_ACTIVATE';
		}
		if( self::$_stepStart === false ){
			self::$_stepStart=microtime(true);
		}
		$_pluginDir=WP_CONTENT_DIR.self::$ds.'plugins'.self::$ds.'xpandbuddy'.self::$ds;
		if( !is_dir( $_pluginDir.'temp' ) ){
			mkdir( $_pluginDir.'temp' );
		}
		$_logFileName=$_pluginDir.'temp'.self::$ds.'log_file_'.self::$_logCode.'.log';
		if( $_logFileLink=fopen( $_logFileName, "a+" ) ){
			$_logFileSize=filesize($_logFileName);
			if( $_logFileSize != 0 ){
				$_logData=unserialize( fread($_logFileLink, $_logFileSize) );
				fclose($_logFileLink);
				if( $_logData === false || ( isset( $_logData['step'] ) && $_logData['step']!=$_stepName ) ){
					if( isset( $_logData['old_steps'] ) ){
						self::$_arrSteps=$_logData['old_steps'];
						self::$_filesCounter=$_logData['counters']['now'];
						self::$_allFilesCounter=$_logData['counters']['all']-1;
					}
					if( in_array( $_stepName, self::$_arrSteps) ){
						return 'STEP_ENDED';
					}else{
						return 'NO_DATA';
					}
				}else{
					self::$_arrSteps=$_logData['old_steps'];
					self::$_filesCounter=$_logData['counters']['now'];
					self::$_allFilesCounter=$_logData['counters']['all']-1;
					if( $_logData['data'] === true ){
						return 'STEP_ENDED';
					}else{
						return $_logData['data'];
					}
				}
			}else{
				return 'EMPTY_LOG';
			}
		}
		return 'NO_CONNECTION';
	}
}}
if(!function_exists('fnmatch')){
    function fnmatch($pattern, $string){
        return preg_match("#^".strtr(preg_quote($pattern, '#'), array('\*' => '.*', '\?' => '.'))."$#i", $string);
    }
}
?>