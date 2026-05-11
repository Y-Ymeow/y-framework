<?php

declare(strict_types=1);

namespace App\Api;

use Admin\Content\Tag;
use Framework\Http\Request\Request;
use Framework\Http\Response\ApiResponse;
use Framework\Routing\Attribute\Route;
use Framework\Routing\Attribute\RouteGroup;

#[RouteGroup(prefix: 'api/tags', middleware: ['api'])]
class TagController extends ApiController
{
    #[Route(methods: ['GET'])]
    public function index(Request $request): ApiResponse
    {
        $query = Tag::query()->orderBy('id', 'asc');

        $search = $request->query('search', '');
        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $items = $query->get();
        $data = [];
        foreach ($items as $item) {
            $data[] = is_array($item) ? $item : $item->toArray();
        }

        return $this->success($data);
    }

    #[Route(methods: ['GET'], path: '{id}')]
    public function show(int $id): ApiResponse
    {
        $tag = Tag::find($id);
        if (!$tag) {
            return $this->notFound('标签不存在');
        }
        return $this->success($tag->toArray());
    }

    #[Route(methods: ['POST'])]
    public function store(Request $request): ApiResponse
    {
        $data = $request->all();
        $tag = Tag::create($data);
        return $this->created($tag->toArray());
    }

    #[Route(methods: ['PUT'], path: '{id}')]
    public function update(int $id, Request $request): ApiResponse
    {
        $tag = Tag::find($id);
        if (!$tag) {
            return $this->notFound('标签不存在');
        }
        $tag->fill($request->all());
        $tag->save();
        return $this->success($tag->toArray(), 'updated');
    }

    #[Route(methods: ['DELETE'], path: '{id}')]
    public function destroy(int $id): ApiResponse
    {
        $tag = Tag::find($id);
        if (!$tag) {
            return $this->notFound('标签不存在');
        }
        Tag::destroy($id);
        return $this->noContent('deleted');
    }
}