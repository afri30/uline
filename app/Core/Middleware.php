<?php

namespace App\Core;

interface Middleware
{
    /**
     * Handle request before controller action
     *
     * @param Request $request
     * @return bool|void Return false or redirect to stop execution
     */
    public function handle(Request $request);
}
