<?php

declare(strict_types=1);

namespace Framework\Http\Response;

use Framework\Component\Live\LiveComponent;
use Framework\Foundation\AppEnvironment;
use Framework\View\Document\Document;

class HtmlResponse extends Response
{
    public function __construct(mixed $html, int $status = 200, array $headers = [])
    {
        if ($html instanceof LiveComponent) {
            $html->_invoke();
        }

        $doc = Document::make();
        $content = $doc->main($html)->render();

        if (AppEnvironment::supportsHeaders()) {
            $headers['Content-Type'] = 'text/html; charset=utf-8';
        }

        parent::__construct($content, $status, $headers);
    }
}