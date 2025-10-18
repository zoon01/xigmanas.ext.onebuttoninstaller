<?php
/*
	onebuttoninstaller-config.php

	Copyright (c) 2020 - 2025 Michael Zoon
	All rights reserved.

	Copyright (c) 2015 - 2020 Andreas Schmidhuber
	All rights reserved.

	XigmaNAS® is a registered trademark of Michael Zoon. (zoon01@xigmanas.com).
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice, this
	   list of conditions and the following disclaimer.
	2. Redistributions in binary form must reproduce the above copyright notice,
	   this list of conditions and the following disclaimer in the documentation
	   and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
	ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
	WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
	DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
	ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
	(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
	LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
	ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
	(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
	SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

require_once 'autoload.php';
require_once 'auth.inc';
require_once 'guiconfig.inc';

$app = [
	'name' => 'OneButtonInstaller',
	'version' => 'v0.4.3',
	'config.name' => 'onebuttoninstaller',
	'repository.path' => 'ms49434/xigmanas.ext.'
];
$app['repository.url'] = 'https://github.com/' . $app['repository.path'] . $app['config.name'];
$app['repository.raw'] = 'https://raw.github.com/' . $app['repository.path'] . $app['config.name'];

$config_file = "ext/{$app['config.name']}/{$app['config.name']}.conf";
require_once "ext/{$app['config.name']}/extension-lib.inc";
$domain = strtolower(get_product_name());
$localeOSDirectory = '/usr/local/share/locale';
$localeExtDirectory = "/usr/local/share/locale-{$app['config.name']}";
bindtextdomain($domain,$localeExtDirectory);
//	Dummy standard message gettext calls for xgettext retrieval!!!
$dummy = gettext('The changes have been applied successfully.');
$dummy = gettext('The configuration has been changed.<br />You must apply the changes in order for them to take effect.');
$dummy = gettext('The following input errors were detected');
$configuration = ext_load_config($config_file);
if(!is_array($configuration)):
	$input_errors[] = sprintf(gettext('Configuration file %s not found!'),"{$app['config.name']}.conf");
	$configuration = [];
endif;
//	default configuration
$configuration['rootfolder'] ??= null;
$configuration['enable'] ??= false;
$configuration['storage_path'] ??= null;
$configuration['path_check'] ??= false;
$configuration['appname'] ??= $app['name'];
$configuration['version'] ??= $app['version'];
$configuration['show_beta'] ??= true;
$configuration['re_install'] ??= false;
$configuration['auto_update'] ??= false;
$configuration['postinit'] ??= '';
$configuration['shutdown'] ??= '';
$configuration['rc_uuid_start'] ??= '';
$configuration['rc_uuid_stop'] ??= '';
//	check installation
if(!isset($configuration['rootfolder']) && !is_dir($configuration['rootfolder'] )):
	$input_errors[] = gettext('Extension installed with fault');
endif;
$platform = $g['platform'];
if($platform == 'livecd' || $platform == 'liveusb'):
	$input_errors[] = sprintf(gettext("Attention: the used platform '%s' is not recommended for extensions! After a reboot all extensions will no longer be available, use embedded or full platform instead!"),$platform);
endif;
$pgtitle = [gettext('Extensions'),$configuration['appname'] . ' ' . $configuration['version'],gettext('Configuration')];
//	Check if the directory exists, the mountpoint has at least o=rx permissions and set the permission to 775 for the last directory in the path
function change_perms($dir) {
	global $input_errors;

//	remove trailing slash
	$path = rtrim($dir,'/');
	if(strlen($path) > 1):
//		check if directory exists
		if(!is_dir($path)):
			$input_errors[] = sprintf(gettext("Directory %s doesn't exist!"),$path);
		else:
//			split path to get directory names
			$path_check = explode("/",$path);
//			get path depth
			$path_elements = count($path_check);
//			get mountpoint permissions for others
			$fp = substr(sprintf('%o',fileperms("/$path_check[1]/$path_check[2]")),-1);
//			transmission needs at least read & search permission at the mountpoint
			if($fp >= 5):
//				set to the mountpoint
				$directory = "/$path_check[1]/$path_check[2]";
//				traverse the path and set permissions to rx
				for($i = 3; $i < $path_elements - 1; $i++):
//					add next level
					$directory = $directory."/$path_check[$i]";
//					set permissions to o=+r+x
					exec("chmod o=+r+x \"$directory\"");
				endfor;
				$path_elements = $path_elements - 1;
//				add last level
				$directory = $directory."/$path_check[$path_elements]";
//				set permissions to 775
				exec("chmod 775 {$directory}");
				exec("chown root {$directory}*");
			else:
				$input_errors[] = sprintf(gettext("{$app['config.name']} needs at least read & execute permissions at the mount point for directory %s! Set the Read and Execute bits for Others (Access Restrictions | Mode) for the mount point %s (in <a href='disks_mount.php'>Disks | Mount Point | Management</a> or <a href='disks_zfs_dataset.php'>Disks | ZFS | Datasets</a>) and hit Save in order to take them effect."),$path,"/{$path_check[1]}/{$path_check[2]}");
			endif;
		endif;
	endif;
}
if(isset($_POST['save']) && $_POST['save']):
	unset($input_errors);
	if (empty($input_errors)):
		$configuration['enable'] = isset($_POST['enable']);
		if($configuration['enable']):
			$configuration['storage_path'] = !empty($_POST['storage_path']) ? $_POST['storage_path'] : $g['media_path'];
//			ensure to have NO trailing slash
			$configuration['storage_path'] = rtrim($configuration['storage_path'],'/');
			if(!isset($_POST['path_check']) && (strpos($configuration['storage_path'],"/mnt/") === false)):
				$input_errors[] = gettext("The common directory for all extensions MUST be set to a directory below <b>'/mnt/'</b> to prevent to loose the extensions after a reboot on embedded systems!");
			else:
				if(!is_dir($configuration['storage_path'])):
					mkdir($configuration['storage_path'],0775,true);
				endif;
				change_perms($_POST['storage_path']);
				$configuration['path_check'] = isset($_POST['path_check']);
				$configuration['re_install'] = isset($_POST['re_install']);
				$configuration['auto_update'] = isset($_POST['auto_update']);
				$configuration['show_beta'] = isset($_POST['show_beta']);
				$savemsg .= get_std_save_message(ext_save_config($config_file,$configuration)) . ' ';
				require_once "{$configuration['rootfolder']}/{$app['config.name']}-start.php";
			endif;
		else:
			$savemsg .= get_std_save_message(ext_save_config($config_file,$configuration)) . ' ';
		endif;
	endif;
endif;
$pconfig['enable'] = $configuration['enable'];
$pconfig['storage_path'] = !empty($configuration['storage_path']) ? $configuration['storage_path'] : $g['media_path'];
$pconfig['path_check'] = $configuration['path_check'];
$pconfig['re_install'] = $configuration['re_install'];
$pconfig['auto_update'] = $configuration['auto_update'];
$pconfig['show_beta'] = $configuration['show_beta'];
$message = ext_check_version("{$configuration['rootfolder']}/version_server.txt","{$app['config.name']}",$configuration['version'],gettext('Maintenance'));
if($message !== false):
	$savemsg .= $message;
endif;
bindtextdomain($domain,$localeOSDirectory);
include 'fbegin.inc';
bindtextdomain($domain,$localeExtDirectory);
?>
<script>
//<![CDATA[
function enable_change(enable_change) {
	var endis = !(document.iform.enable.checked || enable_change);
	document.iform.storage_path.disabled = endis;
	document.iform.storage_pathbrowsebtn.disabled = endis;
	document.iform.path_check.disabled = endis;
	document.iform.re_install.disabled = endis;
	document.iform.auto_update.disabled = endis;
	document.iform.show_beta.disabled = endis;
}
//]]>
</script>
<form action="<?php echo $app['config.name']; ?>-config.php" method="post" name="iform" id="iform" onsubmit="spinner()">
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr>
			<td class="tabnavtbl">
				<ul id="tabnav">
<?php
					if($configuration['enable']):
?>
						<li class="tabinact"><a href="onebuttoninstaller.php"><span><?=gettext('Install');?></span></a></li>
<?php
					endif;
?>
					<li class="tabact"><a href="onebuttoninstaller-config.php"><span><?=gettext('Configuration');?></span></a></li>
					<li class="tabinact"><a href="onebuttoninstaller-update_extension.php"><span><?=gettext('Maintenance');?></span></a></li>
				</ul>
			</td>
		</tr>
		<tr>
			<td class="tabcont">
<?php
				if(!empty($input_errors)):
					print_input_errors($input_errors);
				endif;
				if(!empty($savemsg)):
					print_info_box($savemsg);
				endif;
?>
				<table width="100%" border="0" cellpadding="6" cellspacing="0">
<?php
					html_titleline_checkbox('enable',gettext($configuration['appname']),$pconfig['enable'],gettext('Enable'),'enable_change(false)');
					html_text('installation_directory',gettext('Installation directory'),sprintf(gettext('The extension is installed in %s'),$configuration['rootfolder']));
					html_filechooser('storage_path',gettext('Common directory'),$pconfig['storage_path'],gettext('Common directory for all extensions (a persistant place where all extensions are/should be - a directory below <b>/mnt/</b>).'),$pconfig['storage_path'],true,60);
					html_checkbox('path_check',gettext('Path check'),$pconfig['path_check'],gettext('If this option is selected no examination of the common directory path will be carried out (whether it was set to a directory below /mnt/).'),'<b><font color="red">' . gettext('Please use this option only if you know what you are doing!') . '</font></b>',false);
					html_checkbox('re_install',gettext('Re-install'),$pconfig['re_install'],gettext('If enabled it is possible to install extensions even if they are already installed.'),'<b><font color="red">' . gettext('Please use this option only if you know what you are doing!') . '</font></b>',false);
					html_checkbox('auto_update',gettext('Update'),$pconfig['auto_update'],gettext('Update extensions list automatically.'),'',false);
					html_checkbox('show_beta',gettext('Beta releases'),$pconfig['show_beta'],gettext('If enabled, extensions in beta state will be shown in the extensions list.'),'',false);
?>
				</table>
				<div id="submit">
					<input id="save" name="save" type="submit" class="formbtn" value="<?=gettext('Save');?>"/>
				</div>
			</td>
		</tr>
	</table>
<?php
	include 'formend.inc';
?>
</form>
<script>
<!--
enable_change(false);
//-->
</script>
<?php
include 'fend.inc';
