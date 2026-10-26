<?php

namespace App\Http\Middleware;

use App\Models\UserEventModel;
use App\Services\UserEventLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Names the storefront visitor before the controller runs, so whatever it
 * records belongs to someone, and logs the page once it has rendered.
 */
class VisitorMiddleware
{
    public function __construct(private readonly UserEventLog $events) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin', 'admin/*', 'ckfinder/*')) {
            return $next($request);
        }

        $this->events->identify();

        $response = $next($request);

        if ($this->isPageView($request, $response)) {
            $this->events->record(UserEventModel::PAGE_VIEW, [
                'meta' => ['route' => $request->route()?->getName()],
            ]);
        }

        return $response;
    }

    /**
     * A page a person landed on, not an AJAX fragment, a redirect or an error.
     */
    private function isPageView(Request $request, Response $response): bool
    {
        return $request->isMethod('GET')
            && ! $request->ajax()
            && $response->getStatusCode() === Response::HTTP_OK
            && str_starts_with((string) $response->headers->get('Content-Type'), 'text/html');
    }
}
