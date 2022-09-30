<?php

namespace App\Controller;

use App\Service\PlageApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("", name="plage_users_")
 */
class UserController extends AbstractController
{
    /** @var PlageApiService */
    private $plageApi;

    public function __construct(PlageApiService $plageApi)
    {
        $this->plageApi = $plageApi;
    }

    /**
     * @Route("/mon-compte", name="me", methods={"GET"})
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function me(): Response
    {
        return $this->render('pages/user/me.html.twig', [
            'user' => $this->plageApi->user->getMe(),
        ]);
    }
}
