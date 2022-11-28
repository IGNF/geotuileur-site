<?php

namespace App\Controller;

use App\Exception\AppException;
use App\Exception\PlageApiException;
use App\Service\PlageApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/datastores/{datastoreId}/annexe", name="plage_annexe_")
 */
class AnnexeController extends AbstractController
{
    private PlageApiService $plageApi;
    private ParameterBagInterface $params;

    public function __construct(PlageApiService $plageApi, ParameterBagInterface $params)
    {
        $this->plageApi = $plageApi;
        $this->params = $params;
    }

    /**
     * @Route("/{annexeId}/modify-ajax", name="modify_style_string_ajax", options={"expose"=true})
     */
    public function modifyStyleStringAjax($datastoreId, $annexeId, Request $request): Response
    {
        try {
            $styleString = $request->query->get('style-string', '');
            if ('' == $styleString) {
                throw new AppException('[style-string] must be a string of a mapbox style representation', Response::HTTP_BAD_REQUEST);
            }

            $directory = $this->params->get('oneup_uploader_gallery_path');

            $annexe = $this->plageApi->annexe->get($datastoreId, $annexeId);
            $path = $annexe['paths'][0];
            $pathExploded = explode('/', $path);
            $filename = $pathExploded[count($pathExploded) - 1];
            $filepath = $directory.'/'.$filename;

            file_put_contents($filepath, $styleString);

            $response = $this->plageApi->annexe->modify($datastoreId, $annexeId, $filepath);

            return new JsonResponse($response);
        } catch (AppException|PlageApiException $ex) {
            return new JsonResponse($ex->getMessage(), $ex->getCode());
        }
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
