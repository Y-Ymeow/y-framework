<?php

declare(strict_types=1);

namespace App\Api;

use Admin\Content\Post;
use Admin\Content\PostRevision;
use Framework\Http\Request\Request;
use Framework\Http\Response\ApiResponse;
use Framework\Routing\Attribute\Route;
use Framework\Routing\Attribute\RouteGroup;

#[RouteGroup(prefix: 'api/posts', middleware: ['api'])]
class PostController extends ApiController
{
    #[Route(methods: ['GET'])]
    public function index(Request $request): ApiResponse
    {
        $query = Post::query();

        $status = $request->query('status', '');
        if ($status) {
            $query->where('status', $status);
        }

        $categoryId = $request->query('category_id', '');
        if ($categoryId) {
            $query->where('category_id', (int)$categoryId);
        }

        $search = $request->query('search', '');
        if ($search) {
            $query->where('title', 'like', '%' . $search . '%');
        }

        $perPage = (int)$request->query('per_page', '15');
        $page = (int)$request->query('page', '1');

        $result = $query->orderBy('created_at', 'desc')->paginate($perPage, $page);

        return $this->success($result);
    }

    #[Route(methods: ['GET'], path: '{id}')]
    public function show(int $id): ApiResponse
    {
        $post = Post::find($id);
        if (!$post) {
            return $this->notFound('文章不存在');
        }
        return $this->success($post->toArray());
    }

    #[Route(methods: ['POST'])]
    public function store(Request $request): ApiResponse
    {
        $data = $request->all();
        $post = Post::create($data);
        return $this->created($post->toArray());
    }

    #[Route(methods: ['PUT'], path: '{id}')]
    public function update(int $id, Request $request): ApiResponse
    {
        $post = Post::find($id);
        if (!$post) {
            return $this->notFound('文章不存在');
        }
        $post->fill($request->all());
        $post->save();
        return $this->success($post->toArray(), 'updated');
    }

    #[Route(methods: ['DELETE'], path: '{id}')]
    public function destroy(int $id): ApiResponse
    {
        $post = Post::find($id);
        if (!$post) {
            return $this->notFound('文章不存在');
        }
        Post::destroy($id);
        return $this->noContent('deleted');
    }

    #[Route(methods: ['GET'], path: '{id}/revisions')]
    public function revisions(int $id): ApiResponse
    {
        $post = Post::find($id);
        if (!$post) {
            return $this->notFound('文章不存在');
        }
        $revisions = PostRevision::where('post_id', $id)
            ->orderBy('revision_number', 'desc')
            ->get();
        $data = [];
        foreach ($revisions as $rev) {
            $data[] = is_array($rev) ? $rev : $rev->toArray();
        }
        return $this->success($data);
    }

    #[Route(methods: ['GET'], path: '{id}/revisions/{revId}')]
    public function showRevision(int $id, int $revId): ApiResponse
    {
        $revision = PostRevision::where('post_id', $id)->find($revId);
        if (!$revision) {
            return $this->notFound('版本不存在');
        }
        return $this->success($revision->toArray());
    }

    #[Route(methods: ['GET'], path: '{id}/revisions/diff')]
    public function revisionDiff(int $id, Request $request): ApiResponse
    {
        $from = (int)$request->query('from', '0');
        $to = (int)$request->query('to', '0');
        if (!$from || !$to) {
            return $this->error('需要 from 和 to 参数', 400);
        }

        $revFrom = PostRevision::where('post_id', $id)->find($from);
        $revTo = PostRevision::where('post_id', $id)->find($to);
        if (!$revFrom || !$revTo) {
            return $this->notFound('版本不存在');
        }

        $fields = ['title', 'slug', 'excerpt', 'content', 'cover_image', 'status'];
        $diff = [];
        foreach ($fields as $f) {
            $old = $revFrom->{$f} ?? '';
            $new = $revTo->{$f} ?? '';
            if ($old !== $new) {
                $diff[$f] = ['old' => (string)$old, 'new' => (string)$new];
            }
        }

        return $this->success([
            'from' => $revFrom->toArray(),
            'to' => $revTo->toArray(),
            'diff' => $diff,
        ]);
    }

    #[Route(methods: ['POST'], path: '{id}/revisions/{revId}/rollback')]
    public function rollback(int $id, int $revId): ApiResponse
    {
        $post = Post::find($id);
        if (!$post) {
            return $this->notFound('文章不存在');
        }

        $revision = PostRevision::where('post_id', $id)->find($revId);
        if (!$revision) {
            return $this->notFound('版本不存在');
        }

        if (method_exists($post, 'createRevision')) {
            $post->createRevision('回滚到版本 #' . $revId);
        }

        if (method_exists($post, 'rollbackToRevision')) {
            $post->rollbackToRevision($revId);
        }

        return $this->success($post->fresh()->toArray(), 'rolled back');
    }
}