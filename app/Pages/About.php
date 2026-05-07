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

        $type_c17781853289315mmrp = ComponentRegistry::get('hero');
        if ($type_c17781853289315mmrp) {
            $comp_c17781853289315mmrp = $type_c17781853289315mmrp->render([]);
            $page->child($comp_c17781853289315mmrp);
        }
        return Response::html($page);
    }
}