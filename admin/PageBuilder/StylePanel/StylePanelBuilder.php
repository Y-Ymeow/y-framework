<?php

declare(strict_types=1);

namespace Admin\PageBuilder\StylePanel;

use Framework\CSS\CSSEngine;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Form\Components\TextInput;
use Framework\UX\Display\Collapse;
use Framework\View\Base\Element;
use Admin\PageBuilder\StylePanel\StyleGroups\LayoutGroup;
use Admin\PageBuilder\StylePanel\StyleGroups\SpacingGroup;
use Admin\PageBuilder\StylePanel\StyleGroups\TypographyGroup;
use Admin\PageBuilder\StylePanel\StyleGroups\ColorGroup;
use Admin\PageBuilder\StylePanel\StyleGroups\BorderGroup;
use Admin\PageBuilder\StylePanel\StyleGroups\EffectGroup;

class StylePanelBuilder
{
    private array $groups = [];

    public function __construct()
    {
        $this->groups = [
            new LayoutGroup(),
            new SpacingGroup(),
            new TypographyGroup(),
            new ColorGroup(),
            new BorderGroup(),
            new EffectGroup(),
        ];
    }

    public function parseClasses(string $className): array
    {
        $result = [];
        foreach ($this->groups as $group) {
            $result[$group->name()] = [];
        }

        $classes = array_filter(explode(' ', $className));
        $unmatched = [];

        foreach ($classes as $class) {
            $matched = false;
            $parsed = CSSEngine::parseClass($class);
            foreach ($this->groups as $group) {
                if ($group->canHandle($parsed['base'])) {
                    $result[$group->name()][] = $class;
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                $unmatched[] = $class;
            }
        }

        $result['_unmatched'] = $unmatched;
        return $result;
    }

    public function buildClasses(array $styleData): string
    {
        $classes = [];

        foreach ($this->groups as $group) {
            $groupClasses = $group->extractClasses($styleData);
            foreach ($groupClasses as $cls) {
                if (!empty($cls) && !in_array($cls, $classes)) {
                    $classes[] = $cls;
                }
            }
        }

        if (!empty($styleData['_custom_classes'])) {
            $custom = array_filter(explode(' ', $styleData['_custom_classes']));
            foreach ($custom as $cls) {
                if (!empty($cls) && !in_array($cls, $classes)) {
                    $classes[] = $cls;
                }
            }
        }

        return implode(' ', $classes);
    }

    public function buildCollapses(string $currentClassName): array
    {
        $parsed = $this->parseClasses($currentClassName);
        $collapses = [];

        foreach ($this->groups as $group) {
            $groupForm = new FormBuilder();
            $group->fields($groupForm, $parsed[$group->name()] ?? []);

            $content = Element::make('div')->class('page-builder-style-group-content');
            foreach ($groupForm->getComponents() as $component) {
                if (method_exists($component, 'render')) {
                    $content->child($component->render());
                }
            }

            $collapse = Collapse::make()
                ->title($group->label())
                ->icon($group->icon())
                ->open()
                ->child($content);

            $collapses[] = $collapse;
        }

        $customContent = Element::make('div')->class('page-builder-style-group-content');
        $customInput = TextInput::make('_custom_classes')
            ->label('自定义类名')
            ->default(implode(' ', $parsed['_unmatched'] ?? []))
            ->placeholder('额外的 CSS 类名，空格分隔');
        $customContent->child($customInput->render());

        $collapses[] = Collapse::make()
            ->title('自定义')
            ->icon('code-slash')
            ->child($customContent);

        return $collapses;
    }

    public function buildForm(FormBuilder $form, string $currentClassName): void
    {
        $parsed = $this->parseClasses($currentClassName);

        foreach ($this->groups as $group) {
            $group->fields($form, $parsed[$group->name()] ?? []);
        }

        $form->schema([
            TextInput::make('_custom_classes')
                ->label('自定义类名')
                ->default(implode(' ', $parsed['_unmatched'] ?? []))
                ->placeholder('额外的 CSS 类名，空格分隔'),
        ]);
    }
}
