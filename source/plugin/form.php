<?php
error_reporting(E_ALL|E_STRICT);
ini_set('display_errors', 1);
?>
<script type="text/javascript">
var adminAjaxUrl="<?php echo admin_url( 'admin-ajax.php' ); ?>";
</script>
<div class="wrap">
<h2><?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>Backup<?php }elseif( $_GET['page'] == 'xpandbuddy-clone' ){ ?>Clone<?php } ?></h2>

<form id="project_form" class="wh postbox_form validate" method="post" action="?page=<?php echo $_GET['page']; ?>" enctype="multipart/form-data">
<?php if( isset($error) ){ ?>
<div class="wrap"><span style="color:red">Error: <?php echo $error; ?></span></div>
<?php } ?>
<?php if( isset($action) ){ ?>
<div class="wrap"><span style="color:green"><?php echo $action; ?></span></div>
<?php } ?>

<div id="poststuff">
<div class="postbox-container" style="width:50%;">
<div class="meta-box-sortables" style="padding:5px;">

<div class="postbox">
	<div class="handlediv" title="Click to toggle">
	<br>
	</div>
	<h3 class="hndle"><?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>Backup<?php }else{ ?>Clone<?php } ?> Settings</h3>
	<div class="inside">
	<input type="hidden" name="arrProject[id]" value="<?php if( isset($arrProject['id']) ){ echo $arrProject['id']; } ?>">
	<input type="hidden" name="arrProject[settings][homeurl_hash]" value="<?php echo md5( home_url() ); ?>">
	<input type="hidden" name="arrProject[start]" value="<?php echo time(); ?>">
	<fieldset>
		<ol>
			<?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>
			<li>
				<input type="button" id="check_backup_size" value="Check Server Settings and Site Details" class="button button-primary button-large">
				<img src="<?php echo Xpandbuddy::$baseName; ?>skin/loading.gif" id="loading_check_backup_size" class="loading"/>
				<p class="helper backup_size_show" style="display:none;"><font color="Red">Note:</font><span id="backup_size"></span></p>
			</li>
			<li>
				<label><?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>Backup<?php }else{ ?>Clone<?php } ?> Start Time</label>
				<input type="text" name="arrProject[settings][timer][h]" value="<?php if( isset($arrProject['settings']['timer']['h']) ){ echo $arrProject['settings']['timer']['h']; }else{ echo( date('G', time() ) ); } ?>" size="2" maxlength="2" autocomplete="off" style="width:30px;"> : <input type="text" name="arrProject[settings][timer][m]" value="<?php if( isset($arrProject['settings']['timer']['m']) ){ echo $arrProject['settings']['timer']['m']; }else{ echo( date('i', time() ) ); } ?>" size="2" maxlength="2" autocomplete="off" style="width:30px;">
				<p class="helper"><font color="Red">Note:</font> Set time for your automatic backups. Please note that automatic backups are triggered by WordPress cron.</p>
			</li>
			<?php
				$flg_type='backup';
				include Xpandbuddy::$pathName.'/source/plugin/settings.php';
			}else{
				$flg_type='clone';
				include Xpandbuddy::$pathName.'/source/plugin/settings.php';
			?>
			<li>
				<label>Clone Blog Without Posts</label>
				<input type="hidden" name="arrProject[settings][blog][without_post]" value="0">
				<input type="checkbox" name="arrProject[settings][blog][without_post]" value="1"<?php if( (isset($arrProject['settings']['blog']['without_post']) && $arrProject['settings']['blog']['without_post']==1) ){ ?> checked="checked"<?php } ?> >
			</li>
			<li>
				<label>Clone Blog Without Pages</label>
				<input type="hidden" name="arrProject[settings][blog][without_page]" value="0">
				<input type="checkbox" name="arrProject[settings][blog][without_page]" value="1"<?php if( (isset($arrProject['settings']['blog']['without_page']) && $arrProject['settings']['blog']['without_page']==1) ){ ?> checked="checked"<?php } ?> >
			</li>
			<?php } ?>
			<li <?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>class="hidden"<?php } ?>>
				<label>Dashboard Login <em>*</em></label>
				<input type="text" title="Dashboard Login"  class="required" value="<?php if( isset($arrBlog['dashboad_username']) ){ echo $arrBlog['dashboad_username']; } ?>" name="arrProject[settings][blog][dashboad_username]" >
			</li>
			<li <?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>class="hidden"<?php } ?>>
				<label>Dashboard Password</label>
				<input type="password" title="Dashboard Password"  value="<?php if( isset($arrBlog['dashboad_password']) ){ echo $arrBlog['dashboad_password']; } ?>" name="arrProject[settings][blog][dashboad_password]">
				<p class="helper"><font color="Red">Note:</font> You don't need to provide the password if you do not want to change it.</p>
			</li>
			<li>
				<label>File Type</label>
				<ol>
					<li>
						<label>ZIP</label>
						<input type="radio" name="arrProject[settings][archive_type]" <?php if( (isset($arrProject['settings']['archive_type']) && $arrProject['settings']['archive_type']=='.zip' || !isset($arrProject['settings']['archive_type'])) ){ ?>  checked="checked" <?php } ?> value=".zip" />
					</li>
					<li>
						<label>TAR</label>
						<input type="radio" name="arrProject[settings][archive_type]" value=".tar.gz" <?php if( (isset($arrProject['settings']['archive_type']) && $arrProject['settings']['archive_type']=='.tar.gz') ){ ?>  checked="checked" <?php } ?> />
					</li>
				</ol>
			</li>
		</ol>
	</fieldset>
	</div>
</div>

<?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>
<div class="postbox<?php if(@$arrProject['flg_type'][1]!=1){ ?> closed<?php } ?>">
	<div class="handlediv" title="Click to toggle">
	<br>
	</div>
	<h3 class="hndle"><?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>Backup<?php }else{ ?>Clone<?php } ?> to Local Server</h3>
	<div class="inside">
	<fieldset>
		<ol>
			<li>
				<label>Store <?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>Backup<?php }else{ ?>Clone<?php } ?> on Local Host</label>
				<input type="hidden" name="arrProject[flg_type][1]" value="0" checked />
				<input type="checkbox" class="backup_type" name="arrProject[flg_type][1]" value="1"<?php if(@$arrProject['flg_type'][1]==1){ ?>checked<?php } ?> />
			</li>
			<div class="hidden backup_types backup_type_1">
				<?php $flg_type='1';
				include Xpandbuddy::$pathName.'/source/plugin/settings.php'; ?>
			</div>
			<li class="hidden backup_types backup_type_1">
				<label>Number of <?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>Backups<?php }else{ ?>Clones<?php } ?> to Store</label>
				<input type="number" size="2" name="arrProject[settings][updates_number]" pattern="[1-9]{1,2}" min="1" max="9" value="<?php echo ( isset($arrProject['settings']['updates_number']) )?$arrProject['settings']['updates_number']:3; ?>" />
			</li>
		</ol>
	</fieldset>
	</div>
</div>


<div class="postbox<?php if(@$arrProject['flg_type'][4]!=4){ ?> closed<?php } ?>">
	<div class="handlediv" title="Click to toggle">
	<br>
	</div>
	<h3 class="hndle"><?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>Backup<?php }else{ ?>Clone<?php } ?> to Remote Server</h3>
	<div class="inside">
	<fieldset>
		<ol>
			<li>
				<label>Store <?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>Backup<?php }else{ ?>Clone<?php } ?> on Remote Server</label>
				<input type="hidden" name="arrProject[flg_type][4]" value="0" checked />
				<input type="checkbox" class="backup_type" name="arrProject[flg_type][4]" value="4" <?php if(@$arrProject['flg_type'][4]==4){ ?>checked<?php } ?> />
				<p class="helper"><font color="Red">Note:</font> If <?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>backup<?php }else{ ?>clone<?php } ?> file cannot be sent, it is saved on local host automatically.</p>
			</li>
			<div class="hidden backup_types backup_type_4">
				<?php $flg_type='4';
				include Xpandbuddy::$pathName.'/source/plugin/settings.php'; ?>
			</div>
			<li class="hidden backup_types backup_type_4">
				<label>Host</label>
				<input type="text" id="ftp_host" name="arrProject[settings][host]" value="<?php if( isset($arrProject['settings']['host']) ){ echo $arrProject['settings']['host']; } ?>" />
			</li>
			<li class="hidden backup_types backup_type_4">
				<label>User</label>
				<input type="text" id="ftp_user" name="arrProject[settings][user]" value="<?php if( isset($arrProject['settings']['user']) ){ echo $arrProject['settings']['user']; } ?>" />
			</li>
			<li class="hidden backup_types backup_type_4">
				<label>Password</label>
				<input type="password" id="ftp_pass" name="arrProject[settings][pass]" value="<?php if( isset($arrProject['settings']['pass']) ){ echo $arrProject['settings']['pass']; } ?>" />
			</li>
			<li class="hidden backup_types backup_type_4">
				<input type="button" value="Get Ftp Connection" id="ftp_connect" class="button button-primary button-large" />
				<img src="<?php echo Xpandbuddy::$baseName; ?>skin/loading.gif" id="loading_ftp_connect" class="loading"/>
			</li class="hidden backup_types backup_type_4">
			<dir id="ftp_content" class="hidden backup_types backup_type_4"></dir>
			<li class="hidden backup_types backup_type_4">
				<label>Path</label>
				<input type="text" id="ftp_dir_name" name="arrProject[settings][dir_name]" value="<?php echo (isset( $arrProject['settings']['dir_name'] )) ? $arrProject['settings']['dir_name'] : '/'; ?>" />
			</li>
			<li class="hidden backup_types backup_type_4">
				<label><?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>Backup<?php }else{ ?>Clone<?php } ?> Name</label>
				<input type="text" id="ftp_file_name" name="ftp_file_name" value="" />
				<input type="submit" id="restore_ftp_file" name="submit_ftp_file" value="Restore This <?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>Backup<?php }else{ ?>Clone<?php } ?>" class="button button-primary button-large" style="display:none;" >
			</li>
		</ol>
	</fieldset>
	</div>
</div>


<div class="postbox<?php if(@$arrProject['flg_type'][2]!=2){ ?> closed<?php } ?>">
	<div class="handlediv" title="Click to toggle">
	<br>
	</div>
	<h3 class="hndle"><?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>Backup<?php }else{ ?>Clone<?php } ?> to Dropbox</h3>
	<div class="inside">
	<fieldset>
		<ol>
			<li>
				<label>Upload <?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>Backup<?php }else{ ?>Clone<?php } ?> to Dropbox</label>
				<input type="hidden" name="arrProject[flg_type][2]" value="0" checked />
				<input type="checkbox" class="backup_type" name="arrProject[flg_type][2]" <?php if( $_dropbox64Error ){ ?>disabled<?php }else{ ?> value="2"<?php } if(@$arrProject['flg_type'][2]==2){ ?> checked<?php } ?> />
				<p class="helper"><font color="Red">Note:</font> If <?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>backup<?php }else{ ?>clone<?php } ?> file cannot be sent, it is saved on local host automatically.</p>
				<?php if( $_dropbox64Error ){ ?><p class="helper">The Dropbox SDK uses 64-bit integers, but it looks like we're running on a version of PHP that doesn't support 64-bit integers</p><?php } ?>
			</li>
			<?php if( !$_dropbox64Error ){ ?>
			<div class="hidden backup_types backup_type_2">
				<?php $flg_type='2';
				include Xpandbuddy::$pathName.'/source/plugin/settings.php'; ?>
			</div>
			<li class="hidden backup_types backup_type_2">
				<label>App Key</label>
				<input type="text" name="arrProject[settings][app_key]" id="app_key" value="<?php if( isset($arrProject['settings']['app_key']) ){ echo $arrProject['settings']['app_key']; } ?>" />
			</li>
			<li class="hidden backup_types backup_type_2">
				<label>App Secret</label>
				<input type="text" name="arrProject[settings][app_secret]" id="app_secret" value="<?php if( isset($arrProject['settings']['app_secret']) ){ echo $arrProject['settings']['app_secret']; } ?>" />
			</li>
			<li class="hidden backup_types backup_type_2">
				<input type="button" value="Get Authorization Code" id="get_authorization_code" class="button button-primary button-large" />
				<img src="<?php echo Xpandbuddy::$baseName; ?>skin/loading.gif" id="loading_get_authorization_code" class="loading"/>
			</li>
			<li class="hidden backup_types backup_type_2">
				<label>Authorization Code</label>
				<input type="text" name="arrProject[settings][authorization_code]" id="authorization_code" value="<?php if( isset($arrProject['settings']['authorization_code']) ){ echo $arrProject['settings']['authorization_code']; } ?>" />
			</li>
			<li class="hidden backup_types backup_type_2">
				<input type="button" value="Get Аccess Token" id="get_access_token" class="button button-primary button-large" />
				<img src="<?php echo Xpandbuddy::$baseName; ?>skin/loading.gif" id="loading_get_access_token" class="loading"/>
			</li>
			<li class="hidden backup_types backup_type_2">
				<label>Аccess Token</label>
				<input type="text" name="arrProject[settings][access_token]" id="access_token" value="<?php if( isset($arrProject['settings']['access_token']) ){ echo $arrProject['settings']['access_token']; } ?>" />
			</li>
			<?php } ?>
		</ol>
	</fieldset>
	</div>
</div>

<div class="postbox<?php if(@$arrProject['flg_type'][3]!=3){ ?> closed<?php } ?>">
	<div class="handlediv" title="Click to toggle">
	<br>
	</div>
	<h3 class="hndle"><?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>Backup<?php }else{ ?>Clone<?php } ?> to Google Drive</h3>
	<div class="inside">
	<fieldset>
		<ol>
			<li>
				<label>Upload <?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>Backup<?php }else{ ?>Clone<?php } ?> to Google Drive</label>
				<input type="hidden" name="arrProject[flg_type][3]" value="0" checked />
				<input type="checkbox" class="backup_type" name="arrProject[flg_type][3]" value="3" <?php if(@$arrProject['flg_type'][3]==3){ ?>checked<?php } ?> />
				<p class="helper"><font color="Red">Note:</font> If <?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>backup<?php }else{ ?>clone<?php } ?> file cannot be sent, it is saved on local host automatically.</p>
			</li>
			<div class="hidden backup_types backup_type_3">
				<?php $flg_type='3';
				include Xpandbuddy::$pathName.'/source/plugin/settings.php'; ?>
			</div>
			<li class="hidden backup_types backup_type_3">
				<label>Plugin Redirect URL</label><br/>
				<code><?php echo( admin_url('admin.php?page=xpandbuddy') ); ?></code>
			</li>
			<li class="hidden backup_types backup_type_3">
				<label>Client ID</label>
				<input type="text" name="arrProject[settings][google_key]" id="google_key" value="<?php if( isset($arrProject['settings']['google_key']) ){ echo $arrProject['settings']['google_key']; } ?>" />
			</li>
			<li class="hidden backup_types backup_type_3">
				<label>Client Secret</label>
				<input type="text" name="arrProject[settings][google_secret]" id="google_secret" value="<?php if( isset($arrProject['settings']['google_secret']) ){ echo $arrProject['settings']['google_secret']; } ?>" />
			</li>
			<li class="hidden backup_types backup_type_3">
				<input type="button" value="Get Authorization Code" id="get_google_code" class="button button-primary button-large" />
				<img src="<?php echo Xpandbuddy::$baseName; ?>skin/loading.gif" id="loading_get_google_code" class="loading"/>
			</li>
			<li class="hidden backup_types backup_type_3">
				<label>Authorization Code</label>
				<input type="text" name="arrProject[settings][google_code]" id="google_code" value="<?php if( isset($arrProject['settings']['google_code']) ){ echo $arrProject['settings']['google_code']; } ?>" />
			</li>
			<li class="hidden backup_types backup_type_3">
				<input type="button" value="Get Аccess Tokens" id="get_google_token" class="button button-primary button-large" />
				<img src="<?php echo Xpandbuddy::$baseName; ?>skin/loading.gif" id="loading_get_refresh_token" class="loading"/>
			</li>
			<li class="hidden backup_types backup_type_3">
				<label>Аccess Tokens</label>
				<input type="text" name="arrProject[settings][refresh_token]" id="refresh_token" value="<?php if( isset($arrProject['settings']['refresh_token']) ){ echo $arrProject['settings']['refresh_token']; } ?>" />
			</li>
		</ol>
	</fieldset>
	</div>
</div>


<div class="postbox<?php if(@$arrProject['flg_type'][5]!=5){ ?> closed<?php } ?>">
	<div class="handlediv" title="Click to toggle">
	<br>
	</div>
	<h3 class="hndle"><?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>Backup<?php }else{ ?>Clone<?php } ?> to Email</h3>
	<div class="inside">
	<fieldset>
		<ol>
			<li>
				<label>Send <?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>Backup<?php }else{ ?>Clone<?php } ?> to Email</label>
				<input type="hidden" name="arrProject[flg_type][5]" value="0" checked />
				<input type="checkbox" class="backup_type" name="arrProject[flg_type][5]" value="5" <?php if(@$arrProject['flg_type'][5]==5){ ?>checked<?php } ?> />
				<p class="helper"><font color="Red">Note:</font> You will receive <?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>backup<?php }else{ ?>clone<?php } ?> of all your custom website data to your email but without WordPress engine files. The <?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>backup<?php }else{ ?>clone<?php } ?> can be further restored from the plugin page.
				<br/>If <?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>backup<?php }else{ ?>clone<?php } ?> file cannot be sent, it is saved on local host automatically.</p>
			</li>
			<div class="hidden backup_types backup_type_5">
				<?php $flg_type='5';
				include Xpandbuddy::$pathName.'/source/plugin/settings.php'; ?>
			</div>
			<li class="hidden backup_types backup_type_5">
				<label>Email</label>
				<input type="text" name="arrProject[settings][send_email]" value="<?php if( isset($arrProject['settings']['send_email']) ){ echo $arrProject['settings']['send_email']; } ?>" />
			</li>
		</ol>
	</fieldset>
	</div>
</div>
<?php } ?>

<div class="postbox<?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?> hidden<?php } ?>">
	<div class="handlediv" title="Click to toggle">
	<br>
	</div>
	<h3 class="hndle">Blog Details</h3>
	<div class="inside">
		<fieldset>
			<ol>
				<li>
					<input type="button" id="show_default_settings" value="Show Current Blog's Details" class="button button-primary button-large">
				</li>
				<li>
					<label for="title">Blog Title <em>*</em></label>
					<input type="text" class="required have_current" name="arrProject[settings][blog][title]" <?php if( $_GET['page'] == 'xpandbuddy-backup' ){ echo 'value'; }else{ echo 'value="" default'; } ?>="<?php if( isset($arrBlog['title']) ){ echo $arrBlog['title']; } ?>" id="title" >
				</li>
				<li>
					<label for="domain">Blog URL <em>*</em></label>
					<input type="text" class="required have_current" <?php if( $_GET['page'] == 'xpandbuddy-backup' ){ echo 'value'; }else{ echo 'value="" default'; } ?>="<?php if( isset($arrBlog['url']) ){ echo $arrBlog['url']; } ?>" name="arrProject[settings][blog][url]" id="domain">
					<p  class="helper"><font color="Red">Note:</font> Please provide full URL of the site, where you would like to clone the settings of this blog, including the folder name. i.e. http://www.themexpand.com/blog or http://blog.themexpand.com.</p>
				</li>
				<li>
					<label for="path">Blog Full Path <em>*</em></label>
					<input type="text" class="required have_current" <?php if( $_GET['page'] == 'xpandbuddy-backup' ){ echo 'value'; }else{ echo 'value="" default'; } ?>="<?php if( isset($arrBlog['path']) ){ echo str_replace( array( '\\\\', '\\\\' ), '\\', $arrBlog['path'] ); } ?>" name="arrProject[settings][blog][path]" id="path">
					<p  class="helper"><font color="Red">Note:</font> Please provide full path of the site, where you would like to clone the settings of this blog, including the folder name. i.e. /home/user/public_html/site_name/folder_name.</p>
				</li>
			</ol>
		</fieldset>	
	</div>
</div>

<div class="postbox<?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?> hidden<?php } ?>">
	<div class="handlediv" title="Click to toggle">
	<br>
	</div>
	<h3 class="hndle">Database Details</h3>
	<div class="inside">
		<fieldset>
			
			<ol>
				<li>
					<p class="helper"><font color="Red">Note:</font> Provide the database details of the site, where you would like to install the clone. Please note that the database is not created automatically. You need to create it manually in your Cpanel, or use the button below to create it from here.</p>
				</li>
				<li>
					<p class="hide-if-no-js"><a title="Create data base" onclick="return false;" href="?page=<?php echo $_GET['page']; ?>&runCpanel=1#&TB_iframe=1&width=640&height=322" class="button-primary thickbox add-media">Create Database</a></p>
				</li>
				
				<?php if( is_multisite() && BLOG_ID_CURRENT_SITE != get_current_blog_id() ){ ?>
				<li>
					<input type="hidden" name="arrProject[settings][blog][flg_mu2single]" value="0" checked >
					<input type="checkbox" name="arrProject[settings][blog][flg_mu2single]" id="flg_mu2single" value="1"<?php if( isset( $arrProject['settings']['blog']['flg_mu2single'] ) ){ ?> checked<?php } ?> >&nbsp;Convert MU blog to Single blog<br/><br/>
				</li>
				<div <?php if( !isset($arrProject['settings']['blog']['flg_mu2single']) || $arrProject['settings']['blog']['flg_mu2single']==0 ){ ?>style="display:none"<?php } ?> id="mu2single_block">
				<?php } ?>
				<li>
					<label>Host Name <em>*</em></label>
					<input type="text" title="Host Name" class="required have_current" <?php if( $_GET['page'] == 'xpandbuddy-backup' ){ echo 'value'; }else{ echo 'value="" default'; } ?>="<?php if( isset($arrBlog) ){ echo $arrBlog['db_host']; } ?>" name="arrProject[settings][blog][db_host]" id="db_host" >
					<p class="helper"><font color="Red">Note:</font> Enter host name like "localhost" or "178.124.1.9". For most server setups, host name is "localhost".</p>
				</li>
				<li>
					<label>Database Name <em>*</em></label>
					<input type="text" title="Database Name" class="required have_current" <?php if( $_GET['page'] == 'xpandbuddy-backup' ){ echo 'value'; }else{ echo 'value="" default'; } ?>="<?php if( isset($arrBlog) ){ echo $arrBlog['db_name']; } ?>" name="arrProject[settings][blog][db_name]" id="db_name" >
				</li>
				<li>
					<label>Database User Name <em>*</em></label>
					<input type="text" class="required have_current" title="Database User Name" class="require" <?php if( $_GET['page'] == 'xpandbuddy-backup' ){ echo 'value'; }else{ echo 'value="" default'; } ?>="<?php if( isset($arrBlog) ){ echo $arrBlog['db_username']; } ?>" name="arrProject[settings][blog][db_username]" id="db_user" >
				</li>
				
				<li>
					<label>Database Password <em>*</em></label>
					<input type="password" title="Database Password" class="required have_current" <?php if( $_GET['page'] == 'xpandbuddy-backup' ){ echo 'value'; }else{ echo 'value="" default'; } ?>="<?php if( isset($arrBlog) ){ echo $arrBlog['db_password']; } ?>" name="arrProject[settings][blog][db_password]" id="db_pass" style="width:200px;" >
					<input type="checkbox" id="show_password" style="margin: 4px 0 !important;" >&nbsp;Show Password
				</li>
				
				<?php if( is_multisite() && BLOG_ID_CURRENT_SITE != get_current_blog_id() ){ ?></div><?php } ?>

				<li>
					<label>Table Prefix</label>
					<input type="text" class="have_current" name="arrProject[settings][blog][db_tableprefix]" <?php if( $_GET['page'] == 'xpandbuddy-backup' ){ echo 'value'; }else{ echo 'value="" default'; } ?>="<?php if( isset($arrBlog) ){ echo $arrBlog['db_tableprefix']; } ?>" >
				</li>
			</ol>

		</fieldset>
	</div>
</div>
<p class="submit">
	<?php if( is_multisite() && is_super_admin() ){ ?>
	<input type="checkbox" name="activate_for_miltiusers" id="activate_multiusers"<?php if( isset( $options['flg_active_miltisite'] ) && $options['flg_active_miltisite'] == true ){ ?> checked<?php } ?> >&nbsp;Activate Plugin for Multisite Admins<br/><br/>
	<?php } ?>
	<?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>
	<input type="submit" name="submit_save" value="Save Settings"  class="button button-primary button-large">
	<?php } ?>
	<input type="button" id="submit_clone_button" value="<?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>Backup<?php }else{ ?>Clone<?php } ?>"  class="button button-primary button-large">
	<input type="button" id="submit_clone_show_log" value="Show Log"  class="button button-primary button-large" style="display:none;">
		<img src="<?php echo Xpandbuddy::$baseName; ?>skin/loading.gif" id="loading_submit_clone" class="loading" />
		<p class="helper submit_clone_show" style="display:none;">
			<font color="Red">Status (do not refresh the page, otherwise the process will be stopped):</font>
			<br/><span id="submit_clone_message"></span>
			<br/><div id="submit_clone_log" style="display:none;"></div>
		</p>
</p>

</div>
</div>


<div class="postbox-container" style="width:50%;">
<div class="meta-box-sortables" style="padding:5px;">

<div class="postbox">
	<div class="handlediv" title="Click to toggle">
	<br>
	</div>
	<h3 class="hndle ui-sortable-handle"><span><?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>Backup<?php }else{ ?>Clone<?php } ?> Manager</span></h3>
	<div class="inside">
		<fieldset>
			<table class="wp-list-table widefat fixed" cellspacing="0">
				<thead>
					<tr>
						<th ><span><?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>Backups<?php }else{ ?>Clones<?php } ?></span></th>
						<th width="100px"><span>Status</span></th>
						<th width="170px"><span>Created</span></th>
					</tr>
				</thead>
				<tbody id="the-list">
				<?php 
				$_counter=0;
				if( !empty( $arrList ) ){
					$_homeUrlHash=str_replace( array( "http://", "https://", ".", "/" ), array( "","","_","_" ), home_url() ).'-';
					foreach( $arrList as $project ){
						if( $_GET['page'] == 'xpandbuddy-backup' && isset( $project['settings'] ) ){ ?>
						<tr id="project_block_<?php echo $project['id']; ?>">
							<th colspan="2">
						<?php 
							$_counter++;
							$_location=array();
							$_flgOnlyToLoaclHost=false;
							foreach( $project['flg_type'] as $_type ){
								switch( $_type ){
									case 1:$_location[]=' local host';$_flgOnlyToLoaclHost=true;break;
									case 2:$_location[]=' dropbox';$_flgOnlyToLoaclHost=false;break;
									case 3:$_location[]=' google drive';$_flgOnlyToLoaclHost=false;break;
									case 4:$_location[]=' ftp';$_flgOnlyToLoaclHost=false;break;
									case 5:$_location[]=' email';$_flgOnlyToLoaclHost=false;break;
								}
								if( !empty( $_location ) ){
									echo "Backup to ".implode(' &', $_location);
								}else{
									echo "(Custom settings";
									break;
								}
								if( !empty( $_location ) ){
									echo ", every ";
									switch( $project['settings']['type_'.$_type]['every'] ){
										case 1:echo ' day';break;
										case 2:echo ' week';break;
										case 3:echo ' month';break;
									}
								}
							}
							if( !empty( $_location ) ){
								if( isset( $project['log'] ) ){
									$_stepCounter=0;
									if( isset( $project['log']['arrFiles'] ) ){
										$_stepCounter=@$project['log']['arrAllFilesCount']+5-count( $project['log']['arrFiles'] )-$project['log']['step'] ;
									}
									echo ', <span id="run_date_'.$project['id'].'">run the backup process: <span class="table_backup_process" rel="'.$project['id'].'">'.round( $_stepCounter*100/(@$project['log']['arrAllFilesCount']+5), 2 ).'</span>%</span>';
								}else{
									echo ', <span id="run_date_'.$project['id'].'">next backup on '.date( "m/d/Y H:i", $project['start'] ).'</span>';
								}
							}
							echo "<br>";
							if( isset($project['files']) && count( $project['files'] ) > 0 ){
								echo "<a href='#review' class='file_list_trigger' rel='".$project['id']."'>View Backups</a> | ";
							}
							echo "<a href='?page=".$_GET['page']."&id=".$project['id']."'>Edit</a> | ";
							echo "<a href='?page=".$_GET['page']."&del=".$project['id']."'>Delete</a>";
							if( ( !isset( $project['log'] ) || empty( $project['log'] ) ) && !empty( $_location ) ){ ?>
							<span id="test_run_box_<?php echo $project['id']; ?>" >&nbsp;|&nbsp;<a href="#" rel="<?php echo $project['id']; ?>" class="test_run">Create Backup Now</a></span>
							<?php } ?>
							</th>
							<th><?php echo date( "Y-m-d H:i:s", $project['edited'] ); ?></th>
						</tr>
						<?php 
						}
						if( isset($project['files']) ){
							krsort( $project['files'] );
							foreach( $project['files'] as $file ){
								if( strpos( $file['file'], $_homeUrlHash ) === 0 && $_GET['page'] == 'xpandbuddy-clone' ){
									continue;
								}
								if( strpos( $file['file'], $_homeUrlHash ) === false && $_GET['page'] == 'xpandbuddy-backup' ){
									continue;
								}
								$_strProjectId=$_strProjectBlock='';
								if( isset( $project['id'] ) && !empty( $project['id'] ) ){
									$_strProjectId='&project='.$project['id'];
								?>
								<tr rel="<?php echo $file['file']; ?>" class="file_block_<?php echo $project['id']; if (isset( $project['id'] ) ) { ?> hidden<?php } ?>">
									<th>
										<a href="<?php echo $file['link']; ?>" target="_blank"><?php echo $file['file']; ?></a>
										<br>
										<?php if( strpos( $file['file'], $_homeUrlHash ) === 0 && $_GET['page'] == 'xpandbuddy-backup' ){ ?>
										<a href="<?php echo htmlentities( '?page='.$_GET['page'].$_strProjectId.'&restore='.urlencode( $file['file'] ) ); ?>">Restore</a> | 
										<?php } ?>
										<a href="<?php echo htmlentities( '?page='.$_GET['page'].$_strProjectId.'&remove='.urlencode( $file['file'] ) ); ?>">Delete</a>
										<?php 
										if( $file['flg_status'] === 'Failed' ){
											foreach( $project['flg_type'] as $_typeId => $_type ){
												if( $_typeId != 1 && $_typeId == $_type ){
													switch( $_type ){
														case 2:$_location='dropbox';break;
														case 3:$_location='google drive';break;
														case 4:$_location='ftp';break;
														case 5:$_location='email';break;
													} ?>
													| <a class="file_sender" href="<?php echo htmlentities( 'send='.urlencode( $file['file'] ).$_strProjectId."&type=".$_type ); ?>">Send to <?php echo $_location; ?></a>
													<img src="<?php echo Xpandbuddy::$baseName; ?>skin/loading.gif" class="loading" /><span class="hidden">Log</span>
												<?php }
											}
										} ?>
									</th>
									<th><?php echo $file['flg_status']; ?></th>
									<th><?php echo date( "Y-m-d H:i:s", $file['date'] ); ?></th>
								</tr>
							
								<?php
								}
							}
						}
					}
				}else{ ?>
					<tr class="alternate author-self status-publish format-default iedit" valign="top">
						<td colspan="3"><?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>No Backup Files<?php }else{ ?>No Clone Files<?php } ?></td>
					</tr><?php
				} ?>
				</tbody>
			</table>
			<?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>
			<ol>
				<li>
					<label>Upload &amp; Restore Backup File</label>
					<input type="hidden" name="MAX_FILE_SIZE" id="max_file_size" value="<?php echo $max_upload_size; ?>" />
					<input type="file" id="select_file" name="file" <?php if( ini_get('file_uploads') != '1' ){ echo 'disabled="disabled"'; } ?> ><br/>
					<input type="submit" style="display:none;" id="button_file" name="submit_file" value="Restore Backup" class="button button-primary button-large">
					<p class="helper"><font color="Red">Note:</font> Your maximum upload file size is <?php echo  round( $max_upload_size/1048576, 1); ?> Mb. <?php if( ini_get('file_uploads') != '1' ){ echo "Change file_uploads status in php.ini."; } ?><span id="error_max_file_size"></span>
					<br/>If your backup file exceeds this limit, you can upload it using FTP client to "wp-backups" folder on your server. The backup can be further restored from the plugin page.</p>
				</li>
			</ol>
			<?php } ?>
		</fieldset>
	</div>
</div>

</div>
</div>

</div>
</form>
<script type="text/javascript">
var adminAjaxUrl="<?php echo admin_url( 'admin-ajax.php' ); ?>";
window.addEvent('domready', function(){
	$$('.backup_type').each( function(elt){
		elt.addEvent('change',function(){
			if( $(this).checked ){
				$$('.backup_type_'+$(this).value).setStyle('display','block');
			}else{
				$$('.backup_type_'+$(this).value).setStyle('display','none');
			}
		});
		if( elt.checked ){
			$$('.backup_type_'+elt.value).setStyle('display','block');
		}
	});
	<?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>
	$('select_file').addEvent('change',function(){
		$('error_max_file_size').set('html','');
		if( this.files[0].size < parseInt( $('max_file_size').value ) ){
			$('button_file').show('inline');
		}else{
			$('button_file').hide();
			$('error_max_file_size').set('html',' Your upload file size is '+(this.files[0].size/1048576).toFixed(1)+' Mb. Check with your host to increase the upload file size limit.');
		}
	});
	<?php } ?>
	<?php if( is_multisite() && is_super_admin() ){ ?>
	$('activate_multiusers').addEvent('change', function(event){
		new Request({
			url: adminAjaxUrl,
			data: "&action=activate_multiusers&checked="+event.target.checked,
			method: 'post',
			onFailure: function(){
				alert( 'Not able to establish a connection to the API. Please check your application details and try later.');
			}
		}).send();
	});
	<?php } ?>
	<?php if( is_multisite() && BLOG_ID_CURRENT_SITE != get_current_blog_id() ){ ?>
	$('flg_mu2single').addEvent('change', function(event){
		if( event.target.checked ){
			$('mu2single_block').show();
		}else{
			$('mu2single_block').hide();
		}
	});
	<?php } ?>
	<?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>
	$('check_backup_size').addEvent('click', function(event){
		event||event.stop();
		new Request.JSON({
			url: adminAjaxUrl,
			data: $('project_form').toQueryString()+"&action=check_backup_size",
			method: 'post',
			onRequest: function(){
				$('loading_check_backup_size').show('inline');
				$('backup_size').set( 'html', '' );
				$$('.backup_size_show').hide();
			},
			onSuccess: function( responseJson , responseText ){
				if( typeof responseJson != 'object' ){
					$('backup_size').set( 'html', "Check backup size Error: "+responseText );
					$$('.backup_size_show').show();
				}
				var stringAfter='';
				if( responseJson.flg_function_exist != true ){
					stringAfter=stringAfter+'<br/>Plugin functions is not exist on host. Check disable_functions option in php.ini file. Please contact your host to get the issue resolved.';
				}
				if( responseJson.flg_access != true ){
					stringAfter=stringAfter+'<br/>The plugin could not work successfully because of your host server settings. Low access to wp-backups path. Please contact your host to get the issue resolved.';
				}
				if( responseJson.flg_timelimite != true ){
					stringAfter=stringAfter+'<br/>The plugin could not work correct. Very small time for code processing, need more that 5 seconds. Please contact your host to get the issue resolved.';
				}
				if( !isNaN( parseInt( responseJson.backup_size ) ) ){
					$('backup_size').set( 'html', " Your website size is "+(responseJson.backup_size/1048576).toFixed(1)+" Mb. "+stringAfter );
					$$('.backup_size_show').show();
				}
				$('loading_check_backup_size').hide();
			},
			onFailure: function(){
				alert( 'Not able to establish a connection to the API. Please check your application details and try later.');
				$('loading_check_backup_size').hide();
			}
		}).send();
	});
	<?php } ?>
	var lastSendedData='';
	
	var submitBackup=function( submitData ){
		new Request.JSON({
			url: adminAjaxUrl,
			data: submitData,
			method: 'post',
			onSuccess: function( responseJSON, responseText ){
				if( responseJSON.message!=undefined ){
					$('submit_clone_log').set( 'html', $('submit_clone_log').get( 'html' )+"<br/>"+responseJSON.message );
				}
				if( responseJSON.counters!=undefined ){
					$('submit_clone_message').set( 'html', "<?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>Backup<?php }else{ ?>Clone<?php } ?> Progress: "+( ((responseJSON.counters.now)*100/responseJSON.counters.all).round(3) )+"%" );
				}
				if( responseJSON.file!=undefined && responseJSON.counters.now >= responseJSON.counters.all ){
					$('submit_clone_message').set( 'html', "<?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>Backup<?php }else{ ?>Clone<?php } ?> Completed: <a href='"+responseJSON.file.link+"' target='_blank'>"+responseJSON.file.name+"</a>" );
				}
				if( responseJSON.error!=undefined ){
					stopSubmitions=true;
				}
				if( responseJSON.logcode !=undefined && !stopSubmitions ){
					lastSendedData=$('project_form').toQueryString()+'&action=submit_clone&user_run=true&logcode='+responseJSON.logcode;
					submitBackup( lastSendedData );
				}else{
					$('submit_clone_button').value='<?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>Backup<?php }else{ ?>Clone<?php } ?>';
					$('loading_submit_clone').hide();
				}
			},
			onError: function(text, error){
				if( lastSendedData != '' ){
					setTimeout(function(){submitBackup( lastSendedData );lastSendedData=''}, 5000);
				}else{
					if( typeof( text ) != 'object' ){
						$('submit_clone_log').set( 'html', $('submit_clone_log').get( 'html' )+"<br/>"+text+"<br/>"+error );
						$('submit_clone_message').set( 'html', "No process in activated actions!" );

						$('submit_clone_button').value='<?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>Backup<?php }else{ ?>Clone<?php } ?>';
						$('loading_submit_clone').hide();
						return;
					}
				}
			},
			onFailure: function(){
				if( lastSendedData != '' ){
					setTimeout(function(){submitBackup( lastSendedData );lastSendedData=''}, 3000);
				}else{
					$('submit_clone_message').set( 'html',"Backup request error! Check site." );
					$('submit_clone_button').value='<?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>Backup<?php }else{ ?>Clone<?php } ?>';
					$('loading_submit_clone').hide();
				}
			}
		}).send();
	};
	


	var lastRunData='';
	var rareId;
	var rareLogcode='';
	
	var submitRun=function( id ){
		new Request.JSON({
			url: adminAjaxUrl,
			data: "action=submit_clone&project="+id+rareLogcode,
			method: 'post',
			onSuccess: function( responseJSON, responseText ){
				if( $$('.table_backup_process')[rareId] == undefined ){
					runRandomlyBackup();
					return;
				}
				var projectId=$$('.table_backup_process')[rareId].get('rel');
				if( responseJSON.status != undefined && responseJSON.status == 'sended_all' ){
					$( 'run_date_'+projectId ).set('html', responseJSON.message);
					rareId=undefined;
					rareLogcode='';
					runRandomlyBackup();
					return;
				}
				if( responseJSON.logcode == undefined ){
					$( 'run_date_'+projectId ).set('html', 'have backup problems!');
					rareId=undefined;
					rareLogcode='';
					runRandomlyBackup();
					return;
				}
				if( responseJSON.file != undefined ){
					if( $$('tr[rel="'+responseJSON.file.name+'"]').length>0 ){
						$$('tr[rel="'+responseJSON.file.name+'"]').destroy();
					}
					new Element(
						'tr',{
							'rel':responseJSON.file.name,
							'html':'<th><a href="'+responseJSON.file.link+'" target="_blank">'+responseJSON.file.name+'</a><br><a href="?page=xpandbuddy-backup&project='+projectId+'&remove='+responseJSON.file.name+'">Delete</a> | <a href="?page=xpandbuddy-backup&project='+projectId+'&restore='+responseJSON.file.name+'">Restore</a></th><th>Completed</th><th>'+responseJSON.file.date+'</th>'
						}
					).inject($( 'project_block_'+projectId ), 'after');
					$$('.table_backup_process')[rareId].set( 'html', 'backup creation completed!' );
				}
				if( responseJSON.counters != undefined && $$('.table_backup_process')[rareId] != undefined ){
					$$('.table_backup_process')[rareId].set( 'html', ( ((responseJSON.counters.now)*100/responseJSON.counters.all).round(2) ) );
				}else{
					if( responseJSON.status != undefined && ( responseJSON.status == 'sended' || responseJSON.status === true )){
						$( 'run_date_'+projectId ).set( 'html', 'preparation for sending <span class="table_backup_process" rel="'+projectId+'"></span>' );
					}
				}
				if( responseJSON.return != undefined && $$('.table_backup_process')[rareId] != undefined ){
					$( 'run_date_'+projectId ).set( 'html', 'sended <span class="table_backup_process" rel="'+projectId+'">'+( (responseJSON.return.range_start*100/responseJSON.return.file_size).round(3) )+"</span>%" );
				}
				
				rareLogcode="&logcode="+responseJSON.logcode;
				submitRun( id );
				return;
			},
			onError: function(text, error){
				if( lastRunData != '' ){
					setTimeout(function(){submitRun( lastRunData );lastRunData=''}, 5000);
				}else{
					runRandomlyBackup();
				}
			},
			onFailure: function(){
				if( lastRunData != '' ){
					setTimeout(function(){submitRun( lastRunData );lastRunData=''}, 5000);
				}else{
					runRandomlyBackup();
				}
			}
		}).send();
	};

	$('show_default_settings').addEvent('click', function(event){
		event||event.stop();
		$$('.have_current').each( function( elt, key ){
			$$(elt).set('value', $$(elt).get('default') );
		});
	});

	$('show_password').addEvent('change', function(event){
		event||event.stop();
		if( event.target.checked ){
			$('db_pass').set('type', 'text');
		}else{
			$('db_pass').set('type', 'password');
		}
	});

	$$('.test_run').addEvent('click', function(event){
		event||event.stop();
		new Request({
			url: adminAjaxUrl,
			data: "action=set_start_date&project="+event.target.get('rel'),
			method: 'post',
			onSuccess: function( responseText ){
				if( responseText != '' ){
					$('test_run_box_'+event.target.get('rel')).hide();
					$( 'run_date_'+event.target.get('rel') ).set('html', 'run the backup process: <span class="table_backup_process" rel="'+event.target.get('rel')+'">0</span>%');
					$$('.table_backup_process').each(function(elt, key){
						if( elt.get('rel') == event.target.get('rel') ){
							rareId=key;
						}
					});
					rareLogcode="&logcode="+responseText;
					submitRun( $$('.table_backup_process')[rareId].get('rel') );
				}
			}
		}).send();
		return false;
	});
	
	var runRandomlyBackup = function(){
		if( $$('.table_backup_process').length != 0 ){
			if( rareId == undefined ){
				rareId=Math.floor( Math.random() * $$('.table_backup_process').length );
			}
			submitRun( $$('.table_backup_process')[rareId].get('rel') );
		}
	}
	
	runRandomlyBackup();

	var stopSubmitions=false;
	
	$('submit_clone_show_log').addEvent('click', function(event){
		event||event.stop();
		if( $(this).get('value') == 'Show Log'){
			$(this).value="Hide Log";
			$('submit_clone_log').show();
		}else{
			$(this).value="Show Log";
			$('submit_clone_log').hide();
		}
	});

	var sendBackup=function(element, action){
		var elementLogger=element.getNext().getNext();
		var elementSendIcon=element.getNext();
		new Request.JSON({
			url: adminAjaxUrl,
			data: action+'&action=send_backup',
			method: 'post',
			onSuccess: function( responseJSON, responseText ){
				if( responseJSON.status!=undefined ){
					if( responseJSON.status=='sended' ){
						location.reload();
						return true;
					}
					if( responseJSON.status==true ){
						responseJSON.status='tnx';
						elementLogger.set('html', 'Sended '+( (responseJSON.return.range_start*100/responseJSON.return.file_size).round(3) )+"%");
						sendBackup( element, element.get('href')+'&'+decodeURIComponent( jQuery.param(responseJSON) ) );
					}
					if( responseJSON.status==false ){
						elementLogger.set('html',responseJSON.error);
						elementSendIcon.hide();
						element.show('inline');
					}
				}
			},
			onError: function(text, error){
				elementSendIcon.hide();
				element.show('inline');
			},
			onFailure: function( xhr ){
				elementSendIcon.hide();
				element.show('inline');
			}
		}).send();
	};
	
	$$('.file_list_trigger').addEvent('click', function(event){
		event||event.stop();
		$$('.file_block_'+event.target.get('rel')).each(function(elt){
			if( elt.hasClass( "hidden" ) ){
				elt.removeClass( "hidden" );
			}else{
				elt.addClass( "hidden" );
			}
		});
	});
	
	$$('.file_sender').addEvent('click', function(event){
		event||event.stop();
		stopSubmitions=true;
		$(this).getNext().show('inline');
		$(this).getNext().getNext().show('inline');
		$(this).hide();
		$(this).getNext().getNext().set('html','');
		sendBackup( $(this), $(this).get('href') );
		return false;
	});
	
	$('submit_clone_button').addEvent('click', function(event){
		event||event.stop();
		if( $(this).get('value') == '<?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>Backup<?php }else{ ?>Clone<?php } ?>'){
			$(this).value="Stop";
			$('submit_clone_message').set( 'html', 'Process Started.' );
			$('loading_submit_clone').show('inline');
			$$('.submit_clone_show').show();
			$('submit_clone_show_log').show('inline');
			$('submit_clone_log').set( 'html', '' );
			submitBackup( $('project_form').toQueryString()+'&action=submit_clone&user_run=true' );
			stopSubmitions=false;
		}else{
			$(this).value='<?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>Backup<?php }else{ ?>Clone<?php } ?>';
			$('loading_submit_clone').hide();
			$('submit_clone_show_log').hide();
			stopSubmitions=true;
		}
	});
	<?php if( $_GET['page'] == 'xpandbuddy-backup' ){ ?>
	var opentFtp=function(event){
		event||event.stop();
		var ftpDirValue='';
		if( $('ftp_dir_name')!=null )
			ftpDirValue=$('ftp_dir_name').value;
		if( $('ftp_host').value=='' || $('ftp_user').value=='' || $('ftp_pass').value=='' ){
			$('ftp_content').set( "html", "" );
			return false;
		}
		new Request({
			url: adminAjaxUrl,
			data:{
				action: 'ftp_connect',
				host: $('ftp_host').value,
				user: $('ftp_user').value,
				pass: $('ftp_pass').value,
				dir: ftpDirValue
			},
			method: 'post',
			onRequest: function(){
				$('loading_ftp_connect').show('inline');
				$('restore_ftp_file').hide();
				$('ftp_file_name').value='';
			},
			onSuccess: function( responseText ){
				$('ftp_content').set( "html", '' );
				if( responseText != '' ){
					if( responseText == 0 ){
						$('ftp_content').set( "html", "Sorry, your ftp connection is broken!" );
					}else{
						$('ftp_content').set( "html", responseText );
						$$('.ftp_open_dir').addEvent('click', function(e){
							e||e.stop();
							if( $('ftp_dir_name')!=null ){
								if( $('ftp_dir_name').value=="/" ){
									$('ftp_dir_name').value=$('ftp_dir_name').value+e.target.get('html');
								}else{
									$('ftp_dir_name').value=$('ftp_dir_name').value+'/'+e.target.get('html');
								}
							}
							opentFtp( $('ftp_connect') );
							return false;
						});
						$$('.ftp_use_file').addEvent('click', function(e){
							e||e.stop();
							$('ftp_file_name').value=e.target.get('html');
							$('restore_ftp_file').show();
							return false;
						});
						$$('.root_dir').addEvent('click', function(e){
							e||e.stop();
							if( $('ftp_dir_name')!=null ){
								$('ftp_dir_name').value=e.target.get('rel');
							}
							opentFtp( $('ftp_connect') );
							return false;
						});
						$$('.ftp_other_file').addEvent('click', function(e){e||e.stop();return false;});
					}
				}else{
					alert( 'Sorry, your ftp connection is broken!');
				}
				if( $('ftp_dir_name_load') != undefined ){
					$('ftp_dir_name').value=$('ftp_dir_name_load').value;
				}
				$('loading_ftp_connect').hide();
			},
			onFailure: function(){
				alert( 'Not able to establish a connection to the API. Please check your application details and try later.');
				$('loading_ftp_connect').hide();
			}
		}).send();
	};
	
	$('ftp_connect').addEvent('click', opentFtp);
	<?php if( !$_dropbox64Error ){ ?>
	$('get_authorization_code').addEvent('click', function(event){
		event||event.stop();
		new Request({
			url: adminAjaxUrl,
			data:{
				action: 'get_authorization_code',
				app_key: $('app_key').value,
				app_secret: $('app_secret').value
			},
			method: 'post',
			onRequest: function(){
				$('loading_get_authorization_code').show('inline');
			},
			onSuccess: function( responseText ){
				if( responseText != '' ){
					window.open( responseText,"_blank" );
				}else{
					alert( 'Not able to establish a connection to the API. Please check your application details and try later.');
				}
				$('loading_get_authorization_code').hide();
			},
			onFailure: function(){
				alert( 'Not able to establish a connection to the API. Please check your application details and try later.');
				$('loading_get_authorization_code').hide();
			}
		}).send();
	});

	$('get_access_token').addEvent('click', function(event){
		event||event.stop();
		new Request({
			url: adminAjaxUrl,
			data:{
				action: 'get_access_token',
				app_key: $('app_key').value,
				app_secret: $('app_secret').value,
				authorization_code: $('authorization_code').value
			},
			method: 'post',
			onRequest: function(){
				$('loading_get_access_token').show('inline');
			},
			onSuccess: function( responseText ){
				$('access_token').set('value', responseText);
				$('loading_get_access_token').hide();
			},
			onFailure: function(){
				alert( 'Not able to establish a connection to the API. Please check your application details and try later.');
				$('loading_get_access_token').hide();
			}
		}).send();
	});
	<?php } ?>
	$('get_google_token').addEvent('click', function(event){
		event||event.stop();
		new Request({
			url: adminAjaxUrl,
			data:{
				action: 'get_google_token',
				app_key: $('google_key').value,
				app_secret: $('google_secret').value,
				app_code: $('google_code').value
			},
			method: 'post',
			onRequest: function(){
				$('loading_get_refresh_token').show('inline');
			},
			onSuccess: function( responseText ){
				$('refresh_token').set('value', responseText);
				$('loading_get_refresh_token').hide();
			},
			onFailure: function(){
				alert( 'Not able to establish a connection to the API. Please check your application details and try later.');
				$('loading_get_refresh_token').hide();
			}
		}).send();
	});

	$('get_google_code').addEvent('click', function(event){
		event||event.stop();
		new Request({
			url: adminAjaxUrl,
			data:{
				action: 'get_google_code',
				app_key: $('google_key').value,
				app_secret: $('google_secret').value
			},
			method: 'post',
			onRequest: function(){
				$('loading_get_google_code').show('inline');
			},
			onSuccess: function( responseText ){
				if( responseText != '' ){
					window.open( responseText,"_blank" );
				}else{
					alert( 'Not able to establish a connection to the API. Please check your application details and try later.');
				}
				$('loading_get_google_code').hide();
			},
			onFailure: function(){
				alert( 'Not able to establish a connection to the API. Please check your application details and try later.');
				$('loading_get_google_code').hide();
			}
		}).send();
	});
	<?php } ?>
});
var CpanelDatabaseResult = function(hash){
	$('db_name').value = hash.db;
	$('db_user').value = hash.user;
	$('db_pass').value = hash.pass;
}
</script>
</div>