<?php

if( !class_exists('Xpandbuddy_Projects') ){
class Xpandbuddy_Projects{
	protected $_table='xpandbuddy';
	protected $_db='xpandbuddy';
	protected $_fields=array('id','flg_type','settings','flg_status','start','edited','added');
	protected $_errors=array();
	protected $_data;
	protected $_paging;
	protected $_onlyOne=false;
	protected $_withIds=array();
	protected $_withPaging=array();
	protected $_withOrder='d.id--up';
	protected $_withFilter=false;

	public function __construct(){
		global $wpdb;
		if( !empty( $wpdb ) ){
			$this->_table=$wpdb->prefix.$this->_table;
			$this->_db=$wpdb->dbname;
		}
	}

	public function getErrors( &$arrRes ){
		$arrRes=$this->_errors;
		return $this;
	}

	public function setError( $_strError='' ){
		$this->_errors[]=$_strError;
		return false;
	}

	private function verify(){
		$_bool=true;
		if( empty( $this->_data['settings'] ) ){
			$_bool=$this->setError( 'Empty project settings' );
		}
		return $_bool;
	}

	public function setEntered( $_mix=array() ){
		$this->_data=$_mix;
		return $this;
	}

	public function runFilter(){
		foreach( $this->_data as $k=>&$v ){
			if( !( is_object( $v ) || is_array( $v ) ) ){
				$v=strip_tags( $v );
				$v=stripslashes( $v );
				$v=trim( $v );
				if( $v=='' || !in_array( $k, $this->_fields ) ){
					unset( $this->_data[$k] );
				}
			}
		}
	}

	public function update( $key, $value ){
		if( !isset($key) || empty( $key ) || !isset($value) ){
			return false;
		}
		global $wpdb;
		$_array=null;
		if( !empty( $this->_withIds ) ){
			$_array=array( 'id'=>$this->_withIds );
		}
		return $wpdb->update( $this->_table, array($key=>$value), $_array );
	}

	public function set(){
		$this->runFilter();
		if( !$this->verify() ){
			return false;
		}
		$this->_data['edited']=time();
		$_newTime=date("U", mktime( $this->_data['settings']['timer']['h'], $this->_data['settings']['timer']['m'], 0, date("n", $this->_data['edited']), date("j", $this->_data['edited']), date("Y", $this->_data['edited']) ) );
		if( $_newTime < $this->_data['edited'] ){
			$_newTime+=60*60*24;
		}
		$this->_data['start']=$_newTime;
		$this->_data['id']=isset( $this->_data['id'] )?$this->_data['id']:null;
		$this->_data['settings']=serialize( $this->_data['settings'] );
		$this->_data['flg_type']=serialize( $this->_data['flg_type'] );
		
		global $wpdb;
		if( !$wpdb->update( $this->_table, $this->_data, array( 'id'=>$this->_data['id'] ) ) ){
			$this->_data['added']=$this->_data['edited'];
			$wpdb->insert( $this->_table, $this->_data );
			$this->_data['id']=$wpdb->insert_id;
		}
		if( !empty( $wpdb->last_error ) ){
			$this->setError( $wpdb->last_error );
			return false;
		}
		return true;
	}

	public function getKeyRecord( $query ){
		global $wpdb;
		if( $query )
			$wpdb->query( $query );
		else
			return null;
		$arrData=array();
		foreach( $wpdb->last_result as $row ){
			$key=array_shift( get_object_vars( $row ) );
			if( !isset( $arrData[$key] ) )
				$arrData[$key]=get_object_vars( $row );
		}
		$wpdb->flush();
		return $arrData;
	}

	public function getEntered( &$arrRes ){
		if( is_array( $this->_data ) ){
			$arrRes=$this->_data;
			if( !is_array( $arrRes['settings'] ) ){
				$arrRes['settings']=unserialize( $arrRes['settings'] );
				$arrRes['flg_type']=unserialize( $arrRes['flg_type'] );
			}
		}
		return $this;
	}

	public function setStartDate( $_intDate=0 ){
		$this->_data['start']=$_intDate;
		$this->_data['settings']=serialize( $this->_data['settings'] );
		$this->_data['flg_type']=serialize( $this->_data['flg_type'] );
		global $wpdb;
		$wpdb->update( $this->_table, $this->_data, array( 'id'=>$this->_data['id'] ) );
		if( !empty( $wpdb->last_error ) ){
			$this->setError( $wpdb->last_error );
			return false;
		}
		return true;
	}

	public function withIds( $_arrIds=array() ){
		$this->_withIds=$_arrIds;
		return $this;
	}

	public function withPaging( $_arr=array() ){
		$this->_withPaging=$_arr;
		return $this;
	}

	public function onlyOne() {
		$this->_onlyOne=true;
		return $this;
	}

	public function withOrder( $_str='' ){
		if( !empty( $_str ) ){
			$this->_withOrder=$_str;
		}
		$this->_cashe['order']=$this->_withOrder;
		return $this;
	}

	public function withFilter( $_str='' ){
		if( !empty( $_str ) ){
			$this->_withFilter=$_str;
		}
		return $this;
	}

	public function getFilter( &$arrRes ){
		$arrRes=$this->_cashe+array( 'filter'=>$this->_withFilter );
		return $this;
	}

	public function getPaging( &$arrRes ){
		$arrRes=$this->_paging;
		$this->_paging=array();
		return $this;
	}

	public static function fixInjection( $_mixVar='', $quote=true ){
		if(is_array($_mixVar)){
			foreach($_mixVar as &$val){
				$val=self::fixInjection($val, $quote);
			}
			return implode(", ", $_mixVar);
		}
        if(is_int($_mixVar)){
            return $_mixVar;
        } elseif(is_float($_mixVar)){
            return sprintf('%F', $_mixVar);
        }
		global $wpdb;
        return (($quote)?"'":'').$wpdb->_real_escape($_mixVar).(($quote)?"'":'');
	}

	public function getNewId( &$newId ){
		global $wpdb;
		$newId=$wpdb->get_var( "SELECT Auto_increment FROM information_schema.tables WHERE table_name='".$this->_table."' AND table_schema='".$this->_db."'" );
	}
	
	public function getList( &$mixRes ){
		global $wpdb, $current_user;
		$_strQuery="SELECT d.* FROM {$this->_table} d";
		$_arrWhere=array();
		if( !empty( $this->_withIds ) ){
			$_arrWhere[]='d.id IN ('.self::fixInjection( $this->_withIds ).')';
		}
		$_strQuery.=( ( count( $_arrWhere )>0 )?' WHERE '.implode( ' AND ', $_arrWhere ):'' );
		if( !$this->_onlyOne ){
			$_arrPrt=explode( '--', $this->_withOrder );
			$_strQuery.=" ORDER BY ".$_arrPrt[0].' '.( ( $_arrPrt[1]=='up' ) ? 'DESC':'ASC' );
		}
		if( !empty( $this->_withPaging ) ){
			$_strUrl=urldecode( $_SERVER['REQUEST_URI'] );
			$_strHref=( is_integer( mb_strpos( $_strUrl, '?' ) )?mb_substr( $_strUrl, 0, mb_strpos( $_strUrl, '?' ) ):$_strUrl ).'?';
			$_flgFirst=1;
			foreach( $this->_withPaging['url'] as $k=>$v ){
				if( !is_array( $v ) ){
					$_strHref.=(($_flgFirst!=1)?'&':'').urldecode($k).'='.urldecode($v);
					$_flgFirst=0;
				}
			}
			$_strHref.="&num=";
			$reconpage=get_user_meta($current_user->ID, 'edit_post_per_page', true);
			$this->_paging=array(
				'recall'=>$wpdb->get_var( "SELECT COUNT(*) FROM {$this->_table}".( ( count( $_arrWhere )>0 )?' WHERE '.implode( ' AND ', $_arrWhere ):'' ) ),
				'reconpage'=>(empty($reconpage)?20:$reconpage),
				'curpage'=>isset($this->_withPaging['url']['num'])?$this->_withPaging['url']['num']:1,
				'urlminus'=>'',
				'urlmin'=>'',
				'urlmax'=>'',
				'urlplus'=>'',
			);
			if( $this->_paging['curpage']>1 ){
				$this->_paging['urlminus']=$_strHref.($this->_paging['curpage']-1);
				$this->_paging['urlmin']=$_strHref.'1';
			}
			if( $this->_paging['curpage']*$this->_paging['reconpage']<$this->_paging['recall'] ){
				$this->_paging['urlmax']=$_strHref.ceil($this->_paging['recall']/$this->_paging['reconpage']);
				$this->_paging['urlplus']=$_strHref.($this->_paging['curpage']+1);
			}
			$_strQuery.=" LIMIT ".( ($this->_paging['curpage']-1)*$this->_paging['reconpage'] ).",".( $this->_paging['curpage']*$this->_paging['reconpage'] );
		}
		if( $this->_onlyOne ){
			$mixRes=$wpdb->get_row( $_strQuery, ARRAY_A, 0 );
		} else {
			$mixRes=$wpdb->get_results( $_strQuery, ARRAY_A );
		}
		$this->_isNotEmpty=!empty( $mixRes );
		$this->init();
		if( !is_array( $mixRes ) ){
			return $this;
		}
		if( array_key_exists('settings', $mixRes) ){
			$mixRes['settings']=unserialize( $mixRes['settings'] );
			$mixRes['flg_type']=unserialize( $mixRes['flg_type'] );
			return $this;
		}else{
			foreach( $mixRes as &$_item ){
				$_item['settings']=unserialize( $_item['settings'] );
				$_item['flg_type']=unserialize( $_item['flg_type'] );
			}
		}
		return $this;
	}

	protected function init(){
		$this->_onlyOne=false;
		$this->_withIds=array();
		$this->_withPaging=array();
		$this->_withOrder='d.id--up';
	}

	public function del() {
		if ( empty( $this->_withIds ) ) {
			$_bool=false;
		} else {
			global $wpdb;
			$wpdb->query( 'DELETE FROM '.$this->_table.' WHERE id IN('.self::fixInjection( $this->_withIds ).')' );
			$_bool=true;
		}
		$this->init();
		return $_bool;
	}
}}
?>