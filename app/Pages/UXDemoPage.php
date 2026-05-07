<?php

namespace App\Pages;

use Framework\Routing\Attribute\Route;
use Framework\Http\Response\Response;
use Framework\View\Base\Element;
use Admin\PageBuilder\Components\ComponentRegistry;

class UXDemoPage
{
    #[Route('/demo/ux', methods: ['GET'])]
    public function __invoke(): Response
    {
        $page = Element::make('div')->class('pb-page');

        $type_c17781026426687xll9 = ComponentRegistry::get('text_block');
        if ($type_c17781026426687xll9) {
            $comp_c17781026426687xll9 = $type_c17781026426687xll9->render([]);
            $page->child($comp_c17781026426687xll9);
        }
        return Response::html($page);
    }
}