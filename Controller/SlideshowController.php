<?php

namespace Theapi\CctvBundle\Controller;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SlideshowController extends Controller
{

    public function indexAction()
    {
        return $this->dateAction();
    }

    public function dateAction($date = null)
    {
        $imageManager = $this->get('theapi_cctv.image_manager');
        try {
            $files = $imageManager->getImages($date);
            //$root = $this->get('service_container')->getParameter('theapi_cctv.web_root');
            //$file = str_replace($root, '', $file);
            return $this->render('TheapiCctvBundle:Slideshow:index.html.twig', array('files' => $files));
        } catch (\Exception $e) {
          throw $this->createNotFoundException($e->getMessage());
        }

    }

}
