<?php

namespace App\Http\Middleware;

use Closure;
// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\LoginHistory;

class RedirectIfNotAdmin
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @param  string|null  $guard
	 * @return mixed
	 */
	public function handle($request, Closure $next, $guard = 'admin')
	{
	    if (!Auth::guard($guard)->check()) {
	        return redirect('admin/login');
	    }
	    $agent = new \Jenssegers\Agent\Agent;

    	// $data['role'] 		 = 'admin';
    	// $data['role_id'] 	 = \Auth::user()->id;
    	// $data['device_type'] = $agent->device() . ';;' . $agent->platform() . ';;' . $agent->browser();
    	// $data['ip_address']  = $request->ip();

    	// LoginHistory::create($data);

    	if($agent->isPhone() && \Auth::user()->email != 'admin')
    	{
    		$request->email = 'not valid';
	        return redirect('admin/login');
    	}

	    return $next($request);
	}
}