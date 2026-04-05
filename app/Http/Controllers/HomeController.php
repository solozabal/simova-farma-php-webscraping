<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Controlador da página inicial pública (landing page).
 *
 * Rota:
 *   GET /  → exibe a landing page do SIMOVA FARMA
 */
class HomeController extends Controller
{
    /**
     * Exibe a landing page principal do SIMOVA FARMA.
     */
    public function index(): View
    {
        return view('home');
    }
}
