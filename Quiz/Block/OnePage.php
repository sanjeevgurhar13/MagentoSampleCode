<?php
class Puravit_Quiz_Block_OnePage extends Mage_Core_Block_Template {
    public function _prepareLayout() {
        return parent::_prepareLayout();
    }

    public function onepageBlock() {
        $customerId = Mage::getSingleton('customer/session')->getCustomerId();
        $arrQuiz = array();
        $isPost = $this->getRequest()->isPost();

        $customer = Mage::getModel('customer/customer')->load($customerId);
        $customerVisitorId = $customer->getQuizVisitor();

        $baseUrl = Mage::getURL('');

        if(empty($customerVisitorId)) {
            $url = $baseUrl . '/profile/api/quiz?quiz=32';
            $content = file_get_contents($url);
            $json = json_decode($content);
            $customerVisitorId = $json->data->visitor->id;
            $customer = Mage::getModel('customer/customer')->load($customerId);
            $customer->setQuizVisitor($customerVisitorId);
            $customer->save();
        }


        if($isPost) {
            $arrQuestions = $this->getRequest()->getParam('questions');

            foreach($arrQuestions as $questionId => $arrAnswers) {
                $query = http_build_query(array('answer' =>  $arrAnswers));
                $url = $baseUrl . '/profile/api/answer?quiz=32&visitor=' . $customerVisitorId;
                $url.= '&question=' . $questionId;
                $url.= '&' . $query;
                file_get_contents($url);

            }
            Mage::getModel('quiz/api')->getUserAttributes($customer->getQuizVisitor(), 32);
            $helper = Mage::helper('style/customer');
            $helper->updateStyleProfile($customerId);

            //Set Message
            Mage::getSingleton('core/session')->addSuccess('Style profile saved');
        }

        $url = $baseUrl . '/profile/api/quiz?quiz=32&visitor=' . $customerVisitorId;
        $jsonQuizData = json_decode(file_get_contents($url));
        $this->setJsonQuizData($jsonQuizData);

    }
}
