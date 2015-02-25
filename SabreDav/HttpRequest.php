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

use Sabre\HTTP\Request as BaseRequest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class HttpRequest
 */
class HttpRequest extends BaseRequest {

    /**
     * @var Request
     */
    private $request;

    /**
     * Constructor
     *
     * @param Request $request
     */
    public function __construct(Request $request) {
        parent::__construct($request->getMethod(), $request->getRequestUri(), $request->headers->all(), $request->getContent(true));
        $this->request = $request;
    }

    /**
     * set the current username
     * 
     * @param string $username
     */
    public function setCurrentUsername($username) {
        $this->currentUsername = $username;
    }
}
