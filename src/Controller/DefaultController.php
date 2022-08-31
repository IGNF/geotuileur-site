<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("", name="plage_")
 */
class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="home", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('pages/home.html.twig');
    }

    /**
     * Mentions légales.
     *
     * @Route("/mentions-legales", name="about", methods={"GET"})
     */
    public function about()
    {
        return $this->render('pages/about.html.twig');
    }

    /**
     * Information d'accessibilité.
     *
     * @Route("/accessibilite", name="accessibility", methods={"GET"})
     */
    public function accessibility()
    {
        return $this->render('pages/accessibility.html.twig');
    }

    /**
     * Conditions générales d'utilisation.
     *
     * @Route("/cgu", name="cgu", methods={"GET"})
     */
    public function cgu()
    {
        return $this->render('pages/cgu.html.twig');
    }

    /**
     * Gestion des cookies.
     *
     * @Route("/gestion-des-cookies", name="cookies", methods={"GET"})
     */
    public function cookies()
    {
        return $this->render('pages/cookies.html.twig');
    }
}
