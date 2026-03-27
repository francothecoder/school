<?php
declare(strict_types=1);

namespace Controllers;

class BaseController
{
    protected function render(string $view, array $data = []): void
    {
        view($view, $data);
    }
}
