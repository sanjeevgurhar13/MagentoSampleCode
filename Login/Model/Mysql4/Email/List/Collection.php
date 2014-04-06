<?php
class Puravit_Login_Model_Mysql4_Email_List_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        //parent::__construct();
        $this->_init('login/email_list');
    }
}