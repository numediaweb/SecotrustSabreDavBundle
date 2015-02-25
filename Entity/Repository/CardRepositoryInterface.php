<?php

namespace Secotrust\Bundle\SabreDavBundle\Entity\Repository;

/**
 * Description of CardRepositoryInterface
 *
 * @author lduer
 */
interface CardRepositoryInterface {

    /**
     * Find one Card By vCard-UID and Addressbook-id
     * 
     * @param type $uid
     * @param type $addressBookId
     */
    public function findSingleCardByUid($uid=null, $addressBookId=null);
}
