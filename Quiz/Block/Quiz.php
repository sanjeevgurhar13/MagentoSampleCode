<?php
class Puravit_Quiz_Block_Quiz extends Mage_Core_Block_Template
{
	public function _prepareLayout() 
	{
		$this->getLayout()
			->getBlock('head')
			->setTitle( $this->htmlEscape($this->__('Get Started')) );
			
		return parent::_prepareLayout();
	}
}
