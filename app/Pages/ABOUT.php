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

        $type_c1778102351579oc2q9 = ComponentRegistry::get('heading');
        if ($type_c1778102351579oc2q9) {
            $comp_c1778102351579oc2q9 = $type_c1778102351579oc2q9->render([]);
            $page->child($comp_c1778102351579oc2q9);
        }
        $type_c1778102339811p2x9b = ComponentRegistry::get('grid');
        if ($type_c1778102339811p2x9b) {
            $comp_c1778102339811p2x9b = $type_c1778102339811p2x9b->render([]);
                $type_c17781027356436u7hz = ComponentRegistry::get('text_block');
                if ($type_c17781027356436u7hz) {
                    $comp_c17781027356436u7hz = $type_c17781027356436u7hz->render({"content":"asdasdasd","align":"left","className":""});
                    $comp_c1778102339811p2x9b->child($comp_c17781027356436u7hz);
                }
                $type_c1778102717371v021k = ComponentRegistry::get('image');
                if ($type_c1778102717371v021k) {
                    $comp_c1778102717371v021k = $type_c1778102717371v021k->render([]);
                    $comp_c1778102339811p2x9b->child($comp_c1778102717371v021k);
                }
                $type_c1778102781931n50ev = ComponentRegistry::get('image');
                if ($type_c1778102781931n50ev) {
                    $comp_c1778102781931n50ev = $type_c1778102781931n50ev->render([]);
                    $comp_c1778102339811p2x9b->child($comp_c1778102781931n50ev);
                }
            $page->child($comp_c1778102339811p2x9b);
        }
        return Response::html($page);
    }
}