<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs every state-changing request inside one database transaction.
 *
 * Before this, a sale that failed halfway (a good without a unit, a missing
 * detail, a dropped connection, a PHP error...) left half of its rows behind:
 * a transaction without details, details without the stock update, stock
 * without the journal. Now either everything the request wrote is kept, or
 * nothing is.
 */
class AtomicRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        DB::beginTransaction();

        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        // Exceptions thrown by the controller are already turned into a
        // response by the router; it keeps the exception on the response.
        if (property_exists($response, 'exception') && $response->exception) {
            DB::rollBack();
        } else {
            DB::commit();
        }

        return $response;
    }
}
