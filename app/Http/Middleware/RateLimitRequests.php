<?php

namespace App\Http\Middleware;

use Closure;
// use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Token;


class RateLimitRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $jwt = $request->header('Authorization');
            $splitted = explode(' ', $jwt);

            $token = new Token($splitted[1]);
            $decoded = JWTAuth::decode($token);
            $decoded = JWTAuth::decode($token, array('HS256'));

            $clientPhone = $decoded->get('phone_no');

        $ip = $request->ip();
        $userId = $clientPhone;
        // $userId = $request->user() ? $request->user()->id : null;

        // Check if there is an existing record for this IP address and user ID
        $record = DB::table('api_requests')
            ->where('ip_address', $ip)
            ->where('user_id', $userId)
            ->first();

        // If there is no existing record, create a new one
        if (!$record) {
            DB::table('api_requests')->insert([
                'ip_address' => $ip,
                'user_id' => $userId,
                'request_count' => 1,
                'last_request_at' => Carbon::now(),
            ]);
        } else {
            // If there is an existing record, update it with the latest request details
            $lastRequestAt = Carbon::createFromFormat('Y-m-d H:i:s', $record->last_request_at);
            $timeSinceLastRequest = Carbon::now()->diffInSeconds($lastRequestAt);

            if ($timeSinceLastRequest >= 60) {
                // If the last request was made more than 60 seconds ago, reset the request count to 1
                DB::table('api_requests')
                    ->where('id', $record->id)
                    ->update([
                        'request_count' => 1,
                        'last_request_at' => Carbon::now(),
                    ]);
            } else {
                // If the last request was made less than 60 seconds ago, increment the request count
                $requestCount = $record->request_count + 1;

                if ($requestCount > 1) {
                    // If the request count is greater than 1, return a "Too Many Requests" response
                    return response('Too Many Requests', 429);
                }

                DB::table('api_requests')
                    ->where('id', $record->id)
                    ->update([
                        'request_count' => $requestCount,
                        'last_request_at' => Carbon::now(),
                    ]);
            }
        }

        return $next($request);
    }
}
