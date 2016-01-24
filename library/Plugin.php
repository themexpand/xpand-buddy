<?php
/**
 * Plugin class
 */
if( !class_exists('Xpandbuddy') ){
class Xpandbuddy{
	
	private static $_optionName='plugin_xpandbuddy_options';
	private static $_options=array();
	
	public static $baseName;
	public static $pathName;
	public static $pluginSlug='xpandbuddy';
	

	public static function registerMenu(){
		add_menu_page('Xpand Buddy', 'Xpand Buddy', 'manage_options', 'xpandbuddy-backup','Xpandbuddy::pluginPage', Xpandbuddy::$baseName.'skin/logo_s.png' );
		add_submenu_page('xpandbuddy-backup','Backup','Backup','manage_options','xpandbuddy-backup','Xpandbuddy::pluginPage' );
		add_submenu_page('xpandbuddy-backup','Clone','Clone','manage_options','xpandbuddy-clone','Xpandbuddy::pluginPage' );
//		add_submenu_page('xpandbuddy-backup','Run Cron','Run Cron','manage_options','xpandbuddy-cron','Xpandbuddy_Cron::run' );
	}

	public static function disableEmojis() {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );	
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );	
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'tiny_mce_plugins', array('Xpandbuddy','disableEmojisTinymce'));
	}
	
	function disableEmojisTinymce( $plugins ) {
		if ( is_array( $plugins ) ) {
			return array_diff( $plugins, array( 'wpemoji' ) );
		} else {
			return array();
		}
	}
	
	public function pluginRun(){
		@ini_set( 'memory_limit', '256M' );
		require_once Xpandbuddy::$pathName.'/library/Cron.php';
		require_once Xpandbuddy::$pathName.'/library/Sender.php';
		//dropbox
		add_action( 'wp_ajax_get_authorization_code', array('Xpandbuddy_Sender','getAuthorizationCode') );
		add_action( 'wp_ajax_get_google_code', array('Xpandbuddy_Sender','getGoogleCode') );
		add_action( 'wp_ajax_get_google_token', array('Xpandbuddy_Sender','getGoogleToken') );
		add_action( 'wp_ajax_get_access_token', array('Xpandbuddy_Sender','getAccessToken') );
		add_action( 'wp_ajax_check_backup_size', array('Xpandbuddy','checkBackupSize') );
		add_action( 'wp_ajax_activate_multiusers', array('Xpandbuddy','activateMultiusers') );
		add_action( 'wp_ajax_submit_clone', array('Xpandbuddy','runStepByStepBackup') );
		add_action( 'wp_ajax_send_backup', array('Xpandbuddy_Sender','sendBackupFileAction') );
		add_action( 'wp_ajax_set_start_date', array('Xpandbuddy','setStartDate') );
		add_action( 'wp_ajax_get_local_dirs', array('Xpandbuddy_Sender','ajaxGetDirsTree') );
		
		add_action( 'wp_ajax_ftp_connect', array('Xpandbuddy_Sender','getFtpConnect') );
		if( is_admin() && isset($_GET['page']) && strpos( $_GET['page'], 'xpandbuddy' ) !== false && isset($_GET['code']) ){
			echo( 'This is Authorization Code: '. $_GET['code'] );
			exit;
		}
		//end dropbox
		/*/ Update plugin {
		global $wp_filter;
		$priority=30;
		if( isset( $wp_filter['plugins_api'] ) ){
			foreach( array_keys( $wp_filter['plugins_api'] ) as $_priority ){
				if( $_priority >= $priority ){
					$priority=$_priority+1;
				}
			}
		}
		add_filter('pre_set_site_transient_update_plugins', array( 'Xpandbuddy', 'updatePlugin' ) );
		add_filter('plugins_api', array( 'Xpandbuddy', 'pluginApiCall' ), $priority, 3);
		self::pluginUpdateCall();
		// } Update plugin */
		require_once Xpandbuddy::$pathName.'/library/Options.php';
		$_arrayOptions=Xpandbuddy_Options::get();
		if( !isset( $_arrayOptions['homeurl_hash'] ) || $_arrayOptions['homeurl_hash'] != md5( home_url() ) ){
			add_action('init',array( 'Xpandbuddy','updateRewrite'));
			$_arrayOptions['homeurl_hash']=md5( home_url() );
			Xpandbuddy_Options::set( $_arrayOptions );
		}
		self::getJson();
		if( is_multisite() ){
			if( isset( $_arrayOptions['flg_active_miltisite'] ) && $_arrayOptions['flg_active_miltisite']==true && is_main_network() ){
				add_action('admin_menu', array('Xpandbuddy','registerMenu'));
			}
			if( is_main_network() ){
				add_action('network_admin_menu', array('Xpandbuddy','registerMenu'));
			}
			//echo get_current_blog_id()." ".(int)is_main_site()." ".(int)is_main_network();
		}else{
			add_action('admin_menu', array('Xpandbuddy','registerMenu'));
		}
		// WP-CRON {
		add_filter('cron_schedules', array('Xpandbuddy_Cron','schedules') );
		if( !wp_next_scheduled('blogcloncron_event') ){
			wp_schedule_event( time(), 'blogcloncron_update', 'blogcloncron_event' );
		}
		add_action('blogcloncron_event', array('Xpandbuddy_Cron','run') );
		// }
		if( !is_admin() || ( is_admin() && ( ( isset($_GET['page']) && strpos( $_GET['page'], 'xpandbuddy' ) === false ) || !isset($_GET['page']) ) ) ){
			return;
		}
		add_action( 'init', array('Xpandbuddy','disableEmojis' ) );
		self::get();
		if( !isset( self::$_options['db_version'] ) ){
			self::install();
		}else{
			self::updateDb();
		}
		add_action('admin_enqueue_scripts', array('Xpandbuddy','initialize') );
	}

	public static function activateMultiusers(){
		require_once Xpandbuddy::$pathName.'/library/Options.php';
		if( $_POST['checked'] == 'true' ){
			$_arrayOptions['flg_active_miltisite']=true;
		}else{
			$_arrayOptions['flg_active_miltisite']=false;
		}
		Xpandbuddy_Options::set( $_arrayOptions );
	}

	public static function runStepByStepBackup(){
		add_action( 'init', array('Xpandbuddy','disableEmojis' ));
		header( 'Content-type: application/json' );
		require_once Xpandbuddy::$pathName.'/library/Cron.php';
		if( isset( $_POST['arrProject']['settings']['type_clone'] ) ){
			$_POST['arrProject']['settings']['user_data']=$_POST['arrProject']['settings']['type_clone'];
			unset( $_POST['arrProject']['settings']['type_clone'] );
		}
		if( isset( $_POST['arrProject']['settings']['type_backup'] ) ){
			$_POST['arrProject']['settings']['user_data']=$_POST['arrProject']['settings']['type_backup'];
			unset( $_POST['arrProject']['settings']['type_backup'] );
		}
		$_cron=new Xpandbuddy_Cron();
		echo json_encode( $_cron
			->setProgectId( @$_POST['project'] )
			->setProjectSettings( @$_POST['arrProject'] )
			->setLogDate( @$_POST['logcode'] )
			->backup() );
		exit;
	}

	public static function checkBackupSize(){
		global $table_prefix;
		$_userInfo=get_userdata( get_current_user_id() );
		require_once Xpandbuddy::$pathName.'/library/Twig.php';
		if( isset( $_POST['arrProject']['settings']['type_clone'] ) ){
			$_POST['arrProject']['settings']=$_POST['arrProject']['settings']['type_clone']+$_POST['arrProject']['settings'];
		}
		if( isset( $_POST['arrProject']['settings']['type_backup'] ) ){
			$_POST['arrProject']['settings']=$_POST['arrProject']['settings']['type_backup']+$_POST['arrProject']['settings'];
		}
		$_POST['arrProject']['settings']=$_POST['arrProject']['settings']+array( 'blog'=>array(
			'title'=>get_bloginfo('name'),
			'url'=>home_url(),
			'path'=>substr(ABSPATH,0,-1),
			'db_host'=>DB_HOST,
			'db_name'=>DB_NAME,
			'db_username'=>DB_USER,
			'db_password'=>DB_PASSWORD,
			'db_tableprefix'=>$table_prefix,
			'dashboad_username'=>( isset( $_userInfo->user_login )? $_userInfo->user_login : 'user' ),
			'dashboad_password'=>'',
		),array( 'user_data'=>array( 'only_settings'=>0 ) ) );
		echo Xpandbuddy_Twig::checkSize( $_POST['arrProject'] );
		exit;
	}

	public static function updateRewrite(){
		require_once substr(ABSPATH,0,-1).DIRECTORY_SEPARATOR.'wp-admin/includes/file.php';
		require_once substr(ABSPATH,0,-1).DIRECTORY_SEPARATOR.'wp-admin/includes/misc.php';
		if(function_exists('flush_rewrite_rules')){
			flush_rewrite_rules( true );
		}
	}
	
	public static function pluginUpdateCall(){
		if( @$_GET['action']=='install-plugin' ){
			return false;
		}
	}
	
	public static function pluginApiCall($def, $action, $args){
		return $def;
	}

	public static function updatePlugin( $arrForm ){
		global $wp_version;
		if( !isset( $arrForm->checked ) || empty($arrForm->checked) ){
			return $arrForm;
		}
		return false;
	}

	private function getFilesFromDir( &$arrFIles, $_dirname ){
		if( $_handle=opendir( $_dirname ) ){
			while( false!==( $_file=readdir( $_handle ) ) ){
				if($_file!='.'&&$_file!='..'){
					if( is_file( $_dirname.DIRECTORY_SEPARATOR.$_file ) ){
						$arrFIles[]=$_dirname.DIRECTORY_SEPARATOR.$_file;
					}
					if( is_dir( $_dirname.DIRECTORY_SEPARATOR.$_file ) ){
						$this->getFilesFromDir( $arrFIles, $_dirname.DIRECTORY_SEPARATOR.$_file );
						$arrFIles[]=$_dirname.DIRECTORY_SEPARATOR.$_file;
					}
				}
			}
		}
	}
	
	public static function prepareTmpDir( &$strDir ){
		$strDir=$strDir.DIRECTORY_SEPARATOR;
		if( is_dir( $strDir ) ){
			$_arrFiles=array();
			$_obj=new Xpandbuddy();
			$_obj->getFilesFromDir( $_arrFiles, $strDir );
			foreach( $_arrFiles as $_strFileName ){
				if( is_file( $_strFileName ) ){
					@unlink( $_strFileName );
				}elseif( is_dir( $_strFileName ) ){
					@rmdir( $_strFileName );
				}
			}
		}
		if( !is_dir( $strDir ) ){
			mkdir( $strDir, 0755, true );
		}
		return is_dir( $strDir );
	}

	public static function getJson(){
		if( isset($_GET['runCpanel']) ){
			self::runCpanel( $arrData );
			extract( $arrData );
			require_once( Xpandbuddy::$pathName.'/source/plugin/cpanel.php' );
			die();
		}
	}

	private static function runCpanel( &$arrData=array() ){
		$arrData=array( 'pluginUrl'=>Xpandbuddy::$baseName );
		require_once Xpandbuddy::$pathName.'/library/Cpanel.php';
		require_once Xpandbuddy::$pathName.'/library/Ftp.php';
		$_model=new Xpandbuddy_Cpanel();
		if( !empty($_POST['arrCpanel']) && !$_model->setAccess($_POST['arrCpanel']) ){
			$arrData['error']='Process Aborted. Not correct data';
			return;
		}
		if( !empty( $_POST ) ){
			if( !$_model->createDb( $_POST['arrAction'] ) ){
				$arrData['error']='001';
				if( isset( $_model->error ) && !empty( $_model->error ) ){
					$arrData['error']=$_model->error;
				}
				return;
			}
			$result=$_model->getResult();
			if( isset( $result['bind'] ) && $result['bind'] ){
				$arrData['jsonResult']=json_encode($result);
				$arrData['result']=$result;
				return;
			}else{
				if( isset( $_model->error ) && !empty( $_model->error ) ){
					$arrData['error']=$_model->error;
				}
				$arrData['error']='002';
				return;
			}
		}
	}
	
	public static function initialize(){
		wp_enqueue_script('mootools', Xpandbuddy::$baseName.'skin/_js/mootools.js', false, '1.4.1');
		wp_enqueue_script('roar', Xpandbuddy::$baseName.'skin/_js/roar/roar.js', false, '0.1');
		wp_enqueue_script('validator', Xpandbuddy::$baseName.'skin/_js/validator/validator.js', false, '0.1');
		wp_enqueue_script('post');
		wp_enqueue_script('thickbox');
		wp_enqueue_script('media-upload');
		wp_enqueue_style('xpandbuddy_plugin_clone_css', Xpandbuddy::$baseName.'skin/_css/plugin.css');
		wp_enqueue_style('xpandbuddy_plugin_roar', Xpandbuddy::$baseName.'skin/_js/roar/roar.css');
		wp_enqueue_style('xpandbuddy_validator', Xpandbuddy::$baseName.'skin/_js/validator/style.css');
		wp_enqueue_style('thickbox');
		wp_enqueue_style('colors');
	}

	public static function getMaxUploadSize(){
		$_maxFileSize=ini_get('upload_max_filesize');
		switch (substr($_maxFileSize, -1)){
			case 'K': case 'k': $_maxFileSize=(int)$_maxFileSize*pow(2,10);break;
			case 'M': case 'm': $_maxFileSize=(int)$_maxFileSize*pow(2,20);break;
			case 'G': case 'g': $_maxFileSize=(int)$_maxFileSize*pow(2,30);break;
		}
		$_maxPOSTSize=ini_get('post_max_size');
		switch (substr($_maxPOSTSize, -1)){
			case 'K': case 'k': $_maxPOSTSize=(int)$_maxPOSTSize*pow(2,10);break;
			case 'M': case 'm': $_maxPOSTSize=(int)$_maxPOSTSize*pow(2,20);break;
			case 'G': case 'g': $_maxPOSTSize=(int)$_maxPOSTSize*pow(2,30);break;
		}
		return ( $_maxFileSize<$_maxPOSTSize )? $_maxFileSize : $_maxPOSTSize;
	}

	public static function restoreBackupFile( &$arrForm ){
		// RESTORE BACKUP
		if( !isset( $_POST['submit_file'] ) && !isset( $_FILES['file'] )
			&& ( !isset( $_GET['restore'] ) || empty( $_GET['restore'] ) )
		){
			return;
		}
		require_once Xpandbuddy::$pathName.'/library/Twig.php';
		// cronfile start
		if( isset( $_POST['submit_file'] ) ){
			switch( $_FILES['file']['error'] ){
				case '0':// all ok
					if( !Xpandbuddy_Twig::zipUpload( $_FILES['file'] ) ){
						$arrForm['error']=Xpandbuddy_Twig::$error;
					}else{
						$arrForm['action']='File is uploaded';
						return;
					}
				break;
				case '1': $arrForm['error']='The uploaded file exceeds the upload_max_filesize directive in php.ini.';break;
				case '2': $arrForm['error']='The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';break;
				case '3': $arrForm['error']='The uploaded file was only partially uploaded.';break;
				case '4': /**/ break;
				case '6': $arrForm['error']='Missing a temporary folder.';break;
				case '7': $arrForm['error']='Failed to write file to disk.';break;
				case '8': $arrForm['error']='A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop';break;
			}
			return;
		}
		require_once Xpandbuddy::$pathName.'/library/Projects.php';
		$_object = new Xpandbuddy_Projects();
		if( isset( $_GET['project'] ) && !empty( $_GET['project'] ) ){
			$_object->withIds( $_GET['project'] )->onlyOne()->getList( $_arrProject );
		}
		if( isset( $_arrProject['flg_type'] ) ){
			foreach( $_arrProject['flg_type'] as $type ){
				switch( $type ){
					case '1':
						if( is_file( substr(ABSPATH,0,-1).DIRECTORY_SEPARATOR.'wp-backups'.Xpandbuddy_Twig::_subdirCreation().$_GET['restore'] ) ){
							if( !Xpandbuddy_Twig::zipUpload( substr(ABSPATH,0,-1).DIRECTORY_SEPARATOR.'wp-backups'.Xpandbuddy_Twig::_subdirCreation().$_GET['restore'] ) ){
								$arrForm['error']=Xpandbuddy_Twig::$error;
							}else{
								$arrForm['action']='Backup is uploaded!';
								return;
							}
						}
					break;
					case '2':
						if(strlen((string) PHP_INT_MAX) >= 19){
							require_once Xpandbuddy::$pathName."/library/Dropbox/autoload.php";
							$dbxClient=Dropbox_Client($_arrProject['settings']['access_token']);
							if( substr( $_GET['restore'], strrpos($_GET['restore'], ".") ) == '.gz' ){
								$_tail='.tar.gz';
							}else{
								$_tail='.zip';	
							}
							$file=fopen( substr(ABSPATH,0,-1).DIRECTORY_SEPARATOR.Xpandbuddy_Twig::archiveName($_arrProject['settings']['blog']['url'], $_arrProject['id'], time() ).$_tail, "w+b");
							$dbxClient->getFile( $_GET['restore'], $file );
							fclose($file);
							if( !Xpandbuddy_Twig::zipUpload( substr(ABSPATH,0,-1).DIRECTORY_SEPARATOR.Xpandbuddy_Twig::archiveName($_arrProject['settings']['blog']['url'], $_arrProject['id'], time() ).$_tail ) ){
								$arrForm['error']=Xpandbuddy_Twig::$error;
								@unlink( substr(ABSPATH,0,-1).DIRECTORY_SEPARATOR.Xpandbuddy_Twig::archiveName($_arrProject['settings']['blog']['url'], $_arrProject['id'], time() ).$_tail );
							}else{
								$arrForm['action']='Dropbox backup is uploaded';
								@unlink( substr(ABSPATH,0,-1).DIRECTORY_SEPARATOR.Xpandbuddy_Twig::archiveName($_arrProject['settings']['blog']['url'], $_arrProject['id'], time() ).$_tail );
								return;
							}
						}
					break;
					case '3':
						require_once Xpandbuddy::$pathName."/library/GoogleAPI/Google_Client.php";
						$client = new Google_Client();
						$client->setClientId( $_arrProject['settings']['google_key'] );
						$client->setClientSecret( $_arrProject['settings']['google_secret'] );
						$client->setRedirectUri( admin_url('admin.php?page=xpandbuddy') );
						$client->setScopes(array('https://www.googleapis.com/auth/drive'));
						$client->refreshToken( $_arrProject['settings']['refresh_token'] );
						$accessToken=json_decode( $client->getAccessToken() );
						$accessToken=$accessToken->access_token;
						$_folderName=str_replace( array( "http://", "https://", ".", "/" ), array( "","","_","_" ), $_arrProject['settings']['blog']['url'] );
						$req=new Google_HttpRequest( 'https://www.googleapis.com/drive/v2/files?q='.urlencode( "mimeType = 'application/vnd.google-apps.folder' and title = '".$_folderName."' and trashed = false" ), 'GET', array(
								'Authorization' => "Bearer  ".$accessToken
							)
						);
						$siteFolder=json_decode( Google_Client::$io->makeRequest($req)->getResponseBody() );
						if( count( $siteFolder->items ) != 0 ){
							$req=new Google_HttpRequest( 'https://www.googleapis.com/drive/v2/files?q='.urlencode( "'".$siteFolder->items[0]->id."' in parents" ), 'GET', array(
									'Authorization' => "Bearer  ".$accessToken
								)
							);
							$siteFiles=json_decode( Google_Client::$io->makeRequest($req)->getResponseBody() );
							foreach( $siteFiles->items as $_file ){
								if( $_file->originalFilename == $_GET['restore'] ){
									$filePosition=$_file->originalFilename;
									if( substr( $filePosition, strrpos($filePosition, ".") ) == '.gz' ){
										$_tail='.tar.gz';
									}else{
										$_tail='.zip';	
									}
									$file=fopen( substr(ABSPATH,0,-1).Xpandbuddy_Twig::archiveName($_arrProject['settings']['blog']['url'], $_arrProject['id'], time() ).$_tail, "w+b");
									$src=fopen( $_file->alternateLink, 'r');
									if( !copy($src, $file) ){
										$arrForm['error']="Copy Error!";
										continue;
									}
									fclose($file);
									fclose($src);
									if( !Xpandbuddy_Twig::zipUpload( substr(ABSPATH,0,-1).DIRECTORY_SEPARATOR.Xpandbuddy_Twig::archiveName($_arrProject['settings']['blog']['url'], $_arrProject['id'], time() ).$_tail ) ){
										$arrForm['error']=Xpandbuddy_Twig::$error;
									}else{
										$arrForm['action']='Google Drive backup is uploaded';
									}
									@unlink( substr(ABSPATH,0,-1).Xpandbuddy_Twig::archiveName($_arrProject['settings']['blog']['url'], $_arrProject['id'], time() ).$_tail );
									return;
								}
							}
						}
					break;
					case '4':
						if( !empty($_arrProject['settings']['host'] ) 
							&& !empty($_arrProject['settings']['user'] ) 
							&& !empty($_arrProject['settings']['pass'] ) ){
							require_once Xpandbuddy::$pathName."/library/Ftp.php";
							$_ftp=new Xpandbuddy_Ftp();
							if( !$_ftp
								->setChmod( '0644' )
								->setHost( urldecode( $_arrProject['settings']['host'] ) )
								->setUser( urldecode( $_arrProject['settings']['user'] ) )
								->setPassw( urldecode( $_arrProject['settings']['pass'] ) )
								->setRoot( $_arrProject['settings']['dir_name'] )
								->makeConnectToRootDir() ){
								$arrForm['error']="FTP connnection Error!";
							}
							if( !$_ftp->fileDownload( $_GET['restore'], substr(ABSPATH,0,-1).DIRECTORY_SEPARATOR.$_GET['restore'], true ) ){
								$arrForm['error']="Get file from FTP Error!";
							}
							$_ftp->closeConnection();
							if( !Xpandbuddy_Twig::zipUpload( substr(ABSPATH,0,-1).DIRECTORY_SEPARATOR.$_GET['restore'] ) ){
								$arrForm['error']=Xpandbuddy_Twig::$error;
								@unlink( substr(ABSPATH,0,-1).DIRECTORY_SEPARATOR.$_GET['restore'] );
							}else{
								$arrForm['action']='FTP backup is uploaded';
								@unlink( substr(ABSPATH,0,-1).DIRECTORY_SEPARATOR.$_GET['restore'] );
								return;
							}
						}
					break;
					case '5':
						if( is_file( substr(ABSPATH,0,-1).DIRECTORY_SEPARATOR.'wp-backups'.Xpandbuddy_Twig::_subdirCreation().$_GET['restore'] ) ){
							if( !Xpandbuddy_Twig::zipUpload( substr(ABSPATH,0,-1).DIRECTORY_SEPARATOR.'wp-backups'.Xpandbuddy_Twig::_subdirCreation().$_GET['restore'] ) ){
								$arrForm['error']=Xpandbuddy_Twig::$error;
							}else{
								$arrForm['action']='Backup is uploaded!';
								return;
							}
						}
					break;
				}
			}
			if( isset($_GET['restore']) ){
				if( is_file( substr(ABSPATH,0,-1).DIRECTORY_SEPARATOR.'wp-backups'.Xpandbuddy_Twig::_subdirCreation().$_GET['restore'] ) ){
					if( !Xpandbuddy_Twig::zipUpload( substr(ABSPATH,0,-1).DIRECTORY_SEPARATOR.'wp-backups'.Xpandbuddy_Twig::_subdirCreation().$_GET['restore'] ) ){
						$arrForm['error']=Xpandbuddy_Twig::$error;
					}else{
						$arrForm['action']='Backup is uploaded!';
					}
				}
			}
		}
		return;
	}

	public static function setStartDate(){
		if( !isset( $_POST['project'] ) || empty( $_POST['project'] ) ){
			exit;
		}
		require_once Xpandbuddy::$pathName.'/library/Projects.php';
		$_object = new Xpandbuddy_Projects();
		$_object->withIds( $_POST['project'] )->onlyOne()->getList( $_arrProject );
		if( isset( $_arrProject['id'] ) ){
			$_arrProject['start']=time();
			$_object->setEntered( $_arrProject )->setStartDate( $_arrProject['start'] );
			echo $_arrProject['start'];
		}else{
			echo 0;
		}
		exit;
	}

	public static function removeBackupFile(){
		if( !isset( $_GET['remove'] ) || empty( $_GET['remove'] ) ){
			return;
		}
		require_once Xpandbuddy::$pathName.'/library/Projects.php';
		$_object = new Xpandbuddy_Projects();
		if( isset( $_GET['project'] ) && !empty( $_GET['project'] ) ){
			$_object->withIds( $_GET['project'] )->onlyOne()->getList( $_arrProject );
		}
		if( is_file( substr(ABSPATH,0,-1).DIRECTORY_SEPARATOR.'wp-backups'.Xpandbuddy_Twig::_subdirCreation().$_GET['remove'] ) ){
			@unlink( substr(ABSPATH,0,-1).DIRECTORY_SEPARATOR.'wp-backups'.Xpandbuddy_Twig::_subdirCreation().$_GET['remove'] );
			return;
		}
		if( isset( $_arrProject['flg_type'] ) ){
			switch( $_arrProject['flg_type'] ){
				case '1':/**/break;
				case '2':
					if(strlen((string) PHP_INT_MAX) >= 19){
						require_once Xpandbuddy::$pathName."/library/Dropbox/autoload.php";
						$dbxClient=Dropbox_Client($_arrProject['settings']['access_token']);
						$dbxClient->delete( $_GET['remove'] );
					}
				break;
				case '3':
					require_once Xpandbuddy::$pathName."/library/GoogleAPI/Google_Client.php";
					$client = new Google_Client();
					$client->setClientId( $_arrProject['settings']['google_key'] );
					$client->setClientSecret( $_arrProject['settings']['google_secret'] );
					$client->setRedirectUri( admin_url('admin.php?page=xpandbuddy') );
					$client->setScopes(array('https://www.googleapis.com/auth/drive'));
					$client->refreshToken( $_arrProject['settings']['refresh_token'] );
					$accessToken=json_decode( $client->getAccessToken() );
					$accessToken=$accessToken->access_token;
					$_folderName=str_replace( array( "http://", "https://", ".", "/" ), array( "","","_","_" ), $_arrProject['settings']['blog']['url'] );
					$req=new Google_HttpRequest( 'https://www.googleapis.com/drive/v2/files?q='.urlencode( "mimeType = 'application/vnd.google-apps.folder' and title = '".$_folderName."' and trashed = false" ), 'GET', array(
							'Authorization' => "Bearer  ".$accessToken
						)
					);
					$siteFolder=json_decode( Google_Client::$io->makeRequest($req)->getResponseBody() );
					if( count( $siteFolder->items ) != 0 ){
						$req=new Google_HttpRequest( 'https://www.googleapis.com/drive/v2/files?q='.urlencode( "'".$siteFolder->items[0]->id."' in parents" ), 'GET', array(
								'Authorization' => "Bearer  ".$accessToken
							)
						);
						$siteFiles=json_decode( Google_Client::$io->makeRequest($req)->getResponseBody() );
						foreach( $siteFiles->items as $_file ){
							if( $_file->originalFilename == $_GET['remove'] ){
								new Google_HttpRequest( 'https://www.googleapis.com/drive/v2/files/'.urlencode( $_file->id ), 'DELETE', array(
										'Authorization' => "Bearer  ".$accessToken
									)
								);
								return;
							}
						}
					}
				break;
				case '4':/*ftp*/break;
				case '5':/*email*/break;
			}
		}
		return;
	}

	public static function pluginPage(){
		$_dropbox64Error=false;
		if(strlen((string) PHP_INT_MAX) < 19){
			$_dropbox64Error=true;
		}
		require_once Xpandbuddy::$pathName.'/library/PclZip.php';
		require_once Xpandbuddy::$pathName.'/library/TarArchive.php';
		require_once Xpandbuddy::$pathName.'/library/Backup.php';
		require_once Xpandbuddy::$pathName.'/library/Options.php';
		require_once Xpandbuddy::$pathName.'/library/Twig.php';
		require_once Xpandbuddy::$pathName.'/library/Projects.php';
		$arrForm=array();
		self::restoreBackupFile( $arrForm );
		$arrForm['arrFiles']=array();
		$_object = new Xpandbuddy_Projects();
		if( isset( $_POST['submit_save'] ) ){
			$_projectForSave=$_projectInitial=$_POST['arrProject'];
			$_flgSaveId=false;
			if( array_sum( $_projectInitial['flg_type'] ) > 0 ){
				foreach( $_projectInitial['flg_type'] as $_key=>$_value ){
					$_projectForSave=$_projectInitial;
					if( $_key==$_value ){
						$_projectForSave['settings']['user_data']=$_projectInitial['settings']['type_'.$_value];
						unset( $_projectInitial['settings']['type_'.$_value] );
						foreach( $_projectForSave['settings'] as $_i => $_d ){
							if( $_i == 'type_'.$_value ){
								continue;
							}elseif( strpos( $_i, 'type_' ) === 0 ){
								unset( $_projectForSave['settings'][$_i] );
							}
						}
						$_projectForSave['flg_type']=array( $_key=>$_value );
						if( $_flgSaveId ){
							unset( $_projectForSave['id'] ); // save with new id
						}else{
							$_flgSaveId=true;
						}
						$_object->setEntered( $_projectForSave )->set();
					}
				}
			}else{
				$_object->setEntered( $_projectForSave )->set();
			}
			$arrForm['action']='Backup Project is activated.';
		}
		if( isset( $_GET['del'] ) && !empty( $_GET['del'] ) ){
			$_object->withIds( $_GET['del'] )->del();
		}
		self::removeBackupFile();
		$_object->getList( $arrForm['arrList'] );
		foreach( $arrForm['arrList'] as &$_project ){
			if( isset( $_GET['id'] ) && !empty( $_GET['id']) && $_project['id'] == $_GET['id'] ){
				$arrForm['arrProject']=$_project;
				break;
			}
		}
		$_userInfo=get_userdata( get_current_user_id() );
		$arrForm['arrBlog']=array(
			'title'=>( isset($arrForm['arrProject']['settings']['blog']['title']) ? $arrForm['arrProject']['settings']['blog']['title'] : get_bloginfo('name') ),
			'url'=>( isset($arrForm['arrProject']['settings']['blog']['url']) ? $arrForm['arrProject']['settings']['blog']['url'] : home_url() ),
			'path'=>( isset($arrForm['arrProject']['settings']['blog']['path']) ? $arrForm['arrProject']['settings']['blog']['path'] : substr(ABSPATH,0,-1) ),
			'dashboad_username'=>( isset($arrForm['arrProject']['settings']['blog']['dashboad_username']) ? $arrForm['arrProject']['settings']['blog']['dashboad_username'] : ( isset( $_userInfo->user_login )? $_userInfo->user_login : 'user' ) ),
			'db_tableprefix'=>( isset($arrForm['arrProject']['settings']['blog']['db_tableprefix']) ? $arrForm['arrProject']['settings']['blog']['db_tableprefix'] : @$GLOBALS['table_prefix'] ),
			'dashboad_password'=>''
		);
		if( !is_multisite() || ( is_multisite() && is_super_admin() ) ){
			$arrForm['arrBlog']['db_host']=( isset($arrForm['arrProject']['settings']['blog']['db_host']) ? $arrForm['arrProject']['settings']['blog']['db_host'] : DB_HOST );
			$arrForm['arrBlog']['db_name']=( isset($arrForm['arrProject']['settings']['blog']['db_name']) ? $arrForm['arrProject']['settings']['blog']['db_name'] : DB_NAME );
			$arrForm['arrBlog']['db_username']=( isset($arrForm['arrProject']['settings']['blog']['db_username']) ? $arrForm['arrProject']['settings']['blog']['db_username'] : DB_USER );
			$arrForm['arrBlog']['db_password']=( isset($arrForm['arrProject']['settings']['blog']['db_password']) ? $arrForm['arrProject']['settings']['blog']['db_password'] : DB_PASSWORD );
		}else{
			$arrForm['arrBlog']['db_host']=$arrForm['arrBlog']['db_name']=$arrForm['arrBlog']['db_username']=$arrForm['arrBlog']['db_password']='';
		}
		$_otherBackupFiles=array();
		$_backupsDir=substr(ABSPATH,0,-1).DIRECTORY_SEPARATOR.'wp-backups'.Xpandbuddy_Twig::_subdirCreation();
		$_arrBackupsFiles=scandir($_backupsDir);
		
		$_homeUrlHash=str_replace( array( "http://", "https://", ".", "/" ), array( "","","_","_" ), home_url() );
// Filest list generation
		foreach( $_arrBackupsFiles as $_number=>$_name ){
			if( strpos( $_name, '.tar.gz' ) === false && strpos( $_name, '.zip' ) === false ){
				continue;
			}
			$_flg_clone=false;
// Progect LIST generetion
			if( !empty( $arrForm['arrList'] ) ) 
			foreach( $arrForm['arrList'] as &$_project ){
				if( strpos( $_name, "-prj".$_project['id']."-" ) !== false && strpos( $_name, "backup-" ) !== 0 ){
					if( $_project['settings']['blog']['url'] !== home_url() ){
						$_flg_clone=true;
					}
					global $current_site;
					$_time=@filemtime( $_backupsDir.DIRECTORY_SEPARATOR.$_name );
					if( isset( $_project['files'][$_time] ) ){
						for( $_time=$_time+1; !isset( $_project['files'][$_time+1] ); $_time++ ){
							break;
						}
					}
					$_status='Failed';
					require_once Xpandbuddy::$pathName.'/library/Logger.php';
					$_logger=new Xpandbuddy_Logger( 'sender' );
					$_logger->logDate=$_project['start'];
					$_logger->getStepLog();
					if( !empty( $_logger->stepProject ) ){
						$_status='In Progress';
					}
					if( @$_project['flg_type'][1] == 1 || @array_sum( $_project['flg_type'] ) == 0 ){
						$_status='Completed';
					}
					$_project['files'][$_time]=array(
						'file'=>$_name,
						'link'=>get_site_url( @$current_site->id ).'/wp-backups'.Xpandbuddy_Twig::_subdirCreation('/').$_name,
						'flg_status'=>$_status,
						'flg_clone'=>$_flg_clone,
						'date'=>$_time
					);
					unset( $_arrBackupsFiles[$_number] );
					continue 2;
				}
			}
			
// Progect LIST generetion end
			
			if( strpos( $_name, $_homeUrlHash.'-' ) === false ){
				$_flg_clone=true;
			}
			global $current_site;
			if( strpos( $_name, "backup-" ) !== 0 ){
				$_otherBackupFiles[]=array(
					'file'=>$_name,
					'link'=>get_site_url( @$current_site->id ).'/wp-backups'.Xpandbuddy_Twig::_subdirCreation('/').$_name,
					'flg_status'=>'Completed',
					'flg_clone'=>$_flg_clone,
					'date'=>@filemtime( $_backupsDir.DIRECTORY_SEPARATOR.$_name )
				);
				unset( $_arrBackupsFiles[$_number] );
			}
		}
		
// Filest list generation end
		
		if( !empty( $arrForm['arrList'] ) ) foreach( $arrForm['arrList'] as &$_project ){
			require_once Xpandbuddy::$pathName.'/library/Logger.php';
			$_logger=new Xpandbuddy_Logger( 'sender' );
			$_logger->logDate=$_project['start'];
			$_logger->getStepLog();
			if( !empty( $_logger->stepProject ) ){
				$_project['log']=$_logger->stepProject;
			}
			$_logger=new Xpandbuddy_Logger( 'step_log_file' );
			$_logger->logDate=$_project['start'];
			$_logger->getStepLog();
			if( !empty( $_logger->stepProject ) ){
				$_project['log']=$_logger->stepProject;
			}
			foreach( $_project['flg_type'] as $_type ){
				switch( $_type ){
					case '1': /**/ break;
					case '2':
						try{
							if(strlen((string) PHP_INT_MAX) >= 19){
								require_once Xpandbuddy::$pathName."/library/Dropbox/autoload.php";
								$dbxClient=Dropbox_Client($_project['settings']['access_token']);
								$folderMetadata=$dbxClient->getMetadataWithChildren( "/".$_homeUrlHash );
								if( isset( $folderMetadata['contents'] ) && is_array( @$folderMetadata['contents'] ) ){
									foreach( @$folderMetadata['contents'] as $_file ){
										$_link=$dbxClient->createTemporaryDirectLink( $_file['path'] );
										if( $_file['is_dir'] == false && strpos( $_file['path'], "-prj".$_project['id']."-" )!=false ){
											$_project['files'][strtotime( $_file['modified'] )]=array(
												'file'=>$_file['path'],
												'link'=>$_link[0],
												'flg_status'=>'Completed',
												'flg_clone'=>false,
												'date'=>strtotime( $_file['modified'] )
											);
										}
									}
								}
							}
						}catch(Exception $e){
							$arrForm['error']='Exception: '.$e->getMessage();
						}
					break;
					case '3':
						if( !empty($_project['settings']['refresh_token']) ){
							try{
								require_once Xpandbuddy::$pathName."/library/GoogleAPI/Google_Client.php";
								require_once Xpandbuddy::$pathName."/library/GoogleAPI/contrib/Google_DriveService.php";
								$client = new Google_Client();
								$client->setClientId( $_project['settings']['google_key'] );
								$client->setClientSecret( $_project['settings']['google_secret'] );
								$client->setRedirectUri( admin_url('admin.php?page=xpandbuddy') );
								$client->setScopes(array('https://www.googleapis.com/auth/drive'));
								$client->refreshToken( $_project['settings']['refresh_token'] );
								$accessToken=json_decode( $client->getAccessToken() );
								$accessToken=$accessToken->access_token;
								$req=new Google_HttpRequest( 'https://www.googleapis.com/drive/v2/files?q='.urlencode( "mimeType = 'application/vnd.google-apps.folder' and title = '".$_homeUrlHash."' and trashed = false" ), 'GET', array(
										'Authorization' => "Bearer  ".$accessToken
									)
								);
								$siteFolder=json_decode( Google_Client::$io->makeRequest($req)->getResponseBody() );
								if( isset( $siteFolder->items ) && count( $siteFolder->items ) > 0 ){
									$req=new Google_HttpRequest( 'https://www.googleapis.com/drive/v2/files?q='.urlencode( "'".$siteFolder->items[0]->id."' in parents" ), 'GET', array(
											'Authorization' => "Bearer  ".$accessToken
										)
									);
									$siteFiles=json_decode( Google_Client::$io->makeRequest($req)->getResponseBody() );
									$_fileFromGoogle=array();
									$_flgHaveFileInGoogle=false;
									foreach( $siteFiles->items as $_file ){
										if( strpos( $_file->originalFilename, "-prj".$_project['id']."-" )!==false ){
											$_project['files'][strtotime( $_file->modifiedDate )]=array(
												'file'=>$_file->originalFilename,
												'link'=>$_file->alternateLink,
												'flg_status'=>'Completed',
												'flg_clone'=>false,
												'date'=>strtotime( $_file->modifiedDate )
											);
										}
									}
								}
							}catch(Exception $e){
								$arrForm['error']='Exception: '.$e->getMessage();
							}
						}
					break;
					case '4':
						if( !empty($_project['settings']['host'] ) 
							&& !empty($_project['settings']['user'] ) 
							&& !empty($_project['settings']['pass'] ) ){
							require_once Xpandbuddy::$pathName."/library/Ftp.php";
							$_ftp=new Xpandbuddy_Ftp();
							if( !$_ftp
								->setChmod( '0644' )
								->setHost( urldecode( $_project['settings']['host'] ) )
								->setUser( urldecode( $_project['settings']['user'] ) )
								->setPassw( urldecode( $_project['settings']['pass'] ) )
								->setRoot( $_project['settings']['dir_name'] )
								->makeConnectToRootDir() ){
								break;
							}
							$_ftp->ls( $_arrFiles );
							if( !empty( $_arrFiles ) && is_array( $_arrFiles ) ){
								foreach( $_arrFiles as $file ){
									$_flg_clone=false;
									if( strpos( $file['name'], $_homeUrlHash.'-' ) === false ){
										$_flg_clone=true;
									}
									if( strpos( $file['name'], "-prj".$_project['id']."-" )!==false ){
										$_time=$file['stamp'];
										if( isset( $_project['files'][$_time] ) ){
											for( $_time=$_time; !isset( $_project['files'][$_time+$i] ); $_time++ ){
												break;
											}
										}
										$_project['files'][$_time]=array(
											'file'=>$file['name'],
											'link'=>'ftp://'.urldecode( $_project['settings']['user'] ).':'.urldecode( $_project['settings']['pass'] ).'@'.urldecode( $_project['settings']['host'] ).$_project['settings']['dir_name'].'/'.$file['name'],
											'flg_status'=>'Completed',
											'flg_clone'=>$_flg_clone,
											'date'=>$_time
										);
									}
								}
							}
							unset( $_arrFiles );
						}
					break;
					case '5': /*email*/ break;
					default: /* 0 */ break;
				}
				break;
			}
		}
		if( !empty( $_otherBackupFiles ) ){
			array_unshift( $arrForm['arrList'], array('files'=>$_otherBackupFiles) );
			unset( $_otherBackupFiles );
		}
		$arrForm['options']=Xpandbuddy_Options::get();
		extract( $arrForm );
		$max_upload_size=self::getMaxUploadSize();
		require_once( Xpandbuddy::$pathName.'/source/plugin/form.php' );
	}

	public function del( $_file ){
		global $wpdb;
		require_once pathinfo( $_file, PATHINFO_DIRNAME ).'/library/Options.php';
		Xpandbuddy_Options::reset();
		delete_option( self::$_optionName );
		$wpdb->query( 'DROP TABLE '.$wpdb->prefix.'xpandbuddy' );
		return true;
	}
	
	public static function install(){
		self::getSqlFilesNames( $_arrFiles );
		if( !empty($_arrFiles) && self::execSqlFiles( $_arrFiles ) ){
			self::set( array("db_version"=>end( $_arrFiles ) ) );
		}
	}

	public static function getSqlFilesNames( &$arrFiles ){
		$_strSqlDirName=Xpandbuddy::$pathName."/sql";
		if( is_dir($_strSqlDirName) && $_handle=opendir( $_strSqlDirName ) ){
			while( false!==( $_file=readdir( $_handle ) ) ){
				if($_file!='.'&&$_file!='..'){
					if( is_file( $_strSqlDirName."/".$_file ) ){
						$arrFiles[]=(int)str_replace('.sql','',$_file);;
					}
				}
			}
		}
	}

	public static function execSqlFiles( &$_arrNames ){
		global $wpdb;
		asort( $_arrNames );
		foreach( $_arrNames as $_fileName ){
			if(file_exists( Xpandbuddy::$pathName.'/sql/'.$_fileName.'.sql' )){
				$_content=@file_get_contents( Xpandbuddy::$pathName.'/sql/'.$_fileName.'.sql' );
				$_content=str_replace('##prefix_##',$wpdb->prefix,$_content);
				foreach( explode(';',str_replace('&amp;','&',$_content)) as $_query ){
					if( trim($_query)=='' ){
						break;
					}
					global $wpdb;
					if( $wpdb->query( $_query )===false ){
						die('MySQL error in file');
					}
				}
			}
		}
		return true;
	}

	public static function updateDb(){
		self::getSqlFilesNames( $_arrFiles );
		foreach( $_arrFiles as $_key=>$_n ){
			if( $_n <= self::$_options['db_version'] ){
				unset( $_arrFiles[$_key] );
			}
		}
		if( empty( $_arrFiles ) ){
			return;
		}
		if( self::execSqlFiles( $_arrFiles ) ){
			self::set( array("db_version"=>end( $_arrFiles ) ) );
		}
	}

	public static function set( $_arrSettings=array() ){
		if( empty( $_arrSettings ) || !is_array( self::$_options ) ){
			return false;
		}
		global $wpdb;
		self::$_options=$_arrSettings+self::$_options;
		if( !update_option( self::$_optionName, json_encode( self::$_options ) ) ){
			add_option( self::$_optionName, json_encode( self::$_options ) );
		}
		self::get();
		return true;
	}

	private static function get(){
		global $wpdb;
		self::$_options=json_decode( get_option( self::$_optionName, '{}' ), true );
		if( empty( self::$_options ) ){
			self::$_options=array();
		}
	}
}}
?>
