<?php

namespace Secotrust\Bundle\SabreDavBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Description of AddressbookInterface
 *
 * @author lduer
 */
interface AddressbookInterface {

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId();
    
    /**
     * Get label
     *
     * @return string 
     */
    public function getLabel();
    
    /**
     * Set label
     *
     * @param string $label
     * @return $this
     */
    public function setLabel($label);
       
    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription();
    
    /**
     * Set description
     *
     * @param string $label
     * @return $this
     */
    public function setDescription($description);
    
    /**
     * Get the value of the current CTag
     * 
     * @return string
     */
    public function getCtag();
    
    /**
     * updates the cTag of the current Group 
     * 
     * @return $this
     */
    public function updateCTag();
    
    /**
     * get the Uri
     * 
     * @return type
     */    
    public function getUri();
    

    /**
     * Get all Contacts for current Addressbook
     * 
     * @return array
     */
    public function getContactList();

}
