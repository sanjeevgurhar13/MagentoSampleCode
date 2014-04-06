<?php
class Puravit_Quiz_Model_Quiz extends Mage_Core_Model_Abstract {

	public function _construct() {
		parent::_construct();
		$this->_init('quiz/quiz');
	}

}