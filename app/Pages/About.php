<?php

namespace App\Pages;

use Framework\Routing\Attribute\Route;
use Framework\Http\Response\Response;
use Framework\View\Base\Element;
use Admin\PageBuilder\Components\ComponentRegistry;

class About
{
    #[Route('/about', methods: ['GET'])]
    public function __invoke(): Response
    {
        $page = Element::make('div')->class('pb-page');

        $type_test123 = ComponentRegistry::get('hero');
        if ($type_test123) {
            $comp_test123 = $type_test123->render({"title":"My Hero Title","subtitle":"My Subtitle","className":"flex flex-row p-4 bg-blue-500 my-custom"});
            $page->child($comp_test123);
        }
        return Response::html($page);
    }
}