<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?=Control::getVar('html_title', 'untitled')?></title>
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" id="IE8emu7" />
<meta name="robots" content="noindex,nofollow,noarchive"/>
<?php
	// write stylesheets
	$style_sheets = Control::getVar('stylesheets', array());
	foreach($style_sheets as $style_sheet)
	{
		?>
		<link rel="stylesheet" href="<?=$style_sheet?>"/>
		<?php
	}
	// write javascripts 
	$javascripts = Control::getVar('javascripts', array());
	foreach($javascripts as $javascript)
	{
		?>
		<script type="text/javascript" src="<?=$javascript?>"></script>
		<?php
	}
?>
</head>
<body id="<?= Control::getActionName()?>">
