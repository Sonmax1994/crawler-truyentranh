<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;

class UpdateViewComicController extends Controller
{
    /**
     * @param Request $request
     * @return mixed
     */
    public function __invoke(Request $request)
    {
        $inputs = $request->all();
        try {
            $comicId = $request->comic_id;
            if (!$comicId) {
                throw new Exception('Missing id param!');
            }

            $comic = $this->comicServices->updateViewComic($comicId);

            $data = [
                'view' => $comic->view
            ];

            return $this->commonResponse($data, 'success', 'Success', $inputs);
        } catch (\Exception $e) {
            return $this->commonResponse([], 'error', $e->getMessage(), $inputs);
        }
    }
}
