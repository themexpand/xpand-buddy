<?php
/**
 * Crofile class
 */
if( !class_exists('Xpandbuddy_Cron') ){
error_reporting(E_ERROR);
ini_set('display_errors', 0);
class Xpandbuddy_Cron {
	
	public static function run(){
		$_self=new self();
		$_self->backup();
	}

	public function backup(){
		include_once Xpandbuddy::$pathName.'/library/PclZip.php';
		include_once Xpandbuddy::$pathName.'/library/TarArchive.php';
		include_once Xpandbuddy::$pathName.'/library/Backup.php';
		include_once Xpandbuddy::$pathName.'/library/Options.php';
		include_once Xpandbuddy::$pathName.'/library/Twig.php';
		include_once Xpandbuddy::$pathName.'/library/Projects.php';
		include_once Xpandbuddy::$pathName.'/library/Logger.php';
		include_once Xpandbuddy::$pathName.'/library/Sender.php';
		$_object=new Xpandbuddy_Projects();
		if( !empty( $this->_projectId ) ){
			$_object->withIds( $this->_projectId );
		}
		if( !empty( $this->_projectSettings ) ){
			$arrList=array( $this->_projectSettings );
		}else{
			$_object->getList( $arrList );
			shuffle( $arrList );
		}
		if( empty( $arrList ) ){
			return array( 'message'=>'No project found!' );
		}
		foreach( $arrList as &$_project ){
			$_nowTime=time();
			if( !isset( $_project['start'] ) && empty( $this->_projectLogdate ) ){
				$_project['start']=$this->_projectLogdate=$_nowTime;
			}
			if( $_project['start']>=$_nowTime && empty( $this->_projectLogdate ) ){
				continue;
			}
			if( empty( $_project['start'] ) ){
				$_project['start']=$_nowTime;
			}
			$_senderLog=new Xpandbuddy_Logger( 'sender' );
			if( !empty( $this->_projectLogdate ) ){
				$_senderLog->logDate=$this->_projectLogdate;
				Xpandbuddy_Twig::$logger->logDate=$this->_projectLogdate;
			}else{
				$_senderLog->logDate=$_project['start'];
				Xpandbuddy_Twig::$logger->logDate=$_project['start'];
			}
			$_senderLog->getStepLog();
			if( !empty( $_senderLog->stepProject ) ){ // запускаем пошаговый отправщик
				$_POST['type']=$_senderLog->stepProject['sender'][$_senderLog->stepProject['send_now']]['type'];
				$_POST['send']=$_senderLog->stepProject['settings']['file_name'];
				$_POST['project']=$_senderLog->stepProject['id'];
				$_POST['return']=(array)$_senderLog->stepProject['code']['return'];
				$_senderLog->stepProject['code']=json_decode( Xpandbuddy_Sender::sendBackupFileAction( true ), true );
				
				if( $_senderLog->stepProject['code']['status']=== true || $_senderLog->stepProject['code']['status']=== false ){
					$_checkSender=$_senderLog->setStepLog();
					if( isset( $_senderLog->stepProject['code']['error'] ) ){
						$_senderLog->deleteStepLog();
						return array( 'logcode'=>$_senderLog->logDate,'flg_sender'=>$_checkSender, 'error'=>$_senderLog->stepProject['code']['error'] )+(array)$_senderLog->stepProject['code'];
					}else{
						return array( 'logcode'=>$_senderLog->logDate )+(array)$_senderLog->stepProject['code'];
					}
				}
				if( $_senderLog->stepProject['code']['status']=== 'sended' ){
					$_senderLog->stepProject['sender'][$_senderLog->stepProject['send_now']]['flg_send']=true;
					$_flgRunSender=false;
					foreach( $_senderLog->stepProject['sender'] as $_key=>$_type ){
						if( $_type['flg_send']==false ){
							$_senderLog->stepProject['send_now']=$_key;
							$_flgRunSender=true;
						}
					}
					$_checkSender=$_senderLog->setStepLog();
					if( $_flgRunSender ){
						return array( 'logcode'=>$_senderLog->logDate )+(array)$_senderLog->stepProject['code'];
					}
					$_senderLog->deleteStepLog();
					$_nextRunDate=date("U", mktime( $_project['settings']['timer']['h'], $_project['settings']['timer']['m'], 0, date("n", $_nowTime), date("j", $_nowTime), date("Y", $_nowTime) ) )+Xpandbuddy_Cron::next( $_project['settings']['user_data']['every'] );
					$_object->withIds( $_project['id'] )->update( 'start', $_nextRunDate );
					return array( 'status'=>'sended_all', 'message'=> '<span id="run_date_'.$_project['id'].'">next run after '.date( "m/d/Y H:00", $_nextRunDate ).'</span>' );
				}
				if( isset( $_senderLog->stepProject['code']['error'] ) ){
					return array( 'logcode'=>$_senderLog->logDate,'error'=>$_senderLog->stepProject['code']['error'], 'sender'=>$_senderLog->stepProject['send_now'] );
				}else{
					return array( 'logcode'=>$_senderLog->logDate, 'return'=>$_senderLog->stepProject['code'] );
				}
			}
			if( Xpandbuddy_Twig::stepByStep( Xpandbuddy_Twig::$logger->logDate, $_project,/*force create if no step file*/true ) ){
				$_returnArray=array();
				if( !empty( Xpandbuddy_Twig::$logString ) ){
					$_returnArray['message']=Xpandbuddy_Twig::$logString;
				}
				if( isset( Xpandbuddy_Twig::$logger->stepProject['settings']['file_loader'] ) ){
					global $current_site;
					$_returnArray['file']=array(
						'name'=>Xpandbuddy_Twig::$logger->stepProject['settings']['file_loader'], 
						'link'=>get_site_url( @$current_site->id ).'/wp-backups'.Xpandbuddy_Twig::_subdirCreation('/').Xpandbuddy_Twig::$logger->stepProject['settings']['file_loader'],
						'date'=>date( "Y-m-d H:i:s", @filemtime( substr(ABSPATH,0,-1).DIRECTORY_SEPARATOR.'/wp-backups'.Xpandbuddy_Twig::_subdirCreation('/').Xpandbuddy_Twig::$logger->stepProject['settings']['file_loader'] ) )
					);
				}
				if( !empty( Xpandbuddy_Twig::$logger->logDate ) ){
					$_returnArray['logcode']=Xpandbuddy_Twig::$logger->logDate;
				}
				if( isset( Xpandbuddy_Twig::$logger->stepProject['arrAllFilesCount'] ) ){
					$_returnArray['counters']=array(
						'all'=>Xpandbuddy_Twig::$logger->stepProject['arrAllFilesCount'], 
						'now'=>Xpandbuddy_Twig::$logger->stepProject['arrAllFilesCount']-count( Xpandbuddy_Twig::$logger->stepProject['arrFiles'] )
					);
				}
				if( isset( Xpandbuddy_Twig::$logger->stepProject['settings']['file_loader'] ) && !empty( Xpandbuddy_Twig::$logger->stepProject['settings']['file_loader'] ) ){
					// создаем следующий степовый файл с отправкой
					$_senderLog->stepProject=Xpandbuddy_Twig::$logger->stepProject;
					foreach( $_senderLog->stepProject['flg_type'] as $_key=>$_type ){
						if( $_type==$_key ){
							$_senderLog->stepProject['sender'][$_key]=array(
								'type'=>$_type,
								'flg_send'=>false
							);
							$_senderLog->stepProject['send_now']=$_key;
						}
					}
					$_senderLog->stepProject['code']=array( 'return'=>array( true ) );
					$_senderLog->setStepLog();
				}
				return $_returnArray;
			}else{ // проблема с созданием бэкапа
				$_returnArray['message']=Xpandbuddy_Twig::$logString."<br/>".Xpandbuddy_Twig::$error;
				if( !empty( Xpandbuddy_Twig::$logger->logDate ) ){
					$_returnArray['logcode']=Xpandbuddy_Twig::$logger->logDate;
				}
				if( isset( Xpandbuddy_Twig::$logger->stepProject['arrAllFilesCount'] ) ){
					$_returnArray['counters']=array(
						'all'=>Xpandbuddy_Twig::$logger->stepProject['arrAllFilesCount'], 
						'now'=>Xpandbuddy_Twig::$logger->stepProject['arrAllFilesCount']-count( Xpandbuddy_Twig::$logger->stepProject['arrFiles'] )
					);
				}
				return $_returnArray;
			}
			// not used
		}
	}

	private $_projectId=false;
	
	public function setProgectId( $id ){
		if( isset( $id ) ){
			$this->_projectId=$id;
		}
		return $this;
	}
	
	private $_projectLogdate=false;
	
	public function setLogDate( $date ){
		if( isset( $date ) ){
			$this->_projectLogdate=$date;
		}
		return $this;
	}
	
	private $_projectSettings=false;
	
	public function setProjectSettings( $arr=array() ){
		if( !empty( $arr ) ){
			$this->_projectSettings=$arr;
		}
		return $this;
	}

	public static function next( $_type=1 ){
		switch( $_type ){
			case 1 : return 60*60*24; break;
			case 2 : return 60*60*24*7; break;
			case 3 : return 60*60*24*30; break;
		}
	}

	public static function schedules(){
		$schedules=array(
			'blogcloncron_update'=> array(
				'interval'  => (60),
				'display'  => __('Each 1 minute'),
			)
		);
		return $schedules;
	}
}}
?>