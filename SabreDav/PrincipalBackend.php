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

use Sabre\DAVACL\PrincipalBackend\BackendInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


class PrincipalBackend implements BackendInterface
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
     * @var ContainerInterface
     */
    private $_em;

    /**
     * @var type 
     */
    private $principals_class;
    
   /**
     * A list of additional fields to support
     *
     * @var array
     */
    protected $fieldMap = array(

        /**
         * This property can be used to display the users' real name.
         */
        '{DAV:}displayname' => array(
            'getter' => 'getUsername',
        ),

        /**
         * This property is actually used by the CardDAV plugin, where it gets
         * mapped to {http://calendarserver.orgi/ns/}me-card.
         *
         * The reason we don't straight-up use that property, is because
         * me-card is defined as a property on the users' addressbook
         * collection.
         */
        '{http://sabredav.org/ns}vcard-url' => array(
            'getter' => 'getVCardUrl',
        ),
        /**
         * This is the users' primary email-address.
         */
        '{http://sabredav.org/ns}email-address' => array(
            'getter' => 'getEmail',
        ),
    );    
    
    /**
     * Constructor
     *
     * @param SecurityContextInterface $context
     * @param ContainerInterface $container
     */
    public function __construct(SecurityContextInterface $context, ContainerInterface $container)
    {
        $this->context = $context;
	$this->container = $container;
	
	$this->_em = $container->get('doctrine')->getManager();
        
        $this->principals_class = $this->container->getParameter('secotrust.principals_class');
//        $this->cards_class = $this->container->getParameter('secotrust.cards_class');        
    }
    
    /**
     * get Array with Principal-Data from User-Object
     * 
     * @param $userObject
     * @param type $show_id
     * @return array
     */
    private function getPrincipalArray($userObject, $show_id = false){
	        
        $principal = array();
        if ($show_id){
            $principal['id'] = $userObject->getId();
        }

        $principal['uri'] = 'principals/' . $userObject->getUsername();

        foreach($this->fieldMap as $key=>$value) {
            if (method_exists($userObject, $value['getter']) && call_user_func(array($userObject, $value['getter']))) {
                $principal[$key] = call_user_func(array($userObject, $value['getter']));
            }
        }

        return $principal;
    }
    
    /**
     * Returns a list of principals based on a prefix.
     *
     * This prefix will often contain something like 'principals'. You are only
     * expected to return principals that are in this base path.
     *
     * You are expected to return at least a 'uri' for every user, you can
     * return any additional properties if you wish so. Common properties are:
     *   {DAV:}displayname
     *   {http://sabredav.org/ns}email-address - This is a custom SabreDAV
     *     field that's actually injected in a number of other properties. If
     *     you have an email address, use this property.
     *
     * @param string $prefixPath
     * @return array
     */
    public function getPrincipalsByPrefix($prefixPath){

        $userlist = $this->_em->getRepository($this->principals_class)->findBy(array('enabled' => true));
        $principals = array();

        foreach($userlist as $user) {
            $principals[] = $this->getPrincipalArray($user);
        }

        return $principals;	
    }
   
    /**
     * Returns a specific principal, specified by it's path.
     * The returned structure should be the exact same as from
     * getPrincipalsByPrefix.
     *
     * @param string $path
     * @return array
     */
    public function getPrincipalByPath($path){
	
        $username = str_replace('principals/', '', $path);
        if (!(strpos($username,'/')=== false)){
            $username = substr($username, 0, strpos($username, '/'));    
        }

        $user = $this->_em->getRepository($this->principals_class)->findByUsername($username);

        $user = $user[0];

        return $this->getPrincipalArray($user, true);
    }

    /**
     * Updates one ore more webdav properties on a principal.
     *
     * The list of mutations is supplied as an array. Each key in the array is
     * a propertyname, such as {DAV:}displayname.
     *
     * Each value is the actual value to be updated. If a value is null, it
     * must be deleted.
     *
     * This method should be atomic. It must either completely succeed, or
     * completely fail. Success and failure can simply be returned as 'true' or
     * 'false'.
     *
     * It is also possible to return detailed failure information. In that case
     * an array such as this should be returned:
     *
     * array(
     *   200 => array(
     *      '{DAV:}prop1' => null,
     *   ),
     *   201 => array(
     *      '{DAV:}prop2' => null,
     *   ),
     *   403 => array(
     *      '{DAV:}prop3' => null,
     *   ),
     *   424 => array(
     *      '{DAV:}prop4' => null,
     *   ),
     * );
     *
     * In this previous example prop1 was successfully updated or deleted, and
     * prop2 was succesfully created.
     *
     * prop3 failed to update due to '403 Forbidden' and because of this prop4
     * also could not be updated with '424 Failed dependency'.
     *
     * This last example was actually incorrect. While 200 and 201 could appear
     * in 1 response, if there's any error (403) the other properties should
     * always fail with 423 (failed dependency).
     *
     * But anyway, if you don't want to scratch your head over this, just
     * return true or false.
     *
     * @param string $path
     * @param array $mutations
     * @return array|bool
     */
    public function updatePrincipal($path, $mutations){
        $this->container->get('logger')->error('CardDAV update of Principal currently not possible!');
        return false;
    }

    /**
     * This method is used to search for principals matching a set of
     * properties.
     *
     * This search is specifically used by RFC3744's principal-property-search
     * REPORT. You should at least allow searching on
     * http://sabredav.org/ns}email-address.
     *
     * The actual search should be a unicode-non-case-sensitive search. The
     * keys in searchProperties are the WebDAV property names, while the values
     * are the property values to search on.
     *
     * If multiple properties are being searched on, the search should be
     * AND'ed.
     *
     * This method should simply return an array with full principal uri's.
     *
     * If somebody attempted to search on a property the backend does not
     * support, you should simply return 0 results.
     *
     * You can also just return 0 results if you choose to not support
     * searching at all, but keep in mind that this may stop certain features
     * from working.
     *
     * @param string $prefixPath
     * @param array $searchProperties
     * @return array
     */
    public function searchPrincipals($prefixPath, array $searchProperties){
		
        foreach($searchProperties as $property => $value) {

            switch($property) {

                case '{DAV:}displayname' :
                    $searchArray['email'] = $value;
                    break;
                case '{http://sabredav.org/ns}email-address' :
                    $searchArray['email'] = $value;
                    break;
                default :
                    // Unsupported property
                    return array();
            }
        }		

        $principals = $this->_em->getRepository($this->principals_class)->searchPrincipals($prefixPath, $searchArray);

        return $principals;
    }

    /**
     * Returns the list of members for a group-principal
     *
     * @param string $principal
     * @return array
     */
    public function getGroupMemberSet($principal){
	$principal = $this->getPrincipalByPath($principal);
	
	$groupMemberSet = array();
	//TODO: list group membership for all addressbooks(contactgroups): 
	
	$groupMemberSet[] = $principal['uri'];
	
	return $groupMemberSet;
    }

    /**
     * Returns the list of groups a principal is a member of
     *
     * @param string $principal
     * @return array
     */
    function getGroupMembership($principal){
//	$principal = $this->getPrincipalByPath($principal);
	
	$groupMembership = array($principal);
	
	return $groupMembership;
    }

    /**
     * Updates the list of group members for a group principal.
     *
     * The principals should be passed as a list of uri's.
     *
     * @param string $principal
     * @param array $members
     * @return void
     */
    function setGroupMemberSet($principal, array $members){
	$this->container->get('logger')->error('CardDAV update of Principal-Group-Membership currently possible!');	
    }

}
