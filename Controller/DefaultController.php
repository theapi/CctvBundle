<?php

namespace Theapi\RobocopBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        $name = 'Robocop';
        return $this->render('TheapiRobocopBundle:Default:index.html.twig', array('name' => $name));
    }
}
