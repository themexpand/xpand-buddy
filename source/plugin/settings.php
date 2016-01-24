<?php if( $flg_type != 'clone' && $flg_type != 'backup' ){ ?>
<li>
	<label><?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>Backup<?php }else{ ?>Clone<?php } ?> Every</label>
	<select name="arrProject[settings][type_<?php echo $flg_type;?>][every]">
		<option <?php if(@$arrProject['settings']['type_'.$flg_type]['every']==1) :?> selected="1" <?php endif;?> value="1">Day</option>
		<option <?php if(@$arrProject['settings']['type_'.$flg_type]['every']==2) :?> selected="2" <?php endif;?> value="2">Week</option>
		<option <?php if(@$arrProject['settings']['type_'.$flg_type]['every']==3) :?> selected="3" <?php endif;?> value="3">Month</option>
	</select>
</li>
<?php } ?>
<li>
	<label>Store Your Data Only<br/>(without WordPress files)</label>
	<input type="hidden" name="arrProject[settings][type_<?php echo $flg_type;?>][only_settings]" value="0">
	<input id="only_settings_backup_<?php echo $flg_type;?>" class="update_dir_check_<?php echo $flg_type;?>" type="checkbox" name="arrProject[settings][type_<?php echo $flg_type;?>][only_settings]" value="1"<?php if( (isset($arrProject['settings']['type_'.$flg_type]['only_settings']) && $arrProject['settings']['type_'.$flg_type]['only_settings']==1) || ( !isset($arrProject['settings']['type_'.$flg_type]['only_settings']) && $flg_type==5 ) ){ ?> checked="checked"<?php } ?> >
	<p class="helper"><font color="Red">Note:</font> Use this option to <?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>backup<?php }else{ ?>clone<?php } ?> your database, themes, plugins, and media uploads. Lets you <?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>backup<?php }else{ ?>clone<?php } ?> your custom data excluding default WordPress engine files.</p>
</li>
<div id="only_settings_backup_type_<?php echo $flg_type;?>" style="display:<?php if( (isset($arrProject['settings']['type_'.$flg_type]['only_settings']) && $arrProject['settings']['type_'.$flg_type]['only_settings']==1) || ( !isset($arrProject['settings']['type_'.$flg_type]['only_settings']) && $flg_type==5 ) ){ ?> block <?php } else { ?>none <?php } ?>;">
	<li>
		<label>Database</label>
		<input type="hidden" name="arrProject[settings][type_<?php echo $flg_type;?>][database]" value="0">
		<input type="checkbox" class="update_dir_check_<?php echo $flg_type;?>" name="arrProject[settings][type_<?php echo $flg_type;?>][database]" value="1" id="check_db"<?php if( (isset($arrProject['settings']['type_'.$flg_type]['database']) && $arrProject['settings']['type_'.$flg_type]['database']==1) || ( !isset($arrProject['settings']['type_'.$flg_type]['database']) && $flg_type==5 ) ){ ?> checked="checked"<?php } ?> >
	</li>
	<li>
		<label>Plugins</label>
		<input type="hidden" name="arrProject[settings][type_<?php echo $flg_type;?>][plugins]" value="0">
		<input type="checkbox" class="update_dir_check_<?php echo $flg_type;?>" name="arrProject[settings][type_<?php echo $flg_type;?>][plugins]" value="1" id="check_plugins"<?php if( (isset($arrProject['settings']['type_'.$flg_type]['plugins']) && $arrProject['settings']['type_'.$flg_type]['plugins']==1) || ( !isset($arrProject['settings']['type_'.$flg_type]['plugins']) && $flg_type==5 ) ){ ?> checked="checked"<?php } ?> >
	</li>
	<li>
		<label>Themes</label>
		<input type="hidden" name="arrProject[settings][type_<?php echo $flg_type;?>][themes]" value="0">
		<input type="checkbox" class="update_dir_check_<?php echo $flg_type;?>" name="arrProject[settings][type_<?php echo $flg_type;?>][themes]" value="1" id="check_thems"<?php if( (isset($arrProject['settings']['type_'.$flg_type]['themes']) && $arrProject['settings']['type_'.$flg_type]['themes']==1) || ( !isset($arrProject['settings']['type_'.$flg_type]['themes']) && $flg_type==5 ) ){ ?> checked="checked"<?php } ?> >
	</li>
	<li>
		<label>Uploads (Media)</label>
		<input type="hidden" name="arrProject[settings][type_<?php echo $flg_type;?>][uploads]" value="0">
		<input type="checkbox" class="update_dir_check_<?php echo $flg_type;?>" name="arrProject[settings][type_<?php echo $flg_type;?>][uploads]" value="1" id="check_uploads"<?php if( (isset($arrProject['settings']['type_'.$flg_type]['uploads']) && $arrProject['settings']['type_'.$flg_type]['uploads']==1) || ( !isset($arrProject['settings']['type_'.$flg_type]['uploads']) && $flg_type==5 ) ){ ?> checked="checked"<?php } ?> >
	</li>
</div>
<li>
	<label>Exclude Folders</label>
	<input type="hidden" name="arrProject[settings][type_<?php echo $flg_type;?>][files_exclude]" value="0">
	<input type="checkbox" class="update_dir_check_<?php echo $flg_type;?>" name="arrProject[settings][type_<?php echo $flg_type;?>][files_exclude]" id="show_block_<?php echo $flg_type;?>_files_exclude" value="1"<?php if( (isset($arrProject['settings']['type_'.$flg_type]['files_exclude']) && $arrProject['settings']['type_'.$flg_type]['files_exclude']==1) ){ ?> checked="checked"<?php } ?> >
</li>
<div id="block_<?php echo $flg_type;?>_files_exclude" class="hidden">
	<li id="block_<?php echo $flg_type;?>_files_exclude_tree"></li>
	<!--li>
		<label>Files filter</label>
		<input type="text" name="arrProject[settings][type_<?php echo $flg_type;?>][files_exclude_filter]" value="<?php if( isset($arrProject['settings']['type_'.$flg_type]['files_exclude_filter']) ) echo $arrProject['settings']['type_'.$flg_type]['files_exclude_filter']?>" />
		<p class="helper"><font color="Red">Note:</font> These two characters are the * and ?. The ? matches 1 of any character except string. The * matches 0 or more of any character except  string. 
		<br/>Filter also supports character classes and negative character classes, using the syntax [] and [^]. It will match any one character inside [] or match any one character that is not in [^]. 
		<br/>You can also use ranges of characters inside the character class by having a starting and ending character with a hyphen in between.  
		<br/>For example, [a-z] will match any letter between a and z, [0-9] will match any (one) number, etc.. 
		<br/>Filter also supports limited alternation with {n1, n2, etc..}. </p>
	</li-->
</div>
<script type="text/javascript">
	//var useFoldersEvents=function(){};
	var useExecuteFolder_<?php echo $flg_type;?>=function(){};
	var useFolderOpen_<?php echo $flg_type;?>=function(){};
	
	$('only_settings_backup_<?php echo $flg_type;?>').addEvent('change',function(){
		if( this.checked ){
			$('only_settings_backup_type_<?php echo $flg_type;?>').setStyle('display','block');
		}else{
			$('only_settings_backup_type_<?php echo $flg_type;?>').setStyle('display','none');
			$$('.archive_dir').each(function(elt){
				if( elt.getNext().getNext('.dir_list') != undefined ){
					elt.getNext().getNext('.dir_list').destroy();
				}
				elt.set('rel','active');
				elt.set('src','<?php echo Xpandbuddy::$baseName;?>skin/archive.png');
				elt.getPrevious().set('value','1');
			});
		}
	});
	
	$('show_block_<?php echo $flg_type;?>_files_exclude').addEvent('click', function(event){
		if( $(this).checked ){
			$('block_<?php echo $flg_type;?>_files_exclude').show();
			if( $('block_<?php echo $flg_type;?>_files_exclude_tree').get( 'html' ) == '' ){
				new Request({
					url: adminAjaxUrl,
					data: "action=get_local_dirs&flg_type=<?php echo $flg_type;?>",
					method: 'post',
					onSuccess: function( responseText ){
						$('block_<?php echo $flg_type;?>_files_exclude_tree').set( 'html', responseText );
						updateCheckers_<?php echo $flg_type;?>();
					}
				}).send();
			}
		}else{
			$('block_<?php echo $flg_type;?>_files_exclude').hide();
		}
	});
	$$('.update_dir_check_<?php echo $flg_type;?>').addEvent('click', function(event){
		if( $('block_<?php echo $flg_type;?>_files_exclude_tree').get( 'html' ) != '' ){
			updateCheckers_<?php echo $flg_type;?>();
		}
		return;
	});
	
	var haveRequest=false;
	
	var updateCheckers_<?php echo $flg_type;?>=function(){
		if( $('only_settings_backup_<?php echo $flg_type;?>').get('checked') == true ){
			$$('.archive_dir').each(function(elt){
				if( !( elt.get('id') == 'this_content' && ($('check_plugins').get('checked') == true || $('check_thems').get('checked') == true || $('check_uploads').get('checked') == true) ) ){
					elt.set('rel','none');
					elt.set('src','<?php echo Xpandbuddy::$baseName;?>skin/other.png');
					if( elt.getNext().getNext('.dir_list') != undefined ){
						elt.getNext().getNext('.dir_list').destroy();
					}
					elt.getPrevious().set('value','1');
				}
			});
			if( $('check_db').get('checked') == true ){
				$('this_db').set('rel','active');
				$('this_db').set('src','<?php echo Xpandbuddy::$baseName;?>skin/archive.png');
				$('this_db').getPrevious().set('value','0');
			}
			if( $('check_plugins').get('checked') == true || $('check_thems').get('checked') == true || $('check_uploads').get('checked') == true ){
				$('this_content').set('rel','active');
				$('this_content').set('src','<?php echo Xpandbuddy::$baseName;?>skin/archive.png');
				$('this_content').getPrevious().set('value','0');
				if( $('this_content').getNext().getNext() == null ){
					if( !haveRequest ){
						new Request({
							url: adminAjaxUrl,
							data: 'dir='+$('this_content').getNext().get('rel')+"&action=get_local_dirs&flg_type=<?php echo $flg_type;?>",
							method: 'post',
							onRequest: function(){
								haveRequest=true;
							},
							onSuccess: function( responseText ){
								if( $('this_content').getParent() != undefined ){
									$('this_content').getParent().set( 'html', $('this_content').getParent().get( 'html' )+responseText );
								}
								updateCheckers_<?php echo $flg_type;?>();
								haveRequest=false;
							},
							onFailure: function(){
								haveRequest=false;
							}
						}).send();
					}else{
						setTimeout(function(){
							updateCheckers_<?php echo $flg_type;?>();
						}, 30);
					}
				}else{
					updateContentCheck();
				}
			}
		}
	};
	
	var updateContentCheck=function(){
		if( $('check_plugins').get('checked') == true && $('this_plugins') != undefined ){
			$('this_plugins').set('rel','active');
			$('this_plugins').set('src','<?php echo Xpandbuddy::$baseName;?>skin/archive.png');
			$('this_plugins').getPrevious().set('value','0');
		}
		if( $('check_thems').get('checked') == true && $('this_thems') != undefined ){
			$('this_thems').set('rel','active');
			$('this_thems').set('src','<?php echo Xpandbuddy::$baseName;?>skin/archive.png');
			$('this_thems').getPrevious().set('value','0');
		}
		if( $('check_uploads').get('checked') == true && $('this_uploads') != undefined ){
			$('this_uploads').set('rel','active');
			$('this_uploads').set('src','<?php echo Xpandbuddy::$baseName;?>skin/archive.png');
			$('this_uploads').getPrevious().set('value','0');
		}
	};
	
	useExecuteFolder_<?php echo $flg_type;?>=function(elt){
		if( elt.get('rel') == 'active' ){
			// deactivate
			elt.set('rel','none');
			elt.set('src','<?php echo Xpandbuddy::$baseName;?>skin/other.png');
			if( elt.getNext().getNext('.dir_list') != undefined ){
				elt.getNext().getNext('.dir_list').destroy();
			}
			elt.getPrevious().set('value','1');
		}else{
			elt.set('rel','active');
			elt.set('src','<?php echo Xpandbuddy::$baseName;?>skin/archive.png');
			elt.getPrevious().set('value','0');
		}
		updateCheckers_<?php echo $flg_type;?>();
	};
	
	useFolderOpen_<?php echo $flg_type;?>=function(elt){
		if( elt.getPrevious().getPrevious().get('value') == '1' ){
			return;
		}
		if( !haveRequest ){
			new Request({
				url: adminAjaxUrl,
				data: 'dir='+elt.get('rel')+"&action=get_local_dirs&flg_type=<?php echo $flg_type;?>",
				method: 'post',
				onRequest: function(){
					haveRequest=true;
				},
				onSuccess: function( responseText ){
					if( elt.getNext() != undefined ){
						elt.getNext().destroy();
					}
					if( elt.getParent() != undefined ){
						elt.getParent().set( 'html', elt.getParent().get( 'html' )+responseText );
					}
					updateCheckers_<?php echo $flg_type;?>();
					haveRequest=false;
				},
				onFailure: function(){
					haveRequest=false;
				}
			}).send();
		}else{
			setTimeout(function(){
				useFolderOpen_<?php echo $flg_type;?>(elt);
			}, 30);
		}
		return;
	};
	
</script>
<?php /*
<?php 
$arrS=@$$settings;
?>
<li>
	<label>Store Your Data Only<br/>(without WordPress files)</label>
	<input type="hidden" name="arrBlog[<?php echo $settings;?>][only_settings]" value="0">
	<input id="only_settings_<?php echo $settings;?>" type="checkbox" name="arrBlog[<?php echo $settings;?>][only_settings]" value="1"<?php if( (isset($arrS['only_settings']) && $arrS['only_settings']==1) ){ ?> checked="checked"<?php } ?> >
	<p class="helper"><font color="Red">Note:</font> Use this option to backup your site or copy your blog settings to a different WordPress installation. Lets you store your themes, plugins, and database data.</p>
</li>

<div id="only_settings_<?php echo $settings;?>_type" style="display:<?php if( (isset($arrS['only_settings']) && $arrS['only_settings']==1) ){ ?> block <?php } else { ?>none <?php } ?>;">
	<li>
		<label>Database</label>
		<input type="hidden" name="arrBlog[<?php echo $settings;?>][database]" value="0">
		<input type="checkbox" name="arrBlog[<?php echo $settings;?>][database]" value="1"<?php if( (isset($arrS['database']) && $arrS['database']==1) ){ ?> checked="checked"<?php } ?> >
	</li>
	<li>
		<label>Plugins</label>
		<input type="hidden" name="arrBlog[<?php echo $settings;?>][plugins]" value="0">
		<input type="checkbox" name="arrBlog[<?php echo $settings;?>][plugins]" value="1"<?php if( (isset($arrS['plugins']) && $arrS['plugins']==1) ){ ?> checked="checked"<?php } ?> >
	</li>
	<li>
		<label>Themes</label>
		<input type="hidden" name="arrBlog[<?php echo $settings;?>][themes]" value="0">
		<input type="checkbox" name="arrBlog[<?php echo $settings;?>][themes]" value="1"<?php if( (isset($arrS['themes']) && $arrS['themes']==1) ){ ?> checked="checked"<?php } ?> >
	</li>
	<li>
		<label>Uploads (Media)</label>
		<input type="hidden" name="arrBlog[<?php echo $settings;?>][uploads]" value="0">
		<input type="checkbox" name="arrBlog[<?php echo $settings;?>][uploads]" value="1"<?php if( (isset($arrS['uploads']) && $arrS['uploads']==1) ){ ?> checked="checked"<?php } ?> >
	</li>
</div>
<script type="text/javascript">
window.addEvent('domready', function() {
	$('only_settings_<?php echo $settings;?>').addEvent('change',function(){
		if( this.checked ){
			$('only_settings_<?php echo $settings;?>_type').setStyle('display','block');
		}else{
			$('only_settings_<?php echo $settings;?>_type').setStyle('display','none');
		}
	});
});
</script>
*/
?>