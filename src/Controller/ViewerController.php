<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ViewerController extends AbstractController
{
    /**
     * @Route("/viewer", name="plage_viewer", methods={"GET"}, options={"expose"=true})
     */
    public function index(): Response
    {
        return $this->render('pages/viewer/viewer.html.twig');
    }
}
