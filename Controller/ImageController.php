<?php

namespace Theapi\CctvBundle\Controller;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ImageController extends Controller
{

    public function indexAction()
    {
        return $this->idAction();
    }

    public function idAction($id = null)
    {
        $imageManager = $this->get('theapi_cctv.image_manager');
        try {
            $root = $this->get('service_container')->getParameter('theapi_cctv.web_root');

            $file = $imageManager->getImage($id);
            $file = str_replace($root, '', $file);
            $filename = basename($file);

            $previous = $imageManager->getPreviousImage($id);
            $next = $imageManager->getNextImage($id);

            return $this->render(
                'TheapiCctvBundle:Image:index.html.twig',
                array(
                    'file' => $file,
                    'filename' => $filename,
                    'previous' => $previous,
                    'next' => $next,
                )
            );
        } catch (\Exception $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

    }
}
