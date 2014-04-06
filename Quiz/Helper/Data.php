<?php
class Puravit_Quiz_Helper_Data extends Mage_Core_Helper_Abstract
{
	protected $_funnel_id;

	public function getFunnelId($funnel)
	{
		if(!$this->_funnel_id)
		{
			$cacheId = 'quiz_' . $funnel;
			$this->_funnel_id = $this->_loadCache($cacheId);
			if($this->_funnel_id === false)
			{
				$funnel = Mage::getModel('funnel/funnel')->loadByFunnel($funnel);
				$this->_funnel_id = $funnel ? $funnel->getFunnelId() : null;
				$this->_saveCache($this->_funnel_id, $cacheId);
			}
		}

		return $this->_funnel_id;
	}
}