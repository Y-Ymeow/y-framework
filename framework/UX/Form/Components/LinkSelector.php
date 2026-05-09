<?php

declare(strict_types=1);

namespace Framework\UX\Form\Components;

use Framework\Component\Live\Attribute\LiveAction;
use Framework\Component\Live\Attribute\State;
use Framework\UX\Dialog\Modal;
use Framework\UX\UI\Button;
use Framework\View\Base\Element;

class LinkSelector extends BaseField
{
    protected string $type = 'link';
    protected array $targetOptions = [
        '_self' => '当前窗口',
        '_blank' => '新窗口',
        '_parent' => '父窗口',
        '_top' => '顶层窗口',
    ];

    #[State]
    public string $linkUrl = '';

    #[State]
    public string $linkTarget = '_self';

    #[State]
    public string $linkLabel = '';

    public function getType(): string
    {
        return $this->type;
    }

    public function targetOptions(array $options): static
    {
        $this->targetOptions = $options;
        return $this;
    }

    public function mount(): void
    {
        $value = $this->getValue() ?? [];
        if (is_array($value)) {
            $this->linkUrl = $value['url'] ?? '';
            $this->linkTarget = $value['target'] ?? '_self';
            $this->linkLabel = $value['label'] ?? '';
        }
    }

    #[LiveAction]
    public function applyLink(array $params): void
    {
        $modalId = $params['modalId'] ?? '';

        $this->linkUrl = $params['url'] ?? $this->linkUrl;
        $this->linkTarget = $params['target'] ?? $this->linkTarget;
        $this->linkLabel = $params['label'] ?? $this->linkLabel;

        $value = [
            'url' => $this->linkUrl,
            'target' => $this->linkTarget,
            'label' => $this->linkLabel,
        ];
        $this->value = $value;

        if ($modalId) {
            $this->closeModal($modalId);
        }

        $this->emit('fieldChange', [
            'name' => $this->name,
            'value' => $value,
        ]);

        $this->refresh('link-picker-' . $this->name);
    }

    #[LiveAction]
    public function removeLink(array $params): void
    {
        $this->linkUrl = '';
        $this->linkTarget = '_self';
        $this->linkLabel = '';
        $this->value = [];

        $this->emit('fieldChange', [
            'name' => $this->name,
            'value' => [],
        ]);

        $this->refresh('link-picker-' . $this->name);
    }

    public function render(): Element
    {
        $wrapper = $this->buildWrapper();
        $wrapper->child($this->buildLabel());

        $modalId = 'link-picker-' . $this->name;

        $url = $this->linkUrl;
        $target = $this->linkTarget;
        $label = $this->linkLabel;
        $hasValue = !empty($url);

        $container = Element::make('div')
            ->class('ux-form-link-picker')
            ->liveFragment('link-picker-' . $this->name);

        $display = Element::make('div')->class('ux-form-link-display');
        if ($hasValue) {
            $display->child(
                Element::make('span')->class('ux-form-link-url-display')->text($url)
            );
            $display->child(
                Element::make('span')->class('ux-form-link-target-display')
                    ->text($this->targetOptions[$target] ?? $target)
            );
        } else {
            $display->child(
                Element::make('span')->class('ux-form-link-placeholder')->text('未设置链接')
            );
        }
        $container->child($display);

        $hiddenUrl = Element::make('input')
            ->attr('type', 'hidden')
            ->attr('name', $this->name . '[url]')
            ->attr('data-link-url', '')
            ->attr('data-submit-field', $this->name . '[url]')
            ->attr('value', $url);
        $hiddenTarget = Element::make('input')
            ->attr('type', 'hidden')
            ->attr('name', $this->name . '[target]')
            ->attr('data-link-target', '')
            ->attr('data-submit-field', $this->name . '[target]')
            ->attr('value', $target);
        $hiddenLabel = Element::make('input')
            ->attr('type', 'hidden')
            ->attr('name', $this->name . '[label]')
            ->attr('data-link-label', '')
            ->attr('data-submit-field', $this->name . '[label]')
            ->attr('value', $label);

        $container->child($hiddenUrl);
        $container->child($hiddenTarget);
        $container->child($hiddenLabel);

        $actions = Element::make('div')->class('ux-form-link-actions');
        $actions->child(
            Button::make()
                ->label('设置链接')
                ->variant('secondary')
                ->attr('data-ux-modal-open', $modalId)
        );
        if ($hasValue) {
            $actions->child(
                Button::make()
                    ->label('移除')
                    ->variant('secondary')
                    ->liveAction('removeLink', 'click')
            );
        }
        $container->child($actions);

        $wrapper->child($container);
        $wrapper->child($this->buildModal($modalId));

        $help = $this->buildHelp();
        if ($help) {
            $wrapper->child($help);
        }

        return $wrapper;
    }

    protected function buildModal(string $modalId): Modal
    {
        $modal = Modal::make()
            ->title('设置链接')
            ->size('md')
            ->state([
                'url' => $this->linkUrl,
                'label' => $this->linkLabel,
                'target' => $this->linkTarget,
            ])
            ->content($this->renderModalBody($modalId))
            ->footer(
                Button::make()
                    ->label('取消')
                    ->variant('secondary')
                    ->attr('data-ux-modal-close', $modalId),
                Button::make()
                    ->label('确定')
                    ->primary()
                    ->attr('data-link-apply', $modalId)
                    ->liveAction('applyLink', 'click', [
                        'name' => $this->name,
                        'modalId' => $modalId,
                        'url' => '$url',
                        'label' => '$label',
                        'target' => '$target',
                    ])
            );

        $modal->id($modalId);

        return $modal;
    }

    protected function renderModalBody(string $modalId): Element
    {
        $body = Element::make('div')->class('link-picker-modal');

        $url = $this->linkUrl;
        $target = $this->linkTarget;
        $label = $this->linkLabel;

        $group = Element::make('div')->class('ux-form-group');
        $group->child(
            Element::make('label')->class('ux-form-label')->text('链接地址')
        );
        $group->child(
            Element::make('input')
                ->class('ux-form-input')
                ->attr('type', 'url')
                ->attr('placeholder', 'https://example.com')
                ->model('url')
                ->attr('value', $url)
        );
        $body->child($group);

        $group2 = Element::make('div')->class('ux-form-group');
        $group2->child(
            Element::make('label')->class('ux-form-label')->text('链接文字')
        );
        $group2->child(
            Element::make('input')
                ->class('ux-form-input')
                ->attr('type', 'text')
                ->attr('placeholder', '可选')
                ->model('label')
                ->attr('value', $label)
        );
        $body->child($group2);

        $group3 = Element::make('div')->class('ux-form-group');
        $group3->child(
            Element::make('label')->class('ux-form-label')->text('打开方式')
        );
        $targetSelect = Element::make('select')
            ->class('ux-form-input')
            ->attr('data-link-modal-target', '')
            ->liveModel('target');
        foreach ($this->targetOptions as $key => $name) {
            $opt = Element::make('option')->attr('value', $key)->text($name);
            if ($key === $target) $opt->attr('selected', '');
            $targetSelect->child($opt);
        }
        $group3->child($targetSelect);
        $body->child($group3);

        return $body;
    }
}
