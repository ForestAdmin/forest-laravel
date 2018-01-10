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
        $currentUser = null;

        if ($params) {
            $usersAllowed = $this->getAllowedUsers($params);

            foreach($usersAllowed as $user) {
                if ($user->email == $params->email &&
                  password_verify($params->password, $user->password)) {
                    $currentUser = $user;
                    break;
                }
            }
        }

        return $this->generateTokenAndSendResponse($currentUser);
    }

    public function createWithGoogle(Request $request) {
        $params = json_decode($request->getContent());
        $renderingId = $params->renderingId;
        $forestToken = $params->forestToken;
        $user = null;

        if ($params) {
            $user = $this->checkGoogleAuthAndGetUser($renderingId, $forestToken);
        }

        return $this->generateTokenAndSendResponse($user);
    }

    protected function getAllowedUsers($params) {
        $usersAllowed = [];

        $path = '/renderings/'.$params->renderingId.'/allowed-users';
        $options = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'forest-secret-key' => Config::get('forest.secret_key')
            )
        );
        $response = $this->makeRequestAndGetJsonResponse('GET', $path, $options);

        if ($response) {
            foreach ($response->data as $res) {
                $user = $res->attributes;
                $user->id = $res->id;
                $usersAllowed[] = $user;
            }
        }

        return $usersAllowed;
    }

    protected function checkGoogleAuthAndGetUser($renderingId, $forestToken) {
        $path = '/renderings/'.$renderingId.'/google-authorization';
        $options = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'forest-secret-key' => Config::get('forest.secret_key'),
                'forest-token' => $forestToken,
            )
        );
        $response = $this->makeRequestAndGetJsonResponse('GET', $path, $options);

        if ($response) {
            $data = $response->data;
            $user = $data->attributes;
            $user->id = $data->id;

            return $user;
        }

        return null;
    }

    protected function makeRequestAndGetJsonResponse($type, $path, $options) {
        $client = new Client();
        $response = $client->request($type, $path, $options);

        return json_decode($response->getBody());
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

    protected function generateTokenAndSendResponse($user) {
        if ($user) {
            $token = $this->generateAuthToken($user);
            return response()->json(['token' => $token]);
        }
        Log::info("401");
        return response()->make('Unauthorized', 401);
    }
}
