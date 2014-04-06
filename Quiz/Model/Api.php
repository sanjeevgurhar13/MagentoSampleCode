<?php
class Puravit_Quiz_Model_Api {

	protected $_url = null;
	protected $_queryString = null;
	protected $_data = null;
	protected $_domain = null;

	protected function _sendRequest()
	{
		$this->_domain = isset($_SERVER['SERVER_NAME'])
			? 'http://' . $_SERVER['SERVER_NAME']
			: 'http://www.ellie.com';

		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $this->_domain . $this->_url . $this->_queryString);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, '10');
		#curl_setopt($ch, CURLOPT_VERBOSE, true);
		$result = curl_exec($ch);

		curl_close($ch);

		return $result;
	}

	protected function _buildQuery()
	{
		if(!empty($this->_data))
		{
			$this->_queryString = '?';
			# @todo fixme replace the rest of this function with:
			# $this->_queryString .= http_build_query($this->_data);
			$i = 0;
			foreach($this->_data AS $key=>$value)
			{
				if($i > 0)
				{
					$this->_queryString .= '&';
				}
				$this->_queryString .= $key . '=' . $value; # are we certain we don't need to url encode????
				++$i;
			}
		}
	}

	public function getUserAttributes($visitor_id, $quiz_id)
	{
		$this->_data = array(
			'visitor' => $visitor_id,
			'quiz' => $quiz_id
		);

		$this->_buildQuery();

		$this->_url = '/profile/api/visitor_attribute';

		return json_decode($this->_sendRequest());
	}

	public function getAnswerData($quiz_id, $attribute)
	{
		$this->_data = array(
			'attribute' => $attribute,
			'quiz' => $quiz_id
		);

		$this->_buildQuery();

		$this->_url = '/profile/api/visitor_attributes';

		return json_decode($this->_sendRequest());
	}
}