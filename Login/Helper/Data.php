<?php
class Puravit_Login_Helper_Data extends Mage_Core_Helper_Abstract{

	private $_customer = null;
	
	protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }
    
	public function getCustomer()
	{
		if(!$this->_customer)
		{
			$customerId = Mage::getSingleton('customer/session')->getCustomerId();
			if(!$customerId)
			{
				Mage::getSingleton('adminhtml/session')->addNotice($this->__('The Customer not logged in.'));
				return false;
			}
			
			$this->_customer = Mage::getModel('customer/customer')->load($customerId);
		}
	}

}
