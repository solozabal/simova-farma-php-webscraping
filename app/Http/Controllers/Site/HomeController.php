<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;

class HomeController extends Controller
{
    public function index()
    {
        $posts = BlogPost::published()->latest('publish_at')->take(3)->get();
        return view('site.home', compact('posts'));
    }

    public function assinar()
    {
        $checkoutUrl = config('services.mercadopago.checkout_url');
        if ($checkoutUrl) {
            return redirect()->away($checkoutUrl);
        }
        return view('site.assinar');
    }

    public function obrigado()
    {
        return view('site.obrigado');
    }

    public function blog()
    {
        $posts = BlogPost::published()->latest('publish_at')->paginate(10);
        return view('site.blog', compact('posts'));
    }

    public function blogPost(string $slug)
    {
        $post = BlogPost::published()->where('slug', $slug)->firstOrFail();
        return view('site.blog-post', compact('post'));
    }

    public function sobre()
    {
        return view('site.sobre');
    }

    public function suporte()
    {
        return view('site.suporte');
    }

    public function termos()
    {
        return view('site.termos');
    }

    public function privacidade()
    {
        return view('site.privacidade');
    }

    public function reembolso()
    {
        return view('site.reembolso');
    }

    public function faq()
    {
        return view('site.faq');
    }
}
