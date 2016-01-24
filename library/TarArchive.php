<?php

if( !class_exists('TarArchive') ){
if( !defined( 'PCLZIP_OPT_ADD_PATH' ) ){
	define( 'PCLZIP_OPT_ADD_PATH', 77002 );
	define( 'PCLZIP_OPT_REMOVE_PATH', 77003 );
	define( 'PCLZIP_OPT_COMMENT', 77012 );
	define( 'PCLZIP_OPT_PATH', 77001 );
	define( 'PCLZIP_OPT_NO_COMPRESSION', 77007 );
	define( 'PCLZIP_OPT_SET_CHMOD', 77005 );
	define( 'PCLZIP_OPT_REPLACE_NEWER', 77016 );
	define( 'PCLZIP_OPT_TEMP_FILE_ON', 77021 );
}
class TarArchive{
	private $_archiveName='';
	private $_archiveResource='';
	private $error=array();
	private $_options=array();
	public $error_code=0;
	
	function TarArchive( $archiveName ){
		$this->_archiveName=$archiveName;
	}
	
	public function add( $path ){
		$this->_options=array();
		if( func_num_args() > 1){
			$argsList=func_get_args();
			array_shift($argsList); // remove $path
			while( count( $argsList )>0 ){
				$_arg=array_shift($argsList);
				if( in_array( $_arg, array( PCLZIP_OPT_REPLACE_NEWER, PCLZIP_OPT_NO_COMPRESSION, PCLZIP_OPT_TEMP_FILE_ON ) ) ){
					$this->_options[$_arg]=true;
				}else{
					$this->_options[$_arg]=array_shift($argsList);
				}
			}
		}
		$path=explode( ',', $path );
		$this->openArchive();
		if( empty( $this->_archiveName ) ){
			$arrError=error_get_last();
			$this->error[]=@$arrError['message'];
			return false;
		}
		if( isset( $this->_options[PCLZIP_OPT_REMOVE_PATH] ) ){
			$pwd=getcwd();
			chdir( $this->_options[PCLZIP_OPT_REMOVE_PATH] );
		}
		$_fileList=array();
		foreach( $path as $_element ){
			if( is_file( $_element ) ){
				$_strParentDir=str_replace( rtrim( isset( $this->_options[PCLZIP_OPT_REMOVE_PATH] )?$this->_options[PCLZIP_OPT_REMOVE_PATH]:(substr(ABSPATH,0,-1).DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR ), '', dirname( $_element ) );
				if( $_strParentDir != '' ){
					$_fileList[]=$_strParentDir;
				}
				$_fileList[]=str_replace( isset( $this->_options[PCLZIP_OPT_REMOVE_PATH] )?$this->_options[PCLZIP_OPT_REMOVE_PATH]:(substr(ABSPATH,0,-1).DIRECTORY_SEPARATOR), '', $_element );
			}elseif( is_dir( $_element ) ){
				$_fileList[]=str_replace( isset( $this->_options[PCLZIP_OPT_REMOVE_PATH] )?$this->_options[PCLZIP_OPT_REMOVE_PATH]:(substr(ABSPATH,0,-1).DIRECTORY_SEPARATOR), '', $_element );
				self::getFileList( $_fileList, $_element, isset( $this->_options[PCLZIP_OPT_REMOVE_PATH] )?$this->_options[PCLZIP_OPT_REMOVE_PATH]:(substr(ABSPATH,0,-1).DIRECTORY_SEPARATOR) );
			}
		}
		$_fileList=array_unique( $_fileList );
		foreach( $_fileList as &$_currentFile ){
			$_currentFile=trim( $_currentFile, DIRECTORY_SEPARATOR );
			$this->addFile( $_currentFile );
		}
		if( count( $_fileList ) >1 ){
			$this->closeArchive();
		}
		if( isset( $this->_options[PCLZIP_OPT_REMOVE_PATH] ) ){
			chdir($pwd);
		}
		return true;
	}
	
	public function extract(){
		$this->_options=array();
		if( func_num_args() > 1){
			$argsList=func_get_args();
			while( count( $argsList )>0 ){
				$_arg=array_shift($argsList);
				if( in_array( $_arg, array( PCLZIP_OPT_REPLACE_NEWER, PCLZIP_OPT_NO_COMPRESSION, PCLZIP_OPT_TEMP_FILE_ON ) ) ){
					$this->_options[$_arg]=true;
				}else{
					$this->_options[$_arg]=array_shift($argsList);
				}
			}
		}
		$pwd=getcwd();
		if( isset( $this->_options[PCLZIP_OPT_PATH] ) ){
			chdir( $this->_options[PCLZIP_OPT_PATH] );
		}
		if( $fp=fopen($this->_archiveName, "rb") ){
			while ($block=fread($fp, 512)){
				$temp=unpack("a100name/a8mode/a8uid/a8gid/a12size/a12mtime/a8checksum/a1type/a100symlink/a6magic/a2temp/a32temp/a32temp/a8temp/a8temp/a155prefix/a12temp", $block);
				$file=array(
					'name'=>$temp['prefix'].$temp['name'],
					'stat'=>array(
						'mode'=>substr( $temp['mode'], -4 ),
						'uid'=>octdec($temp['uid']),
						'gid'=>octdec($temp['gid']),
						'size'=>octdec($temp['size']),
						'mtime'=>octdec($temp['mtime']),
					),
					'checksum'=>octdec($temp['checksum']),
					'type'=>$temp['type'],
					'magic'=>$temp['magic'],
				);
				if($file['checksum']==0x00000000){
					break;
				}else if(substr($file['magic'], 0, 5) != "ustar"){
					$this->error[]="This script does not support extracting this type of tar file.";
					break;
				}
				$block=substr_replace($block, "        ", 148, 8);
				$checksum=0;
				for ($i=0; $i < 512; $i++){
					$checksum+=ord(substr($block, $i, 1));
				}
				if($file['checksum'] != $checksum){
					$this->error[]="Could not extract from {$this->_archiveName}, it is corrupt.";
				}
				if($file['type']==5){
					if(!is_dir($file['name'])){
						@mkdir($file['name'], intval($file['stat']['mode'], 8), true);
					}
					@chmod($file['name'], $file['stat']['mode']);
				}elseif($file['type']==2){
					@symlink($temp['symlink'], $file['name']);
					@chmod($file['name'], $file['stat']['mode']);
				}else{
					if(!is_dir(dirname($file['name']))){
						@mkdir(dirname($file['name']), 0755, true);
					}
					$new=fopen($file['name'], "w+b");
					if( $new !== false ){
						if( $file['stat']['size'] != 0 ){
							$_readBlockSize=1024*1024;
							if( $file['stat']['size'] < $_readBlockSize ){
								$_readBlockSize=$file['stat']['size'];
							}
							$_readedFromFile=0;
							while( $_readBlockSize!=0 && ( $temp=fread( $fp, $_readBlockSize ) ) ){
								$_readedFromFile+=$_readBlockSize;
								if( $_readedFromFile+$_readBlockSize >= $file['stat']['size'] ){
									$_readBlockSize=$file['stat']['size']-$_readedFromFile;
								}
								fwrite($new, $temp);
							}
							if( (512 - $file['stat']['size'] % 512) != 512 ){
								$temp=fread($fp, ( 512 - $file['stat']['size'] % 512 ));
							}
						}
						fclose($new);
						if( isset( $this->_options[PCLZIP_OPT_SET_CHMOD] ) ){
							@chmod($file['name'], $this->_options[PCLZIP_OPT_SET_CHMOD]);
						}else{
							@chmod( $file['name'], intval($file['stat']['mode'], 8) );
						}
					}else{
						$this->error[]="Could not open {$file['name']} for writing.";
						continue;
					}
				}
				@chown($file['name'], $file['stat']['uid']);
				@chgrp($file['name'], $file['stat']['gid']);
				@touch($file['name'], $file['stat']['mtime']);
				unset($file);
			}
		}else{
			$this->error[]="Could not open file {$this->_archiveName}";
		}
		chdir($pwd);
	}
	
	private function openArchive(){
		if( is_string( $this->_archiveName ) && !empty( $this->_archiveName ) ){
			$this->_archiveResource=fopen($this->_archiveName, "a+b");
		}
	}
	
	private function closeArchive(){
		if( is_resource( $this->_archiveResource ) ){
			fclose($this->_archiveResource);
		}
	}
	
	public function listContent(){
		$arrList=array();
		if( $fp=fopen($this->_archiveName, "rb") ){
			while ($block=fread($fp, 512)){
				$temp=unpack("a100name/a8mode/a8uid/a8gid/a12size/a12mtime/a8checksum/a1type/a100symlink/a6magic/a2temp/a32temp/a32temp/a8temp/a8temp/a155prefix/a12temp", $block);
				$file=array(
					'name'=>$temp['prefix'].$temp['name'],
					'stat'=>array (
						'size'=>octdec($temp['size']),
					)
				);
				fseek($fp, $file['stat']['size'], SEEK_CUR);
				fseek($fp, (512 - $file['stat']['size'] % 512)==512 ? 0 : (512 - $file['stat']['size'] % 512), SEEK_CUR);
				$arrList[]=array( 'filename' => $file['name'] );
				unset ($file);
			}
		}else{
			$this->error[]="Could not open file {$this->_archiveName}";
		}
		return $arrList;
	}
	
	public function addFile( &$_currentFile ){
		$_currentFileLink=( isset( $this->_options[PCLZIP_OPT_REMOVE_PATH] )?$this->_options[PCLZIP_OPT_REMOVE_PATH]:'').$_currentFile;
		$_currentFile=array( 'name'=>$_currentFile );
		$_currentFile['stat']=stat( $_currentFileLink );
		if( $_currentFile['stat'] === false ){
			$this->error[]="Could not open file {$_currentFile['name']} for stat.";
			return false;
		}
		$_currentFile['type']=@is_link($_currentFile['name'])?2:( @is_dir($_currentFile['name'])?5:0 );
		if( isset( $this->_options[PCLZIP_OPT_ADD_PATH] ) && !empty( $this->_options[PCLZIP_OPT_ADD_PATH] ) ){
			$_currentFile['name2']=$this->_options[PCLZIP_OPT_ADD_PATH].DIRECTORY_SEPARATOR.$_currentFile['name'];
		}else{
			$_currentFile['name2']=$_currentFile['name'];
		}
		$_archiveFilePath='';
		if(strlen($_currentFile['name2']) > 99){
			$_archiveFilePath=substr( $_currentFile['name2'], 0, strpos($_currentFile['name2'], DIRECTORY_SEPARATOR, strlen($_currentFile['name2']) - 100) + 1 );
			$_currentFile['name2']=substr($_currentFile['name2'], strlen($_archiveFilePath));
			if(strlen($_archiveFilePath) > 154 || strlen($_currentFile['name2']) > 99){
				$this->error[]="Could not add {$_archiveFilePath}{$_currentFile['name2']} to archive because the filename is too long.";
				return false;
			}
		}
		$block=pack(
			"a100a8a8a8a12a12a8a1a100a6a2a32a32a8a8a155a12",
			$_currentFile['name2'],
			sprintf("%07o", $_currentFile['stat']['mode']),
			sprintf("%07o", $_currentFile['stat']['uid']),
			sprintf("%07o", $_currentFile['stat']['gid']),
			sprintf("%011o", ( is_file( $_currentFile['name'] )?$_currentFile['stat']['size']:0 ) ),
			sprintf("%011o", $_currentFile['stat']['mtime']), 
			"        ",
			$_currentFile['type'],
			$_currentFile['type']==2?@readlink($_currentFile['name']):"",
			"ustar ",
			" ", 
			"Unknown",
			"Unknown",
			"",
			"",
			$_archiveFilePath,
			""
		);
		$checksum=0;
		for ($i=0; $i < 512; $i++)
			$checksum += ord(substr($block, $i, 1));
		$checksum=pack("a8", sprintf("%07o", $checksum));
		$block=substr_replace($block, $checksum, 148, 8);
		// добавить проверку на размер файла
		if( is_dir( $_currentFileLink ) || $_currentFile['stat']['size'] == 0){
			fwrite($this->_archiveResource, $block);
		}elseif( is_file( $_currentFileLink ) && $fp=@fopen($_currentFileLink, "rb")){
			fwrite($this->_archiveResource, $block);
			while($temp=@fread($fp,1048576)){
				fwrite($this->_archiveResource, $temp);
			}
			if($_currentFile['stat']['size'] % 512 > 0){
				$temp="";
				for ($i=0; $i < 512-$_currentFile['stat']['size'] % 512; $i++)
					$temp.="\0";
				fwrite($this->_archiveResource, $temp);
			}
			fclose($fp);
		}else{
			$this->error[]="Could not open file {$_currentFile['name']} for reading. It was not added.";
		}
	}
	
	private static function getFileList( &$arrFiles, $eltName, $dirStart, $openDirs=true ){
		if( $_handle=opendir( $eltName ) ){
			while( false!==( $_file=readdir( $_handle ) ) ){
				if( $_file!='.' && $_file!='..' ){
					if( is_file( $eltName.DIRECTORY_SEPARATOR.$_file ) ){
						$arrFiles[]=str_replace( $dirStart, '', $eltName.DIRECTORY_SEPARATOR.$_file );
					}elseif( $openDirs && is_dir( $eltName.DIRECTORY_SEPARATOR.$_file ) ){
						$arrFiles[]=str_replace( $dirStart, '', $eltName.DIRECTORY_SEPARATOR.$_file );
						self::getFileList( $arrFiles, $eltName.DIRECTORY_SEPARATOR.$_file, $dirStart );
					}
				}
			}
		}
	}
	
	public function errorInfo( $flag ){
		return end( $this->error );
	}
	
}
}
?>