<!DOCTYPE html>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="<?php echo $pluginUrl; ?>skin/_css/plugin.css">
	<link rel="stylesheet" type="text/css" href="<?php echo $pluginUrl; ?>skin/_css/tips.css">
	<script type="text/javascript" src="<?php echo $pluginUrl; ?>skin/_js/mootools.js"></script>
	<script type="text/javascript" src="<?php echo $pluginUrl; ?>skin/_js/validator/validator.js"></script>
	<link rel="stylesheet" type="text/css" href="<?php echo $pluginUrl; ?>skin/_js/validator/style.css">
</head>
<body style="padding:0px;">
	<div>
		<?php if( isset($error) ){?>
		<div style="font-color:red;padding:10px;">
			<?php if( $error=='001' ||  $error=='002' ){?>
				Sorry, the database was not created. Please make sure all provided details are correct. Check with your host about the issue.
			<?php }elseif(!empty($error)) echo "Sorry, the database was not created. ".$error; ?>
		</div>
		<?php } ?>
		<?php if( !empty($result) ){?>
		<div style="font-color:green;padding:10px;">
			Database Created Successfully. <br> <b>Username privileges added.</b><br>username: <?php echo $result['user'] ?>, database: <?php echo $result['db'] ?>
		</div>
		<?php } ?>
	</div>
	<form class="validate postbox_form" action="?page=xpandbuddy&runCpanel=1" method="POST">
		<fieldset>
			<legend>cPanel info</legend>
			<ol>
				<li>
					<label>Domain name</label>
					<input type="text" name="arrCpanel[host]" id="cpanel_host" class="required">&nbsp;<a href="Hostname like mysite.com" class="Tips" title="Example">?</a>
				</li>
				<li>
					<label>Username</label>
					<input type="text" name="arrCpanel[user]" id="cpanel_user" class="required">&nbsp;<a href="Cpanel user name" class="Tips" title="Example">?</a>
				</li>
				<li>
					<label>Password</label>
					<input type="password" name="arrCpanel[passwd]" id="cpanel_passwd" class="required">&nbsp;<a href="Cpanel password" class="Tips" title="Example">?</a>
				</li>
				<li>
					<label>cPanel Theme / Skin</label>
					<select name="arrCpanel[theme]"  id="cpanel_theme">
						<option value="x">x</option>
						<option value="x2">x2</option>
						<option value="x3">x3</option>
						<option value="other">other</option>
					</select>&nbsp;<a href="<div style='width:300px;'><strong>Try following steps if( you do not know what your current cPanel theme is:</strong> 	
					<ul>
	  					<li>- Login to your cPanel account</li>
	  					<li>- Look at the URL in your browser. It would look somewhat similar to <strong>http://www.hosting.com:2082/frontend/x/index.html</strong></li>
	  					<li>- cPanel  theme	name is everything after the &quot;/frontend/&quot;, and before the next  slash &quot;/&quot;. In above example cPanel theme is &quot;x&quot;. It could be &quot;x2&quot;,  &quot;rvblue&quot;, etc.</li>
					</ul></div>"  
					class="Tips" title="cPanel Theme / Skin">?</a>
				</li>
				<li id="other">
					<label>&nbsp;</label>
				</li>
				<li>
					<p><font color="Red">Note</font>: Please Check your cpanel theme/skin before select.The script will not work if( wrong cPanel theme is selected. Usually cPanel skin name would be "x", but yours may be different.</p>
				</li>
			</ol>
		</fieldset>
		<fieldset>
			<legend>New database info</legend>
			<ol>
				<li>
					<label>Database name</label>
					<input type="text" name="arrAction[name]" id="base_name" class="required validate-alpha"/>&nbsp;<a href="New database name.<br/>Database name must be alphanumeric characters." class="Tips" title="Example">?</a> 
				</li>
				<li>
					<label>Username</label>
					<input type="text" name="arrAction[user]" id="base_user" class="required maxLength:8 validate-alpha">&nbsp;<a href="New database user name<br/>Username must be alphanumeric characters.<br/>Username cannot be longer than 8 characters." class="Tips" title="Example">?</a>
				</li>
				
				<li>
					<label>Password</label>
					<input type="password" name="arrAction[passwd]" id="base_passwd" class="required minLength:5">&nbsp;<a href="<div style='width:300px;'>As Some cpanel doesnot allow to create database user which have not above 70% password strength.So you need to set password which is Uber Secure(above 70%).<br/><br/>Example like <strong>Mypass_12</strong> i.e Password like Uppercase(Mypass)+symbol(_)+number(12)<br/>Password strength must be at least 5.</div>"  class="Tips" title="Password">?</a>
				</li>
				
				<li>
					<input type="submit" name="submit" value="Submit">
				</li>
			</ol>
		</fieldset>
	</form>
	<script type="text/javascript">
	var cPanelResult = '<?php echo isset($jsonResult)?$jsonResult:"{}"; ?>';
	window.addEvent('domready', function() {
		if(window.parent.$('ftp_address')){
			$('cpanel_host').set('value', window.parent.$('ftp_address').get('value'));
			$('cpanel_user').set('value', window.parent.$('ftp_username').get('value'));
			$('cpanel_passwd').set('value', window.parent.$('ftp_password').get('value'));
		}
		var hash = new Hash(JSON.decode(cPanelResult));
		if( hash.getLength() > 0 ){
			window.parent.CpanelDatabaseResult(hash);
		}		
		$('cpanel_theme').addEvent('change',function(){
			if( $('cpanel_theme').value  == 'other' ) {
				new Element('input', {'type':'text','name':'arrCpanel[theme]', 'id':'input_other', 'class':'required'})
					.inject($('other'));
				new Element('a',{'href':'Your cPanel skin name','title':'Example','class':'Tips'})
					.set('html','&nbsp;?')
					.inject($('other'));
				new Tips('.Tips', {className: 'tips'});
			} else {
				if($('input_other')) {
					$('input_other').destroy();
				}
			}
			new WhValidator({className:'validate'});
		});
		new WhValidator({className:'validate'});
		var optTips = new Tips('.Tips', {className: 'tips'});		
		$$('.Tips').each(function(a){a.addEvent('click',function(e){e.stop()})});		
	});
	</script>
</body>
</html>	