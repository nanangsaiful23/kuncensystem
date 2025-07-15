<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

use App\Models\LoginHistory;

class RedirectIfNotCashier
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @param  string|null  $guard
	 * @return mixed
	 */
	public function handle($request, Closure $next, $guard = 'cashier')
	{
	    if (!Auth::guard($guard)->check()) {
	        return redirect('cashier/login');
	    }
	    $agent = new \Jenssegers\Agent\Agent;

    	// $data['role'] 		 = 'cashier';
    	// $data['role_id'] 	 = \Auth::user()->id;
    	// $data['device_type'] = $agent->device() . ';;' . $agent->platform() . ';;' . $agent->browser();
    	// $data['ip_address']  = $request->ip();

    	// LoginHistory::create($data);

    	if($agent->isPhone() && \Auth::user()->email != 'cashier')
    	{
    		$request->email = 'not valid';
	        return redirect('cashier/login');
    	}

	    return $next($request);
	}
}