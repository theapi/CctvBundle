<?php

namespace Theapi\CctvBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        $name = 'CCTV';
        return $this->render('TheapiCctvBundle:Default:index.html.twig', array('name' => $name));
    }
}
