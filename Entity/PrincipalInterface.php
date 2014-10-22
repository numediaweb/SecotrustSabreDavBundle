<?php

namespace Secotrust\Bundle\SabreDavBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Description of PrincipalInterface
 *
 * @author lduer
 */
interface PrincipalInterface {
    
    /**
     * Get id
     *
     * @return integer 
     */    
    public function getId();
        
    /**
     * get username
     * 
     * @return string
     */    
    public function getUsername();
    
    /**
     * get Email
     * 
     * @return string
     */    
    public function getEmail();

    /**
     * requried to define me-card as a property on the users' addressbook'
     * 
     * @return string
     */
    public function getVCardUrl();
}
