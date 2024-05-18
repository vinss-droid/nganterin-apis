<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class HotelPartner
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $isPartnerType = User::join('partners', 'partners.id', '=', 'users.partner_id')
                        ->where('partners.id', Auth::user()->partner_id)
                        ->select('partners.company_field')
                        ->first();

        if ($isPartnerType->company_field !== 'hotels') {
            return \response()->json([
                'status' => 'failed',
                'message' => "You don't have access to this action"
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
