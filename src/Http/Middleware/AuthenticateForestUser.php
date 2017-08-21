<?php

namespace ForestAdmin\ForestLaravel\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;
use Psecio\Jwt\Header as JwtHeader;
use Psecio\Jwt\Jwt;

class AuthenticateForestUser {
    public function handle($request, Closure $next) {
        $authorizationHeader = $request->header('Authorization');
        $authorizationCookie = $request->cookie('liana_auth:session');

        if ($authorizationHeader || $authorizationCookie) {
            if ($authorizationHeader) {
              $authorizationHeader = explode(' ', $authorizationHeader);
              $token = $authorizationHeader[1];
            } else {
              $token = json_decode($authorizationCookie)->token;
            }

            if ($token) {
                $jwtHeader = new JwtHeader(Config::get('forest.auth_key'));
                $jwtHeader->setAlgorithm('HS256');
                $jwt = new Jwt($jwtHeader);

                try {
                    $forestUser = $jwt->decode($token);
                } catch (\Exception $exception) { // NOTICE: ExpiredException
                    return response()->make('Expired token', 401);
                }

                return $next($request);
            }
        }

        return response()->make('Unauthorized', 401);
    }

}
