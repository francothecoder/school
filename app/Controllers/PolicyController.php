<?php
declare(strict_types=1);

namespace Controllers;

class PolicyController extends BaseController
{
    public function terms(): void
    {
        $this->render('public/terms', [
            'title' => 'Terms and Conditions',
        ]);
    }

    public function privacy(): void
    {
        $this->render('public/privacy', [
            'title' => 'Privacy Policy',
        ]);
    }
}
