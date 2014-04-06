<?php
class Puravit_Login_Model_Email_List extends Mage_Core_Model_Abstract
{
   	public function _construct()
    {
        parent::_construct();
        $this->_init('login/email_list');
    }
}