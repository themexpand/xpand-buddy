<style>
#button_open_popup {
<?php echo ( isset($arrData['button']['top']) && $arrData['button']['top']!=='' )?"top: ".$arrData['button']['top']."px;":''; ?>
<?php echo ( isset($arrData['button']['bottom']) && $arrData['button']['bottom']!=='' )?"bottom: ".($arrData['button']['bottom']-$arrData['button']['radius'])."px;":''; ?>
<?php echo ( isset($arrData['button']['left']) && $arrData['button']['left']!=='' )?"left: ".$arrData['button']['left']."px;":''; ?>
<?php echo ( isset($arrData['button']['right']) && $arrData['button']['right']!=='' )?"right: ".$arrData['button']['right']."px;":''; ?>
	cursor: pointer;
	z-index:10000;
}
#button_open_popup img{
	margin: 0;
	padding: 0;
}
#contactme_overlay, #contactme_dialog {
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
	z-index: 375336691605;
}
#contactme_overlay {
	display: none;
    background-color: <?php echo ( isset($arrData['popup']['overlay-color']) && $arrData['popup']['overlay-color']!=='' )?$arrData['popup']['overlay-color']:'#000'; ?>;
    opacity: <?php echo ( isset($arrData['popup']['overlay-opacity']) && $arrData['popup']['overlay-opacity']!=='' )?$arrData['popup']['overlay-opacity']:'0.5'; ?>;
    position: fixed;
}
#contactme_dialog {
	display: none;
    position: fixed;
}
#contactme_dialog_close {
	background-color: #d00;
    cursor: pointer;
	position: relative;
    left: <?php echo ( ( isset($arrData['popup']['width']) && $arrData['popup']['width']!=='' )?$arrData['popup']['width']:795)-5; ?>px;
    bottom: 15px;
	height: 20px;
    width: 40px;
	border-radius:10px;
	border: 1px solid #000;
}
#contactme_form {
	background: <?php echo ( isset($arrData['popup']['background-color']) && $arrData['popup']['background-color']!=='' )?$arrData['popup']['background-color']:'#fff'; ?>;
	display:block;
	height: 100%;
	width: 100%;
	border: solid 5px <?php echo ( isset($arrData['popup']['border-color']) && $arrData['popup']['border-color']!=='' )?$arrData['popup']['border-color']:'#000'; ?>;
	-moz-border-radius: 16px;
	-webkit-border-radius: 16px;
	border-radius: 16px;
	padding: 1em 2em;
	z-index: 375336691605;
}
</style>
<div id="button_open_popup" style="<?php echo ( self::$_flgPreview === false )?"position:fixed;":''; ?>"><p class="hide-if-no-js"><img src='data:image/png;base64,<?php echo base64_encode($_strImage); ?>' ></p></div>
<div id="contactme_overlay">
&nbsp;
</div>
<div id="contactme_dialog" style="width:<?php echo ( isset($arrData['popup']['width']) && $arrData['popup']['width']!=='' )?$arrData['popup']['width']:'800'; ?>px; height:<?php echo ( isset($arrData['popup']['height']) && $arrData['popup']['height']!=='' )?$arrData['popup']['height']:'600'; ?>px">
	<div id="contactme_form">
		<div id="contactme_dialog_close" onclick="document.getElementById('contactme_overlay').style.display='none';document.getElementById('contactme_dialog').style.display='none';return false;return false;">Close</div>
		<?php include( Xpandbuddy::$pathName.'/source/code.php' ); ?>
	</div>
</div>

<script type="text/javascript">
/* <![CDATA[ */
function resizeWindow(){
	var windowWidth=800, windowHeight=600;
	if (document.body && document.body.offsetWidth) {
		windowWidth=document.body.offsetWidth;
		windowHeight=document.body.offsetHeight;
	}
	if (document.compatMode=='CSS1Compat' && document.documentElement && document.documentElement.offsetWidth ) {
		windowWidth=document.documentElement.offsetWidth;
		windowHeight=document.documentElement.offsetHeight;
	}
	if (window.innerWidth && window.innerHeight) {
		windowWidth=window.innerWidth;
		windowHeight=window.innerHeight;
	}
	document.getElementById('contactme_dialog').style.left=(windowWidth-parseInt( document.getElementById('contactme_dialog').style.width ) )/2+"px";
	document.getElementById('contactme_dialog').style.top=(windowHeight-parseInt( document.getElementById('contactme_dialog').style.height ) )/2+"px";
}
window.onresize = function() {
  resizeWindow();
};
document.getElementById('button_open_popup').onclick=function(){
	resizeWindow();
	document.getElementById('contactme_overlay').style.display='block';
	document.getElementById('contactme_dialog').style.display='block';
}
document.getElementById('contactme_overlay').onclick=document.getElementById('contactme_dialog_close').onclick=function(){
	document.getElementById('contactme_overlay').style.display='none';
	document.getElementById('contactme_dialog').style.display='none';
}
/* ]]> */
</script>