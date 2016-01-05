<?php

/*
 * This file is part of the SecotrustSabreDavBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Secotrust\Bundle\SabreDavBundle\Controller;

use Sabre\DAV\Server;
use Secotrust\Bundle\SabreDavBundle\SabreDav\HttpResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Sabre\HTTP\Request;

class SabreDavController
{
    /**
     * @var Server
     */
    private $dav;

    /**
     * @param Server          $dav
     * @param RouterInterface $router
     */
    public function __construct(Server $dav, RouterInterface $router)
    {
        $this->dav = $dav;
        $this->dav->setBaseUri($router->generate('secotrust_sabre_dav', array()));//TODO this can be done in service container
    }

    /**
     * @param SymfonyRequest $request
     *
     * @return StreamedResponse
     */
    public function execAction(SymfonyRequest $request)
    {
        $dav = $this->dav;
        $callback = function () use ($dav) {
            $dav->exec();
        };
        $response = new StreamedResponse($callback);
        $dav->httpRequest = new Request($request->getMethod(), $request->getRequestUri(), $request->headers->all(), $request->getContent(true));
        $dav->httpResponse = new HttpResponse($response);

        return $response;
    }
}
