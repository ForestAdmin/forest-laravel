<?php

namespace ForestAdmin\ForestLaravel\Http\Controllers;

use ForestAdmin\ForestLaravel\DatabaseStructure;
use ForestAdmin\ForestLaravel\Forest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests;

class ForestController extends Controller
{
    /**
     * Return a 204 response to let know ForestAdmin that the plugin is well installed
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        DatabaseStructure::getCollections();
        
        return \Response::make('Package installed correctly', 204);
    }

    /**
     * Session to verify if a user is allowed to be connected
     *
     * @param Request $request
     * @param Forest $forest
     * @return \Illuminate\Http\Response
     */
    public function sessions(Request $request, Forest $forest) {
        $data = json_decode($request->getContent());
//        $forest = new Forest();

        $allowedUsers = $forest->getAllowedUsers($data);

        $currentUser = null;
        foreach($allowedUsers as $user) {
            if ($user->email == $data->email && password_verify($data->password, $user->password)) {
                $currentUser = $user;
                break;
            }
        }

        if ($currentUser) {
            $token = $forest->generateAuthToken($currentUser);
            return response()->json(['token' => $token]);
        }

        return response()->make('Unauthorized', 401);
    }
}

