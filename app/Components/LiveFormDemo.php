<?php

declare(strict_types=1);

namespace App\Components;

use Framework\Component\Live\LiveComponent;
use Framework\Component\Live\Attribute\LiveAction;
use Framework\Component\Live\Attribute\State;
use Framework\Routing\Attribute\Route;
use Framework\UX\Form\Components\LiveTextInput;
use Framework\View\Base\Element;

#[Route('/live-form-demo')]
class LiveFormDemo extends LiveComponent
{
    #[State]
    public string $title = '';

    #[State]
    public string $slug = '';

    #[State]
    public int $changeCount = 0;

    #[State]
    public string $lastChangedField = '';

    #[State]
    public string $lastChangedValue = '';

    public function mount(): void {}

    #[LiveAction]
    public function onFieldChange(array $params = []): void
    {
        $field = $params['field'] ?? '';
        $value = $params['value'] ?? '';

        $this->lastChangedField = $field;
        $this->lastChangedValue = $value;
        $this->changeCount++;

        if ($field === 'title') {
            $this->title = $value;
            $this->slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $value));
        } elseif ($field === 'slug') {
            $this->slug = $value;
        }
    }

    #[LiveAction]
    public function resetForm(): void
    {
        $this->title = '';
        $this->slug = '';
        $this->changeCount = 0;
        $this->lastChangedField = '';
        $this->lastChangedValue = '';
    }

    public function render(): Element
    {
        $titleInput = LiveTextInput::make('title')
            ->label('标题')
            ->required()
            ->placeholder('输入文章标题...');
        $titleInput->inputValue = $this->title;
        $titleInput->setParent($this);

        $slugInput = LiveTextInput::make('slug')
            ->label('标识')
            ->placeholder('auto-generated-slug');
        $slugInput->inputValue = $this->slug;
        $slugInput->setParent($this);

        return Element::make('div')
            ->class('live-form-demo')
            ->liveFragment('demo-form')
            ->children(
                Element::make('h2')->class('text-xl font-bold mb-4')->text('Live Form Demo'),
                Element::make('div')->class('mb-2 p-2 bg-gray-100 rounded text-sm')
                    ->children(
                        Element::make('span')->text("Changes: {$this->changeCount} | Last: {$this->lastChangedField} = {$this->lastChangedValue}")
                    ),
                Element::make('div')->class('mb-4')->child($titleInput),
                Element::make('div')->class('mb-4')->child($slugInput),
                Element::make('button')
                    ->class('px-4 py-2 bg-gray-500 text-white rounded')
                    ->liveAction('resetForm')
                    ->text('Reset')
            );
    }
}
