<?php
class Puravit_Login_Model_Mysql4_Email_List extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {   
        $this->_init('login/email_list', 'email_id');
    }
}