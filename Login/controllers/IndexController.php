<?php
class Puravit_Login_IndexController extends Mage_Core_Controller_Front_Action
{	
	protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }
    
	public function indexAction()
	{
		$method = $this->getRequest()->getParam('method');
		$flag = $this->getRequest()->getParam('flag');
		if(!$method)
		{
			$method = 'signup';
		}

        Mage::register('login_method', $method );
        Mage::register('login_flag', $flag);
     
		$this->loadLayout();
		$this->renderLayout();
	}
	
	public function loginPostAction()
	{
		if ($this->_getSession()->isLoggedIn()) 
		{
            $this->getResponse()->setBody('success');
            return;
        }
        $session = $this->_getSession();

        if ($this->getRequest()->isPost()) 
        {
            $login = $this->getRequest()->getPost('login');
            if (!empty($login['username']) && !empty($login['password'])) 
            {
                try 
                {
                    $session->login($login['username'], $login['password']);
                    Mage::getModel('core/cookie')->set('elliecookie', true, 5184000);
                    
                    if ($session->getCustomer()->getIsJustConfirmed()) 
                    {
                        $this->_welcomeCustomer($session->getCustomer(), true); 
                    }
                    $this->getResponse()->setBody('success');
                    return;
                } 
                catch (Mage_Core_Exception $e) 
                {
                    switch ($e->getCode()) 
                    {
                        case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
                            $value = Mage::helper('customer')->getEmailConfirmationUrl($login['username']);
                            $message = Mage::helper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $value);
                            break;
                        case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
                            $message = $e->getMessage();
                            break;
                        default:
                            $message = $e->getMessage();
                    }
                    $this->getResponse()->setBody($message);
                } 
                catch (Exception $e) 
                {
                    // Mage::logException($e); // PA DSS violation: this exception log can disclose customer password
                    $this->getResponse()->setBody('Login and password are required.');
                }
            } 
            else 
            {
                $this->getResponse()->setBody('Login and password are required.');
            }
        }
	} 
	
	public function registerPostAction()
	{
            $session = $this->_getSession();
        if ($session->isLoggedIn()) 
        {
            $this->getResponse()->setBody('success');
            return;
        }
        $session->setEscapeMessages(true); // prevent XSS injection in user input
        if ($this->getRequest()->isPost()) 
        {
            $errors = array();

            if ($this->getRequest()->getPost('email') && $this->getRequest()->getPost('password')) 
            {
                try 
                {
                    if(Mage::getModel('customer/session')->login($this->getRequest()->getPost('email'), $this->getRequest()->getPost('password')))
                    {
                    	Mage::getModel('core/cookie')->set('elliecookie', true, 5184000);
                        $this->getResponse()->setBody('success');
                        return;
                    }
                }
                catch(Exception $e)
                {
                    $customer = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
                    $customer->loadByEmail($this->getRequest()->getPost('email'));

                    if($customer->getId() && $e->getMessage() == 'Invalid login or password.')
                    {
                        $this->getResponse()->setBody('Provided Email Already Exist.');
                        return;
                    }
                }
               }
                   
            if (!$customer = Mage::registry('current_customer')) 
            {
                $customer = Mage::getModel('customer/customer')->setId(null);
            }

            /* @var $customerForm Mage_Customer_Model_Form */
            $customerForm = Mage::getModel('customer/form');
            $customerForm
            	->setFormCode('customer_account_create')
                ->setEntity($customer);

            $customerData = $customerForm->extractData($this->getRequest());

            if ($this->getRequest()->getParam('is_subscribed', false)) 
            {
                $customer->setIsSubscribed(1);
            }

            /**
             * Initialize customer group id
             */
            $customer->getGroupId();

            if ($this->getRequest()->getPost('create_address')) 
            {
                /* @var $address Mage_Customer_Model_Address */
                $address = Mage::getModel('customer/address');
                /* @var $addressForm Mage_Customer_Model_Form */
                $addressForm = Mage::getModel('customer/form');
                $addressForm->setFormCode('customer_register_address')
                    ->setEntity($address);

                $addressData    = $addressForm->extractData($this->getRequest(), 'address', false);
                $addressErrors  = $addressForm->validateData($addressData);
                if ($addressErrors === true) 
                {
                    $address->setId(null)
                        ->setIsDefaultBilling($this->getRequest()->getParam('default_billing', false))
                        ->setIsDefaultShipping($this->getRequest()->getParam('default_shipping', false));
                    $addressForm->compactData($addressData);
                    $customer->addAddress($address);

                    $addressErrors = $address->validate();
                    if (is_array($addressErrors)) 
                    {
                        $errors = array_merge($errors, $addressErrors);
                    }
                } 
                else 
                {
                    $errors = array_merge($errors, $addressErrors);
                }
            }

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
                    
                    if(!$this->getRequest()->getPost('confirmation'))
                    {
                    	$customer->setConfirmation($this->getRequest()->getPost('password'));
                    }
                    else 
                    {
                    	$customer->setConfirmation($this->getRequest()->getPost('confirmation'));
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

                    //if ($customer->isConfirmationRequired()) 
                    //{
                        $customer->sendNewAccountEmail(
                            'registered',
                            $session->getBeforeAuthUrl(),
                            Mage::app()->getStore()->getId()
                        );
                        
                        $session->setCustomerAsLoggedIn($customer);
                        Mage::getModel('core/cookie')->set('elliecookie', true, 5184000);
                        $this->getResponse()->setBody('success');
					/*
                	} 
                    else 
                    {
                        $session->setCustomerAsLoggedIn($customer);
                        $this->getResponse()->setBody('success');
                    }
                    */
                } 
                else 
                {
                    $session->setCustomerFormData($this->getRequest()->getPost());
                    if (is_array($errors)) 
                    {
                      $this->getResponse()->setBody(implode(', ', $errors));
                    } 
                    else 
                    {
                        $this->getResponse()->setBody('Invalid customer data');
                    }
                }
            } 
            catch (Mage_Core_Exception $e) 
            {
                $session->setCustomerFormData($this->getRequest()->getPost());
                if ($e->getCode() === Mage_Customer_Model_Customer::EXCEPTION_EMAIL_EXISTS) 
                {
                    $url = Mage::getUrl('customer/account/forgotpassword');
                    $message = $this->__('There is already an account with this email address. If you are sure that it is your email address, <a href="%s">click here</a> to get your password and access your account.', $url);
                    $session->setEscapeMessages(false);
                } 
                else 
                {
                    $message = $e->getMessage();
                }

                $this->getResponse()->setBody($message);
            } 
            catch (Exception $e) 
            {
                $this->getResponse()->setBody('Cannot save the customer.');
            }
        }
        
        //$this->getResponse()->setBody('Cannot save the customer.');
	}

	public function preregisterPostAction()
	{
		$session = $this->_getSession();
        if ($session->isLoggedIn()) 
        {
            $this->getResponse()->setBody('success');
            return;
        }
        $session->setEscapeMessages(true); // prevent XSS injection in user input
        if ($this->getRequest()->isPost()) 
        {
            $errors = array();

            if (!$customer = Mage::registry('current_customer')) 
            {
                $customer = Mage::getModel('customer/customer')->setId(null);
            }

            /* @var $customerForm Mage_Customer_Model_Form */
            $customerForm = Mage::getModel('customer/form');
            $customerForm
            	->setFormCode('customer_account_create')
                ->setEntity($customer);

            $customerData = $customerForm->extractData($this->getRequest());

            $customerData['confirmation'] = $this->getRequest()->getPost('password');

            $customer->setIsSubscribed(1);

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

                    if ($customer->isConfirmationRequired()) 
                    {
                        $customer->sendNewAccountEmail(
                            'confirmation',
                            $session->getBeforeAuthUrl(),
                            Mage::app()->getStore()->getId()
                        );
                        
                        $this->getResponse()->setBody('success');
                    } 
                    else 
                    {
                        $session->setCustomerAsLoggedIn($customer);
                        Mage::getModel('core/cookie')->set('elliecookie', true, 5184000);
                        $this->getResponse()->setBody('success');
                    }
                } 
                else 
                {
                    $session->setCustomerFormData($this->getRequest()->getPost());
                    if (is_array($errors)) 
                    {
                      $this->getResponse()->setBody('Invalid customer data');
                    } 
                    else 
                    {
                        $this->getResponse()->setBody('Invalid customer data');
                    }
                }
            } 
            catch (Mage_Core_Exception $e) 
            {
                $session->setCustomerFormData($this->getRequest()->getPost());
                if ($e->getCode() === Mage_Customer_Model_Customer::EXCEPTION_EMAIL_EXISTS) 
                {
                    $url = Mage::getUrl('customer/account/forgotpassword');
                    $message = $this->__('There is already an account with this email address. If you are sure that it is your email address, <a href="%s">click here</a> to get your password and access your account.', $url);
                    $session->setEscapeMessages(false);
                } 
                else 
                {
                    $message = $e->getMessage();
                }

                $this->getResponse()->setBody($message);
            } 
            catch (Exception $e) 
            {
                $this->getResponse()->setBody('Cannot save the customer.');
            }
        }
        
        //$this->getResponse()->setBody('Cannot save the customer.');
	}
	
	public function emailAction()
	{
		if ($this->getRequest()->isPost()) 
        {
        	$email = Mage::getModel('login/email_list');
        	$email->setEmail($this->getRequest()->getPost('email'))
        		->setPromo($this->getRequest()->getPost('promo'))
        		->setCreatedDate(date('Y-m-d H:i:s'))
        		->save();
    	
        	$this->getResponse()->setBody(true);
        }
        else 
        {
        	$this->getResponse()->setBody(false);
        }
        
	}
}
