<?php

namespace ForestAdmin\ForestLaravel\Http\Controllers;

use App\Http\Controllers\Controller;
use ForestAdmin\ForestLaravel\DatabaseStructure;
use ForestAdmin\ForestLaravel\Liana;
use ForestAdmin\Liana\Api\ResourceFilter;
use ForestAdmin\Liana\Exception\CollectionNotFoundException;
use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Redis\Database;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class LianaController extends Controller
{

    /**
     * @param $modelName
     * @param $recordId
     * @return JsonResponse
     */
    public function getResource($modelName, $recordId) {

        try {
            $collections = DatabaseStructure::getCollections();
            $liana = (new Liana)->setCollections($collections);
            $resource = $liana->getResource($modelName, $recordId);
        } catch (CollectionNotFoundException $exc) {
            return Response::make('Collection not found', 404);
        }

        return $this->returnJson($resource);
    }

    /**
     * @param Request $request
     * @param $modelName
     * @return Response
     */
    public function listResources(Request $request, $modelName)
    {
        try {
            $collections = DatabaseStructure::getCollections();
            $liana = (new Liana)->setCollections($collections);
            $filter = new ResourceFilter($request->all());
            $resources = $liana->listResources($modelName, $filter);
        } catch (CollectionNotFoundException $exc) {
            return new Response('Collection not Found', 404);
        }

        return $this->returnJson($resources);
    }

    /**
     * @param $modelName
     * @param $recordId
     * @param $associationName
     * @param Request $request
     */
    public function getHasMany($modelName, $recordId, $associationName, Request $request)
    {

    }

    public function createResource(Request $request, $modelName)
    {
        try {
            $collections = DatabaseStructure::getCollections();
            $liana = (new Liana)->setCollections($collections);
            $contentData = $this->getContentData($request);
            $resource = $liana->createResource($modelName, $contentData);

            return $this->returnJson($resource);
        } catch(\Exception $exc) {
            return new Response($exc->getMessage(), 400);
        }
    }

    public function updateResource($modelName, $recordId, Request $request)
    {
        try {
            $collections = DatabaseStructure::getCollections();
            $liana = (new Liana)->setCollections($collections);
            $contentData = $this->getContentData($request);
            $resource = $liana->updateResource($modelName, $recordId, $contentData);
            
            return $this->returnJson($resource);
        } catch(\Exception $exc) {
            return new Response('Bad request : '.$exc->getMessage(), 400);
        }
    }

    protected function returnJson($resource)
    {
        $response = new JsonResponse($resource);
        $response->setEncodingOptions(
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT |
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        return $response;
    }

    protected function getContentData(Request $request)
    {
        $content = json_decode($request->getContent(), true);

        if (!array_key_exists('data', $content) || !array_key_exists('attributes', $content['data'])) {
            throw new \Exception('Malformed content');
        }

        return $content;
    }
}
