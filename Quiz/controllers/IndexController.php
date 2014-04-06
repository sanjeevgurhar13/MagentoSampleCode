<?php
class Puravit_Quiz_IndexController extends Mage_Core_Controller_Front_Action
{
    protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }

    public function indexAction()
    {
        $passed_route = $this->getRequest()->getParam('route');

        if($passed_route)
        {
            Mage::getSingleton('core/session')->setFunnel($passed_route);
        }

        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->renderLayout();
    }

    public function updateAction()
    {
    	$session = $this->_getSession();

        $session->setEscapeMessages(true);

        if ($this->getRequest()->isPost())
        {
            $errors = array();

            if ($session->isLoggedIn())
            {
                $customerId = Mage::getSingleton('customer/session')->getCustomerId();

                $customer = Mage::getModel('customer/customer')->load($customerId);

                if(!$customer->getQuizVisitor())
                {
                    $customer->setQuizVisitor($this->getRequest()->getPost('quiz_visitor'));
                    $customer->save();
                }

                //update quiz
                Mage::helper('style/customer')->updateStyleProfile($customerId);

                //success
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array('status'=>'success')));
                return;
            }
        }

        //failed
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array('status'=>'error', 'message'=>'Account could not be updated')));
    }

    public function registerAction()
    {
    	$session = $this->_getSession();

        $session->setEscapeMessages(true); // prevent XSS injection in user input
        if ($this->getRequest()->isPost())
        {
            $errors = array();

            if ($session->isLoggedIn())
            {
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array('status'=>'success')));
                return;
            }

            if (!$customer = Mage::registry('current_customer'))
            {
                $customer = Mage::getModel('customer/customer')->setId(null);
            }

            //check for existing FB account
            $facebookID = $this->getRequest()->getPost('facebook_uid');
            if($facebookID)
            {
                $collection = $customer->getCollection()
                     ->addAttributeToFilter('facebook_uid', $facebookID)
                    ->setPageSize(1);

                $uidExist = (bool)$collection->count();

                if($uidExist)
                {
                    $uidCustomer = $collection->getFirstItem();

                    $uidCustomer->setFacebookUid($facebookID);
                    Mage::getResourceModel('customer/customer')->saveAttribute($uidCustomer, 'facebook_uid');

                    Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($uidCustomer);
                    Mage::getModel('core/cookie')->set('elliecookie', true, 5184000);
                    $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array('status'=>'success')));
                    return;
                }
            }


            /* @var $customerForm Mage_Customer_Model_Form */
            $customerForm = Mage::getModel('customer/form');
            $customerForm->setFormCode('customer_account_create')
                ->setEntity($customer);

            $customerData = $customerForm->extractData($this->getRequest());

            //set customer recommended product
            $slug = explode('.', trim($this->getRequest()->getPost('link'), '/'));
            //$product =  Mage::getModel('catalog/product')->loadByAttribute('url_key', $slug);

            //check for facebook access token
            if($this->getRequest()->getPost('fb_token'))
            {
                Mage::getSingleton('core/session')->setFacebookToken($this->getRequest()->getPost('fb_token'));
            }

            /**
             * Initialize customer group id
             */
            $customer->getGroupId();


            try
            {
                $customerErrors = $customerForm->validateData($customerData);
                if ($customerErrors !== true)
                {
                    $errors = array_merge($customerErrors, $errors);
                }
                else
                {
                    $customerForm->compactData($customerData);
                    $customer->setPassword($this->getRequest()->getPost('password'));
                    $customer->setConfirmation($this->getRequest()->getPost('password'));
                   // $customer->setRecommendation($product->getId());
                    $customer->setQuizVisitor($this->getRequest()->getPost('quiz_visitor'));
                    $customer->setSizeTop($this->getRequest()->getPost('size_top'));
                    $customer->setSizeBottom($this->getRequest()->getPost('size_bottom'));
                    /*
                    $customer->setFunnel($product->getUrlKey());
                    if(Mage::getSingleton('core/session')->getFunnel() != $product->getUrlKey())
                    {
                        Mage::getSingleton('core/session')->setFunnel($product->getUrlKey());
                    }
                    */
                    $facebookID = $this->getRequest()->getPost('facebook_uid');
                    if($facebookID)
                    {
                        $customer->setFacebookUid($facebookID);
                    }

                    $customerErrors = $customer->validate();
                    if (is_array($customerErrors))
                    {
                        $errors = array_merge($customerErrors, $errors);
                    }
                }


                $validationResult = count($errors) == 0;

                if (true === $validationResult)
                {
                    $customer->save();

                    Mage::dispatchEvent('customer_register_success',
                        array('account_controller' => $this, 'customer' => $customer)
                    );

                    $session->setCustomerAsLoggedIn($customer);
					Mage::getModel('core/cookie')->set('elliecookie', true, 5184000);
                    $customer->sendNewAccountEmail(
                        'registered',
                        $session->getBeforeAuthUrl(),
                        Mage::app()->getStore()->getId()
                    );

                    //create style profile
                    Mage::helper('style/customer')->updateStyleProfile($customer->getId());

                    $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array('status'=>'success')));
                    return;
                }
                else
                {
                    $session->setCustomerFormData($this->getRequest()->getPost());
                }
            }
            catch (Mage_Core_Exception $e)
            {
                $session->setCustomerFormData($this->getRequest()->getPost());
                if ($e->getCode() === Mage_Customer_Model_Customer::EXCEPTION_EMAIL_EXISTS)
                {
                    $url = Mage::getUrl('customer/account/forgotpassword');
                    $message = $this->__('There is already an account with this email address. If you are sure that it is your email address, click "Sign In" to get your password and access your account.', $url);
                    $session->setEscapeMessages(false);
                }
                else
                {
                    $message = $e->getMessage();
                }

                $errors[] = $message;
            }
            catch (Exception $e)
            {
                $session->setCustomerFormData($this->getRequest()->getPost())
                    ->addException($e, $this->__('Cannot save the customer.'));

                $errors[] = $e->getMessage();
            }
        }

        if ($errors && is_array($errors))
        {
            $message = implode('<br>', $errors);
        }
        else
        {
             $message = $this->__('Invalid customer data');
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array('status'=>'error', 'message'=>$message)));
    }

    public function notifyPostAction()
    {
        //mens notify list post
        $email = $this->getRequest()->getPost('email');
        if($email)
        {
            $apikey = Mage::getStoreConfig('mailchimp/general/apikey',Mage::app()->getStore()->getStoreId());

            $mailchimp = Mage::getModel('mailchimp/MCAPI');
            $mailchimp->MCAPI($apikey);
            $mailchimp->listSubscribe('b712716add', $email, NULL, 'html', false, false, false, false);
            $response = array(
                'title' => 'Thank You!',
                'text' => 'We will send you an email as soon as men\'s products are available.<br><br><a  data-dismiss="modal" >close</a>'
            );

            echo json_encode($response);
        }
    }

    public function onePageAction() { 
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle($this->__('Edit Style Profile'));
        $this->renderLayout();
        // item count get from cart
		$itemCount = Mage::helper('checkout/cart')->getCart()->getItemsCount();
         // if process come from another site url(we are checking the product counter and post data)            
        if(!$itemCount and $this->getRequest()->getPost())
        {
           $this->_redirect('/');
        }
    }
}
