<?php

declare(strict_types=1);

namespace App\Pages;

use Framework\Component\Live\LiveComponent;
use Framework\Component\Live\Attribute\LiveAction;
use Framework\Component\Live\Attribute\State;
use Framework\Routing\Attribute\Route;
use Framework\View\Base\Element;

#[Route('/demo')]
class DirectiveDemoPage extends LiveComponent
{
    #[State()]
    public string $name = 'World';
    #[State()]
    public int $count = 0;
    #[State()]
    public bool $visible = true;
    #[State()]
    public array $items = ['Apple', 'Banana', 'Orange'];

    protected bool $loading = true;

    #[LiveAction]
    public function increment(): void
    {
        $this->count++;
    }

    #[LiveAction]
    public function decrement(): void
    {
        $this->count--;
    }

    #[LiveAction]
    public function toggle(): void
    {
        $this->visible = !$this->visible;
    }

    #[LiveAction]
    public function greet(string $who): void
    {
        $this->name = $who;
    }

    public function render(): Element
    {
        $wrapper = Element::make('div')
            ->class('p-8 max-w-2xl mx-auto')
            ->children(
                Element::make('h1')
                    ->class('text-2xl font-bold mb-6')
                    ->text('y-directive Demo')
            );

        $wrapper->child(
            Element::make('div')
                ->class('bg-gray-100 p-4 rounded mb-4')
                ->children(
                    Element::make('p')
                    ->attr('data-text', "'Count: ' + count"),
                )
        );

        $wrapper->child(
            Element::make('div')
                ->class('space-y-2 mb-4')
                ->children(
                    Element::make('div')->attr('data-text', 'name'),
                    Element::make('div')->attr('data-text', 'count'),
                    Element::make('div')->attr('data-text', 'visible ? "visible" : "hidden"'),
                )
        );

        $wrapper->child(
            Element::make('button')
                ->class('px-4 py-2 bg-blue-500 text-white rounded mr-2')
                ->attr('data-on:click', 'count++;')
                ->text('Increment')
        );

        $wrapper->child(
            Element::make('button')
                ->class('px-4 py-2 bg-red-500 text-white rounded mr-2')
                ->attr('data-on:click', 'count--;')
                ->text('Decrement')
        );

        $wrapper->child(
            Element::make('button')
                ->class('px-4 py-2 bg-gray-500 text-white rounded mr-2')
                ->attr('data-on:click', 'visible = !visible')
                ->text('Toggle')
        );

        $wrapper->child(
            Element::make('button')
                ->class('px-4 py-2 bg-green-500 text-white rounded')
                ->attr('data-on:click', 'greet.Hello')
                ->text('Say Hello')
        );

        $wrapper->child(
            Element::make('div')
                ->attr('data-show', 'visible')
                ->class('mt-4 p-4 bg-green-100 rounded')
                ->text('This is visible when data-show is true')
        );

        return $wrapper;
    }
}
