<?php

namespace App\Pages;

use Framework\Routing\Attribute\Route;
use Framework\Http\Response\Response;
use Framework\View\Base\Element;
use Admin\PageBuilder\Components\ComponentRegistry;

class ABOUT
{
    #[Route('/about', methods: ['GET'])]
    public function __invoke(): Response
    {
        $page = Element::make('div')->class('pb-page');

        $type_c1778102339811p2x9b = ComponentRegistry::get('grid');
        if ($type_c1778102339811p2x9b) {
            $comp_c1778102339811p2x9b = $type_c1778102339811p2x9b->render([]);
                $type_c17781027356436u7hz = ComponentRegistry::get('text_block');
                if ($type_c17781027356436u7hz) {
                    $comp_c17781027356436u7hz = $type_c17781027356436u7hz->render({"content":"asdasdasd","align":"left","className":""});
                    $comp_c1778102339811p2x9b->child($comp_c17781027356436u7hz);
                }
                $type_cb2e7403178 = ComponentRegistry::get('text_block');
                if ($type_cb2e7403178) {
                    $comp_cb2e7403178 = $type_cb2e7403178->render({"content":"asdasdsadasd","align":"left","className":""});
                    $comp_c1778102339811p2x9b->child($comp_cb2e7403178);
                }
                $type_cd9af045a4c = ComponentRegistry::get('heading');
                if ($type_cd9af045a4c) {
                    $comp_cd9af045a4c = $type_cd9af045a4c->render({"text":"啊飒沓时代","level":"h2","align":"left","className":""});
                    $comp_c1778102339811p2x9b->child($comp_cd9af045a4c);
                }
            $page->child($comp_c1778102339811p2x9b);
        }
        return Response::html($page);
    }
}