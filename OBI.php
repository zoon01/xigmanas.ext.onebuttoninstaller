<?php
/*
	OBI.php

	Copyright (c) 2015 - 2020 Andreas Schmidhuber
	All rights reserved.

	XigmaNAS� is a registered trademark of Michael Zoon. (zoon01@xigmanas.com).
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

use common\arr;

$app = [
	'name' => 'OneButtonInstaller',
	'version' => 'v0.4.3',
	'config.name' => 'onebuttoninstaller',
	'repository.path' => 'ms49434/xigmanas.ext.'
];
$app['repository.url'] = 'https://github.com/' . $app['repository.path'] . $app['config.name'];
$app['repository.raw'] = 'https://raw.github.com/' . $app['repository.path'] . $app['config.name'];

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
			$path_check = explode('/',$path);
//			get path depth
			$path_elements = count($path_check);
//			get mountpoint permissions for others
			$fp = substr(sprintf('%o',fileperms("/$path_check[1]/$path_check[2]")),-1);
//			transmission needs at least read & search permission at the mountpoint
			if($fp >= 5):
//				set to the mountpoint
				$directory = "/$path_check[1]/$path_check[2]";
//				traverse the path and set permissions to rx
				for($i = 3;$i < $path_elements - 1;$i++):
//					add next level
					$directory = $directory . "/$path_check[$i]";
//					set permissions to o=+r+x
					exec("chmod o=+r+x \"$directory\"");
				endfor;
				$path_elements = $path_elements - 1;
//				add last level
				$directory = $directory . "/$path_check[$path_elements]";
//				set permissions to 775
				exec("chmod 775 {$directory}");
				exec("chown root {$directory}*");
			else:
				$input_errors[] = sprintf(gettext("%s needs at least read & execute permissions at the mount point for directory %s! Set the Read and Execute bits for Others (Access Restrictions | Mode) for the mount point %s (in <a href='disks_mount.php'>Disks | Mount Point | Management</a> or <a href='disks_zfs_dataset.php'>Disks | ZFS | Datasets</a>) and hit Save in order to take them effect."),$app['name'],$path,"/{$path_check[1]}/{$path_check[2]}");
			endif;
		endif;
	endif;
}
$php_version_ok = PHP_MAJOR_VERSION >= 8;
if(!$php_version_ok):
	$input_errors[] = gettext('Attention: this extension is not compatible with the PHP version of this platform!');
endif;
$platform = $g['platform'];
if($platform == 'livecd' || $platform == 'liveusb'):
	$input_errors[] = sprintf(gettext("Attention: the used platform '%s' is not recommended for extensions! After a reboot all extensions will no longer be available, use embedded or full platform instead!"),$platform);
endif;
$app_config = &arr::make_branch($config,$app['config.name']);
//	Check if the directory exists, the mountpoint has at least o=rx permissions and set the permission to 775 for the last directory in the path
if(isset($_POST['save']) && $_POST['save']):
	unset($input_errors);
	if(empty($input_errors)):
		$app_config['storage_path'] = !empty($_POST['storage_path']) ? $_POST['storage_path'] : $g['media_path'];
//		ensure to have NO trailing slashes
		$app_config['storage_path'] = rtrim($app_config['storage_path'],'/');
		if(!isset($_POST['path_check']) && (strpos($app_config['storage_path'],'/mnt/') === false)):
			$input_errors[] = gettext("The common directory for all extensions MUST be set to a directory below <b>'/mnt/'</b> to prevent the extensions from being lost after a reboot on embedded systems!");
		else:
			if(!is_dir($app_config['storage_path'])):
				mkdir($app_config['storage_path'],0775,true);
			endif;
			change_perms($app_config['storage_path']);
			$app_config['path_check'] = isset($_POST['path_check']);
//			get directory where the installer script resides
			$install_dir = $app_config['storage_path'] . '/';
			if(!is_dir("{$install_dir}onebuttoninstaller/log")):
				mkdir("{$install_dir}onebuttoninstaller/log",0775,true);
			endif;
			$return_val = mwexec("fetch --no-verify-hostname --no-verify-peer -vo {$install_dir}onebuttoninstaller/onebuttoninstaller-install.php '{$app['repository.raw']}/master/onebuttoninstaller/onebuttoninstaller-install.php'",false);
			if($return_val == 0):
				chmod("{$install_dir}onebuttoninstaller/onebuttoninstaller-install.php",0775);
				require_once "{$install_dir}onebuttoninstaller/onebuttoninstaller-install.php";
			else:
				$input_errors[] = sprintf(gettext("Installation file %s not found, installation aborted!"),"{$install_dir}onebuttoninstaller/onebuttoninstaller-install.php");
				return;
			endif;
			mwexec('rm -Rf ext/OBI; rm -f OBI.php',true);
			header('Location: onebuttoninstaller-config.php');
		endif;
	endif;
endif;
if(isset($_POST['cancel']) && $_POST['cancel']):
	$return_val = mwexec('rm -Rf ext/OBI; rm -f OBI.php',true);
	if($return_val == 0):
		$savemsg .= $app['name'] . ' ' . gettext('not installed');
	else:
		$input_errors[] = $app['name'] . ' removal failed';
	endif;
	header('Location: index.php');
endif;
$pconfig['storage_path'] = !empty($app_config['storage_path']) ? $app_config['storage_path'] : $g['media_path'];
$pconfig['path_check'] = isset($app_config['path_check']);
$pgtitle = [gettext('Extensions'),gettext($app['name']),gettext('Configuration')];
include 'fbegin.inc';
?>
<form action="OBI.php" method="post" name="iform" id="iform">
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
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
					html_titleline($app['name']);
					html_filechooser('storage_path',gettext('Common directory'),$pconfig['storage_path'],gettext('Common directory for all extensions (a persistant place where all extensions are/should be - a directory below <b>/mnt/</b>).'),$pconfig['storage_path'],true,60);
					html_checkbox('path_check',gettext('Path check'),$pconfig['path_check'],gettext('If this option is selected no examination of the common directory path will be carried out (whether it was set to a directory below /mnt/).'),'<b><font color="red">' . gettext('Please use this option only if you know what you are doing!') . '</font></b>',false);
?>
				</table>
				<div id="submit">
<?php
					if($php_version_ok):
?>
						<input id="save" name="save" type="submit" class="formbtn" value="<?=gettext('Save');?>"/>
<?php
					endif;
?>
					<input id="cancel" name="cancel" type="submit" class="formbtn" value="<?=gettext('Cancel');?>"/>
				</div>
			</td>
		</tr>
	</table>
<?php
	include 'formend.inc';
?>
</form>
<?php
include 'fend.inc';
