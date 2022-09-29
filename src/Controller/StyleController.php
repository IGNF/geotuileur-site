<?php

namespace App\Controller;

use App\Exception\PlageApiException;
use App\Service\PlageApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/datastores/{datastoreId}/pyramid/{pyramidId}", name="plage_style_")
 */
class StyleController extends AbstractController
{
    /** @var PlageApiService */
    private $plageApi;

    /** @var ParameterBagInterface */
    private $params;

    public function __construct(PlageApiService $plageApi, ParameterBagInterface $parameters)
    {
        $this->plageApi = $plageApi;
        $this->params = $parameters;
    }

    /**
     * @Route("/styles", name="manage", methods={"GET","POST"}, options={"expose"=true})
     */
    public function manage($datastoreId, $pyramidId)
    {
        $pyramid = $this->plageApi->storedData->get($datastoreId, $pyramidId);

        // Recherche des styles
        $result = $this->plageApi->storedData->getStyles($datastoreId, $pyramidId);

        return $this->render('pages/style/manage.html.twig', [
            'datastore' => $this->plageApi->datastore->get($datastoreId),
            'pyramid' => $pyramid,
            'styles' => $result['styles'],
            'defaultStyle' => $result['defaultStyle'],
        ]);
    }

    /**
     * AJAX Request
     * Ajout d'un style.
     *
     * @Route("/styles/add",
     *      name="add_ajax",
     *      options={"expose"=true},
     *      condition="request.isXmlHttpRequest()",
     *      methods={"POST"}
     * )
     * Response JSON
     */
    public function ajaxAdd($datastoreId, $pyramidId, Request $request)
    {
        try {
            $tagStyles = $this->plageApi->storedData->getTagStyles($datastoreId, $pyramidId);

            $name = $request->request->get('name');
            $file = $request->files->get('file');
            $extension = $file->getClientOriginalExtension();

            $id = uniqid();
            $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $filename = "$filename-$id.$extension";

            $path = "/datastores/$datastoreId/$pyramidId/styles/$filename";

            // Ajout de l'annexe
            $annexe = $this->plageApi->annexe->add($datastoreId, $file, $path);

            // Mise a jour des tags de $pyramid
            $id = $annexe['_id'];
            $tagStyles['styles'][$id] = $name;
            $tags = [
                'default_style' => $id,
                'styles' => json_encode($tagStyles['styles']),
            ];

            $this->plageApi->storedData->addTags($datastoreId, $pyramidId, $tags);

            $url = $this->params->get('api_plage_annexe_url').$annexe['paths'][0];

            return new JsonResponse(['id' => $id, 'name' => $name, 'url' => $url]);
        } catch (PlageApiException $e) {
            return new JsonResponse($e->getMessage(), $e->getCode());
        }
    }

    /**
     * AJAX Request
     * Ajout d'un style.
     *
     * @Route("/styles/add-mapbox",
     *      name="add_ajax_mapbox",
     *      options={"expose"=true},
     *      condition="request.isXmlHttpRequest()",
     *      methods={"POST"}
     * )
     * Response JSON
     */
    public function ajaxAddMapbox($datastoreId, $pyramidId, Request $request)
    {
        try {
            $tagStyles = $this->plageApi->storedData->getTagStyles($datastoreId, $pyramidId);

            $name = $request->request->get('name');
            $style = $request->request->get('style');

            $id = uniqid();
            $filename = "mapbox-$id.json";

            $filepath = join([$this->params->get('oneup_uploader_gallery_path'), DIRECTORY_SEPARATOR, $filename]);
            file_put_contents($filepath, $style);
            $file = new UploadedFile($filepath, $filename, 'application/json', null, true);

            // Ajout de l'annexe
            $path = "/datastores/$datastoreId/$pyramidId/styles/$filename";
            $annexe = $this->plageApi->annexe->add($datastoreId, $file, $path);

            // Mise a jour des tags de $pyramid
            $id = $annexe['_id'];
            $tagStyles['styles'][$id] = $name;
            $tags = [
                'default_style' => $id,
                'styles' => json_encode($tagStyles['styles']),
            ];

            $this->plageApi->storedData->addTags($datastoreId, $pyramidId, $tags);

            $url = $this->params->get('api_plage_annexe_url').$annexe['paths'][0];

            return new JsonResponse(['id' => $id, 'name' => $name, 'url' => $url]);
        } catch (PlageApiException $e) {
            return new JsonResponse($e->getMessage(), $e->getCode());
        }
    }

    /**
     * AJAX Request
     * Ajout d'un style.
     *
     * @Route("/styles/change-default/{annexeId}",
     *      name="change_default_ajax",
     *      options={"expose"=true},
     *      condition="request.isXmlHttpRequest()",
     *      methods={"POST"}
     * )
     * Response JSON
     */
    public function ajaxChangeDefault($datastoreId, $pyramidId, $annexeId)
    {
        try {
            // Mise a jour des tags de $pyramid
            $this->plageApi->storedData->addTags($datastoreId, $pyramidId, ['default_style' => $annexeId]);

            return new JsonResponse();
        } catch (PlageApiException $e) {
            return new JsonResponse($e->getMessage(), $e->getCode());
        }
    }

    /**
     * AJAX Request
     * Suppression d'un style.
     *
     * @Route("/styles/remove/{annexeId}",
     *      name="remove_ajax",
     *      options={"expose"=true},
     *      condition="request.isXmlHttpRequest()",
     *      methods={"POST"}
     * )
     * Response JSON
     */
    public function ajaxRemove($datastoreId, $pyramidId, $annexeId)
    {
        try {
            // On recupere les styles
            $tagStyles = $this->plageApi->storedData->getTagStyles($datastoreId, $pyramidId);

            // Suppression de l'annexe
            $this->plageApi->annexe->remove($datastoreId, $annexeId);

            // Mise a jour des tags dans pyramid
            unset($tagStyles['styles'][$annexeId]);
            $defaultStyle = $tagStyles['default_style'];

            if (empty($tagStyles['styles'])) {    // Suppression des tags dans le storedData
                $this->plageApi->storedData->removeTags($datastoreId, $pyramidId, ['styles', 'default_style']);

                return new JsonResponse(['styles' => [], 'default_style' => null]);
            }

            if ($defaultStyle == $annexeId) {  // Suppression du style par defaut, on met le dernier
                $ids = array_keys($tagStyles['styles']);
                $defaultStyle = end($ids);
            }

            // Mise a jour des tags
            $tags = [
                'default_style' => $defaultStyle,
                'styles' => json_encode($tagStyles['styles']),
            ];
            $this->plageApi->storedData->addTags($datastoreId, $pyramidId, $tags);

            // On recupere les styles mis a jour
            $result = $this->plageApi->storedData->getStyles($datastoreId, $pyramidId);

            return new JsonResponse($result);
        } catch (PlageApiException $e) {
            return new JsonResponse($e->getMessage(), $e->getCode());
        }
    }

    /**
     * AJAX Request
     * Telechargement d'un style.
     *
     * @Route("/styles/download/{annexeId}",
     *      name="download_ajax",
     *      options={"expose"=true},
     *      condition="request.isXmlHttpRequest()",
     *      methods={"POST"}
     * )
     * Response JSON
     */
    public function ajaxDownload($datastoreId, $annexeId)
    {
        try {
            $annexe = $this->plageApi->annexe->get($datastoreId, $annexeId);

            $filepath = $annexe['paths'][0];
            $basename = pathinfo($filepath, PATHINFO_BASENAME);

            $response = new BinaryFileResponse($annexe['paths'][0]);
            $response->headers->set('Content-Type', 'text/plain');
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $basename);

            return $response;
        } catch (PlageApiException $e) {
            return new JsonResponse($e->getMessage(), $e->getCode());
        }
    }
}
