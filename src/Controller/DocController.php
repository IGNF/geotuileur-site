<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DocController extends AbstractController
{
    /**
     * @Route("/doc", name="plage_doc", methods={"GET"})
     */
    public function docsify(): Response
    {
        return $this->render('pages/doc.html.twig');
    }
}
