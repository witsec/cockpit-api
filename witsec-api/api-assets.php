<?php

/**
 *
 * @OA\Tag(
 *   name="assets",
 *   description="Assets module",
 * )
 */
$this->on('restApi.config', function($restApi) {
	$restApi->addEndPoint('/witsec/assets/upload', [
		'POST' => function($params, $app) {
			// Check if user has access to the model
			if (!$app->helper('acl')->isAllowed("assets/upload", $app->helper('auth')->getUser('role'))) {
				$app->response->status = 403;
				return ['error' => 'Permission denied'];
			}

			// Check if a file upload is present
			if (sizeof($_FILES) === 0) {
				$app->response->status = 412;
				return ['error' => 'Item data is missing'];
			}

			$folderId = $app->param('folder:string', '');

			// Check if a folder exists with this id
			if ($folderId) {
				$folders = $app->module('assets')->folders();

				$folderFound = false;
				foreach($folders as $f) {
					if ($folderId == $f['_id']) {
						$folderFound = true;
						break;
					}
				}

				if (!$folderFound) {
					$app->response->status = 404;
					return ['error' => 'Folder not found'];
				}
			}

			// Upload file(s)
			$meta = ['folder' => $folderId];
			return $app->module('assets')->upload('files', $meta);
		}
	]);

	/**
	 * @OA\Delete(
	 *     path="/witsec/assets/{id}",
	 *     tags={"assets"},
	 *     @OA\Parameter(
	 *         description="Asset ID",
	 *         in="path",
	 *         name="id",
	 *         required=true,
	 *         @OA\Schema(type="string")
	 *     ),
	 *     @OA\Response(response="200", description="Asset removed"),
	 *     @OA\Response(response="403", description="Permission denied"),
	 *     @OA\Response(response="404", description="Asset not found")
	 * )
	 */
	$restApi->addEndPoint('/witsec/assets/{id}', [
		'DELETE' => function($params, $app) {
			// Check if user is allowed to perform operation
			if (!$app->helper('acl')->isAllowed("assets/delete", $app->helper('auth')->getUser('role'))) {
				$app->response->status = 403;
				return ['error' => 'Permission denied'];
			}

			$id = $params['id'];

			if ($app->dataStorage->findOne('assets', ['_id' => $id])) {
				return $app->module('assets')->remove([$id]);
			} else {
				$app->response->status = 404;
				return ['error' => 'Asset not found'];
			}
		}
	]);

	/**
	 * @OA\Delete(
	 *     path="/witsec/assets",
	 *     tags={"assets"},
	 *     @OA\Parameter(
	 *         description="Array of asset IDs",
	 *         in="query",
	 *         name="ids",
	 *         required=true,
	 *         @OA\Schema(type="json")
	 *     ),
	 *     @OA\Response(response="200", description="Assets removed"),
	 *     @OA\Response(response="403", description="Permission denied")
	 * )
	 */
	$restApi->addEndPoint('/witsec/assets', [
		'DELETE' => function($params, $app) {
			// Check if user is allowed to perform operation
			if (!$app->helper('acl')->isAllowed("assets/delete", $app->helper('auth')->getUser('role'))) {
				$app->response->status = 403;
				return ['error' => 'Permission denied'];
			}

			try {
				$ids = json5_decode($app->param('ids'));
			} catch(\Throwable $e) {
				$app->response->status = 400;
				return ['error' => "<{ids}> is not valid json"];
			}

			// Try to delete whatever IDs are sent
			$app->module('assets')->remove($ids);
			return ['success' => true];
		}
	]);

	/**
	 * @OA\Get(
	 *     path="/witsec/assets/folders",
	 *     tags={"assets"},
	 *     @OA\Response(response="200", description="Get list of folders")
	 * )
	 */
    $restApi->addEndPoint('/witsec/assets/folders', [
        'GET' => function($params, $app) {
            $folders = $this->module('assets')->folders();
            return $folders;
        }
    ]);
});