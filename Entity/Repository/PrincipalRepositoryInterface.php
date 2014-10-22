<?php

namespace Secotrust\Bundle\SabreDavBundle\Entity\Repository;

/**
 * Description of AddressbookRepositoryInterface
 *
 * @author lduer
 */
interface PrincipalRepositoryInterface {

    /**
     * Returns a list of principals based on a prefix.
     *
     * This prefix will often contain something like 'principals'. You are only
     * expected to return principals that are in this base path.
     *
     * 
     * @param type $uid
     */
    public function getPrincipalsByPrefix($prefixPath);
    
    /**
     * This method should simply return an array with full principal uri's.
     * 
     * The actual search should be a unicode-non-case-sensitive search. The
     * keys in searchProperties are the WebDAV property names, while the values
     * are the property values to search on.
     * 
     * @param type $prefixPath
     * @param array $searchArray
     */
    public function searchPrincipals($prefixPath, array $searchArray);
    
}
