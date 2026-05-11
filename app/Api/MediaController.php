<?php

declare(strict_types=1);

namespace App\Api;

use Admin\Content\Media;
use Framework\Http\Request\Request;
use Framework\Http\Response\ApiResponse;
use Framework\Routing\Attribute\Route;
use Framework\Routing\Attribute\RouteGroup;

#[RouteGroup(prefix: 'api/media', middleware: ['api'])]
class MediaController extends ApiController
{
    #[Route(methods: ['GET'])]
    public function index(Request $request): ApiResponse
    {
        $query = Media::query();

        $filter = $request->query('filter', 'all');
        if ($filter === 'image') {
            $query->where('mime_type', 'like', 'image/%');
        } elseif ($filter === 'video') {
            $query->where('mime_type', 'like', 'video/%');
        } elseif ($filter === 'document') {
            $query->where('extension', 'in', ['pdf', 'doc', 'docx', 'xls', 'xlsx']);
        }

        $search = $request->query('search', '');
        if ($search) {
            $query->where('filename', 'like', '%' . $search . '%');
        }

        $perPage = (int)$request->query('per_page', '40');
        $page = (int)$request->query('page', '1');

        $result = $query->orderBy('created_at', 'desc')->paginate($perPage, $page);

        return $this->success($result);
    }

    #[Route(methods: ['GET'], path: '{id}')]
    public function show(int $id): ApiResponse
    {
        $media = Media::find($id);
        if (!$media) {
            return $this->notFound('媒体文件不存在');
        }
        return $this->success($media->toArray());
    }

    #[Route(methods: ['PUT'], path: '{id}')]
    public function update(int $id, Request $request): ApiResponse
    {
        $media = Media::find($id);
        if (!$media) {
            return $this->notFound('媒体文件不存在');
        }
        $media->fill($request->all());
        $media->save();
        return $this->success($media->toArray(), 'updated');
    }

    #[Route(methods: ['DELETE'], path: '{id}')]
    public function destroy(int $id): ApiResponse
    {
        $media = Media::find($id);
        if (!$media) {
            return $this->notFound('媒体文件不存在');
        }
        Media::destroy($id);
        return $this->noContent('deleted');
    }
}