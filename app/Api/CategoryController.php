<?php

declare(strict_types=1);

namespace App\Api;

use Admin\Content\Category;
use Framework\Http\Request\Request;
use Framework\Http\Response\ApiResponse;
use Framework\Routing\Attribute\Route;
use Framework\Routing\Attribute\RouteGroup;

#[RouteGroup(prefix: 'api/categories', middleware: ['api'])]
class CategoryController extends ApiController
{
    #[Route(methods: ['GET'])]
    public function index(Request $request): ApiResponse
    {
        $query = Category::query()->orderBy('sort', 'asc');

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
        $category = Category::find($id);
        if (!$category) {
            return $this->notFound('分类不存在');
        }
        return $this->success($category->toArray());
    }

    #[Route(methods: ['POST'])]
    public function store(Request $request): ApiResponse
    {
        $data = $request->all();
        $category = Category::create($data);
        return $this->created($category->toArray());
    }

    #[Route(methods: ['PUT'], path: '{id}')]
    public function update(int $id, Request $request): ApiResponse
    {
        $category = Category::find($id);
        if (!$category) {
            return $this->notFound('分类不存在');
        }
        $category->fill($request->all());
        $category->save();
        return $this->success($category->toArray(), 'updated');
    }

    #[Route(methods: ['DELETE'], path: '{id}')]
    public function destroy(int $id): ApiResponse
    {
        $category = Category::find($id);
        if (!$category) {
            return $this->notFound('分类不存在');
        }
        Category::destroy($id);
        return $this->noContent('deleted');
    }
}