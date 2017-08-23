<?php

namespace ForestAdmin\ForestLaravel\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;
use Psecio\Jwt\Header as JwtHeader;
use Psecio\Jwt\Jwt;

class AuthenticateForestUser {
    public function handle($request, Closure $next) {
        $authorizationHeader = $request->header('Authorization');
        $authorizationParameter = $request->sessionToken;

        if ($authorizationHeader || $authorizationParameter) {
            if ($authorizationHeader) {
              $authorizationHeader = explode(' ', $authorizationHeader);
              $token = $authorizationHeader[1];
            } else {
              $token = $authorizationParameter;
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
