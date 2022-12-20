<?php

/**
 *
 * @OA\Tag(
 *   name="content",
 *   description="Content module",
 * )
 */
$this->on('restApi.config', function($restApi) {

	/**
	 * @OA\Get(
	 *     path="/witsec/content/allitems/{model}",
	 *     tags={"content"},
	 *     @OA\Parameter(
	 *         description="Model name",
	 *         in="path",
	 *         name="model",
	 *         required=true,
	 *         @OA\Schema(type="string")
	 *     ),
	 *    @OA\Parameter(
	 *         description="Return content for specified locale",
	 *         in="query",
	 *         name="locale",
	 *         required=false,
	 *         @OA\Schema(type="String")
	 *     ),
	 *     @OA\Parameter(
	 *         description="Url encoded filter json",
	 *         in="query",
	 *         name="filter",
	 *         required=false,
	 *         @OA\Schema(type="json")
	 *     ),
	 *     @OA\Parameter(
	 *         description="Url encoded sort json",
	 *         in="query",
	 *         name="sort",
	 *         required=false,
	 *         @OA\Schema(type="json")
	 *     ),
	 *     @OA\Parameter(
	 *         description="Url encoded fields projection as json",
	 *         in="query",
	 *         name="fields",
	 *         required=false,
	 *         @OA\Schema(type="json")
	 *     ),
	 *     @OA\Parameter(
	 *         description="Max amount of items to return",
	 *         in="query",
	 *         name="limit",
	 *         required=false,
	 *         @OA\Schema(type="int")
	 *     ),
	 *     @OA\Parameter(
	 *         description="Amount of items to skip",
	 *         in="query",
	 *         name="skip",
	 *         required=false,
	 *         @OA\Schema(type="int")
	 *     ),
	 *     @OA\Parameter(
	 *         description="Populate items with linked content items.",
	 *         in="query",
	 *         name="populate",
	 *         required=false,
	 *         @OA\Schema(type="int")
	 *     ),
	 *     @OA\OpenApi(
	 *         security={
	 *             {"api_key": {}}
	 *         }
	 *     ),
	 *     @OA\Response(response="200", description="Get list of published model items"),
	 *     @OA\Response(response="401", description="Unauthorized"),
	 *     @OA\Response(response="404", description="Model not found")
	 * )
	 */
    $restApi->addEndPoint('/witsec/content/allitems/{model}', [
        'GET' => function($params, $app) {
            $model = $params['model'];

            // Check if model exists
            if (!$app->module('content')->model($model)) {
                $app->response->status = 404;
                return ["error" => "Model <{$model}> not found"];
            }

            // Check if user is allowed to perform operation
            if (!$app->helper('acl')->isAllowed("content/{$model}/read", $app->helper('auth')->getUser('role'))) {
                $app->response->status = 403;
                return ['error' => 'Permission denied'];
            }

            $options = [];
            $process = ['locale' => $app->param('locale', 'default')];

            $limit = $app->param('limit:int', null);
            $skip = $app->param('skip:int', null);
            $populate = $app->param('populate:int', null);
            $filter = $app->param('filter:string', null);
            $sort = $app->param('sort:string', null);
            $fields = $app->param('fields:string', null);

            if (!is_null($filter)) $options['filter'] = $filter;
            if (!is_null($sort)) $options['sort'] = $sort;
            if (!is_null($fields)) $options['fields'] = $fields;
            if (!is_null($limit)) $options['limit'] = $limit;
            if (!is_null($skip)) $options['skip'] = $skip;

            foreach (['filter', 'fields', 'sort'] as $prop) {
                if (isset($options[$prop])) {
                    try {
                        $options[$prop] = json5_decode($options[$prop], true);
                    } catch(\Throwable $e) {
                        $app->response->status = 400;
                        return ['error' => "<{$prop}> is not valid json"];
                    }
                }
            }

            if ($populate) {
                $process['populate'] = $populate;
            }

            if (!isset($options['filter']) || !is_array($options['filter'])) {
                $options['filter'] = [];
            }

            // If the model doesn't allow to show unpublished (state=0) entries, force state=1
            $modelProperties = $app->module('content')->model($model);
            if ($modelProperties["meta"]["showUnpublished"] !== true) {    
                $options['filter']['_state'] = 1;
            }

            $items = $app->module('content')->items($model, $options, $process);

            if (isset($options['skip'], $options['limit'])) {
                return [
                    'data' => $items,
                    'meta' => [
                        'total' => $app->module('content')->count($model, $options['filter'] ?? [])
                    ]
                ];
            }

            if (count($items)) {
                $app->trigger('content.api.items', [&$items, $model]);
            }

            return $items;
        }
    ]);

});