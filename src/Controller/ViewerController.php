<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ViewerController extends AbstractController
{
    /** @var ParameterBagInterface */
    private $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    /**
     * @Route("/viewer", name="plage_viewer", methods={"GET"}, options={"expose"=true})
     */
    public function index(Request $request): Response
    {
        return $this->render('pages/viewer/viewer.html.twig', []);
    }
}
