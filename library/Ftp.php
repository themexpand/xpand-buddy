<?php
/**
 * Plugin class
 */
if( !class_exists('Xpandbuddy_Ftp') ){
class Xpandbuddy_Ftp{

	public $ftp;
	protected $_errors=array();
	private $_log=array();

	/**
	 * Determine whether to use passive-mode (true) or active-mode (false)
	 *
	 * @access  private
	 * @var     bool
	 */
	private $_passv=false;

	/**
	 * эти переменные и константы описывают режим трансфера
	 */
	const TMODE_ASCII=FTP_ASCII;
	const TMODE_BINARY=FTP_BINARY;
	private $_mode=self::TMODE_BINARY;

	/**
	 * эти переменные и константы для создания объектов на фтп
	 */
	const CHMOD_DIR='dir';
	const CHMOD_FILE='file';
	public $permissionFile='0777';
	public $permissionDir='0777';

	/**
	 * эти переменные и константы для получения списка файлов и папок фтп
	 */
	const LS_DIRS_FILES=1;
	const LS_DIRS_ONLY=2;
	const LS_FILES_ONLY=3;
	const LS_RAWLIST=4;
	private $_dirForLs='';
	private $_dirForLsPrev='';
	private $_lsMatch=array(
		'unix' => array(
			'pattern' => '/(.)([rwxts-]{9})\s+(\w+)\s+([\w\d\-()?.@]+)\s+([\w\d\-()?.]+)\s+(\w+)\s+(\S+\s+\S+\s+\S+)\s+(.+)/',
			'map' => array(
				'type' => 1,
				'rights' => 2,
				'files_inside' => 3,
				'user' => 4,
				'group' => 5,
				'size' => 6,
				'date' => 7,
				'name' => 8,
			)
		),
		'windows' => array(
			'pattern' => '/([0-9\-]+)\s+([0-9:APM]+)\s+((<DIR>)|\d+)\s+(.+)/',
			'map' => array(
				'date' => 1,
				'time' => 2,
				'size' => 3,
				'is_dir' => 4,
				'name' => 5,
			)
		)
	);

	/**
	 * Returns the transfermode
	 *
	 * @return int The transfermode, either self::TMODE_ASCII or self::TMODE_BINARY.
	 */
	public function getMode() {
		return $this->_mode;
	}

	/**
	 * Set the transfermode
	 *
	 * @param int $mode The mode to set
	 * @return bool
	 */
	public function setMode( $mode=null ) {
		if ( !in_array( $mode, array( self::TMODE_ASCII, self::TMODE_BINARY ) ) ) {
			return $this->setError( 'transfermode has either to be Xpandbuddy_Ftp::TMODE_ASCII or Xpandbuddy_Ftp::TMODE_BINARY' );
		}
		$this->_mode=$mode;
		return true;
	}

	/**
	 * Set the transfer-method to passive mode
	 *
	 * @access public
	 * @return void
	 */
	public function setPassive() {
		$this->_passv=true;
		@ftp_pasv( $this->ftp, true );
	}

	/**
	 * Set the transfer-method to active mode
	 *
	 * @access public
	 * @return void
	 */
	public function setActive() {
		$this->_passv=false;
		@ftp_pasv( $this->ftp, false );
	}

	/**
	 * Returns, whether the connection is set to passive mode or not
	 *
	 * @access public
	 * @return bool True if passive-, false if active-mode
	 */
	public function isPassive() {
		return $this->_passv;
	}

	/**
	 * устанавливаем права на файлы и директории для данного фтп
	 *
	 * @return object
	 */
	public function setChmod( $_str='0777' ) {
		$this->permissionFile=$_str;
		$this->permissionDir=$this->getDirPermission( $_str );
		return $this;
	}

	/**
	 * тип фтп public/private
	 *
	 * @var boolean
	 */
	private $_isPub=false;

	/**
	 * подготовка к соединению с public ftp
	 *
	 * @return object
	 */
	public function setPublicFtp() {
		$this->_isPub=true;
		return $this;
	}

	protected $_host, $_user, $_passw, $_root;

	/**
	 * ftp host
	 *
	 * @param string $_str
	 * @return object
	 */
	public function setHost( $_str='' ) {
		$this->_host=$_str;
		return $this;
	}

	/**
	 * ftp user нужен только если _isPub==false
	 *
	 * @param string $_str
	 * @return object
	 */
	public function setUser( $_str='' ) {
		$this->_user=$_str;
		return $this;
	}

	/**
	 * ftp password нужен только если _isPub==false
	 *
	 * @param string $_str
	 * @return object
	 */
	public function setPassw( $_str='' ) {
		$this->_passw=$_str;
		return $this;
	}

	/**
	 * корневая папка фтп аккаунта (устанавливается при подключении)
	 *
	 * @param string $_str
	 * @return object
	 */
	public function setRoot( $_str='' ) {
		$this->_root=$_str;
		return $this;
	}

	/**
	 * подключение и смена директории на корневую
	 * $this->setPublicFtp()->setHost( 'host' )->setRoot( 'dir/to/root' )->makeConnectToRootDir();
	 *
	 * @return boolean
	 */
	public function makeConnectToRootDir() {
		if ( empty( $this->_root ) ) {
			return $this->setError( 'empty rootdir variable' );
		}
		if ( !$this->makeConnect() ) {
			return false;
		}
		if ( !$this->cd( $this->_root ) ) {
			$this->closeConnection();
			return false;
		}
		return true;
	}

	/**
	 * подключение
	 * $this->setHost( 'host' )->setUser( 'user' )->setPassw( 'passw' )->makeConnect();
	 *
	 * @return boolean
	 */
	public function makeConnect() {
		if ( is_resource( $this->ftp ) ) {
			return true;
		}
		if ( empty( $this->_host ) ) {
			return $this->setError( 'empty hostname variable' );
		}
		$this->ftp=@ftp_connect( $this->_host );
		if ( $this->ftp===false ) {
			return $this->setError( 'wrong ftp_connect( '.$this->_host.' )' );
		}
		return $this->makeLogin();
	}

	/**
	 * авторизация пользователя при необходимости
	 *
	 * @return boolean
	 */
	private function makeLogin() {
		if ( $this->_isPub ) {
			return true;
		}
		if ( empty( $this->_user )||empty( $this->_passw ) ) {
			return $this->setError( 'empty authorization variables' );
		}
		if ( !@ftp_login( $this->ftp, $this->_user, $this->_passw ) ) {
			$this->closeConnection();
			return $this->setError( 'wrong ftp_login('.$this->_user.', '.$this->_passw.')' );
		}
		$this->setPassive(); // это только для проверки - вообще надо указывать при создании объекта TODO!!!
		return true;
	}

	/**
	 * закрываем коннект к фтп
	 *
	 * @return bool
	 */
	public function closeConnection() {
		if ( !is_resource( $this->ftp ) ) {
			return true;
		}
		return ftp_close( $this->ftp );
	}

	private $_pathTo, $_pathFrom;

	/**
	 * папка из которой копируем на сервер
	 *
	 * @param string $_str
	 * @return object
	 */
	public function setPathFrom( $_str='' ) {
		$this->_pathFrom=$_str;
		return $this;
	}

	/**
	 * папка на фтп в которую копируем
	 *
	 * @param string $_str
	 * @return object
	 */
	public function setPathTo( $_str='' ) {
		$this->_pathTo=$_str;
		return $this;
	}

	/**
	 * копируем папку со всем содержимым
	 * $this->setPathFrom( 'local/path' )->setPathTo( 'path/on/ftp' )->dirUpload();
	 * если _pathTo не установили то используем _root, если она тоже не установлена то процесс прерывается
	 *
	 * @param array $_arrSource - если массив пуст то система берёт файлы и папки из _pathFrom
	 * @return boolean
	 */
	public function dirUpload( $_arrSource=array() ) {
		$_strDirTo=empty( $this->_pathTo )? $this->_root:$this->_pathTo;
		if ( empty( $_strDirTo ) ) {
			return false;
		}
		if ( empty( $_arrSource ) ) {
			if ( !Core_Files::dirScan( $_arrSource, $this->_pathFrom ) ) {
				return false;
			}
		}
		if ( empty( $this->_pathFrom ) ) { // если есть $_arrSource, $this->_pathFrom указывать необязательно TODO!!!15.04.2010
			return false;
		}
		$_int=strlen( $this->_pathFrom );
		foreach( $_arrSource as $_strDir=>$_arrFiles ) {
			$_strDest=str_replace( array( '\\', '//' ), '/', $_strDirTo.substr( $_strDir, $_int ) );
			if ( !$this->changeOrMakeDir( $_strDest ) ) {
				return false;
			}
			if ( empty( $_arrFiles ) ) {
				continue;
			}
			// теперь заливаем файлы в диру
			foreach( $_arrFiles as $v ) {
				if ( !$this->fileUpload( $_strDest.'/'.$v, $_strDir.'/'.$v ) ) {
					return false;
				}
			}
		}
		$this->_pathFrom=$this->_pathTo='';
		return true;
	}

	/**
	 * заливаем файл и меняем права
	 *
	 * @param string $_strRemote - куда
	 * @param string $_strLocal - что
	 * @return bool
	 */
	public function fileUpload( $_strRemote='', $_strLocal='' ) {
		if ( empty( $_strRemote )||empty( $_strLocal ) ) {
			return $this->setError( 'empty $_strRemote or $_strLocal' );
		}
		if ( !file_exists( $_strLocal )||!is_file( $_strLocal ) ) {
			return $this->setError( $_strLocal.' not a file or not exists' );
		}
		if ( !@ftp_put( $this->ftp, $_strRemote, $_strLocal, $this->_mode ) ) {
			return $this->setError( 'ftp_put(): '.$_strLocal.' >> '.$_strRemote );
		}
		return $this->chmod( $_strRemote );
	}

	public function dirDownload() {}

	/**
	 * This function will download a file from the ftp-server.
	 * You can specify the path to which the file will be downloaded on the local
	 * machine, if the file should be overwritten if it exists (optionally, default
	 * is no overwriting) and in which mode (FTP_ASCII or FTP_BINARY) the file
	 * should be downloaded (if you do not specify this, the method tries to
	 * determine it automatically from the mode-directory or uses the default-mode,
	 * set by you).
	 *
	 * @param string $remoteFile The absolute or relative path to the file to download
	 * @param string $localFile  The local file to put the downloaded in
	 * @param bool   $overwrite   (optional) Whether to overwrite existing file
	 * @param int    $mode        (optional) Either FTP_ASCII or FTP_BINARY
	 * @return bool
	 */
	public function fileDownload( $remoteFile, $localFile, $overwrite=false ) {
		if ( @file_exists( $localFile )&&!$overwrite ) {
			return $this->setError( 'local file '.$localFile.' exists and may not be overwriten.' );
		}
		if ( @file_exists( $localFile )&&!@is_writeable( $localFile )&&$overwrite ) {
			return $this->setError( 'local file '.$localFile.' is not writeable. Can not overwrite.' );
		}
		$res=@ftp_get( $this->ftp, $localFile, $remoteFile, $this->_mode );
		if (!$res) {
			return $this->setError( 'remote file '.$remoteFile.' could not be downloaded to $localFile.' );
		}
		return true;
	}
	/**
	 * меять права для файлов
	 *
	 * @var bool
	 */
	private $_flgFileChmod=true;
	
	/**
	 * устанавливает флаг: оставляет дефолтные права у файлов при upload
	 *
	 * @return object
	 */
	public function setFileChmod(){
		$this->_flgFileChmod=false;
		return $this;
	}
	
	private function getFileChmod(){
		return $this->_flgFileChmod;
	}

	/**
	 * меняет права на фтп сервере
	 * может менять пачками, но тип должен быть один (или папки или файлы)
	 *
	 * @param mixed $_mix string or array of strings
	 * @param const $_flg permission type self::CHMOD_FILE or self::CHMOD_DIR
	 * @return bool
	 */
	public function chmod( $_mix=array(), $_flg=self::CHMOD_FILE ) {
		if ( empty( $_mix ) ) {
			return false;
		}
		if ( !$this->getFileChmod() && $_flg==self::CHMOD_FILE ){
			return true;
		}
		if ( is_array( $_mix ) ) {
			foreach( $_mix as $v ) {
				if ( !$this->chmod( $v, $_flg ) ) {
					return false;
				}
			}
		} else {
			switch ( $_flg ) {
				case self::CHMOD_FILE: $_str=$this->permissionFile; break;
				case self::CHMOD_DIR: $_str=$this->permissionDir; break;
			}
			if ( !@ftp_chmod( $this->ftp, intval( $_str, 8 ), $_mix ) ) {
				return $this->setError( 'wrong ftp_chmod '.$_mix.' to '.$_str );
			}
		}
		return true;
	}

	public function chmodRecursive() {}

	/**
	 * создаём директорию и меняем права
	 *
	 * @param string $_strDir
	 * @return bool
	 */
	public function makeDir( $_strDir='' ) {
		if ( empty( $_strDir ) ) {
			return $this->setError( '$_strDir is empty' );
		}
		if ( !@ftp_mkdir( $this->ftp, $_strDir ) ) {
			return $this->setError( 'wrong ftp_mkdir '.$_strDir );
		}
		return $this->chmod( $_strDir, self::CHMOD_DIR );
	}

	/**
	 * если директория существует переходим в неё иначе создаём и переходим
	 *
	 * @param string $_strDest
	 * @return bool
	 */
	private function changeOrMakeDir( $_strDest='' ) {
		if ( empty( $_strDest ) ) {
			return $this->setError( 'empty $_strDest' );
		}
		if ( !$this->cd( $_strDest ) ) { // возможно диры нет
			if ( !$this->makeDir( $_strDest ) ) {
				return false;
			}
			if ( !$this->cd( $_strDest ) ) { // что-то явно не так на удалённом сервере
				return false;
			}
		}
		return true;
	}

	/**
	 * Show's you the actual path on the server
	 * This function questions the ftp-handle for the actual selected path and
	 * returns it.
	 *
	 * @return bool
	 */
	public function pwd( &$strRes ) {
		$strRes=@ftp_pwd( $this->ftp );
		if ( $strRes===false ) {
			return $this->setError( 'Could not determine the actual path.' );
		}
		return true;
	}

	public function rename( $_strOldName='', $_strNewName='' ) {
		if ( empty( $_strOldName )||empty( $_strNewName ) ) {
			return false;
		}
		return @ftp_rename( $this->ftp, $_strOldName, $_strNewName );
	}

	public function rmFile( $_strRemote='' ) {
		if ( empty( $_strRemote ) ) {
			return false;
		}
		return @ftp_delete( $this->ftp, $_strRemote );
	}

	public function rmDir() {}

	/**
	 * This changes the currently used directory. You can use either an absolute
	 * directory-path (e.g. "/home/blah") or a relative one (e.g. "../test").
	 *
	 * @param string $dir The directory to go to.
	 * @return mixed True on success, otherwise PEAR::Error
	 */
	public function cd( $_strDir='' ) {
		if ( !@ftp_chdir( $this->ftp, $_strDir ) ) {
			return $this->setError( 'Directory change (ftp_chdir( $this->ftp, '.$_strDir.' )) failed' );
		}
		return true;
	}

	public function dirForLs( $_strDir='' ) {
		$this->_dirForLs=$_strDir;
		return $this;
	}

	public function getForLsDir( &$strRes ) {
		$strRes=$this->_dirForLsPrev.(substr( $this->_dirForLsPrev, -1 )=='/'? '':'/');
	}

	/**
	 * This method returns a directory-list of the current directory or given one.
	 * To display the current selected directory, not use dirForLs setter
	 *
	 * There are 4 different modes of listing directories. Either to list only
	 * the files (using Xpandbuddy_Ftp::LS_FILES_ONLY), to list only directories (using
	 * Xpandbuddy_Ftp::LS_DIRS_ONLY) or to show both (using Xpandbuddy_Ftp::LS_DIRS_FILES, which is
	 * default).
	 *
	 * The 4th one is the Xpandbuddy_Ftp::LS_RAWLIST, which returns just the array created by
	 * the ftp_rawlist()-function build into PHP.
	 *
	 * The other function-modes will return an array containing the requested data.
	 * The files and dirs are listed in human-sorted order, but if you select
	 * Xpandbuddy_Ftp::LS_DIRS_FILES the directories will be added above the files,
	 * but although both sorted. - check this TODO!!! 20.08.2009
	 *
	 * All elements in the arrays are associative arrays themselves. They have the
	 * following structure:
	 *
	 * Dirs:
	 *           ["name"]        =>  string The name of the directory<br>
	 *           ["rights"]      =>  string The rights of the directory (in style
	 *                               "rwxr-xr-x")<br>
	 *           ["user"]        =>  string The owner of the directory<br>
	 *           ["group"]       =>  string The group-owner of the directory<br>
	 *           ["files_inside"]=>  string The number of files/dirs inside the
	 *                               directory excluding "." and ".."<br>
	 *           ["date"]        =>  int The creation-date as Unix timestamp<br>
	 *           ["is_dir"]      =>  bool true, cause this is a dir<br>
	 *
	 * Files:
	 *           ["name"]        =>  string The name of the file<br>
	 *           ["size"]        =>  int Size in bytes<br>
	 *           ["rights"]      =>  string The rights of the file (in style 
	 *                               "rwxr-xr-x")<br>
	 *           ["user"]        =>  string The owner of the file<br>
	 *           ["group"]       =>  string The group-owner of the file<br>
	 *           ["date"]        =>  int The creation-date as Unix timestamp<br>
	 *           ["is_dir"]      =>  bool false, cause this is a file<br>
	 *
	 * @param array $arrRes return by link the directory list as described above
	 * @param const $_mode (optional) the mode which types to list (files, directories, both or rawlist).
	 * @return bool
	 */
	public function ls( &$arrRes, $_mode=self::LS_DIRS_FILES ) {
		if ( empty( $this->_dirForLs ) ) {
			if ( !$this->pwd( $_strDir ) ) {
				return $this->setError( 'Could not retrieve current directory' );
			}
			$this->_dirForLs=$_strDir;
		}
		switch( $_mode ) {
			case self::LS_DIRS_ONLY: $arrRes=$this->lsDirs(); break;
			case self::LS_FILES_ONLY: $arrRes=$this->lsFiles(); break;
			case self::LS_RAWLIST: $this->getRawList( $arrRes ); break;
			case self::LS_DIRS_FILES:
			default: $arrRes=$this->lsBoth(); break;
		}
		$this->_dirForLsPrev=$this->_dirForLs; // example to show on html page
		$this->dirForLs(); // to init
		return !empty( $arrRes );
	}

	/**
	 * Lists up files and directories
	 *
	 * @return array An array of dirs and files
	 */
	private function lsBoth() {
		if ( !$this->listAndParse( $list_splitted ) ) {
			return false;
		}
		if (!is_array($list_splitted["files"])) {
			$list_splitted["files"]=array();
		}
		if (!is_array($list_splitted["dirs"])) {
			$list_splitted["dirs"]=array();
		}
		$res=array();
		@array_splice($res, 0, 0, $list_splitted["files"]);
		@array_splice($res, 0, 0, $list_splitted["dirs"]);
		return $res;
	}

	/**
	 * Lists up directories
	 *
	 * @return array An array of dirs
	 */
	private function lsDirs() {
		if ( !$this->listAndParse( $_arrRes ) ) {
			return false;
		}
		if ( empty( $_arrRes['dirs'] ) ) {
			// в случае если сервер ответил но в ответе не оказалось директорий (и даже ../.)
			// эмулируем текущую директорию
			// помоему это зависит от прав пользователя на сервере
			$_arrRes['dirs']=array( array( 
				'type'=>'d',
				'name'=>'.',
				'is_dir'=>true,
			) );
		}
		return $_arrRes["dirs"];
	}

	/**
	 * Lists up files
	 *
	 * @return array An array of files
	 */
	private function lsFiles() {
		if ( !$this->listAndParse( $list ) ) {
			return false;
		}
		return $list["files"];
	}

	/**
	 * Данные отдаются без обработки
	 * в случае несрабатывания активного режима пробуем пассивный
	 *
	 * @return array
	 */
	private function getRawList( &$arrList ) {
		$arrList=@ftp_rawlist( $this->ftp, $this->_dirForLs );
		if ( is_array( $arrList ) ) {
			return true;
		}
		if ( !$this->isPassive() ) {
			$this->setPassive();
			return $this->getRawList( $arrList );
		}
		return $this->setError( 'Could not get raw directory listing' );
	}

	/**
	 * This lists up the directory-content and parses the items into well-formated arrays.
	 * The results of this array are sorted (dirs on top, sorted by name; files below, sorted by name).
	 *
	 * @return array Lists of dirs and files
	 */
	private function listAndParse( &$arrRes ) {
		$dirs_list=array();
		$files_list=array();
		if ( !$this->getRawList( $dir_list ) ) {
			return false;
		}
		foreach( $dir_list as $k=>$v ) {
			if (strncmp($v, 'total: ', 7)==0&&preg_match('/total: \d+/', $v)) {
				unset($dir_list[$k]);
				break; // usually there is just one line like this
			}
		}
		// Handle empty directories
		if (count($dir_list)==0) {
			return array('dirs' => $dirs_list, 'files' => $files_list);
		}
		// Exception for some FTP servers seem to return this wiered result instead
		// of an empty list
		if (count($dirs_list)==1&&$dirs_list[0]=='total 0') {
			return array('dirs' => array(), 'files' => $files_list);
		}
		if (!isset($this->_matcher)) {
			$this->_matcher=$this->determineOSMatch( $dir_list );
			if ( !$this->_matcher ) {
				return false; // Операционка не определена
			}
		}
		foreach ($dir_list as $entry) {
			if (!preg_match($this->_matcher['pattern'], $entry, $m)) {
				continue;
			}
			$entry=array();
			foreach ( $this->_matcher['map'] as $key=>$val ) {
				$entry[$key]=$m[$val];
			}
			$entry['stamp']=$this->parseDate( $entry['date'] );
			// is_dir нужен для совместимости с виндовыми хостами, т.к. type в их случае будет неизвестен
			if ( !empty( $entry['is_dir'] )||$entry['type']=='d' ) {
				$entry['is_dir']=true;
				$entry['type']='d'; // тоже для совместимости
				$dirs_list[]=$entry;
			} else {
				$entry['is_dir']=false;
				if ( empty( $entry['type'] ) ) { // тоже для совместимости
					$entry['type']='-';
				}
				$files_list[]=$entry;
			}
		}
		usort( $dirs_list, array( 'Xpandbuddy_Ftp', 'sort' ) );
		usort( $files_list, array( 'Xpandbuddy_Ftp', 'sort' ) );
		$arrRes["dirs"] =(is_array($dirs_list)) ? $dirs_list : array();
		$arrRes["files"]=(is_array($files_list)) ? $files_list : array();
		return true;
	}

	/**
	 * Function for use with usort().
	 * Compares the list-array-elements by name.
	 *
	 * @param string $item_1 first item to be compared
	 * @param string $item_2 second item to be compared
	 * @return int < 0 if $item_1 is less than $item_2, 0 if equal and > 0 otherwise
	 */
	private static function sort($item_1, $item_2) {
		return strnatcmp($item_1['name'], $item_2['name']);
	}

	/**
	 * Determine server OS
	 * This determines the server OS and returns a valid regex to parse ls() output.
	 *
	 * @param array &$dir_list The raw dir list to parse
	 * @return mixed An array of 'pattern' and 'map' on success, otherwise
	 */
	private function determineOSMatch( &$dir_list ) {
		foreach( $dir_list as $entry ) {
			foreach( $this->_lsMatch as $os=>$match ) {
				if ( preg_match( $match['pattern'], $entry ) ) {
					return $match;
				}
			}
		}
		return $this->setError( 'The list style of your server seems not to be supported' );
	}

	/**
	 * Parse dates to timestamps
	 *
	 * @param string $date Date
	 * @return int Timestamp
	 */
	private function parseDate($date) {
		if (preg_match('/([A-Za-z]+)[ ]+([0-9]+)[ ]+([0-9]+):([0-9]+)/', $date, $res)) {
			$year   =date('Y');
			$month  =$res[1];
			$day    =$res[2];
			$hour   =$res[3];
			$minute =$res[4];
			$date   ="$month $day, $year $hour:$minute";
			$tmpDate=strtotime($date);
			if ($tmpDate > time()) {
				$year--;
				$date="$month $day, $year $hour:$minute";
			}
		} elseif (preg_match('/^\d\d-\d\d-\d\d/', $date)) {
			$date=str_replace('-', '/', $date);
		}
		$res=strtotime($date);
		if (!$res) {
			return $this->setError( 'Dateconversion failed' );
		}
		return $res;
	}

	/**
	 * This will return logical permissions mask for directory.
	 * if directory has to be readable it have also be executable
	 *
	 * @return string File permissions in digits for directory (i.e. if file permission is 0644 then return 0755 for dirs)
	 */
	private function getDirPermission() {
		$permissions=(string)$this->permissionFile;
		// going through (user, group, world)
		for ($i=0; $i < strlen($permissions); $i++) {
			// Read permission is set but execute not yet
			if ((int)$permissions{$i} & 4 and !((int)$permissions{$i} & 1)) {
				// Adding execute flag
				(int)$permissions{$i}=(int)$permissions{$i} + 1;
			}
		}
		return (string)$permissions;
	}

	/**
	 * последовательность всех сообщений Xpandbuddy_Ftp
	 * включая все отнаследованные классы
	 * включает и ошибки и сообщения
	 *
	 * @return array
	 */
	public function getLog( &$arrRes ) {
		$arrRes=$this->_log;
		return $arrRes;
	}

	/**
	 * только ошибки полученные при исполнении Xpandbuddy_Ftp
	 * включая все отнаследованные классы
	 *
	 * @return array
	 */
	public function getErrors( &$arrRes ) {
		$arrRes=$this->_errors;
		return $arrRes;
	}

	/**
	 * например сообщение о удачно проведённой операции
	 * можно ставить например return $this->setlog() вместо return true
	 *
	 * @return bool
	 */
	public function setlog( $_strMsg='' ) {
		$this->_log[]['msg']=$_strMsg;
		return true;
	}

	/**
	 * например сообщение о удачно проведённой операции
	 * можно ставить например return $this->setError() вместо return false
	 *
	 * @return bool
	 */
	public function setError( $_strError='' ) {
		$this->_errors[]=$_strError;
		$this->_log[]['error']=$_strError; // change to Zend_Logger this depercated!!!
		return false;
	}
}
}
?>