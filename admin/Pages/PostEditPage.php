<?php

namespace Admin\Pages;

use Admin\Content\Category;
use Admin\Content\Post;
use Admin\Contracts\Live\AdminLayout;
use Framework\Component\Live\EmbeddedLiveComponent;
use Framework\Component\Live\Attribute\LiveAction;
use Framework\Component\Live\Attribute\LiveListener;
use Framework\Component\Live\Attribute\State;
use Framework\UX\Form\RichTextEditor;
use Framework\UX\Form\Components\MediaPicker;
use Framework\UX\Form\TagInput;
use Framework\UX\Navigation\Tabs;
use Framework\UX\UI\Button;
use Framework\UX\UXComponent;
use Framework\View\Base\Element;
use Framework\View\Element\Container;
use Framework\View\Element\Text;

class PostEditPage extends EmbeddedLiveComponent
{
    #[State]
    public ?int $recordId = null;

    #[State]
    public string $title = '';

    #[State]
    public string $slug = '';

    #[State]
    public string $content = '';

    #[State]
    public string $excerpt = '';

    #[State]
    public string $status = 'draft';

    #[State]
    public ?int $categoryId = null;

    #[State]
    public array $tagIds = [];

    #[State]
    public string $coverImage = '';

    #[State]
    public bool $saved = false;

    public function mount(?int $id = null): void
    {
        if (!$id) {
            return;
        }

        $post = Post::find($id);

        if (!$post) {
            return;
        }

        $this->recordId = $id;
        $this->title = $post->title;
        $this->slug = $post->slug;
        $this->content = $post->content;
        $this->excerpt = $post->excerpt;
        $this->status = $post->status;
        $this->categoryId = $post->category_id;
        $this->coverImage = $this->normalizeMediaUrl($post->cover_image);
        $this->tagIds = $post->getTagIds();
    }

    public static function go(?int $id = null): AdminLayout
    {
        $page = new static();
        $page->recordId = $id;
        $page->named('post-edit-' . ($id ?: 'create'));

        $layout = new AdminLayout();
        $layout->activeMenu = 'posts';
        $layout->setContent($page);

        return $layout;
    }

    #[LiveAction]
    public function save(): void
    {
        $model = $this->recordId ? Post::find($this->recordId) : new Post();

        if (!$model) {
            return;
        }

        $model->title = $this->title;
        $model->slug = $this->slug ?: $this->generateSlug();
        $model->content = $this->content;
        $model->excerpt = $this->excerpt;
        $model->status = $this->status;
        $model->category_id = $this->categoryId;
        $model->cover_image = $this->coverImage;

        if (!$model->user_id) {
            $model->user_id = auth()->id();
        }

        if ($this->status === 'published' && !$model->published_at) {
            $model->published_at = date('Y-m-d H:i:s');
        }

        $model->save();

        if (!$this->recordId) {
            $this->recordId = (int) $model->id;
        }

        $model->syncTags($this->tagIds);

        $this->saved = true;
        $this->toast('success', t('admin:posts.toast.saved', [], '保存成功'));
        $this->refresh('post-editor');
    }

    #[LiveAction]
    public function publish(): void
    {
        $this->status = 'published';
        $this->save();
    }

    #[LiveAction]
    public function selectMedia(array $params): void
    {
        $url = $params['url'] ?? '';
        $fieldName = $params['name'] ?? '';
        $modalId = $params['modalId'] ?? '';

        if ($fieldName === 'cover_image') {
            $this->coverImage = $url;
        }

        if ($modalId) {
            $this->closeModal($modalId);
        }

        $this->refresh('post-editor');
    }

    #[LiveAction]
    public function applyLink(array $params): void
    {
        $this->closeModal($params['modalId'] ?? '');
        $this->refresh('post-editor');
    }

    public function render(): Element
    {
        $wrapper = Element::make('div')->class('post-edit-wrapper');
        $wrapper->liveFragment('post-editor');

        $wrapper->child($this->renderToolbar());
        $wrapper->child($this->renderEditor());

        return $wrapper;
    }

    protected function renderToolbar(): Element
    {
        $toolbar = Element::make('div')
            ->class('post-edit-toolbar', 'flex', 'items-center', 'justify-between', 'px-6', 'py-3', 'border-b', 'border-gray-200', 'bg-white', 'sticky', 'top-0', 'z-10');

        $left = Element::make('div')->class('flex', 'items-center', 'gap-4');

        $prefix = \Admin\Services\AdminManager::getPrefix() ?: '/admin';
        $left->child(
            Element::make('a')
                ->class('text-sm', 'text-gray-500', 'hover:text-gray-700', 'flex', 'items-center', 'gap-1')
                ->attr('href', "{$prefix}/posts")
                ->attr('data-navigate', '')
                ->intl('admin:posts.editor.back', [], '← 返回列表')
        );

        $left->child(
            Text::h1()
                ->intl($this->recordId ? 'admin:posts.editor.edit_title' : 'admin:posts.editor.new_title', [], $this->recordId ? '编辑文章' : '新建文章')
                ->class('text-lg', 'font-semibold', 'ml-4')
        );

        $right = Element::make('div')->class('flex', 'items-center', 'gap-3');

        $right->child(
            Element::make('select')
                ->class('form-select', 'text-sm', 'border', 'border-gray-300', 'rounded', 'px-3', 'py-1.5')
                ->attr('data-live-model', 'status')
                ->attr('data-live-debounce', '0')
                ->child($this->option('draft', 'admin:posts.statuses.draft', '草稿'))
                ->child($this->option('published', 'admin:posts.statuses.published', '已发布'))
                ->child($this->option('archived', 'admin:posts.statuses.archived', '归档'))
        );

        $right->child(
            Button::make()
                ->intl('admin:posts.save', [], '保存')
                ->primary()
                ->liveAction('save')
        );

        $right->child(
            Button::make()
                ->intl('admin:posts.publish', [], '发布')
                ->success()
                ->liveAction('publish')
        );

        if ($this->saved) {
            $right->child(
                Element::make('span')
                    ->class('text-sm', 'text-green-600')
                    ->intl('admin:posts.saved_indicator', [], '已保存')
            );
        }

        $toolbar->child($left);
        $toolbar->child($right);

        return $toolbar;
    }

    protected function renderEditor(): Element
    {
        $container = Element::make('div')
            ->class('post-edit-body', 'flex', 'gap-6', 'p-6', 'max-w-7xl', 'mx-auto');

        $main = Element::make('div')->class('flex-1', 'min-w-0');

        $main->child(
            Element::make('input')
                ->attr('type', 'text')
                ->intlAttr('placeholder', 'admin:posts.editor.title_placeholder', [], '输入文章标题')
                ->attr('value', $this->title)
                ->class('post-edit-title', 'w-full', 'text-3xl', 'font-bold', 'border-none', 'outline-none', 'mb-6', 'px-4', 'py-3', 'bg-white', 'rounded-lg', 'shadow-sm')
                ->attr('data-live-model', 'title')
                ->attr('data-live-debounce', '500')
        );

        $editor = RichTextEditor::make()
            ->placeholder(t('admin:posts.editor.content_placeholder', [], '输入内容...'))
            ->minHeight('400px')
            ->liveModel('content');

        if ($this->content) {
            $editor->content($this->content);
        }

        $main->child($editor->render());

        $sidebar = Element::make('div')
            ->class('post-edit-sidebar', 'w-80', 'flex-shrink-0', 'space-y-4');

        $sidebar->child($this->renderSidebarCard('admin:posts.sidebar.status', '状态', $this->renderStatusFields()));
        $sidebar->child($this->renderSidebarCard('admin:posts.sidebar.categories_tags', '分类与标签', $this->renderCategoryFields()));
        $sidebar->child($this->renderSidebarCard('admin:posts.sidebar.cover', '封面图', $this->renderCoverField()));
        $sidebar->child($this->renderSidebarCard('admin:posts.sidebar.excerpt', '摘要', $this->renderExcerptField()));
        $sidebar->child($this->renderSidebarCard('admin:posts.sidebar.slug', '标识', $this->renderSlugField()));

        $container->child($main);
        $container->child($sidebar);

        return $container;
    }

    protected function renderSidebarCard(string $intlKey, string $defaultTitle, Element $content): Element
    {
        $card = Element::make('div')
            ->class('bg-white', 'rounded-lg', 'shadow-sm', 'border', 'border-gray-200', 'overflow-hidden');

        $card->child(
            Element::make('div')
                ->class('px-4', 'py-2.5', 'border-b', 'border-gray-200', 'bg-gray-50', 'text-sm', 'font-semibold', 'text-gray-700')
                ->intl($intlKey, [], $defaultTitle)
        );

        $card->child(
            Element::make('div')->class('p-4')->child($content)
        );

        return $card;
    }

    protected function renderStatusFields(): Element
    {
        $group = Element::make('div')->class('space-y-2');

        $statuses = [
            'draft' => ['key' => 'admin:posts.statuses.draft', 'default' => '草稿'],
            'published' => ['key' => 'admin:posts.statuses.published', 'default' => '已发布'],
            'archived' => ['key' => 'admin:posts.statuses.archived', 'default' => '归档'],
        ];

        foreach ($statuses as $value => $cfg) {
            $radio = Element::make('label')
                ->class('flex', 'items-center', 'gap-2', 'cursor-pointer', 'text-sm');

            $input = Element::make('input')
                ->attr('type', 'radio')
                ->attr('name', 'sidebar_status')
                ->attr('value', $value)
                ->class('form-radio')
                ->attr('data-live-model', 'status')
                ->attr('data-live-debounce', '0');

            if ($this->status === $value) {
                $input->attr('checked', 'checked');
            }

            $radio->child($input);
            $radio->child(Element::make('span')->intl($cfg['key'], [], $cfg['default']));

            $group->child($radio);
        }

        return $group;
    }

    protected function renderCategoryFields(): Element
    {
        $wrapper = Element::make('div')->class('space-y-3');

        $categories = Category::query()->orderBy('sort', 'asc')->get()->toArray();

        $select = Element::make('select')
            ->class('form-select', 'w-full', 'text-sm', 'border', 'border-gray-300', 'rounded', 'px-3', 'py-2')
            ->attr('data-live-model', 'categoryId')
            ->attr('data-live-debounce', '0');

        $select->child(
            Element::make('option')->attr('value', '')                ->intl('admin:posts.categories.none', [], '无分类')
        );

        foreach ($categories as $cat) {
            $opt = Element::make('option')
                ->attr('value', $cat['id'])
                ->text($cat['name']);

            if ((string) $this->categoryId === (string) $cat['id']) {
                $opt->attr('selected', 'selected');
            }

            $select->child($opt);
        }

        $wrapper->child($select);

        $tagInput = new TagInput();
        $tagInput->value($this->tagIds)
            ->placeholder(t('admin:posts.tags.placeholder', [], '输入标签后回车'))
            ->allowClear()
            ->liveModel('tagIds');

        $wrapper->child($tagInput);

        return $wrapper;
    }

    protected function renderCoverField(): Element
    {
        $wrapper = Element::make('div')->class('space-y-2');

        $picker = new MediaPicker('cover_image');
        $picker->label('')
            ->accept('image/*')
            ->value($this->coverImage);

        $wrapper->child($picker);

        return $wrapper;
    }

    #[LiveListener('fieldChange')]
    public function onFieldChange(array $eventData): void
    {
        $name = $eventData['name'] ?? '';
        $value = $eventData['value'] ?? '';

        if ($name === 'cover_image') {
            $this->coverImage = $value;
        }

        $this->refresh('post-editor');
    }

    protected function renderExcerptField(): Element
    {
        return Element::make('textarea')
            ->class('w-full', 'text-sm', 'border', 'border-gray-300', 'rounded', 'px-3', 'py-2', 'resize-none')
            ->attr('rows', '4')
            ->intlAttr('placeholder', 'admin:posts.excerpt_placeholder', [], '输入文章摘要...')
            ->attr('data-live-model', 'excerpt')
            ->attr('data-live-debounce', '500')
            ->text(htmlspecialchars($this->excerpt));
    }

    protected function renderSlugField(): Element
    {
        $wrapper = Element::make('div')->class('space-y-1');

        $wrapper->child(
            Element::make('input')
                ->attr('type', 'text')
                ->intlAttr('placeholder', 'admin:posts.slug_placeholder', [], 'auto-generated')
                ->attr('value', $this->slug)
                ->class('w-full', 'text-sm', 'border', 'border-gray-300', 'rounded', 'px-3', 'py-2')
                ->attr('data-live-model', 'slug')
                ->attr('data-live-debounce', '500')
        );

        $wrapper->child(
            Element::make('p')
                ->class('text-xs', 'text-gray-400')
                ->intl('admin:posts.slug_hint', [], '留空将自动从标题生成')
        );

        return $wrapper;
    }

    protected function generateSlug(): string
    {
        $slug = preg_replace('/[^a-zA-Z0-9\x{4e00}-\x{9fff}-]/u', '-', $this->title);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        return mb_strtolower($slug);
    }

    protected function option(string $value, string $intlKey, string $default): Element
    {
        return Element::make('option')
            ->attr('value', $value)
            ->attrs($this->status === $value ? ['selected' => 'selected'] : [])
            ->intl($intlKey, [], $default);
    }

    protected function normalizeMediaUrl(string $path): string
    {
        if (empty($path)) {
            return '';
        }

        if (str_starts_with($path, '/media/') || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return $path;
    }
}