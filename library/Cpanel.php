<?php
/**
 * Plugin class
 */
if( !class_exists('Xpandbuddy_Cpanel') ){
class Xpandbuddy_Cpanel{

	private $_uri=array();
	private $_skin='x';
	private $_port='2082';
	private $_domain='';
	private $_result=array();
	private $_access=array( 'user'=>'', 'passwd'=>'', 'host'=>'', 'theme'=>'x' );

	public $error=false;
	
	public function __construct( $_arrParams=array() ) {
		if ( !empty( $_arrParams ) ) {
			$this->setAccess( $_arrParams );
		}
	}

	// http://<user>:<passwd>@<host>:<port>
	public function setAccess( $_arrParams=array() ) {
		$_arrParams=$_arrParams+$this->_access;
		$this->_uri['username']=urlencode( $_arrParams['user'] );
		$this->_uri['password']=urlencode( $_arrParams['passwd'] );
		$this->_uri['host']=urlencode( $_arrParams['host'] );
		$this->_uri['port']=$this->_port;
		$this->setSkinName( $_arrParams['theme'] );
		return true;
	}

	public function setSkinName( $_strName='x' ) {
		$this->_skin=$_strName;
		return $this;
	}

	public function setDomain( $_str='' ) {
		if ( empty( $_str ) ) {
			return false;
		}
		$this->_domain=$_str;
		return true;
	}

	public function getResult() {
		return $this->_result;
	}

	private function getHost() {
		return 'HOST form uri getHost';
	}

	public function createDb( $_arrParams=array() ) {
		if ( empty( $_arrParams['name'] ) ) {
			return false;
		}
		// create db
		$this->_uri['path']='/frontend/'.$this->_skin.'/sql/add'.($this->_skin=='x3'? '':'d').'b.html';
		$this->_uri['query']=array( 
			'db'=>$_arrParams['name'] 
		);
		if ( !$this->getResponce( $_strTmp ) ) {
			return false;
		}
		$this->_result['db']=$this->_uri['username'].'_'.$_arrParams['name'];
		if ( !empty( $_arrParams['user'] )&&!empty( $_arrParams['passwd'] ) ) {
			// add user
			$this->_uri['path']='/frontend/'.$this->_skin.'/sql/adduser.html';
			$this->_uri['query']=array( 
				'user'=>$_arrParams['user'], 
				'pass'=>$_arrParams['passwd'] 
			);
			if ( !$this->getResponce( $_strTmp ) ) {
				return false;
			}
			$this->_result['user']=$this->_uri['username'].'_'.$_arrParams['user'];
			$this->_result['pass']=$_arrParams['passwd'];
			// add user to db
			$this->_uri['path']='/frontend/'.$this->_skin.'/sql/addusertodb.html';
			if ( $this->_skin=='x3' ) {
				$this->_uri['query']=array( 
					'user'=>$this->_uri['username'].'_'.$_arrParams['user'], 
					'db'=>$this->_uri['username'].'_'.$_arrParams['name'],
					'update'=>'',
					'ALL'=>'ALL',
				);
			} else {
				$this->_uri['query']=array( 
					'user'=>$this->_uri['username'].'_'.$_arrParams['user'], 
					'db'=>$this->_uri['username'].'_'.$_arrParams['name'],
					'ALL'=>'ALL',
				);
			}
			if ( !$this->getResponce( $_strTmp ) ) {
				return false;
			}
			$this->_result['bind']=true;
		}
		return true;
	}
	
    public function getUri($_ssl=false){
        $password=strlen($this->_uri['password']) > 0 ? ":".$this->_uri['password'] : '';
        $auth=strlen($this->_uri['username']) > 0 ? $this->_uri['username'].$password."@" : '';
        $port=strlen($this->_uri['port']) > 0 ? ":".$this->_uri['port'] : '';
		$query=http_build_query($this->_uri['query'], '', '&');
        $query=strlen($query) > 0 ? "?".$query : '';
        return 'http'.(($_ssl)?'s':'').'://'.$auth.$this->_uri['host'].(($_ssl)?':2083':$port).$this->_uri['path'].$query;
    }

	private function getResponceCurl( &$strRes ) {
		$strRes=$this->getUrl( $this->getUri() );
		return $strRes!==false;
	}
	
	private function getResponce( &$strRes ) {
		$_userTempDir=Xpandbuddy::$pathName.DIRECTORY_SEPARATOR.'temp';
		if ( !Xpandbuddy::prepareTmpDir( $_userTempDir ) ) {
			return false;
		}
		$file = '<?php 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,  "'. $this->getUri() .'" );
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1) Gecko/20061010 Firefox/2.0" );
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
			"Cache-Control: max-age=0",
			"Connection: keep-alive",
			"Keep-Alive: 300",
			"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7",
			"Accept-Language: en-us,en;q=0.5",
		));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$responce = curl_exec($ch);
//		if( stripos($responce,"https://")&&stripos($responce,"refresh") ){
//			curl_setopt($ch, CURLOPT_URL,  "'.$this->getUri(true).'" );
//			$responce = curl_exec($ch);
//		}
		curl_close ($ch);';
		if ($this->_skin == 'x3') {
		$file .='if( stripos( $responce , \'class="errors"\') ) {
			preg_match(\'/id="details">[\n ]+([a-zA-Z .!]+)\n/\', $responce, $match );
			if( isset( $match[1] ) ){
				echo $match[1];
			}else{
				echo "error";
			}
		} else {
			echo "added";
		}?>';
		} else {
		$file .='if( stripos( $responce , "error") ) {
			echo "error";
		} else {
			echo "added";
		}?>';
		}
		$_fileName=$_userTempDir.'cpanel_creat.php';
		if( !is_file( $_fileName ) ){
			$handle=fopen( $_fileName, 'w' );
			fwrite($handle, $file);
			fclose( $handle );
		}
		$_ftp=new Xpandbuddy_Ftp();
		if ( !$_ftp
			->setChmod( '0644' )
			->setHost( urldecode( $this->_uri['host'] ) )
			->setUser( urldecode( $this->_uri['username'] ) )
			->setPassw( urldecode( $this->_uri['password'] ) )
			->setRoot( 'public_html' )
			->makeConnectToRootDir() ) {
			return false;
		}
		if ( $_ftp->fileUpload( 'cpanel_creat.php',$_fileName ) !== true) {
			return false;
		}
		@unlink($_fileName);
		$strRes=$this->getUrl( 'http://'.$this->_uri['host'].'/cpanel_creat.php' );
		if ( $strRes===false ) {
			return false;
		}
		if ( $strRes === "added" ) {
			return true;
		}
		if( !empty( $strRes ) && $strRes != "error" ){
			$this->error=$strRes;
		}
		return false;
	}
	
	private function getUrl($url){
		if (ini_get('allow_url_fopen') && function_exists('file_get_contents')){
			return @file_get_contents($url);
		}
		if (ini_get('allow_url_fopen') && !function_exists('file_get_contents')){
			if (false === $fh=fopen($url, 'rb', false)){
				user_error('file_get_contents() failed to open stream: No such file or directory', E_USER_WARNING);
				return false;
			}
			clearstatcache();
			if ($fsize=@filesize($url)){
				$data=fread($fh, $fsize);
			} else {
				$data='';
				while (!feof($fh)){
					$data .= fread($fh, 8192);
				}
			}
			fclose($fh);
			return $data;
		}
		if (function_exists('curl_init')){
			$c=curl_init($url);
			curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($c, CURLOPT_TIMEOUT, 15);
			$data=@curl_exec($c);
			curl_close($c);

			return $data;
		}
		return false;
	}
}
}
?>