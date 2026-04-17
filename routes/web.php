<?php

declare(strict_types=1);

return [
    get('/hello/{name}', 'App\\Actions\\hello', 'hello'),
    get('/sql/ping', 'App\\Actions\\sqlPing', 'sql.ping'),
];
