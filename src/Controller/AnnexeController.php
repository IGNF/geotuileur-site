<?php

namespace App\Controller;

use App\Exception\PlageApiException;
use App\Service\PlageApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/datastores/{datastoreId}/annexe", name="plage_annexe_")
 */
class AnnexeController extends AbstractController
{
    /** @var PlageApiService */
    private $plageApi;

    public function __construct(PlageApiService $plageApi)
    {
        $this->plageApi = $plageApi;
    }

    /**
     * @Route("/{annexeId}/delete-ajax", name="delete_ajax", options={"expose"=true})
     */
    public function deleteAjax($datastoreId, $annexeId): Response
    {
        try {
            $this->plageApi->annexe->remove($datastoreId, $annexeId);

            return new JsonResponse();
        } catch (PlageApiException $ex) {
            return new JsonResponse($ex->getMessage(), $ex->getCode());
        }
    }
}
