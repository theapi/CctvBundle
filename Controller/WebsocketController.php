<?php

namespace Theapi\CctvBundle\Controller;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class WebsocketController extends Controller
{

    public function indexAction()
    {
        // @todo configurable host.
        return $this->render(
            'TheapiCctvBundle:Websocket:index.html.twig',
            array('host' => 'ws://mythtv.theapi.co.uk:8080')
        );
    }
}
