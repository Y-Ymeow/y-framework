<?php

declare(strict_types=1);

namespace Admin\Content;

trait HasRevisions
{
    public function revisions()
    {
        return $this->hasMany(PostRevision::class, 'post_id')->orderBy('revision_number', 'desc');
    }

    public function createRevision(?string $summary = null): PostRevision
    {
        $lastRev = PostRevision::where('post_id', $this->id)
            ->orderBy('revision_number', 'desc')
            ->first();

        $revNum = $lastRev ? $lastRev->revision_number + 1 : 1;

        $snapshot = [
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'cover_image' => $this->cover_image,
            'status' => $this->status,
        ];

        $diff = null;
        if ($lastRev) {
            $diff = $this->computeDiff($lastRev, $snapshot);
        }

        $revision = PostRevision::create([
            'post_id' => $this->id,
            'user_id' => function_exists('auth') ? auth()->id() : null,
            'revision_number' => $revNum,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'cover_image' => $this->cover_image,
            'status' => $this->status,
            'summary' => $summary,
            'meta_snapshot' => method_exists($this, 'getAllMeta') ? $this->getAllMeta() : null,
            'diff_preview' => $diff,
        ]);

        $count = PostRevision::where('post_id', $this->id)->count();
        $maxRevisions = 50;
        if ($count > $maxRevisions) {
            $toDelete = $count - $maxRevisions;
            PostRevision::where('post_id', $this->id)
                ->orderBy('revision_number', 'asc')
                ->limit($toDelete)
                ->delete();
        }

        return $revision;
    }

    public function rollbackToRevision(int $revisionId): void
    {
        $revision = PostRevision::where('post_id', $this->id)->findOrFail($revisionId);

        $this->title = $revision->title;
        $this->slug = $revision->slug;
        $this->excerpt = $revision->excerpt;
        $this->content = $revision->content;
        $this->cover_image = $revision->cover_image;
        $this->status = $revision->status;
        $this->save();

        if ($revision->meta_snapshot && method_exists($this, 'syncMeta')) {
            $this->syncMeta($revision->meta_snapshot);
        }
    }

    private function computeDiff(PostRevision $prev, array $current): array
    {
        $diff = [];
        $fields = ['title', 'slug', 'excerpt', 'content', 'cover_image', 'status'];
        foreach ($fields as $field) {
            $old = $prev->{$field} ?? '';
            $new = $current[$field] ?? '';
            if ((string)$old !== (string)$new) {
                $diff[$field] = [
                    'old' => mb_substr((string)$old, 0, 500),
                    'new' => mb_substr((string)$new, 0, 500),
                ];
            }
        }
        return $diff;
    }
}