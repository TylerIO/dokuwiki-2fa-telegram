<?php
class helper_plugin_twofactortelegram extends Twofactor_Auth_Module {

    public function canUse($user = null){		
		return ($this->_settingExists("verified", $user));
	}
	
    public function canAuthLogin() {
		return false;
	}

    public function renderProfileForm(){
		$elements = array();		
			$id = $this->_settingGet("id", '');
			$elements[] = form_makeTextField('telegram_id', $id, $this->getLang('id'), '', 'block', array('size'=>'50'));			

			if ($id) {
				if (!$this->_settingExists("verified")) {
					$elements[] = '<span>'.$this->getLang('verifynotice').'</span>';				
					$elements[] = form_makeTextField('telegram_verify', '', $this->getLang('verifymodule'), '', 'block', array('size'=>'50', 'autocomplete'=>'off'));
					$elements[] = form_makeCheckboxField('telegram_send', '1', $this->getLang('resendcode'),'','block');
				}
				$elements[] = form_makeCheckboxField('telegram_disable', '1', $this->getLang('killmodule'), '', 'block');
			}			
		return $elements;
	}

    public function processProfileForm(){
		global $INPUT;
		$id = $INPUT->str('telegram_id', '');
		if ($INPUT->bool('telegram_disable', false) || $id === '') {
			$this->_settingDelete("id");
			$this->_settingDelete("verified");
			return true;
		}
		$oldid = $this->_settingGet("id", '');
		if ($oldid) {
			if ($INPUT->bool('telegram_send', false)) {
				return 'otp';
			}
			$otp = $INPUT->str('telegram_verify', '');
			if ($otp) {
				$checkResult = $this->processLogin($otp);
				if ($checkResult == false) {
					return 'failed';
				}
				else {
					$this->_settingSet("verified", true);
					return 'verified';
				}					
			}							
		}
		
		$changed = null;				
		if (preg_match('/^[0-9]{5,}$/',$id) != false) { 
			if ($id != $oldid) {
				if ($this->_settingSet("id", $id)== false) {
					msg("TwoFactor: Error setting id.", -1);
				}
				$this->_settingDelete("verified");
				return 'deleted';
			}
		}
		if ($changed === true && $this->_settingExists("id")) {
			$changed = 'otp';
		}		
		return $changed;
	}	
	public function canTransmitMessage(){
		return true;
	}
	
	public function transmitMessage($subject, $message, $force = false){
		if (!$this->canUse()  && !$force) { return false; }
		$id = $this->_settingGet("id", null);
		if (!$id) {
			return false;
		}
		$token = $this->getConf('token');
		$text = rawurlencode($this->getLang('msgtext'));
		$code = rawurlencode($message);
		$url = "https://api.telegram.org/bot{$token}/sendMessage?chat_id={$id}&text={$code}{$text}";
		$result = file_get_contents($url);
		return true;
		}
}