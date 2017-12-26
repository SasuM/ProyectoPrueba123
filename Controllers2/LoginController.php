<?php

/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Authentication\Result;
use Zend\Uri\Uri;

class LoginController extends AbstractActionController {
	private $entityManager;
	private $authManager;
	private $authService;
	// Constructor method is used to inject dependencies to the controller.
	public function __construct($entityManager, $authManager, $authService) {
		$this->entityManager = $entityManager;
		$this->authManager = $authManager;
		$this->authService = $authService;
	}

	public function loginAction() {
		$this->layout ( 'layout/login' );
		$redirectUrl = ( string ) $this->params ()->fromQuery ( 'redirectUrl', '' );
		if (strlen ( $redirectUrl ) > 2048) {
			throw new \Exception ( "Too long redirectUrl argument passed" );
		}
		$isLoginError = false;
		if ($this->getRequest ()->isPost ()) {
			$usuario = $this->getRequest ()->getPost ( "txtUsuario" );
			$pass = $this->getRequest ()->getPost ( "txtPassword" );
			$result = $this->authManager->login ( $usuario, $pass );
			if ($result->getCode () == Result::SUCCESS) {
				$redirectUrl = $this->params ()->fromPost ( 'redirect_url', '' );
				if (! empty ( $redirectUrl )) {
					// The below check is to prevent possible redirect attack
					// (if someone tries to redirect user to another domain).
					$uri = new Uri ( $redirectUrl );
					if (! $uri->isValid () || $uri->getHost () != null)
						throw new \Exception ( 'Incorrect redirect URL: ' . $redirectUrl );
				}
				if (empty ( $redirectUrl )) {
					return $this->redirect ()->toRoute ( 'dashboard' );
				} else {
					$this->redirect ()->toUrl ( $redirectUrl );
				}
			} else {
				$isLoginError = true;
			}
		} else {
			$isLoginError = true;
		}
		return new ViewModel ( [ 'isLoginError' => $isLoginError,'redirectUrl' => $redirectUrl 
		] );
	}
	public function logoutAction() {
		$this->authManager->logout ();
		return $this->redirect ()->toRoute ( 'login' );
	}
}
