<?php

namespace Theapi\CctvBundle\Controller;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{

    public function indexAction()
    {
        return $this->videoAction();
    }

    public function videoAction($date = null)
    {
        $imageManager = $this->get('theapi_cctv.image_manager');
        try {
            $file = $imageManager->getVideoFile($date);
            $root = $this->get('service_container')->getParameter('theapi_cctv.web_root');
            $file = str_replace($root, '', $file);
            return $this->render('TheapiCctvBundle:Default:index.html.twig', array('file' => $file));
        } catch (\Exception $e) {
          throw $this->createNotFoundException($e->getMessage());
        }

    }

    /**
     * Serve the file using BinaryFileResponse.
     * 
     * @param string $date
     */
    public function vidAction($date)
    {
        $imageManager = $this->get('theapi_cctv.image_manager');
        try {
            $filename = $imageManager->getVideoFile($date);
            $response = new BinaryFileResponse($filename);
            $response->trustXSendfileTypeHeader();
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_INLINE,
                $filename,
                iconv('UTF-8', 'ASCII//TRANSLIT', $filename)
            );
        } catch (\Exception $e) {
            throw $this->createNotFoundException($e->getMessage());
        }
        
        return $response;
    }

}
