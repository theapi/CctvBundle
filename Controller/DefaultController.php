<?php

namespace Theapi\CctvBundle\Controller;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{

    public function indexAction()
    {
        return $this->render('TheapiCctvBundle:Default:index.html.twig', array('date' => 'today'));
    }

    public function videoAction($date)
    {
        return $this->render('TheapiCctvBundle:Default:index.html.twig', array('date' => $date));
    }

    /**
     * This doesn't work too well. Android chrome will hang on longer videos
     * @param string $date
     */
    public function vidAction($date)
    {
        $imageManager = $this->get('theapi_cctv.image_manager');
        try {
          $file = $imageManager->getVideoFile($date);
          return new BinaryFileResponse($file);
        } catch (\Exception $e) {
          // 404
        }
    }

}
