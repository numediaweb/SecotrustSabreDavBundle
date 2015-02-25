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
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Event;

class AuthBackend implements BackendInterface {

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var \FOS\UserBundle\Model\UserManagerInterface
     */
    private $user_manager;

    /**
     * @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
     */
    private $token_storage;

    /**
     * @var \Symfony\Component\Serializer\Encoder\EncoderInterface
     */
    private $encoder_service;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var string 
     */
    protected $currentUser;

    /**
     * Constructor
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container) {

        $this->container = $container;
        $this->user_manager = $this->container->get('fos_user.user_manager');
        $this->token_storage = $this->container->get('security.token_storage');
        $this->encoder_service = $this->container->get('security.encoder_factory');
        $this->dispatcher = $this->container->get('event_dispatcher');
    }

    /**
     * Checks if username and password are valid. (Checked by the FOSUserManager)
     * Returns 
     * 
     * @param type $username
     * @param type $password
     * @return boolean
     */
    public function validateUserPass($username, $passwordHash) {

        $user = $this->user_manager->findUserByUsername($username);

        if (is_null($user)) {
            return false;
        }

        if ($passwordHash === $user->getPassword()) {

            $this->userLoginAction($user, $passwordHash);

            return true;
        }

        return false;
    }

    /**
     * Authenticate
     * 
     * Authenticates the User via basc-auth
     * 
     * @param Server $server
     * @param type $realm
     * @return void
     */
    public function authenticate(Server $server, $realm) {
        $auth = new Auth\BasicAuth($realm, $server->httpRequest, $server->httpResponse, $this->user_manager);
        $userpass = $auth->getCredentials($this->encoder_service);

        if (!$userpass) {
            $auth->requireLogin();
            throw new Exception\NotAuthenticated('No authentication headers were found');
        }

        // Authenticates the user
        if (!$this->validateUserPass($userpass[0], $userpass[1])) {
            $auth->requireLogin();
            throw new Exception\NotAuthenticated('Username or password does not match');
        }

        $this->currentUser = $userpass[0];

        return true;
    }

    /**
     * add the symfony-login "manually" 
     * 
     * use the symfony token-storage for the generated UsernamePasswordToken 
     * to access the (logged in) user later (e.g. to check for roles or permissions)
     * 
     * the given $passwordHash must match the encrypted password in the user-object
     * 
     * before and after the generation/setting of the token, the events "secotrust.user_login.before" 
     * and "secotrust.user_login.after" are called, if some EventListeners are configured
     * 
     * @param string $user
     * @param string $passwordHash
     */
    private function userLoginAction(\FOS\UserBundle\Model\UserInterface $user, $passwordHash) {
        // call the pre-login-event        
        $event = new Event();
        $this->dispatcher->dispatch('secotrust.user_login.before', $event);

        if ($user->getPassword() !== $passwordHash) {
            // stop the login-action, when the password doesn't match
            return;
        }

        $token = new UsernamePasswordToken($user, null, 'secured_area', $user->getRoles());
        $this->token_storage->setToken($token);

        // call the post-login-event
        $event = new Event();
        $this->dispatcher->dispatch('secotrust.user_login.after', $event);
    }

    /**
     * @inheritdoc
     */
    public function getCurrentUser() {
        return $this->currentUser;
    }
}
