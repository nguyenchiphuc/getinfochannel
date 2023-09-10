<?php


namespace App\Http\Controllers\API;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

/**
 * @OA\Info(title="Subscribe API", version="1.0")
 */
class BaseController extends Controller
{
    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendResponse($result, $message)
    {
    	$response = [
            'success' => true,
            'data'    => $result,
            //'message' => $message,
        ];
        return response()->json($response, 200);
    }

    public function sendResponseAPI($result)
    {
    	$response = $result;
        return response()->json($response, 200);
    }

    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendError($error, $errorMessages = [], $code = 404)
    {
    	$response = [
            'success' => false,
            //'message' => $error,
        ];

        if(!empty($errorMessages)){
            $response['data'] = $errorMessages;
        }


        return response()->json($response, $code);
    }


    public function sendErrorAPI($error, $code = 404)
    {
    	$response = $error;

        return response()->json($response, $code);
    }

    public function sendErrorOkie($error, $errorMessages = [], $code = 200)
    {
    	$response = [
            'success' => false,
            //'message' => $error,
        ];

        if(!empty($errorMessages)){
            $response['data'] = $errorMessages;
        }


        return response()->json($response, $code);
    }
}
