<?php
/**
 * Crofile class
 */
if( !class_exists('Xpandbuddy_Sender') ){
class Xpandbuddy_Sender {

	public static $logger=false;

	public static $ds=DIRECTORY_SEPARATOR;

	public static function sendBackupFileAction( $_flgNotShow=false ){
		if( empty($_flgNotShow) || $_flgNotShow === false ){
			header( 'Content-type: application/json' );
		}
		if( !isset( $_POST['project'] ) || empty( $_POST['project'] ) ){
			return json_encode( array('error'=>"Empty project data!",'status'=>false) );
		}
		include_once Xpandbuddy::$pathName.'/library/Projects.php';
		include_once Xpandbuddy::$pathName.'/library/Twig.php';
		include_once Xpandbuddy::$pathName.'/library/Cron.php';
		$_object = new Xpandbuddy_Projects();
		$_object->withIds( $_POST['project'] )->onlyOne()->getList( $_arrProject );
		if( isset( $_POST['send'] ) ){
			$_arrProject['settings']['file_name']=$_POST['send'];
		}
		if( isset( $_POST['return'] ) ){
			$_arrProject['settings']['return']=$_POST['return'];
		}else{
			$_arrProject['settings']['return']=true;
		}
		if( isset( $_POST['type'] ) ){
			$_flgType=$_POST['type'];
		}else{
			$_flgType=1;
		}
		Xpandbuddy_Twig::setOptions( $_arrProject['settings'], $_flgType );
		$_this=new Xpandbuddy_Sender();
		$_return=$_this->sendTo( $_flgType );
		if( empty($_flgNotShow) || $_flgNotShow === false ){
			if( $_return == 'sended' ){
				echo json_encode( array('status'=>'sended', 'logcode'=>$_arrProject['start']) );
			}else{
				echo json_encode( json_decode( $_return, true )+array('logcode'=>$_arrProject['start']));
			}
			exit;
		}
		if( $_return == 'sended' ){
			return json_encode( array('status'=>'sended', 'logcode'=>$_arrProject['start']) );
		}else{
			return json_encode( json_decode( $_return, true )+array('logcode'=>$_arrProject['start']));
		}
	}
	
	public function sendTo( $_type=1 ){
		switch( $_type ){
			case '1':
				return 'sended';
			break;
			case '2':
				return $this->sendDropbox();
			break;
			case '3': 
				return $this->sendGoogle();
			break;
			case '4':
				return $this->sendToFtp();
			break;
			case '5':
				return $this->send();
			break;
		}
	}

	private function send(){
		$attachments=array( substr(ABSPATH,0,-1).self::$ds.'wp-backups'.Xpandbuddy_Twig::_subdirCreation().Xpandbuddy_Twig::$arrOptions['file_name'] );
		$headers='From: '.get_bloginfo('name').' <'.get_bloginfo('admin_email').'>' . "\r\n";
		$_massage=get_bloginfo('name')."\n".get_bloginfo('url')."\nbackup ".date('Y-m-d H:i:s', time() );
		if(@wp_mail(
			Xpandbuddy_Twig::$arrOptions['send_email'],
			get_bloginfo('name').': backup '.date('Y-m-d', time() ),
			$_massage,
			$headers,
			$attachments
		)){
			@unlink( $attachments[0] );
			return 'sended';
		}
	}
	
	private function sendDropbox(){
		require_once Xpandbuddy::$pathName."/library/Dropbox/autoload.php";
		$attachments=substr(ABSPATH,0,-1).self::$ds.'wp-backups'.Xpandbuddy_Twig::_subdirCreation().Xpandbuddy_Twig::$arrOptions['file_name'];
		$dbxClient=Dropbox_Client(Xpandbuddy_Twig::$arrOptions['access_token']);
		try{
			$fileInSteam=@fopen($attachments,"rb");
			if( !isset( Xpandbuddy_Twig::$arrOptions['return']['file_size'] ) ){
				$settings['file_size']=@filesize( $attachments );
			}else{
				$settings['file_size']=(int)Xpandbuddy_Twig::$arrOptions['return']['file_size'];
			}
			if( $fileInSteam !== false ){
				if( !isset( Xpandbuddy_Twig::$arrOptions['return']['upload_id'] ) ){
					$data='';
					$bytesRemaining=4194304;
					while (!feof($fileInSteam) && $bytesRemaining > 0){
						$part=fread($fileInSteam, $bytesRemaining);
						if($part === false){
							if( isset( Xpandbuddy_Twig::$arrOptions['return'] ) ){
								return json_encode( array('error'=>"Error reading from file",'status'=>false) );
							}
						}
						$data.=$part;
						$bytesRemaining-=strlen($part);
					}
					$len=strlen($data);
					$settings['upload_id']=$dbxClient->chunkedUploadStart($data); //3
				}else{
					$settings['upload_id']=Xpandbuddy_Twig::$arrOptions['return']['upload_id'];
				}
				if( !isset( Xpandbuddy_Twig::$arrOptions['return']['range_start'] ) ){
					$settings['range_start']=$len;
				}else{
					$settings['range_start']=(int)Xpandbuddy_Twig::$arrOptions['return']['range_start'];
				}
				if( $settings['range_start'] < $settings['file_size'] ){
					while (!feof($fileInSteam)){
						$data='';
						$bytesRemaining=4194304;
						fseek( $fileInSteam, $settings['range_start'] );
						while (!feof($fileInSteam) && $bytesRemaining > 0){
							$part=fread($fileInSteam, $bytesRemaining);
							if($part === false){
								if( isset( Xpandbuddy_Twig::$arrOptions['return'] ) ){
									return json_encode( array('error'=>"Error reading from file",'status'=>false) );
								}
							}
							$data.=$part;
							$bytesRemaining-=strlen($part);
						}
						$len=strlen($data);
						while (true){
							$r=$dbxClient->chunkedUploadContinue($settings['upload_id'], $settings['range_start'], $data); //3
							if($r === true){  // Chunk got uploaded!
								$settings['range_start']+=$len;
								if( isset( Xpandbuddy_Twig::$arrOptions['return'] ) ){
									return json_encode( array('return'=>$settings,'status'=>true) );
								}
								break;
							}
							if($r === false){  // Server didn't recognize our upload ID
								// This is very unlikely since we're uploading all the chunks in sequence.
								if( isset( Xpandbuddy_Twig::$arrOptions['return'] ) ){
									return json_encode( array('error'=>"Server forgot our uploadId",'status'=>false) );
								}
							}
							// Otherwise, the server is at a different byte offset from us.
							$settings['server_range_start']=$r;
							assert($settings['server_range_start'] !== $settings['range_start']);  // chunkedUploadContinue ensures this.
							// An earlier byte offset means the server has lost data we sent earlier.
							if($settings['server_range_start'] < $settings['range_start']){
								if( isset( Xpandbuddy_Twig::$arrOptions['return'] ) ){
									return json_encode( array('error'=>"Server is at an ealier byte offset: us=".$settings['range_start'].", server=".$settings['server_range_start'],'status'=>false) );
								}
							}
							$diff=$settings['server_range_start'] - $settings['range_start'];
							// If the server is past where we think it could possibly be, something went wrong.
							if($diff > $len){
								if( isset( Xpandbuddy_Twig::$arrOptions['return'] ) ){
									return json_encode( array('error'=>"Server is more than a chunk ahead: us=".$settings['range_start'].", server=".$settings['server_range_start'],'status'=>false) );
								}
							}
							// The normal case is that the server is a bit further along than us because of a
							// partially-uploaded chunk.  Finish it off.
							$settings['range_start'] += $diff;
							if($diff === $len){
								break;  // If the server is at the end, we're done.
							}
							$data=substr($data, $diff);
						}
					}
				}
				$dbxClient->chunkedUploadFinish( //3
					$settings['upload_id'],
					self::$ds.str_replace( array( "http://", "https://", ".", "/" ), array( "","","_","_" ), Xpandbuddy_Twig::$arrOptions['blog']['url'] ).self::$ds.str_replace( 'wp-backups'.Xpandbuddy_Twig::_subdirCreation(),'','wp-backups'.Xpandbuddy_Twig::_subdirCreation().Xpandbuddy_Twig::$arrOptions['file_name'] ),
					Dropbox_WriteMode_add()
				);
				fclose($fileInSteam);
			}
			@unlink( $attachments );
			return 'sended';
		}catch(Exception $e){
			if( isset( Xpandbuddy_Twig::$arrOptions['return'] ) ){
				return json_encode( array('error'=>$e->getMessage(),'status'=>false) );
			}
		}
	}
	
	private function sendToFtp(){
		try{
			require_once Xpandbuddy::$pathName."/library/Ftp.php";
			if( is_file( substr(ABSPATH,0,-1).self::$ds.'wp-backups'.Xpandbuddy_Twig::_subdirCreation().Xpandbuddy_Twig::$arrOptions['file_name'] ) !== false ){
				$_object=new Xpandbuddy_Ftp();
				$_object
					->setChmod( '0644' )
					->setHost( urldecode( Xpandbuddy_Twig::$arrOptions['host'] ) )
					->setUser( urldecode( Xpandbuddy_Twig::$arrOptions['user'] ) )
					->setPassw( urldecode( Xpandbuddy_Twig::$arrOptions['pass'] ) )
					->setRoot( '/' )
					->makeConnectToRootDir();
				$_object->cd( Xpandbuddy_Twig::$arrOptions['dir_name'] );
				$_object->fileUpload( Xpandbuddy_Twig::$arrOptions['file_name'], substr(ABSPATH,0,-1).self::$ds.'wp-backups'.Xpandbuddy_Twig::_subdirCreation().Xpandbuddy_Twig::$arrOptions['file_name'] );
				$_object->closeConnection();
			}
			@unlink( substr(ABSPATH,0,-1).self::$ds.'wp-backups'.Xpandbuddy_Twig::_subdirCreation().Xpandbuddy_Twig::$arrOptions['file_name'] );
			return 'sended';
		}catch(Exception $e){
			return json_encode( array('error'=>$e->getMessage(),'status'=>false) );
		}
	}
	
	private function sendGoogle(){
		try{
			require_once Xpandbuddy::$pathName."/library/GoogleAPI/Google_Client.php";
			if( !isset( Xpandbuddy_Twig::$arrOptions['return']['access'] ) ){
				$client=new Google_Client();
				$client->setClientId( Xpandbuddy_Twig::$arrOptions['google_key'] );
				$client->setClientSecret( Xpandbuddy_Twig::$arrOptions['google_secret'] );
				$client->setRedirectUri( admin_url('admin.php?page=xpandbuddy') );
				$client->setScopes(array('https://www.googleapis.com/auth/drive'));
				$client->refreshToken( Xpandbuddy_Twig::$arrOptions['refresh_token'] );
				$settings=array();
				$_accessToken=json_decode( $client->getAccessToken() );
				$settings['access']['token']=$_accessToken->access_token;
				$settings['access']['expired']=$_accessToken->created+$_accessToken->expires_in;
			}else{
				Xpandbuddy_Twig::$arrOptions['return']['access']=(array)Xpandbuddy_Twig::$arrOptions['return']['access'];
				$settings['access']['token']=Xpandbuddy_Twig::$arrOptions['return']['access']['token'];
				$settings['access']['expired']=Xpandbuddy_Twig::$arrOptions['return']['access']['expired'];
			}
			if( !isset( Xpandbuddy_Twig::$arrOptions['return']['folder_id'] ) ){
				$_folderName=str_replace( array( "http://", "https://", ".", "/" ), array( "","","_","_" ), Xpandbuddy_Twig::$arrOptions['blog']['url'] );
				$req=new Google_HttpRequest( 'https://www.googleapis.com/drive/v2/files?q='.urlencode( "mimeType='application/vnd.google-apps.folder' and title='".$_folderName."' and trashed=false" ), 'GET', array(
						'Authorization' => "Bearer  ".$settings['access']['token']
					)
				);
				$_siteFolder=json_decode( Google_Client::$io->makeRequest($req)->getResponseBody() );
				if( count( $_siteFolder->items ) == 0 ){ // create folder
					$req=new Google_HttpRequest( 'https://www.googleapis.com/drive/v2/files', 'POST', array(
						'Authorization' => "Bearer  ".$settings['access']['token'],
						'Content-Type' => 'application/json; charset=UTF-8'
					), json_encode( array( 
						'title' => $_folderName,
						'mimeType' => 'application/vnd.google-apps.folder'
					)));
					$siteFolder=json_decode( Google_Client::$io->makeRequest($req)->getResponseBody() );
				}else{
					$siteFolder=$_siteFolder->items[0];
				}
				$settings['folder_id']=$siteFolder->id;
			}else{
				$settings['folder_id']=Xpandbuddy_Twig::$arrOptions['return']['folder_id'];
			}
			if( !isset( Xpandbuddy_Twig::$arrOptions['return']['file_name'] ) ){
				$settings['file_name']=substr(ABSPATH,0,-1).self::$ds.'wp-backups'.Xpandbuddy_Twig::_subdirCreation().Xpandbuddy_Twig::$arrOptions['file_name'];
			}else{
				$settings['file_name']=Xpandbuddy_Twig::$arrOptions['return']['file_name'];
			}
			if( !isset( Xpandbuddy_Twig::$arrOptions['return']['file_size'] ) ){
				$settings['file_size']=@filesize( $settings['file_name'] );
			}else{
				$settings['file_size']=Xpandbuddy_Twig::$arrOptions['return']['file_size'];
			}
			$chunkSize=256*1024*16; // dont remove 256
			if( !isset( Xpandbuddy_Twig::$arrOptions['return']['location'] ) ){
				$_postBody=json_encode( array( 
					'title' => Xpandbuddy_Twig::$arrOptions['file_name'],
					'parents' => array( json_decode( '{ "kind": "drive#fileLink","id": "'.$settings['folder_id'].'"}' ) )
				));
				$req=new Google_HttpRequest( 'https://www.googleapis.com/upload/drive/v2/files?uploadType=resumable', 'POST', array(
					'Authorization' => "Bearer  ".$settings['access']['token'],
					'Content-Length' => strlen( $_postBody ),
					'Content-Type' => 'application/json; charset=UTF-8',
					'X-Upload-Content-Type' => 'application/zip',
					'X-Upload-Content-Length' => $settings['file_size']
				), $_postBody );
				$response=Google_Client::$io->makeRequest($req);
				$location=$response->getResponseHeader('location');
				$code=$response->getResponseHttpCode();
				if( $code != 200 || empty( $location ) ){
					if( isset( Xpandbuddy_Twig::$arrOptions['return'] ) ){
						return json_encode( array('error'=>"Response Code or Location Error",'status'=>false) );
					}
				}
				$settings['location']=urlencode( $location );
			}else{
				$settings['location']=Xpandbuddy_Twig::$arrOptions['return']['location'];
			}
			if( !isset( Xpandbuddy_Twig::$arrOptions['return']['response_code'] ) ){
				$settings['response_code']=false;
			}else{
				$settings['response_code']=Xpandbuddy_Twig::$arrOptions['return']['response_code'];
			}
			if( !isset( Xpandbuddy_Twig::$arrOptions['return']['last_range'] ) ){
				$settings['last_range']=false;
			}else{
				$settings['last_range']=Xpandbuddy_Twig::$arrOptions['return']['last_range'];
			}
			if( !isset( Xpandbuddy_Twig::$arrOptions['return']['flg_ext_backoff'] ) ){
				$settings['flg_ext_backoff']=false;
			}else{
				$settings['flg_ext_backoff']=Xpandbuddy_Twig::$arrOptions['return']['flg_ext_backoff'];
			}
			if( !isset( Xpandbuddy_Twig::$arrOptions['return']['backoff_counter'] ) ){
				$settings['backoff_counter']=0;
			}else{
				$settings['backoff_counter']=Xpandbuddy_Twig::$arrOptions['return']['backoff_counter'];
			}
			while( $settings['response_code']===false || $settings['response_code']=='308' ){
				if( $settings['flg_ext_backoff'] ){
					$sleep_for=pow( 2, $settings['backoff_counter'] );
					sleep( $sleep_for );
					usleep( rand(0, 1000) );
					$settings['backoff_counter']++;
					if( $settings['backoff_counter']>5 ){
						return json_encode( array('error'=>"We've waited too long as per Google's instructions", 'return'=>$settings, 'status'=>false) );
					}
				}
				// determining what range is next
				if( !isset( Xpandbuddy_Twig::$arrOptions['return']['range_start'] ) ){
					$settings['range_start']=0;
				}else{
					$settings['range_start']=Xpandbuddy_Twig::$arrOptions['return']['range_start'];
				}
				if( !isset( Xpandbuddy_Twig::$arrOptions['return']['range_end'] ) ){
					$settings['range_end']=min( $chunkSize, $settings['file_size'] - 1 );
				}else{
					$settings['range_end']=Xpandbuddy_Twig::$arrOptions['return']['range_end'];
				}
				if( $settings['last_range']!==false ){
					if( !is_array( $settings['last_range'] ) ){
						$settings['last_range']=explode( '-', $settings['last_range'] );
					}
					$settings['range_start']=(int)$settings['last_range'][1] + 1;
					$settings['range_end']=min( $settings['range_start'] + $chunkSize, $settings['file_size'] - 1 );
				}
				$ch=curl_init();
				curl_setopt( $ch, CURLOPT_URL, urldecode( $settings['location'] ) );
				curl_setopt( $ch, CURLOPT_PORT , 443 );
				curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "PUT" );
				curl_setopt( $ch, CURLOPT_BINARYTRANSFER, 1 );
				// grabbing the data to send
				if( is_file( $settings['file_name'] ) ){
					$sendFile=file_get_contents( $settings['file_name'], false, NULL, $settings['range_start'], ($settings['range_end'] - $settings['range_start'] + 1) );
				}else{
					return json_encode( array('error'=>"No access to file ".$settings['file_name'], 'return'=>$settings, 'status'=>false) );
				}
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $sendFile );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt( $ch, CURLOPT_HEADER, true );
				curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
					"Authorization: Bearer ".$settings['access']['token'],
					"Content-Length: " .(string)($settings['range_end'] - $settings['range_start'] + 1),
					"Content-Type: application/zip",
					"Content-Range: bytes ".$settings['range_start']."-".$settings['range_end']."/".$settings['file_size']
				));
				$response=$this->parseResponse( curl_exec($ch) );
				$post_transaction_info=curl_getinfo( $ch );
				curl_close( $ch );
				$settings['flg_ext_backoff']=false;
				if( isset($response['code']) ){
					// проверка полученного ответа
					if( $response['code']=="401" ){
						// todo: make sure that we also got an invalid credential response
						$client=new Google_Client();
						$client->setClientId( Xpandbuddy_Twig::$arrOptions['google_key'] );
						$client->setClientSecret( Xpandbuddy_Twig::$arrOptions['google_secret'] );
						$client->setRedirectUri( admin_url('admin.php?page=xpandbuddy') );
						$client->setScopes(array('https://www.googleapis.com/auth/drive'));
						$client->refreshToken( Xpandbuddy_Twig::$arrOptions['refresh_token'] );
						$_accessToken=json_decode( $client->getAccessToken() );
						$settings['access']['token']=$_accessToken->access_token;
						$settings['access']['expired']=$_accessToken->created+$_accessToken->expires_in;
						$settings['response_code']=false;
					}elseif( $response['code']=="308" ){
						$settings['response_code']=$response['code'];
						$settings['last_range']=$response['headers']['range'];
						// todo: проверять x-range-md5 header, понято только что это такое x-range-md5...
						return json_encode( array('return'=>$settings,'status'=>true) );
					}elseif( $response['code']=="503" ){
						// Google's letting us know we should retry
						$settings['flg_ext_backoff']=true;
						$settings['response_code']=false;
					}elseif( $response['code']=="200" ){
						// все в прядке
						$settings['response_code']=$response['code'];
//						$lastOutput=$response;
					}elseif( $response['code']=="400" ){
						$settings['response_code']=$response['code'];
						return json_encode( array('error'=>"The server didn't understand the syntax of the request. Error: ".$response['headers']['request'],'return'=>$settings,'status'=>false) );
					}else{
						$client=new Google_Client();
						$client->setClientId( Xpandbuddy_Twig::$arrOptions['google_key'] );
						$client->setClientSecret( Xpandbuddy_Twig::$arrOptions['google_secret'] );
						$client->setRedirectUri( admin_url('admin.php?page=xpandbuddy') );
						$client->setScopes(array('https://www.googleapis.com/auth/drive'));
						$client->refreshToken( Xpandbuddy_Twig::$arrOptions['refresh_token'] );
						$_accessToken=json_decode( $client->getAccessToken() );
						$settings['access']['token']=$_accessToken->access_token;
						$settings['access']['expired']=$_accessToken->created+$_accessToken->expires_in;
						$settings['response_code']=false;
						return json_encode( array('error'=>'bad response code!' ,'return'=>$settings,'status'=>false) );
					}
					if( $post_transaction_info['size_upload'] == 0 ){
						$settings['response_code']=true;
					}
				}else{
					$settings['flg_ext_backoff']=true;
					$settings['response_code']=false;
				}
			}
			@unlink( $settings['file_name'] );
			return 'sended';
		}catch(Exception $e){
			return json_encode( array('error'=>$e->getMessage(),'status'=>false, 'return'=>@$settings ) );
		}
	}
	
	private function parseResponse( $raw_data ){
		$parsed_response=array( 'code'=>-1, 'headers'=>array() );
		$raw_data=array_filter( explode( "\r\n", $raw_data ) );
		foreach( $raw_data as &$_header ){
			$_header=explode( " ", $_header );
			if( $_header[0] == 'HTTP/1.1' ){
				$parsed_response['code']=$_header[1];
			}else{
				$_headerName=strtolower( trim($_header[0], ':') );
				unset( $_header[0] );
				$parsed_response['headers'][ $_headerName ]=implode( ' ', $_header );
			}
		}
		return $parsed_response;
	}
		//google drive
	public static function getGoogleCode(){
		if( !isset( $_POST['app_key'] ) || !isset( $_POST['app_secret'] ) || empty( $_POST['app_key'] ) || empty( $_POST['app_secret'] ) ){
			die();
		}
		require_once Xpandbuddy::$pathName."/library/GoogleAPI/Google_Client.php";
		require_once Xpandbuddy::$pathName."/library/GoogleAPI/contrib/Google_DriveService.php";
		$client = new Google_Client();
		$client->setClientId($_POST['app_key']);
		$client->setClientSecret($_POST['app_secret']);
		$client->setRedirectUri( admin_url('admin.php?page=xpandbuddy') );
		$client->setScopes(array( 'https://www.googleapis.com/auth/drive' ));
		if(!$client->getAccessToken()){
			$authUrl = $client->createAuthUrl();
			print $authUrl;
		}
		die();
	}
	
	public static function getGoogleToken(){
		if( !isset( $_POST['app_key'] ) 
			|| !isset( $_POST['app_secret'] ) 
			|| !isset( $_POST['app_code'] ) 
			|| empty( $_POST['app_key'] ) 
			|| empty( $_POST['app_secret'] ) 
			|| empty( $_POST['app_code'] )
		){
			die();
		}
		require_once Xpandbuddy::$pathName."/library/GoogleAPI/Google_Client.php";
		require_once Xpandbuddy::$pathName."/library/GoogleAPI/contrib/Google_DriveService.php";
		$client = new Google_Client();
		$client->setClientId($_POST['app_key']);
		$client->setClientSecret($_POST['app_secret']);
		$client->setRedirectUri( admin_url('admin.php?page=xpandbuddy') );
		$client->setScopes(array('https://www.googleapis.com/auth/drive'));
		$client->authenticate( $_POST['app_code'] );
		$_return=json_decode( $client->getAccessToken() );
		echo $_return->refresh_token;
		die();
	}
	//*google drive
	//dropbox
	public static function getAuthorizationCode(){
		if( !isset( $_POST['app_key'] ) || !isset( $_POST['app_secret'] ) || empty( $_POST['app_key'] ) || empty( $_POST['app_secret'] ) ){
			die();
		}
		try{
			require_once Xpandbuddy::$pathName."/library/Dropbox/autoload.php";
			$webAuth=Dropbox_WebAuthNoRedirect($_POST['app_key'],$_POST['app_secret']);
			$_webAuthLink=$webAuth->start();
			$file_headers=@get_headers($_webAuthLink);
			if( $file_headers[0] != 'HTTP/1.1 404 Not Found' ){
				echo $_webAuthLink;
			}
		}catch(Exception $e){
			echo $e->getMessage();
		}
		die();
	}
	
	public static function getAccessToken(){
		if( !isset( $_POST['app_key'] ) 
			|| !isset( $_POST['app_secret'] ) 
			|| empty( $_POST['app_key'] ) 
			|| empty( $_POST['app_secret'] ) 
			|| empty( $_POST['authorization_code'] ) 
			|| empty( $_POST['authorization_code'] ) 
		){
			die();
		}
		try{
			require_once Xpandbuddy::$pathName."/library/Dropbox/autoload.php";
			$webAuth=Dropbox_WebAuthNoRedirect($_POST['app_key'],$_POST['app_secret']);
			list( $accessToken, $dropboxUserId )=$webAuth->finish( $_POST['authorization_code'] );
			if( !isset( $accessToken ) || empty( $accessToken ) ){
				die();
			}
			echo $accessToken;
		}catch(Exception $e){
			echo $e->getMessage();
		}
		die();
	}
	//end dropbox
	// ftp
	public static function getFtpConnect(){
		if( !isset( $_POST['host'] ) || empty( $_POST['user'] ) || empty( $_POST['pass'] ) ){
			die();
		}
		require_once Xpandbuddy::$pathName."/library/Ftp.php";
		$_ftp=new Xpandbuddy_Ftp();
		$_arrDirs='/';
		if( !empty( $_POST['dir'] ) ){
			$_arrDirs=$_POST['dir'];
			$_arrDirs=explode('/',$_arrDirs);
			if( end( $_arrDirs ) == '..' ){
				array_pop( $_arrDirs );
				array_pop( $_arrDirs );
			}
			if( $_arrDirs===array('') || end( $_arrDirs ) == '.' ){
				$_arrDirs=array('','');
			}
			$_arrDirs=implode('/',$_arrDirs);
		}
		if( !$_ftp
			->setChmod( '0644' )
			->setHost( urldecode( $_POST['host'] ) )
			->setUser( urldecode( $_POST['user'] ) )
			->setPassw( urldecode( $_POST['pass'] ) )
			->setRoot( $_arrDirs )
			->makeConnectToRootDir() ){
			return false;
		}
		$_ftp->ls( $arrRes );
		if( $_arrDirs != '/' ){
			$_arrDirs=explode('/',$_arrDirs);
			foreach( $_arrDirs as $_k=>$_dir ){
				if( $_dir == '' && $_k==0 ){
					$_dir='root';
					$_strDirs='/';
				}elseif( $_dir != '' ){
					$_strDirs='';
					foreach( $_arrDirs as $_l=>$_n ){
						if( $_l <= $_k && $_n!='' ){
							$_strDirs.='/'.$_n;
						}
					}
				}
				if( $_k < count( $_arrDirs )-1 )
					echo '/<a href="" class="root_dir" rel="'.$_strDirs.'">'.$_dir.'</a>';
				else
					echo '/'.$_dir;
			}
			$_arrDirs=implode('/',$_arrDirs);
		}else{
			echo 'root/';
		}
		echo '</br>';
		foreach( $arrRes as $_file ){
			if( $_file['is_dir'] ){
				if( $_arrDirs=='/' && ( $_file['name']=='..' || $_file['name']=='.' ) ){
					continue;
				}
				$_str='<a href="" class="ftp_open_dir">'.$_file['name'].'</a>';
			}else{
				if( substr( $_file['name'], strrpos($_file['name'], ".") ) == '.gz' || substr( $_file['name'], strrpos($_file['name'], ".") ) == 'zip' ){
					$_str='<a href="" class="ftp_use_file">'.$_file['name'].'</a>';
				}else{
					$_str='<a href="" class="ftp_other_file">'.$_file['name'].'</a>';
				}
			}
			echo $_str.'</br>';
		}
		echo '<input type="hidden" id="ftp_dir_name_load" value="'.$_arrDirs.'" >';
		die();
	}

	public static function ajaxGetDirsTree(){
		$_dirname=substr(ABSPATH,0,-1);
		if( !empty( $_POST['dir'] ) ){
			$_dirname=stripslashes( $_POST['dir'] );
		}
		$_flgType=1;
		if( !empty( $_POST['flg_type'] ) ){
			$_flgType=stripslashes( $_POST['flg_type'] );
		}
		if( $_handle=opendir( $_dirname ) ){
			echo '<div class="dir_list">';
			$_wpContentDir=substr( WP_CONTENT_DIR, strlen( ABSPATH ) );
			$_dirStart=substr(ABSPATH,0,-1);
			while( false!==( $_file=readdir( $_handle ) ) ){
				if( $_file!='.'&&$_file!='..' ){
					if( is_dir( $_dirname.self::$ds.$_file ) ){
						$_addId=$_hidden="";
						if( $_dirname.self::$ds.$_file == $_dirStart.self::$ds.'wp-backups' ){
							$_hidden=' style="display:none;"';
							$_addId="this_db";
						} 
						if( $_dirname.self::$ds.$_file == $_dirStart.self::$ds.$_wpContentDir.self::$ds.'plugins' ){
							$_addId="this_plugins";
						}
						if( $_dirname.self::$ds.$_file == $_dirStart.self::$ds.$_wpContentDir.self::$ds.'themes' ){
							$_addId="this_thems";
						}
						if( $_dirname.self::$ds.$_file == $_dirStart.self::$ds.$_wpContentDir.self::$ds.'uploads' ){
							$_addId="this_uploads";
						}
						if( $_dirname.self::$ds.$_file == $_dirStart.self::$ds.$_wpContentDir ){
							$_addId="this_content";
						}
						if( !empty( $_addId ) ){
							$_addId=' id="'.$_addId.'"';
						}
						$_md5chash=md5($_dirname.self::$ds.$_file);
						echo '<div class="dir_box"'.$_hidden.'>
							<input type="hidden" name="arrProject[settings][type_'.$_flgType.'][arr_execute_dirs]['.$_md5chash.'][name]" value="'.$_dirname.self::$ds.$_file.'">
							<input type="hidden" name="arrProject[settings][type_'.$_flgType.'][arr_execute_dirs]['.$_md5chash.'][value]" class="archive_dir_check" value="0">
							<img class="archive_dir"'.$_addId.' src="'.Xpandbuddy::$baseName.'skin/archive.png" rel="active" onclick="useExecuteFolder_'.$_flgType.'(this);">
							<span class="dir_name" rel="'.$_dirname.self::$ds.$_file.'" onclick="useFolderOpen_'.$_flgType.'(this);">
								<img alt="close" src="'.Xpandbuddy::$baseName.'skin/folder.png" >&nbsp;'.$_file.'
							</span>
						</div>';
					}
				}
			}
			echo '</div>';
		}
		exit;
	}

}}
?>