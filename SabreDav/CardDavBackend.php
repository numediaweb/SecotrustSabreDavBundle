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

use Sabre\CardDAV\Backend\BackendInterface;
use Sabre\CardDAV;
use Secotrust\Bundle\SabreDavBundle\Entity\CardInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CardDavBackend implements BackendInterface
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
     * @var EntityManager
     */
    private $_em;
       
    /**
     * @var type 
     */
    private $addressbooks_class;
    
    /**
     * @var type 
     */
    private $cards_class;
    
    /**
     * Create array with Card-Data
     * 
     * @param type $entity
     * @param type $show_id
     * @return array
     */
    private function getCardArray($entity, $show_id = false) {

        if (!($entity instanceof CardInterface)) {
            return false;
        }
            
        $card = array(
            'id' => $entity->getId(),
            'carddata' => $entity->getVCard(),
            'uri' => $entity->getVCardUid().'.vcf',
            'lastmodified' => $entity->getLastmodified(),
            'size' => strlen($entity->getVCard()),
            'etag' => $entity->getETag(),
        );	

        if ($show_id === false){
            unset($card['id']);
        }
        
        return $card;
    } 
    
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

        $this->addressbooks_class = $this->container->getParameter('secotrust.addressbooks_class');
        $this->cards_class = $this->container->getParameter('secotrust.cards_class');
    }

    /**
     * Returns the list of addressbooks for a specific user.
     *
     * Every addressbook should have the following properties:
     *   id - an arbitrary unique id
     *   uri - the 'basename' part of the url
     *   principaluri - Same as the passed parameter
     *
     * Any additional clark-notation property may be passed besides this. Some
     * common ones are :
     *   {DAV:}displayname
     *   {urn:ietf:params:xml:ns:carddav}addressbook-description
     *   {http://calendarserver.org/ns/}getctag
     *
     * @param string $principalUri
     * @return array
     */
    public function getAddressBooksForUser($principalUri) {	
        
        $addressBooks = array();

        // TODO: limit addressbook-listing for all (ACL-)Permissions
        // create Service in Addressbook-Bundle (e.g. for additional permissions)

        $entities = $this->_em->getRepository($this->addressbooks_class)->findAllPrincipalAddressbooks($principalUri);

        foreach ($entities as $entity) {

            // TODO: don't list, if the user doesn't have permission!!

            $addressBooks[] = array(
                'id'  => $entity->getId(),
                'uri' => $entity->getUriLabel(),
                'principaluri' => $principalUri,
                '{DAV:}displayname' => $entity->getLabel(),
                '{' . CardDAV\Plugin::NS_CARDDAV . '}addressbook-description' => $entity->getDescription(),
                '{http://calendarserver.org/ns/}getctag' => $entity->getCtag(),
                '{' . CardDAV\Plugin::NS_CARDDAV . '}supported-address-data' =>
                    new CardDAV\Property\SupportedAddressData(),
            );
        }

        return $addressBooks;
    }

    /**
     * Updates an addressbook's properties
     *
     * See Sabre\DAV\IProperties for a description of the mutations array, as
     * well as the return value.
     *
     * @param mixed $addressBookId
     * @param array $mutations
     * @see Sabre\DAV\IProperties::updateProperties
     * @return bool|array
     */
    public function updateAddressBook($addressBookId, array $mutations) {
        $addressBookId = 0;
        $updates = array();

        foreach($mutations as $property=>$newValue) {

            switch($property) {
                case '{DAV:}displayname' :
                    $updates['setLabel'] = $newValue;
                    break;
                case '{' . CardDAV\Plugin::NS_CARDDAV . '}addressbook-description' :
                    $updates['setDescription'] = $newValue;
                    break;
                default :
                    // If any unsupported values were being updated, we must
                    // let the entire request fail.
                    return false;
            }

        }

        // No values are being updated?
        if (!$updates) {
            return false;
        }

        $addressbook = $this->_em->getRepository($this->addressbooks_class)->find($addressBookId);

        foreach ($updates as $setter => $value){
            if (method_exists($addressbook, $setter)) {
                $addressbook->$setter($value);
            }
        }

        $this->_em->flush();

        return true;
    }
    
    /**
     * Creates a new address book
     *
     * @param string $principalUri
     * @param string $url Just the 'basename' of the url.
     * @param array $properties
     * @return void
     */
    public function createAddressBook($principalUri, $url, array $properties) {
	
        $values = array(
            'displayname' => null,
            'description' => null,
            'principaluri' => $principalUri,
            'uri' => $url,
        );

        foreach($properties as $property=>$newValue) {

            switch($property) {
                case '{DAV:}displayname' :
                    $values['setLabel'] = $newValue;
                    break;
                case '{' . CardDAV\Plugin::NS_CARDDAV . '}addressbook-description' :
                    $values['setDescription'] = $newValue;
                    break;
                default :
                    throw new DAV\Exception\BadRequest('Unknown property: ' . $property);
            }
        }	

        $addressbook = new $this->addressbooks_class();
        
        foreach ($values as $setter => $value){
            if (method_exists($addressbook, $setter)){
                $addressbook->$setter($value);
            }
        }
        
        $this->_em->persist($addressbook);
        $this->_em->flush();
    }

    /**
     * Deletes an entire addressbook and all its contents
     *
     * @param mixed $addressBookId
     * @return void
     */
    public function deleteAddressBook($addressBookId) {
	
	//TODO: delete request for addressbook
	//TODO: check if this should be done via carddav!!
	
    }
    
    /**
     * Returns all cards for a specific addressbook id.
     *
     * This method should return the following properties for each card:
     *   * carddata - raw vcard data
     *   * uri - Some unique url
     *   * lastmodified - A unix timestamp
     *
     * It's recommended to also return the following properties:
     *   * etag - A unique etag. This must change every time the card changes.
     *   * size - The size of the card in bytes.
     *
     * If these last two properties are provided, less time will be spent
     * calculating them. If they are specified, you can also ommit carddata.
     * This may speed up certain requests, especially with large cards.
     *
     * @param mixed $addressbookId
     * @return array
     */  
    public function getCards($addressbookId) {
	
        $contactGroup = $this->_em->getRepository($this->addressbooks_class)->findOneById($addressbookId);
        $entities = $contactGroup->getContactCollection();

        $cards = array();
        foreach ($entities as $entity) {
            $cards[] = $this->getCardArray($entity, true);
        }
        return $cards;	
    }
    
    /**
     * Returns a specfic card.
     *
     * The same set of properties must be returned as with getCards. The only
     * exception is that 'carddata' is absolutely required.
     *
     * @param mixed $addressBookId
     * @param string $cardUri
     * @return array
     */    
    public function getCard($addressBookId, $cardUri) {
	$addressBookId = 0;
	$vCardUid = substr($cardUri, 0, strlen('.vcf')*(-1));

	$entity = $this->_em->getRepository($this->cards_class)->findSingleCardByUid($vCardUid);

        return $this->getCardArray($entity, true);
    }
    
    /**
     * Creates a new card.
     *
     * The addressbook id will be passed as the first argument. This is the
     * same id as it is returned from the getAddressbooksForUser method.
     *
     * The cardUri is a base uri, and doesn't include the full path. The
     * cardData argument is the vcard body, and is passed as a string.
     *
     * It is possible to return an ETag from this method. This ETag is for the
     * newly created resource, and must be enclosed with double quotes (that
     * is, the string itself must contain the double quotes).
     *
     * You should only return the ETag if you store the carddata as-is. If a
     * subsequent GET request on the same card does not have the same body,
     * byte-by-byte and you did return an ETag here, clients tend to get
     * confused.
     *
     * If you don't return an ETag, you can just return null.
     *
     * @param mixed $addressBookId
     * @param string $cardUri
     * @param string $cardData
     * @return string|null
     */  
    public function createCard($addressBookId, $cardUri, $cardData) {
        
        $addressbook = $this->_em->getRepository($this->addressbooks_class)->find($addressBookId);
        
        $card = new $this->cards_class();
        $card->setVCard($cardData);
        $card->setVCardUid($cardUri);
        
        $this->_em->persist($card);
        $this->_em->flush();
        
	return null;
    }
    
    /**
     * Updates a card.
     *
     * The addressbook id will be passed as the first argument. This is the
     * same id as it is returned from the getAddressbooksForUser method.
     *
     * The cardUri is a base uri, and doesn't include the full path. The
     * cardData argument is the vcard body, and is passed as a string.
     *
     * It is possible to return an ETag from this method. This ETag should
     * match that of the updated resource, and must be enclosed with double
     * quotes (that is: the string itself must contain the actual quotes).
     *
     * You should only return the ETag if you store the carddata as-is. If a
     * subsequent GET request on the same card does not have the same body,
     * byte-by-byte and you did return an ETag here, clients tend to get
     * confused.
     *
     * If you don't return an ETag, you can just return null.
     *
     * @param mixed $addressBookId
     * @param string $cardUri
     * @param string $cardData
     * @return string|null
     */    
    public function updateCard($addressBookId, $cardUri, $cardData) {
	        
        $card = $this->_em->getRepository($this->cards_class)->findSingleCardByUid($addressBookId);
        $card->setVCard($cardData);
        
        $addressbook = $this->_em->getRepository($this->addressbooks_class)->find($addressBookId);
        $addressbook->updateCTag();
        
        $this->_em->flush();
        return null;
    }
    
    /**
     * Deletes a card
     *
     * @param mixed $addressBookId
     * @param string $cardUri
     * @return bool
     */    
    public function deleteCard($addressBookId, $cardUri) {
	
        return $this->_em->getRepository($this->cards_class)->deleteCard($cardUri);    
    }

}
