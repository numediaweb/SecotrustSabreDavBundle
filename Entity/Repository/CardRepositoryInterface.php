<?php

namespace Secotrust\Bundle\SabreDavBundle\Entity\Repository;

/**
 * Description of CardRepositoryInterface
 *
 * @author lduer
 */
interface CardRepositoryInterface {

    /**
     * Find one Card By vCard UID
     * 
     * @param type $uid
     */
    public function findSingleCardByUid($uid=null);
    
    /**
     * delete Card by cardUri
     * 
     * @param type $cardUri
     */    
    public function deleteCard($cardUri);
}
