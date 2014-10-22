<?php

/*
 * This file is part of the SecotrustSabreDavBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Secotrust\Bundle\SabreDavBundle\SabreDav;

use Sabre\DAV\Auth\Backend\BackendInterface;
use Sabre\DAV\Exception;
use Sabre\DAV\Server;
use Sabre\HTTP;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AuthBackend implements BackendInterface
{
    /**
     * @var SecurityContextInterface
     */
    private $context;

    /**
     * @var ContainerInterface
     */
    private $container;
    
    /**
     * @var type 
     */
    private $currentUser;
    
    /**
     * Constructor
     *
     * @param SecurityContextInterface $context
     */
    public function __construct(SecurityContextInterface $context, ContainerInterface $container)
    {
        $this->context = $context;
        $this->container = $container;
    }
    
    /**
     * Checks if username and password are valid. (Checked by the FOSUserManager)
     * Returns 
     * 
     * @param type $username
     * @param type $password
     * @return boolean
     */
    public function validateUserPass($username, $password) 
    {

        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->findUserByUsername($username);

        if (is_null($user)) {
            return false;
        }
        $encoder_service = $this->container->get('security.encoder_factory');
        $encoder = $encoder_service->getEncoder($user);
        $encoded_pass = $encoder->encodePassword($password, $user->getSalt());

        if ($encoded_pass === $user->getPassword()) {
            return true;
        } 

        return false;
    }
    
    /**
     * Authenticate
     * 
     * Authenticates the User with the HTTP\BasicAuth()
     * if the user is not logged in via Browser in the Tool.
     * 
     * 
     * @param Server $server
     * @param type $realm
     * @return void
     */
    public function authenticate(Server $server, $realm)
    {
        if (null === $this->context->getToken()) {
            throw new Exception\NotAuthenticated('The security token is NULL');
        }

        $auth = new HTTP\BasicAuth();
        $auth->setHTTPRequest($server->httpRequest);
        $auth->setHTTPResponse($server->httpResponse);
        $userpass = $auth->getUserPass();

        if (!$userpass) {
            $auth->requireLogin();
            throw new Exception\NotAuthenticated('No authentication headers were found');
        }

        // Authenticates the user
        if (!$this->validateUserPass($userpass[0],$userpass[1])) {
            $auth->requireLogin();
            throw new Exception\NotAuthenticated('Username or password does not match');
        }

        $this->currentUser = $userpass[0];

        return true;
    }

    /**
     * Save User-Login to session
     * 
     * @param type $userpass
     */    
    private function userLoginAction($userpass)
    {
        //process Symfony2 Login
        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->findUserByUsername($userpass[0]);

        $token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken($user, $user->getPassword(), 'main', $user->getRoles());
        $request = $this->container->get('request');
        $session = $request->getSession();
        $session->set('_security_main',  serialize($token));        
    }
    
    /**
     * @inheritdoc
     */
    public function getCurrentUser()
    {
        return $this->currentUser;
    }
}
