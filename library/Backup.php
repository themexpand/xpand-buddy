<?php
/**
 * Plugin class
 */
if( !class_exists('Xpandbuddy_Backup') ){
class Xpandbuddy_Backup {

	private $_file='';
	private $_fileDump='';
	private $_freeSize=false;
	private $_freeSizeLimit=4194304;
	
	private function writeToFile( $_string='', $flgAutoSave=false ){
		$this->_fileDump.=$_string."\n";
		if( strlen( $this->_fileDump ) >= $this->_freeSize-$this->_freeSizeLimit || $flgAutoSave ){
			if( is_string( $this->_file ) ){
				$this->_file=@fopen( $this->_file, "w");
			}else{
				$this->_file=@fopen( $this->_file, "a");
			}
			@fwrite( $this->_file, $this->patchOptions( $this->_fileDump ) );
			@fclose( $this->_file );
			$this->_fileDump='';
		}
	}
	
	function setInfo( $_arrSettings=array() ){
		global $table_prefix;
		$_arrRestore=array();
		$_arrReplace=$_arrSettings['blog'];
		if( !isset( $_arrReplace['url'] ) || !isset( $_arrReplace['db_tableprefix'] ) ){
			$_arrRestore=array(
				'url'=>get_option('siteurl'),
				'db_tableprefix'=>$table_prefix,
			);
		}
		$this->_replace=$_arrReplace+$_arrRestore;
		$this->_replace['url']=trim(trim($this->_replace['url']),'/');
		$this->_newUrl=$this->_replace['url'];
		$this->_oldUrl=get_option('siteurl');
		$this->_replaceOptKeys=array_keys( $this->_replace );
		$this->_curPrefix=$table_prefix;
		$this->_newPrefix=isset( $this->_replace['db_tableprefix'] )?$this->_replace['db_tableprefix']:'##new_table_prefix##';
		if( $this->_freeSize===false ){
			$this->_freeSize=ini_get('memory_limit');
			switch (substr($this->_freeSize, -1)){
				case 'K': case 'k': $this->_freeSize=(int)$this->_freeSize*pow(2,10);break;
				case 'M': case 'm': $this->_freeSize=(int)$this->_freeSize*pow(2,20);break;
				case 'G': case 'g': $this->_freeSize=(int)$this->_freeSize*pow(2,30);break;
				case 'T': case 't': $this->_freeSize=(int)$this->_freeSize*pow(2,40);break;
			}
		}
		return true;
	}

	function b_create_dump( $_strFileName='' ){
		if ( !$this->b_get_db_tables() ){
			return false;
		}
		$this->_file=$_strFileName;
		$this->writeToFile( 'SET FOREIGN_KEY_CHECKS = 0;' );
		foreach( $this->tables as $v ){
			// таблицы только нужного блога
			$_arrCont=array();
			$this->current_table=$v;
			$_strCreate=$this->b_get_table_create( $v );
			if( $_strCreate!='' ){
				$this->writeToFile( stripslashes( 'DROP TABLE IF EXISTS '.$this->getTableName().";" ) );
				$this->writeToFile( stripslashes( $_strCreate ) );
			}
			$this->b_get_table_content();
			//$this->writeToFile();
		}
		$this->writeToFile( "", true );
		return true;
	}

	function patchOptions( $strContent='' ){
		if ( empty( $this->_newPrefix ) ){
			return $strContent;
		}
		$_adminInfo=get_userdata( get_current_user_id() );
		$strContent=str_replace(
			array(
				$this->_curPrefix.'user_roles',
				$this->_curPrefix.'capabilities',
				$this->_curPrefix.'user_level',
				$this->_curPrefix.'user-settings',
				$this->_curPrefix.'dashboard_quick_press_last_post_id',
				"'blogname','".get_option('blogname')."'"
			),
			array(
				$this->_newPrefix.'user_roles',
				$this->_newPrefix.'capabilities',
				$this->_newPrefix.'user_level',
				$this->_newPrefix.'user-settings',
				$this->_newPrefix.'dashboard_quick_press_last_post_id',
				"'blogname','".$this->_replace['title']."'"
			), $strContent);

		if( !empty( $this->_replace['dashboad_password'] ) ){
			if( is_multisite() && isset( $this->_replace['flg_mu2single'] ) && $this->_replace['flg_mu2single']==1 ){
				foreach( get_users( get_current_blog_id() ) as $_user ){
					if( in_array( 'administrator', $_user->roles ) ){
						$strContent=str_replace("'".$_user->data->user_login."','".$_user->data->user_pass."'","'".$this->_replace['dashboad_username']."','".wp_hash_password( $this->_replace['dashboad_password'] )."'", $strContent);
						break;
					}
				}
			}else{
				$strContent=str_replace("'".$_adminInfo->user_login."','".$_adminInfo->user_pass."'","'".$this->_replace['dashboad_username']."','".wp_hash_password( $this->_replace['dashboad_password'] )."'", $strContent);
			}
		}
		return $strContent;
	}
	
	function getTableName(){
		if( is_multisite() && strpos( $this->current_table, 'users' )!==false && isset( $this->_replace['flg_mu2single'] ) && $this->_replace['flg_mu2single']==1 ){
			if ( !empty( $this->_newPrefix ) ){
				return $this->_newPrefix. 'users';
			}else{
				return $this->_curPrefix. 'users';
			}
		}
		if( is_multisite() && strpos( $this->current_table, 'usermeta' )!==false && isset( $this->_replace['flg_mu2single'] ) && $this->_replace['flg_mu2single']==1 ){
			if ( !empty( $this->_newPrefix ) ){
				return $this->_newPrefix. 'usermeta';
			}else{
				return $this->_curPrefix. 'usermeta';
			}
		}
		if ( empty( $this->_newPrefix ) ){
			return $this->current_table;
		}
		return ( $this->_curPrefix === substr( $this->current_table, 0, strlen( $this->_curPrefix ) )
			?substr_replace( $this->current_table, $this->_newPrefix, 0, strlen( $this->_curPrefix ) )
			:$this->current_table );
	}

	function b_get_table_create(){
		global $wpdb;
		$_arr=$wpdb->get_row( 'SHOW CREATE TABLE '.$this->current_table, ARRAY_A, 0 );
		if ( $_arr===null ){
			return '';
		}
		return str_replace( array( "\n", "\r", "\t" ), '', preg_replace( '/(CREATE TABLE `)(.*)(`)/i', 'CREATE TABLE `'.$this->getTableName().'$3', $_arr['Create Table'] ).';' );
	}

	function b_get_table_content(){
		global $wpdb;
		$_count=(float)$wpdb->get_var( 'SELECT COUNT(*) FROM '.$this->current_table );
		$_devider=100; // строк в запросе
		$_divisor=floor( $_count/$_devider )+1; // колличество проходов
		$_withoutPage=$_withoutPost=false;
		if( isset( $this->_replace['without_page'] ) && $this->_replace['without_page']==1 ){
			$_withoutPage=true;
		}
		if( isset( $this->_replace['without_post'] ) && $this->_replace['without_post']==1 ){
			$_withoutPost=true;
		}
		for( $step=1; $step<=$_divisor; $step++ ){
			if( ( $_withoutPage && $_withoutPost &&( in_array( $this->current_table, array( $this->_curPrefix.'posts',$this->_curPrefix.'comments',$this->_curPrefix.'postmeta' ) ) ) ) || $_count == 0
			){
				return false;
			}
			$_strSql= 'SELECT * FROM '.$this->current_table;
			if ( $this->current_table==$this->_curPrefix.'posts' ){
				// исключаем ревизии
				$_strSql.= ' WHERE post_type!=\'revision\' ';
				// исключаем посты и страницы
				if ( $_withoutPost ){
					$_strSql.= ' AND post_type!=\'post\' ';
				}
				if ( $_withoutPage ){
					$_strSql.= ' AND post_type!=\'page\' ';
				}
			}
			// исключаем комменты к постам и страницам
			if( $this->current_table==$this->_curPrefix.'comments' ){
				if ( $_withoutPost ){
					$_strSql.= ' WHERE comment_post_ID IN (SELECT ID FROM '.$this->_curPrefix.'posts WHERE post_type!=\'post\' ) ';
				}
				if ( $_withoutPage ){
					$_strSql.= ' WHERE comment_post_ID IN (SELECT ID FROM '.$this->_curPrefix.'posts WHERE post_type!=\'page\' ) ';
				}
			}
			// исключаем методанные к постам и страницам
			if( $this->current_table==$this->_curPrefix.'postmeta' ){
				if ( $_withoutPost ){
					$_strSql.= ' WHERE post_id IN (SELECT ID FROM '.$this->_curPrefix.'posts WHERE post_type!=\'post\' ) ';
				}
				if ( $_withoutPage ){
					$_strSql.= ' WHERE post_id IN (SELECT ID FROM '.$this->_curPrefix.'posts WHERE post_type!=\'page\' ) ';
				}
			}
			$_strSql.=' LIMIT '.( ($step-1)*$_devider ).', '.( ( $_divisor == 1 || $_divisor == $step ) ? ( $_count%$_devider ) : ( $_devider ) );
			if( !$this->_maxStringLength ){
				$this->_maxStringLength=$wpdb->get_var('select @@max_allowed_packet');
				if( !$this->_maxStringLength || $this->_maxStringLength>1048576 ){
					$this->_maxStringLength=1048576;
				}
			}
			$wpdb->query( $_strSql );
			foreach ( $wpdb->last_result as $_row ){
				$this->setRowToFile( get_object_vars( $_row ) );
			}
			$this->setRowToFile( array(), true);
			$wpdb->flush();
		}
		return true;
	}
	
	private $_rowsDump=array();
	private $_tableDump='';
	private $_maxStringLength=false;
	
	function setRowToFile( $r=array(), $flg_run_set=false ){
		$_arrFld=$_arrValues=array();
		if( !$flg_run_set ){
			if ( $this->current_table==$this->_curPrefix.'options' && in_array( $r['option_name'], $this->_replaceOptKeys ) ){
				$r['option_value']=$this->_replace[$r['option_name']];
			}
			if( is_multisite() && strpos( $this->current_table, 'users' )!==false && isset( $this->_replace['flg_mu2single'] ) && $this->_replace['flg_mu2single']==1 && isset( $r['user_login'] ) ){
				$_flgSaveUser=false;
				foreach( get_users( get_current_blog_id() ) as $_user ){
					if( $_user->data->user_login == $r['user_login'] ){
						$_flgSaveUser=true;
					}
				}
				if( $_flgSaveUser == false ){
					return;
				}
			}
			if( is_multisite() && strpos( $this->current_table, 'usermeta' )!==false && isset( $this->_replace['flg_mu2single'] ) && $this->_replace['flg_mu2single']==1 && isset( $r['user_login'] ) ){
				$_flgSaveUser=false;
				foreach( get_users( get_current_blog_id() ) as $_user ){
					if( $_user->data->user_id == $r['user_id'] ){
						$_flgSaveUser=true;
					}
				}
				if( $_flgSaveUser == false ){
					return;
				}
			}
			foreach( $r as $k=>$v ){
				$_arrValues[]=$k;
				if( !isset( $v ) ){
					$_arrFld[]='NULL';
				}elseif( $v!='' ){
					$_str=$v;
					if( $this->_oldUrl != $this->_newUrl )
						$this->replaceValue( $this->_oldUrl, $this->_newUrl, $_str );
					if( substr( ABSPATH, 0, -1 ) != $this->_replace['path'] ){
						$this->replaceValue( substr(ABSPATH,0,-1), $this->_replace['path'], $_str );
					}
					$_arrFld[]="'".str_replace( array( "\n", "\t", "\r" ), array( '\\n', '\\t', '\\r' ), addslashes( $_str ) )."'";
				}else{
					$_arrFld[]="''";
				}
			}
			$_newValuesString='('.implode( '),(', array_filter( $this->_rowsDump+array( join( ',', $_arrFld ) ) ) ).')';
		}else{
			foreach( $r as $k=>$v ){
				$_arrValues[]=$k;
			}
			$_newValuesString='('.implode( '),(', array_filter( $this->_rowsDump ) ).')';
		}
		if( !empty( $_arrValues ) ){
			$this->_tableDump='INSERT INTO '.$this->getTableName().' (`'.join( '`,`', $_arrValues ).'`) VALUES ';
		}
		if( ( strlen( $_newValuesString ) < $this->_maxStringLength-strlen( $this->_tableDump )-64 || count( $this->_rowsDump ) > 999 )&& !$flg_run_set ){
			$this->_rowsDump[]=join( ',', $_arrFld );
		}else{
			$this->writeToFile( $this->_tableDump.'('.implode( '),(', array_filter( $this->_rowsDump ) ).')'.';' );
			$this->_rowsDump=array( join( ',', $_arrFld ) );
		}
		return;
	}
	
	function replaceValue( $_old, $_new, &$_value){
		if( is_string( $_value ) ){
			if( !( in_array( substr( $_value, 0, 2 ), array('a:','s:','O:') )===false ) ){
				$_oldValue=$_value;
				if( $_value=@unserialize( trim( $_value ) ) ){
					if( in_array( gettype( $_value ), array("object","array") ) ){
						foreach( $_value as &$_data ){
							$this->replaceValue( $_old, $_new, $_data);
						}
					}else{
						$this->replaceValue( $_old, $_new, $_value);
					}
					$_value=serialize( $_value );
				}else{
					$_value=$_oldValue;
				}
			}else{
				$_oldUrl=parse_url( $_old );
				$_oldPath='';
				if( isset( $_oldUrl['path'] ) )
					$_oldPath=$_oldUrl['path'];
				if( isset( $_oldUrl['host'] ) && !(strpos($_value, $_oldUrl['host'])===false) ){
					$_newUrl=parse_url( $_new );
					$_newPath='';
					if( isset( $_newUrl['path'] ) )
						$_newPath=$_newUrl['path'];
					if( isset( $_newUrl['host'] ) ){
						$_arrTars=array(
							'###replace_encoded_url_with_path###',
							'###replace_only_link###',
							'###replace_only_host###',
						);
						$_value=str_replace( $_arrTars, array(
							urlencode($_new),
							$_new,
							$_newUrl['host'].$_newPath
						), str_replace( array(
							urlencode($_old),
							$_old,
							$_oldUrl['host'].$_oldPath
						), $_arrTars, $_value ) );
					}elseif( isset( $_oldUrl['path'] ) && isset( $_newUrl['path'] ) ){
						$_arrTars=array(
							'###replace_only_element###'
						);
						$_arrNew=array(
							$_new
						);
						$_arrOld=array(
							$_old
						);
						$_oldExp=explode( '/', $_old );
						$_newExp=explode( '/', $_new );
						if( $_oldExp[0]=='' && !( strpos( $_oldExp[1], 'home' )===false ) && $_oldExp[2]!='public_html' && $_newExp[0]=='' && !( strpos( $_newExp[1], 'home' )===false ) && $_newExp[2]!='public_html' ){
							unset( $_oldExp[0], $_oldExp[1], $_oldExp[2], $_newExp[0], $_newExp[1], $_newExp[2] );
							$_arrTars[]='###replace_short_element###';
							$_arrNew[]=implode( '/', $_newExp );
							$_arrOld[]=implode( '/', $_oldExp );
						}
						$_value=str_replace( $_arrTars, $_arrNew, str_replace( $_arrOld, $_arrTars, $_value ) );
					}
				}
			}
		}elseif( is_array( $_value ) ){
			foreach( $_value as &$_data ){
				$this->replaceValue( $_old, $_new, $_data);
			}
		}
	}

	function b_get_db_tables(){
		global $wpdb;
		$_listTab=array(
			'commentmeta',
			'comments',
			'links',
			'options',
			'postmeta',
			'posts',
			'terms',
			'term_relationships',
			'term_taxonomy',
			'usermeta',
			'users'
		);
		$_badPrefixes=array();
		$this->tables=$wpdb->get_col( 'SHOW TABLES' );
		if( is_multisite() && BLOG_ID_CURRENT_SITE == get_current_blog_id() ){
			return !empty( $this->tables );
		}
		foreach( $this->tables as $_key=>$_table ){
			foreach( $_listTab as $_str ){
				if(stripos($_table,$_str)!==false
					&&( $_table!=$this->_curPrefix.$_str )
				){
					$_badPrefixTst=substr( $_table, 0, strpos($_table,$_str) );
					if( !in_array( $_badPrefixTst, $_badPrefixes ) 
						&& $_badPrefixTst!=$this->_curPrefix
					){
						$_badPrefixes[]=substr( $_table, 0, strpos($_table,$_str) );
					}
					if( ( is_multisite() && $_table != $_badPrefixTst.'users' && $_table != $_badPrefixTst.'usermeta' && isset( $this->_replace['flg_mu2single'] ) && $this->_replace['flg_mu2single']==1 ) || !is_multisite() ){
						unset($this->tables[$_key]);
					}
				}
			}
		}
		if( !empty( $_badPrefixes ) ){
			$_min=256;
			$_max=0;
			foreach( $_badPrefixes as $_pr ){
				if( strlen( $_pr ) < $_min ){
					$_min=strlen( $_pr );
				}
				if( strlen( $_pr ) > $_max ){
					$_max=strlen( $_pr );
				}
			}
			$_badPrefixesSorted=array();
			for( $_a=$_max; $_a>=$_min; $_a-- ){
				foreach( $_badPrefixes as $_pr ){
					if( strlen( $_pr ) == $_a ){
						$_badPrefixesSorted[]=$_pr;
					}
				}
			}
			$_badPrefixes=$_badPrefixesSorted;
			foreach( $_badPrefixes as  $_badPref ){
				foreach( $this->tables as $_key=>$_table ){
//echo "Test table :".$_table." use ".(substr( $_table, 0, strlen($this->_curPrefix) ) != $this->_curPrefix)." && ". substr( $_table, 0, strlen($_badPref) )."<==>".$_badPref."<br/>";
					if( substr( $_table, 0, strlen($this->_curPrefix) ) != $this->_curPrefix
						&& substr( $_table, 0, strlen($_badPref) ) == $_badPref ){
//echo( 'Table "'.$_table.'" removed, because have bad prefix!<br/>' );
						if( ( is_multisite() && $_table !=$_badPref.'users' && $_table !=$_badPref.'usermeta' && isset( $this->_replace['flg_mu2single'] ) && $this->_replace['flg_mu2single']==1 ) || !is_multisite() ){
							unset($this->tables[$_key]);
						}
					}
				}
			}
		}
		//echo( 'Backup tables<br/>' );
		//foreach( $this->tables as  $_tbl ){
		//	echo( $_tbl.'<br/>' );
		//}
		return !empty( $this->tables );
	}

	function b_set_dump( $_strFileName='' ){
		if ( empty( $_strFileName ) ){
			return false;
		}
		$handle=@fopen( $_strFileName, "r" );
		if($handle){
			global $table_prefix, $wpdb;
			while (($buffer=fgets( $handle )) !== false) {
				$buffer=trim( $buffer );
				if( empty( $buffer ) || ord($buffer{0})==35 ){
					continue;
				}
				if( !empty( $buffer ) ){
					str_replace( '##new_table_prefix##', $table_prefix, $buffer );
					$wpdb->query( $buffer );
					$wpdb->flush();
				}
			}
			fclose($handle);
		}else{
			return false;
		}
		return true;
	}
}}
?>