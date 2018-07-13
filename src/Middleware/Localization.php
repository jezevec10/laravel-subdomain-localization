<?php namespace LaurentEsc\Localization\Middleware;

use Illuminate\Http\RedirectResponse;
use Closure;
use LaurentEsc\Localization\Facades\Localize;
use LaurentEsc\Localization\Facades\Router;
use Illuminate\Support\Facades\Auth;

class Localization
{

    /**
     * Handle an incoming request.
     *
     * Redirect only GET requests, and not Ajax
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Override the app locale with the one set in the account settings
        if(Auth::check() && $lang=Auth::user()->lang){
            app()->setLocale($lang);
        }
        
        if (Localize::shouldRedirect()) {
            return new RedirectResponse(Router::getRedirectURL(), 302, ['Vary', 'Accept-Language']);
        }
        
        return $next($request);
    }

}
