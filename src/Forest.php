<?php
/**
 * Created by PhpStorm.
 * User: dib258
 * Date: 09/08/16
 * Time: 15:02
 */

namespace ForestAdmin\ForestLaravel;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use Psecio\Jwt\Header as JwtHeader;
use Psecio\Jwt\Jwt;
use ForestAdmin\ForestPhp\Liana\Api\Map;

class Forest {

    public function getAllowedUsers($data) {
        $renderingId = $data->renderingID;

        $uri = Config::get('forest.URI').'/forest/renderings/'.$renderingId.'/allowed-users';

        $options = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'forest-secret-key' => Config::get('forest.SecretKey')
            )
        );
        $client = new Client();

        $response = $client->request('GET', $uri, $options);
        $response = json_decode($response->getBody());

        $allowedUsers = [];

        foreach($response->data as $res) {
            $user = $res->atributes;
            $user->id = $res->id;
            $allowedUsers[] = $user;
        }

        return $allowedUsers;
    }

    public function generateAuthToken($user) {
        $header = new JwtHeader(Config::get('forest.AuthKey'));

        $jwt = new Jwt($header);

        // TODO: real getAuthOptions
        $jwt->custom(array(
            'foo' => 'bar',
        ));

        $jwt->issuer(Config::get('forest.URI'))
            ->isuedAt(time())
            ->notBefore(time()+60)
            ->expiredTime(time()+3600)
            ->jwtId($user->id);

        return $jwt->encode();
    }
}
