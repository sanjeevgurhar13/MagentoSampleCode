<?php
class Puravit_Login_Block_Modal extends Mage_Core_Block_Template
{
	public function _toHtml()
	{
		$this->setTemplate('login/modal.phtml');
		
		return parent::_toHtml();
	}
		
	public function _prepareLayout() 
	{
		return parent::_prepareLayout();
	}
}