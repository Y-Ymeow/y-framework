<?php

declare(strict_types=1);

namespace Framework\UX\Form\Components;

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

    public function getType(): string
    {
        return $this->type;
    }

    public function targetOptions(array $options): static
    {
        $this->targetOptions = $options;
        return $this;
    }

    public function render(): Element
    {
        $wrapper = $this->buildWrapper();
        $wrapper->child($this->buildLabel());

        $modalId = 'link-picker-' . $this->name;

        $value = $this->getValue() ?? [];
        $url = $value['url'] ?? '';
        $target = $value['target'] ?? '_self';
        $label = $value['label'] ?? '';

        $container = Element::make('div')->class('ux-form-link-picker');

        $display = Element::make('div')->class('ux-form-link-display');
        if (!empty($url)) {
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
        if (!empty($url)) {
            $removeBtn = Element::make('button')
                ->class('ux-form-link-remove')
                ->attr('type', 'button')
                ->attr('data-link-remove', '')
                ->html('<i class="bi bi-x"></i>');
            $actions->child($removeBtn);
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
                    ->attr('data-link-params', json_encode([
                        'name' => $this->name,
                        'modalId' => $modalId
                    ], JSON_UNESCAPED_UNICODE))
            );

        $modal->id($modalId);

        return $modal;
    }

    protected function renderModalBody(string $modalId): Element
    {
        $body = Element::make('div')->class('link-picker-modal');

        $value = $this->getValue() ?? [];
        $url = $value['url'] ?? '';
        $target = $value['target'] ?? '_self';
        $label = $value['label'] ?? '';

        $group = Element::make('div')->class('ux-form-group');
        $group->child(
            Element::make('label')->class('ux-form-label')->text('链接地址')
        );
        $group->child(
            Element::make('input')
                ->class('ux-form-input')
                ->attr('type', 'url')
                ->attr('placeholder', 'https://example.com')
                ->attr('data-link-modal-url', '')
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
                ->attr('data-link-modal-label', '')
                ->attr('value', $label)
        );
        $body->child($group2);

        $group3 = Element::make('div')->class('ux-form-group');
        $group3->child(
            Element::make('label')->class('ux-form-label')->text('打开方式')
        );
        $targetSelect = Element::make('select')
            ->class('ux-form-input')
            ->attr('data-link-modal-target', '');
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
