<?php

namespace ForestAdmin\ForestLaravel\Http\Controllers;

// use App\Http\Controllers\Controller;
use ForestAdmin\ForestLaravel\Http\Utils\Client;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Psecio\Jwt\Header as JwtHeader;
use Psecio\Jwt\Jwt;

class SessionController extends Controller {
    public function create(Request $request) {
        $params = json_decode($request->getContent());

        if ($params) {
            $currentUser = null;
            $usersAllowed = $this->getAllowedUsers($params);

            foreach($usersAllowed as $user) {
                if ($user->email == $params->email &&
                  password_verify($params->password, $user->password)) {
                    $currentUser = $user;
                    break;
                }
            }
            if ($currentUser) {
                $token = $this->generateAuthToken($currentUser);
                return response()->json(['token' => $token]);
            }
        }
        return response()->make('Unauthorized', 401);
    }

    protected function getAllowedUsers($params) {
        $usersAllowed = [];

        $client = new Client();
        $path = '/renderings/'.$params->renderingId.'/allowed-users';
        $options = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'forest-secret-key' => Config::get('forest.secret_key')
            )
        );
        $response = $client->request('GET', $path, $options);
        $response = json_decode($response->getBody());

        if ($response) {
            foreach ($response->data as $res) {
                $user = $res->attributes;
                $user->id = $res->id;
                $usersAllowed[] = $user;
            }
        }

        return $usersAllowed;
    }

    protected function generateAuthToken($user) {
        $jwtHeader = new JwtHeader(Config::get('forest.auth_key'));
        $jwtHeader->setAlgorithm('HS256');

        $jwt = new Jwt($jwtHeader);
        $jwt->custom(array(
            'id' => $user->id,
            'type' => 'users',
            'data' => array(
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'teams' => $user->teams
            )
        ));

        // NOTICE: Expire in 2 weeks
        $jwt->issuedAt(time())
            ->expireTime(time() + (14 * 24 * 3600));

        return $jwt->encode();
    }
}
