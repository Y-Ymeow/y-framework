<?php

declare(strict_types=1);

// 自动加载 Actions 目录下的所有文件
return glob(dirname(__DIR__) . '/app/Actions/*.php');
