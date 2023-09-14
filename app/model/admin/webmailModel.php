<?php namespace model\admin;
use api as app;

class webmailModel extends app\controller {
	
	public function __construct(){
		parent::__construct();
	}
	
	public function setWebmail(){
		$tb='webmail';
		$post=$this->requestPost();
		$old_data=$this->db->findOne($this->db->getSQLSelect('webmail',[],"where webmail_id={$post['webmail_id']}"));
		if($post['webmail_password']!=$old_data['webmail_pass'])$this->getJSON(PASSWORD_INVALID);
		$post['webmail_pass']=$post['webmail_password'];
		if(isset($post['webmail_new_password']) && !empty($post['webmail_new_password'])) $post['webmail_pass']=$post['webmail_new_password'];
		// Cambios en la cuenta de usuario
		$this->getJSON($this->db->executeSingle($this->db->getSQLUpdate($post,$tb)));
	}
	
}