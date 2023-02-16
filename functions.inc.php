<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2013 Schmooze Com Inc.
//
 /* $Id$ */

// The destinations this module provides
// returns a associative arrays with keys 'destination' and 'description'
function ivr_destinations() {
	global $module_page;

	//get the list of IVR's
	$results = ivr_get_details();

	// return an associative array with destination and description
	if (isset($results)) {
		foreach($results as $result){
			$name = $result['name'] ? $result['name'] : 'IVR ' . $result['id'];
			$extens[] = array('destination' => 'ivr-'.$result['id'].',s,1', 'description' => $name);
		}
	}
	if (isset($extens)) {
		return $extens;
	} else {
		return null;
	}

}

//dialplan generator
function ivr_get_config($engine) {
	global $ext;

	switch($engine) {
		case "asterisk":
			//FREEPBX-17112 Return to IVR after VM broken
			$ext->splice('macro-vm','exit-RETURN', 1, new ext_gotoif('$["${RETVM}" = "RETURN"]','ext-local,vmret,1'));
			$ddial_contexts = array();
			$ivrlist = ivr_get_details();
			if(!is_array($ivrlist)) {
				break;
			}
			//FREEPBX-14431
			$ext->splice('macro-dial-one','s', 'dial', new ext_execif('$["${ivrreturn}" = "1"]', 'Set', 'D_OPTIONS=${D_OPTIONS}g'));
			// splice into macro-dial-one
			$ext->splice('macro-dial-one','s-ANSWER','bye', new ext_gotoif('$["${ivrreturn}" = "1"]','${IVR_CONTEXT},return,1'));
			$ext->splice('macro-dial-one','s-CHANUNAVAIL','return', new ext_gotoif('$["${ivrreturn}" = "1"]','${IVR_CONTEXT},return,1'));
			$ext->splice('macro-dial-one','s-NOANSWER','return', new ext_gotoif('$["${ivrreturn}" = "1"]','${IVR_CONTEXT},return,1'));
			$ext->splice('macro-dial-one','s-BUSY','return', new ext_gotoif('$["${ivrreturn}" = "1"]','${IVR_CONTEXT},return,1'));

			//splice into macro dial
			$ext->splice('macro-dial','s','nddialapp', new ext_execif('$["${ivrreturn}" = "1"]', 'Set', 'ds=${ds}g'));
			$ext->splice('macro-dial','s','hsdialapp', new ext_execif('$["${ivrreturn}" = "1"]', 'Set', 'ds=${ds}g'));

			$ext->splice('macro-dial','ANSWER','bye', new ext_gotoif('$["${ivrreturn}" = "1"]','${IVR_CONTEXT},return,1'));
			$ext->splice('macro-dial','NOANSWER','bye', new ext_gotoif('$["${ivrreturn}" = "1"]','${IVR_CONTEXT},return,1'));

			if (function_exists('queues_list')) {
				//draw a list of ivrs included by any queues
				$queues = queues_list(true);
				$qivr = array();
				foreach ($queues as $q) {
					$thisq = queues_get($q[0]);
					if ($thisq['context'] && strpos($thisq['context'], 'ivr-') === 0) {
						$qivr[] = str_replace('ivr-', '', $thisq['context']);
					}
				}
			}

			foreach($ivrlist as $ivr) {
				$c = 'ivr-' . $ivr['id'];
				$ivr = ivr_get_details($ivr['id']);
				$ext->addSectionComment($c, $ivr['name'] ? $ivr['name'] : 'IVR ' . $ivr['id']);

				if ($ivr['directdial']) {
					if ($ivr['directdial'] != 'ext-local') {
						//generated by directory
						$ext->addInclude($c, 'from-ivr-directory-' . $ivr['directdial']);
						$directdial_contexts[$ivr['directdial']] = $ivr['directdial'];
					}
				}

				//set variables for loops when used
				if ($ivr['timeout_loops'] != 'disabled' && $ivr['timeout_loops'] > 0) {
					$ext->add($c, 's', '', new ext_setvar('TIMEOUT_LOOPCOUNT', 0));
				}
				if ($ivr['invalid_loops'] != 'disabled' && $ivr['invalid_loops'] > 0) {
					$ext->add($c, 's', '', new ext_setvar('INVALID_LOOPCOUNT', 0));
				}

				$ext->add($c, 's', '', new ext_setvar('_IVR_CONTEXT_${CONTEXT}', '${IVR_CONTEXT}'));
				$ext->add($c, 's', '', new ext_setvar('_IVR_CONTEXT', '${CONTEXT}'));
				if ($ivr['retvm']) {
					$ext->add($c, 's', '', new ext_setvar('__IVR_RETVM', 'RETURN'));
				} else {
					//TODO: do we need to set anything at all?
					$ext->add($c, 's', '', new ext_setvar('__IVR_RETVM', ''));
				}
				if ($ivr['alertinfo'] != '') {
					$ext->add($c, 's', '', new ext_setvar('__ALERT_INFO', str_replace(';', '\;', $ivr['alertinfo'])));
				}
				if (!empty($ivr['rvolume'])) {
					$ext->add($c, 's', '', new ext_setvar("__RVOL", $ivr['rvolume']));
				}
				$ext->add($c, 's', '', new ext_gotoif('$["${CHANNEL(state)}" = "Up"]','skip'));
				$ext->add($c, 's', '', new ext_answer(''));

				$ivr_announcement = recordings_get_file($ivr['announcement']);
				$ext->add($c, 's', 'skip', new ext_set('IVR_MSG', $ivr_announcement));
				switch($ivr['strict_dial_timeout']) {
					case "0": //force strict dial timeout :: no
						$ext->add($c, 's', 'start', new ext_setvar("DIGITS",""));
						$ext->add($c, 's', '', new ext_setvar("IVREXT",""));
						$ext->add($c, 's', '', new ext_setvar("NODEFOUND","0"));
						$ext->add($c, 's', '', new ext_setvar("LOCALEXT","0"));
						$ext->add($c, 's', '', new ext_setvar("DIREXT","0"));
						$ext->add($c, 's', '', new ext_playback('calling'));
						$ext->add($c, 's', 'beforewhile', new ext_execif('$["${IVREXT}" != ""]', 'Set', 'DIGITS=${DIGITS}${IVREXT}'));
						$ext->add($c, 's', '', new ext_while('$["${NODEFOUND}" = "0"] '));
						$ext->add($c, 's', '', new ext_read('IVREXT', '${IVR_MSG}', '1', '', '0', $ivr['timeout_time']));
						$ext->add($c, 's', '', new ext_set('IVR_MSG', ''));
                    	$ext->add($c, 's', '', new ext_gotoif('$["${READSTATUS}" = "OK" & "${IVREXT}" = ""]','#,1'));
						$ext->add($c, 's', '', new ext_gotoif('$["${READSTATUS}" = "TIMEOUT" & "${DIGITS}" != ""]','i,1'));
						$ext->add($c, 's', '', new ext_gotoif('$["${READSTATUS}" = "TIMEOUT" & "${IVREXT}" = ""]','t,1'));
						$ext->add($c, 's', '', new ext_noop('${DB(DEVICE/${DIGITS}${IVREXT}/user)}'));
						if ($ivr['directdial']!= "" && $ivr['directdial'] !="Enabled" ) {
							if ($ivr['directdial'] == 'ext-local') {
								$ext->add($c, 's', '', new ext_execif('$["${DB(DEVICE/${DIGITS}${IVREXT}/user)}" != ""]', 'Set', 'LOCALEXT=1'));
								$ext->add($c, 's', '', new ext_gotoif('$["${LOCALEXT}" = "1"]','from-did-direct-ivr,${DIGITS}${IVREXT},1'));
								$ext->add($c, 's', '', new ext_noop('${CONTEXT}${DIGITS},${IVREXT},1'));
								$ext->add($c, 's', '', new ext_execif('$["${DIALPLAN_EXISTS(${CONTEXT},${DIGITS}${IVREXT},1)}" != "0"]', 'Set', 'NODEFOUND=1'));
								$ext->add($c, 's', '', new ext_gotoif('$["${NODEFOUND}" = "0"]','beforewhile:nodedial'));
							} else {// this should be a ivr node or directory exten
								$ext->add($c, 's', '', new ext_playback('calling'));
								$ext->add($c, 's', '', new ext_execif('"${DIALPLAN_EXISTS(from-ivr-directory-' . $ivr['directdial'].',${DIGITS}${IVREXT},1)}" != "0"]', 'Set', 'DIREXT=1'));
								$ext->add($c, 's', '', new ext_playback('calling'));
								$ext->add($c, 's', '', new ext_execif('$["${DIALPLAN_EXISTS(${CONTEXT},${DIGITS}${IVREXT},1)}" != "0"]', 'Set', 'NODEFOUND=1'));
								$ext->add($c, 's', '', new ext_playback('calling'));
								$ext->add($c, 's', '', new ext_gotoif('$["${NODEFOUND}" = "0"]','beforewhile:nodedial'));
								$ext->add($c, 's', '', new ext_playback('calling'));
							}
						}else {
    $ext->add($c, 's', 1, new exten('s', null, '1', new ext_playback('calling'), new exten('s', null, '2', new ext_Dial('SIP/username', 60))));
}
						$ext->add($c, 's', '', new ext_playback('calling'));
$ext->add($c, 's', '', new ext_endwhile(''));
$ext->add($c, 's', '', new ext_playback('calling'));
$ext->add($c, 's', '', new ext_gotoif('$["${DIALPLAN_EXISTS(${CONTEXT},${DIGITS},1)}" = "0"]','i,1'));
						$ext->add($c, 's', '', new ext_playback('calling'));
$ext->add($c, 's', 'nodedial', new ext_goto('${DIGITS}${IVREXT},1'));
						$ext->add($c, 's', '', new ext_playback('calling'));

					break;
					case "1": //force strict dial timeout :: yes
						$ext->add($c, 's', 'start', new ext_digittimeout($ivr['timeout_time']));
						$ext->add($c, 's', '', new ext_read('IVREXT', '${IVR_MSG}', '', '', '0', $ivr['timeout_time']));
                    	$ext->add($c, 's', '', new ext_gotoif('$["${READSTATUS}" = "OK" & "${IVREXT}" = ""]','#,1'));
						$ext->add($c, 's', '', new ext_gotoif('$["${READSTATUS}" = "TIMEOUT" & "${IVREXT}" = ""]','t,1'));
						if ($ivr['directdial']) {
							if ($ivr['directdial'] == 'ext-local') {
								$ext->add($c, 's', '', new ext_playback('calling'));
								$ext->add($c, 's', '', new ext_execif('$["${DB(DEVICE/${IVREXT}/user)}" != ""]', 'Set', 'LOCALEXT=1'));
								$ext->add($c, 's', '', new ext_playback('calling'));
								$ext->add($c, 's', '', new ext_gotoif('$["${DIALPLAN_EXISTS(${CONTEXT},${IVREXT},1)}" = "0" & "${DIALPLAN_EXISTS(from-did-direct-ivr,${IVREXT},1)}" = "0"]','i,1'));
								$ext->add($c, 's', '', new ext_playback('calling'));
							} else {
								//generated by directory
								$ext->add($c, 's', '', new ext_playback('calling'));
								$ext->add($c, 's', '', new ext_gotoif('$["${DIALPLAN_EXISTS(${CONTEXT},${IVREXT},1)}" = "0" & "${DIALPLAN_EXISTS(from-ivr-directory-' . $ivr['directdial'].',${IVREXT},1)}" = "0"]','i,1'));
								$ext->add($c, 's', '', new ext_playback('calling'));
							}
						} else {
							$ext->add($c, 's', '', new ext_playback('calling'));
							$ext->add($c, 's', '', new ext_gotoif('$["${DIALPLAN_EXISTS(${CONTEXT},${IVREXT},1)}" = "0"]','i,1'));
						}
						$ext->add($c, 's', '', new ext_playback('calling'));
						$ext->add($c, 's', '', new ext_gotoif('$["${LOCALEXT}" = "1"]','from-did-direct-ivr,${IVREXT},1'));
						$ext->add($c, 's', '', new ext_playback('calling'));
						$ext->add($c, 's', '', new ext_goto('${IVREXT},1'));
					break;
					case "2": //force strict dial timeout :: instant
						if ($ivr['directdial'] != "" && $ivr['directdial'] == "ext-local" ) {
							$ext->addInclude($c, 'from-did-direct-ivr'); //generated in core module
						}
						$ext->add($c, 's', 'start', new ext_digittimeout(3));
						$ext->add($c, 's', '', new ext_execif('$["${IVR_MSG}" != ""]','Background','${IVR_MSG}'));
						$ext->add($c, 's', '', new ext_waitexten($ivr['timeout_time']));
					break;
				}

				// Actually add the IVR entires now
				$entries = ivr_get_entries($ivr['id']);

				if ($entries) {
					foreach($entries as $e) {
						//dont set a t or i if there already defined above
						if ($e['selection'] == 't' && $ivr['timeout_loops'] != 'disabled') {
						 	continue;
						}
						if ($e['selection'] == 'i' && $ivr['invalid_loops'] != 'disabled') {
						 	continue;
						}

						//only display these two lines if the ivr is included in any queues
						if (function_exists('queues_list') && in_array($ivr['id'], $qivr)) {
							$ext->add($c, $e['selection'],'', new ext_macro('blkvm-clr'));
							$ext->add($c, $e['selection'], '', new ext_setvar('__NODEST', ''));
						}
						if ($e['ivr_ret']) {
							//FREEPBX-14431 ivr return option not working : should work for extension only.//from-did-direct,111,1
							$desarray = explode(',',$e['dest']);
							if ($desarray[0] == 'from-did-direct') {
								$ext->add($c, $e['selection'], '', new ext_setvar('__ivrreturn', '1'));
								$ext->add($c, $e['selection'], '',
								new ext_gotoif('$["x${IVR_CONTEXT_${CONTEXT}}" = "x"]',
									$e['dest'] . ':${IVR_CONTEXT_${CONTEXT}},return,1'));
							} else {
								 $ext->add($c, $e['selection'], '', new ext_setvar('__ivrreturn', '0'));
								$ext->add($c, $e['selection'],'ivrsel-' . $e['selection'], new ext_goto($e['dest']));
							}
						} else {
							$ext->add($c, $e['selection'], '', new ext_setvar('__ivrreturn', '0'));
							$ext->add($c, $e['selection'],'ivrsel-' . $e['selection'], new ext_goto($e['dest']));
						}
					}
				}

				// add invalid destination if required
				if ($ivr['invalid_loops'] != 'disabled') {
					if ($ivr['invalid_loops'] > 0) {
						$ext->add($c, 'i', '', new ext_set('INVALID_LOOPCOUNT', '$[${INVALID_LOOPCOUNT}+1]'));
						$ext->add($c, 'i', '',	new ext_gotoif('$[${INVALID_LOOPCOUNT} > ' . $ivr['invalid_loops'] . ']','final'));
						switch ($ivr['invalid_retry_recording']) {
							case 'default':
								$invalid_annoucement = 'ivr-invalid';
								break;
							case '':
								$invalid_annoucement = '';
								break;
							default:
								$invalid_annoucement = recordings_get_file($ivr['invalid_retry_recording']);
								break;
						}

						if ($ivr['invalid_append_announce'] || $invalid_annoucement == '') {
							$invalid_annoucement .= '&' . $ivr_announcement;
						}
						$ext->add($c, 'i', '', new ext_set('IVR_MSG', trim($invalid_annoucement, '&')));
						$ext->add($c, 'i', '', new ext_goto('s,start'));
					}

					$label = 'final';
					switch ($ivr['invalid_recording']) {
						case 'default':
							$ext->add($c, 'i', $label, new ext_playback('ivr-goodbye'));
							$label ='';
							break;
						case '':
							break;
						default:
							$ext->add($c, 'i', $label,
								new ext_playback(recordings_get_file($ivr['invalid_recording'])));
							$label = '';
							break;
					}
					if (!empty($ivr['invalid_ivr_ret'])) {
						$ext->add($c, 'i', $label,
							new ext_gotoif('$["x${IVR_CONTEXT_${CONTEXT}}" = "x"]',
								$ivr['invalid_destination'] . ':${IVR_CONTEXT_${CONTEXT}},return,1'));
					} else {
						$ext->add($c, 'i', $label, new ext_goto($ivr['invalid_destination']));
					}
				} else {
					// If no invalid destination provided we need to do something
					$ext->add($c, 'i', '', new ext_playback('sorry-youre-having-problems'));
					$ext->add($c, 'i', '', new ext_goto('1','hang'));
				}

				// Apply timeout destination if required
				if ($ivr['timeout_loops'] != 'disabled') {
					if ($ivr['timeout_loops'] > 0) {
						$ext->add($c, 't', '', new ext_set('TIMEOUT_LOOPCOUNT', '$[${TIMEOUT_LOOPCOUNT}+1]'));
						$ext->add($c, 't', '', new ext_gotoif('$[${TIMEOUT_LOOPCOUNT} > ' . $ivr['timeout_loops'] . ']','final'));

						switch ($ivr['timeout_retry_recording']) {
							case 'default':
								$timeout_annoucement = 'ivr-invalid';
								break;
							case '':
								$timeout_annoucement = '';
								break;
							default:
								$timeout_annoucement = recordings_get_file($ivr['timeout_retry_recording']);
								break;
						}

						if ($ivr['timeout_append_announce'] || $timeout_annoucement == '') {
							$timeout_annoucement .= '&' . $ivr_announcement;
						}
						$ext->add($c, 't', '', new ext_set('IVR_MSG', trim($timeout_annoucement, '&')));
						$ext->add($c, 't', '', new ext_goto('s,start'));
					}

					$label = 'final';
					switch ($ivr['timeout_recording']) {
						case 'default':
							$ext->add($c, 't', $label, new ext_playback('ivr-goodbye'));
							$label = '';
							break;
						case '':
							break;
						default:
							$ext->add($c, 't', $label,
								new ext_playback(recordings_get_file($ivr['timeout_recording'])));
							$label = '';
							break;
					}
					if (!empty($ivr['timeout_ivr_ret'])) {
						$ext->add($c, 't', $label,
							new ext_gotoif('$["x${IVR_CONTEXT_${CONTEXT}}" = "x"]',
								$ivr['timeout_destination'] . ':${IVR_CONTEXT_${CONTEXT}},return,1'));
					} else {
						$ext->add($c, 't', $label, new ext_goto($ivr['timeout_destination']));
					}
				} else {
					// If no invalid destination provided we need to do something
					$ext->add($c, 't', '', new ext_playback('sorry-youre-having-problems'));
					$ext->add($c, 's', '', new ext_playback('calling'));
					$ext->add($c, 't', '', new ext_goto('1','500'));
				}

				// these need to be reset or inheritance problems makes them go away in some conditions
				//and infinite inheritance creates other problems
				$ext->add($c, 'return', '', new ext_setvar('_IVR_CONTEXT', '${CONTEXT}'));
				$ext->add($c, 'return', '', new ext_setvar('_IVR_CONTEXT_${CONTEXT}', '${IVR_CONTEXT_${CONTEXT}}'));
				$ext->add($c, 'return', '', new ext_set('IVR_MSG', $ivr_announcement));
				$ext->add($c, 'return', '', new ext_goto('s,start'));

				//h extension
				$ext->add($c, 'h', '', new ext_hangup(''));
				$ext->add($c, 'hang', '', new ext_playback('ivr-goodbye'));
				$ext->add($c, 'hang', '', new ext_hangup(''));
			}


			//generate from-ivr-directory contexts for direct dialing a directory entire
			if (!empty($directdial_contexts)) {
				foreach($directdial_contexts as $dir_id) {
					$c = 'from-ivr-directory-' . $dir_id;
					$entries = function_exists('directory_get_dir_entries') ? directory_get_dir_entries($dir_id) : array();
					foreach ($entries as $dstring) {
						$exten = $dstring['dial'] == '' ? $dstring['foreign_id'] : $dstring['dial'];
						if ($exten == '' || $exten == 'custom') {
							continue;
						}
						$ext->add($c, $exten, '', new ext_macro('blkvm-clr'));
						$ext->add($c, $exten, '', new ext_setvar('__NODEST', ''));
						$ext->add($c, $exten, '', new ext_goto('1', $exten, 'from-internal'));
					}
				}
			}
		break;
	}
}

//replaces ivr_list(), returns all details of any ivr
function ivr_get_details($id = '') {
	return FreePBX::Ivr()->getDetails($id);
}

//get all ivr entires
function ivr_get_entries($id) {
	global $db;

	//+0 to convert string to an integer
	$sql = "SELECT * FROM ivr_entries WHERE ivr_id = ? ORDER BY selection + 0";
	$res = $db->getAll($sql, array($id), DB_FETCHMODE_ASSOC);
	if ($db->IsError($res)) {
		die_freepbx($res->getDebugInfo());
	}
	return $res;
}


//draw ivr options page
function ivr_configpageload() {
	global $currentcomponent, $display;
	return true;
}

function ivr_configpageinit($pagename) {
	global $currentcomponent;
	$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
	$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';

	if($pagename == 'ivr'){
		$currentcomponent->addprocessfunc('ivr_configprocess');

		//dont show page if there is no action set
		if ($action && $action != 'delete' || $id) {
			$currentcomponent->addguifunc('ivr_configpageload');
		}

    return true;
	}
}

//prosses received arguments
function ivr_configprocess(){
	if (isset($_REQUEST['display']) && $_REQUEST['display'] == 'ivr'){
		global $db;
		//get variables
		$get_var = array('id', 'name', 'alertinfo', 'description', 'announcement',
						'directdial', 'invalid_loops', 'invalid_retry_recording',
						'invalid_destination', 'invalid_recording',
						'retvm', 'timeout_time', 'timeout_recording',
						'timeout_retry_recording', 'timeout_destination', 'timeout_loops',
						'timeout_append_announce', 'invalid_append_announce',
						'timeout_ivr_ret', 'invalid_ivr_ret','rvolume','strict_dial_timeout');
		foreach($get_var as $var){
			$vars[$var] = isset($_REQUEST[$var]) 	? $_REQUEST[$var]		: '';
		}

		$vars['timeout_append_announce'] = empty($vars['timeout_append_announce']) ? '0' : '1';
		$vars['invalid_append_announce'] = empty($vars['invalid_append_announce']) ? '0' : '1';
		$vars['timeout_ivr_ret'] = empty($vars['timeout_ivr_ret']) ? '0' : '1';
		$vars['invalid_ivr_ret'] = empty($vars['invalid_ivr_ret']) ? '0' : '1';

		$action		= isset($_REQUEST['action'])	? $_REQUEST['action']	: '';
		$entries	= isset($_REQUEST['entries'])	? $_REQUEST['entries']	: '';

		switch ($action) {
			case 'save':
				if(isset($_REQUEST['announcementrecording'])) {
					$filepath = FreePBX::Config()->get("ASTSPOOLDIR") . "/tmp/".$_REQUEST['announcementrecording'];
					$soundspath = FreePBX::Config()->get("ASTVARLIBDIR")."/sounds";
					$codec = "wav";
					if(file_exists($filepath)) {
						FreePBX::Media()->load($filepath);
						$filename = "ivr-".$vars['name']."-recording-".time();
						FreePBX::Media()->convert($soundspath."/en/custom/".$filename.".".$codec);
						$id = FreePBX::Recordings()->addRecording("ivr-".$vars['name']."-recording-".time(),sprintf(_("Recording created for IVR named '%s'"),$vars['name']),"custom/".$filename);
						$vars['announcement'] = $id;
					} else {
						$vars['announcement'] = '';
					}
				}
				//get real dest
				$vars['id'] = ivr_save_details($vars);
				ivr_save_entries($vars['id'], $entries);
				needreload();
				$this_dest = ivr_getdest($vars['id']);
				fwmsg::set_dest($this_dest[0]);
			break;
			case 'delete':
				ivr_delete($vars['id']);
        isset($_REQUEST['id'])?$_REQUEST['id'] = null:'';
        isset($_REQUEST['action'])?$_REQUEST['action'] = null:'';
				needreload();
			break;
		}
	}
}

//save ivr settings
function ivr_save_details($vals){
	global $db, $amp_conf;

	if ($vals['id']) {
		$start = "REPLACE INTO `ivr_details` (";
	} else {
		unset($vals['id']);
		$start = "INSERT INTO `ivr_details` (";
	}

	$end = ") VALUES (";
	foreach ($vals as $k => $v) {
		$start .= "$k, ";
		$end .= ":$k, ";
	}

	$sql = substr($start, 0, -2).substr($end, 0, -2).")";
	$foo = $db->query($sql, $vals);
	if($db->IsError($foo)) {
		die_freepbx(print_r($vals,true).' '.$foo->getDebugInfo());
	}
	// Was this a new one?
	if (!isset($vals['id'])) {
		$sql = ( ($amp_conf["AMPDBENGINE"]=="sqlite3") ? 'SELECT last_insert_rowid()' : 'SELECT LAST_INSERT_ID()');
		$id = $db->getOne($sql);
		if ($db->IsError($id)){
			die_freepbx($id->getDebugInfo());
		}
		$vals['id'] = $id;
	}

	return $vals['id'];
}

//save ivr entires
function ivr_save_entries($id, $entries){
	global $db;
	$db->query('DELETE FROM ivr_entries WHERE ivr_id = ?', $id);
	if ($entries) {
		$entries['ivr_ret'] = array_values($entries['ivr_ret']);
		for ($i = 0; $i < count($entries['ext']); $i++) {
			//make sure there is an extension & goto set - otherwise SKIP IT
			if (trim($entries['ext'][$i]) != '' && $entries['goto'][$i]) {
				$d[] = array(
							'ivr_id'	=> $id,
							'selection' 	=> $entries['ext'][$i],
							'dest'		=> $entries['goto'][$i],
							'ivr_ret'	=> (int) (isset($entries['ivr_ret'][$i]) ? $entries['ivr_ret'][$i] : '0')
						);
			}

		}
		$sql = $db->prepare('INSERT INTO ivr_entries VALUES (?, ?, ?, ?)');
		$res = $db->executeMultiple($sql, $d);
		if ($db->IsError($res)){
			die_freepbx($res->getDebugInfo());
		}
	}

	return true;
}

//draw uvr entires table header
function ivr_draw_entries_table_header_ivr() {
	return  array(fpbx_label(_('Digits'),_("Digits the caller needs to dial to access said destination. Digits are limited to 10 digits.")), fpbx_label(_('Destination'),_("Choose a destination to route the call to")), fpbx_label(_('Return'), _('Return to this IVR when finished')), _('Delete'));
}

//draw actualy entires
function ivr_draw_entries($id,$restrict_mods=false){
	$headers		= mod_func_iterator('draw_entries_table_header_ivr');
	$ivr_entries	= ivr_get_entries($id);

	if ($ivr_entries) {
		foreach ($ivr_entries as $k => $e) {
			$entries[$k]= $e;
			$array = array('id' => $id, 'ext' => $e['selection']);
			$entries[$k]['hooks'] = mod_func_iterator('draw_entries_ivr', $array);
		}
	}

	$entries['blank'] = array('selection' => '', 'dest' => '', 'ivr_ret' => '0');
	//assign to a vatriable first so that it can be passed by reference
	$array = array('id' => '', 'ext' => '');
	$entries['blank']['hooks'] = mod_func_iterator('draw_entries_ivr', $array);

	return load_view(dirname(__FILE__) . '/views/entries.php',
				array(
					'headers'	=> $headers,
					'entries'	=>  $entries,
					'restrict_mods' => $restrict_mods
				)
			);

}

//delete an ivr + entires
function ivr_delete($id) {
	global $db;
	$db->query('DELETE FROM ivr_details WHERE id = ?', $id);
	$db->query('DELETE FROM ivr_entries WHERE ivr_id = ?', $id);
}
//----------------------------------------------------------------------------
// Dynamic Destination Registry and Recordings Registry Functions
function ivr_check_destinations($dest=true) {
	global $active_modules;
	global $db;

	$destlist = array();
	$destlist_option = array();
	$destlist_invalid = array();
	$destlist_timeout = array();

	if (is_array($dest) && empty($dest)) {
		return $destlist;
	}
	$sql = "SELECT dest, name, selection, a.id id FROM ivr_details a INNER JOIN ivr_entries d ON a.id = d.ivr_id  ";
	if ($dest !== true && is_array($dest)) {
		$sql .= "WHERE dest in (".implode(",",array_fill(0, count($dest), "?")).")";
	}
	$sql .= "ORDER BY name";
	$results = $db->getAll($sql, $dest, DB_FETCHMODE_ASSOC);

	foreach ($results as $result) {
		$thisdest = $result['dest'];
		$thisid   = $result['id'];
		$name = $result['name'] ? $result['name'] : 'IVR ' . $thisid;
		$destlist_option[] = array(
			'dest' => $thisdest,
			'description' => sprintf(_("IVR: %s / Option: %s"),$name,$result['selection']),
			'edit_url' => 'config.php?display=ivr&action=edit&id='.urlencode($thisid),
		);
	}

	$sql = "SELECT invalid_destination, name, id FROM ivr_details ";
	if ($dest !== true && is_array($dest)) {
		$sql .= "WHERE invalid_destination in (".implode(",",array_fill(0, count($dest), "?")).")";
	}
	$sql .= "ORDER BY name";
	$results = $db->getAll($sql, $dest, DB_FETCHMODE_ASSOC);

	foreach ($results as $result) {
		$thisdest = $result['invalid_destination'];
		$thisid   = $result['id'];
		$name = $result['name'] ? $result['name'] : 'IVR ' . $thisid;
		$destlist_invalid[] = array(
			'dest' => $thisdest,
			'description' => sprintf(_("IVR: %s (%s)"),$name,"Invalid Destination"),
			'edit_url' => 'config.php?display=ivr&action=edit&id='.urlencode($thisid),
			'allow_empty' => true,
		);
	}

	$sql = "SELECT timeout_destination, name, id FROM ivr_details ";
	if ($dest !== true && is_array($dest)) {
		$sql .= "WHERE timeout_destination in (".implode(",",array_fill(0, count($dest), "?")).")";
	}
	$sql .= "ORDER BY name";
	$results = $db->getAll($sql, $dest, DB_FETCHMODE_ASSOC);

	foreach ($results as $result) {
		$thisdest = $result['timeout_destination'];
		$thisid   = $result['id'];
		$name = $result['name'] ? $result['name'] : 'IVR ' . $thisid;
		$destlist_timeout[] = array(
			'dest' => $thisdest,
			'description' => sprintf(_("IVR: %s (%s)"),$name,"Timeout Destination"),
			'edit_url' => 'config.php?display=ivr&action=edit&id='.urlencode($thisid),
			'allow_empty' => true,
		);
	}
	$destlist = array_merge($destlist_option, $destlist_invalid, $destlist_timeout);
	return $destlist;
}



function ivr_change_destination($old_dest, $new_dest) {
	global $db;
 	$sql = "UPDATE ivr_entires SET dest = ? WHERE dest = ?";
	$params = array($new_dest, $old_dest);
 	$db->query($sql, $params);

}


function ivr_getdest($exten) {
	return array('ivr-'.$exten.',s,1');
}

function ivr_getdestinfo($dest) {
	global $active_modules;

	if (substr(trim($dest),0,4) == 'ivr-') {
		$exten = explode(',',$dest);
		$exten = substr($exten[0],4);

		$thisexten = ivr_get_details($exten);
		if (empty($thisexten)) {
			return array();
		} else {
			//$type = isset($active_modules['ivr']['type'])?$active_modules['ivr']['type']:'setup';
			return array('description' => sprintf(_("IVR: %s"), ($thisexten['name'] ? $thisexten['name'] : $thisexten['id'])),
			             'edit_url' => 'config.php?display=ivr&action=edit&id='.urlencode($exten),
								  );
		}
	} else {
		return false;
	}
}

function ivr_recordings_usage($recording_id) {
	global $active_modules;
	global $db;

	$sql = "SELECT `id`, `name` FROM `ivr_details` WHERE `announcement` = :recording_id OR `invalid_retry_recording` = :recording_id OR `invalid_recording` = :recording_id OR `timeout_recording` = :recording_id OR `timeout_retry_recording` = :recording_id";
	$params = array(":recording_id"=>$recording_id);
	$results = $db->getAll($sql, $params, DB_FETCHMODE_ASSOC);
	if (empty($results)) {
		return array();
	} else {
		foreach ($results as $result) {
			$usage_arr[] = array(
				'url_query' => 'config.php?display=ivr&action=edit&id='.urlencode($result['id']),
				'description' => sprintf(_("IVR: %s"), ($result['name'] ? $result['name'] : $result['id'])),
			);
		}
		return $usage_arr;
	}
}

?>
