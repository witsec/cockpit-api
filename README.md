# Additional API endpoints
This addon to [Cockpit](https://getcockpit.com/) adds a couple of new API endpoints.

### `api/witsec/assets/upload`
Upload one or more files using POST method and `Content-Type: multipart/form-data`.

Parameters:
* `files[]` - array of one or more files (required)
* `folder` - optional folder ID

### `api/witsec/assets/{id}`
Delete a single file using DELETE method.

Parameters:
* `id` - ID of asset (required)

### `api/witsec/assets`
Delete multiple files using DELETE method.

Parameters:
* `ids` - Array of IDs (required)

### `api/witsec/assets/folders`
Retrieve a list of asset folders using GET method. Returns an empty array if no folders exist. Useful if you want to easily find folder IDs.

### `api/witsec/content/allitems/{model}`
This endpoint has the same functionality as the built-in `api/content/items/{model}`, except this one can also return unpublished items. Unpublished items will only be returned if a model has been set to show them, using the following json code in 'meta':

```
{
  showUnpublished: true
}
```

Parameters:
* `model` - Name of the model

## How to install
Simply download this repository and move the folder `witsec-api` into `<cockpit>/addons/`. The new endpoints should now be available and you should see this addon being loaded under Settings > System Info.

## Contributing
If you want to improve this addon - awesome! Simply start coding and create a pull request.

## Requirements
This addon is compatible with Cockpit v2.