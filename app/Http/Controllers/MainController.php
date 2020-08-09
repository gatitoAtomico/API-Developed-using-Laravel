<?php

namespace App\Http\Controllers;

use App\CustomClasses\CarBrand;
use App\CustomClasses\CarDetails;
use App\CustomClasses\DbHelper;
use App\Numeration\UserActions;
use App\Numeration\UserRoles;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class MainController extends Controller
{

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|confirmed'

        ]);

        $dbHelper = new DbHelper();
        $dbHelper->registerUser($request->name, $request->email, $request->password);

        return response()->json([
            'message' => 'A new User Has been registered'
        ], 201);
    }

    public function login(Request $request)
    {
        $userCredentials = request(['email', 'password']);

        if (!Auth::attempt($userCredentials))
            return response()->json([
                'message' => 'Unauthorised'
            ], 401);


        $request->validate([
            'email' => 'required|string|email',
            'remember_me' => 'boolean'
        ]);

        $user = $request->user();
        $tokenResult = $user->createToken('User Personal Access Token');
        $token = $tokenResult->token;

        if ($request->remember_me) {
            //token will expire two weeks from now
            $token->expires_at = Carbon::now()->addWeeks(2);
        }

        $token->save();

        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse($tokenResult->token->expires_at)->toDateString()
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'User have been Logged out Successfully'
        ]);
    }

    public function tradeIn(Request $request)
    {
        $userId = Auth::id();
        $dbHelper = new DbHelper();

        $request->validate([
            'brand' => 'required|string',
            'model' => 'required|string',
            'price' => 'required|integer'
        ]);

        $car = $dbHelper->addCar($request->model, $request->price, $request->brand);

        $dbHelper->updateHistoryLog($userId, $car->id, UserActions::$sell);

        $user = $dbHelper->addUserTokens($userId, $request->price);

        return response()->json('Your wallet have been credited with ' . $request->price . ' tokens, your wallet balance is ' . $user->tokens . ' tokens');
    }

    public function removeClient(Request $request)
    {
        $request->validate([
            'clientEmail' => 'required|string|email',
        ]);

        $dbHelper = new DbHelper();
        $useRoleId = $dbHelper->getUserRole(Auth::id());

        if ($useRoleId === UserRoles::$Manager) {

            $client = $dbHelper->getClientToBeRemoved($request->clientEmail);
            if (!$client) {
                return response()->json('No such user in the Database');
            }
            $dbHelper->removeClient($client->id);
            return response()->json('Client has been successfully removed from the Database');
        }
        return response()->json('Permission denied');
    }


    public function getCarsForSale()
    {
        $dbHelper = new DbHelper();
        $cars = $dbHelper->getCarsForSale();
        $carsOnSale = array();
        $lastCarBrand = "";

        foreach ($cars as $car) {
            //this is a car with different brand
            if ($lastCarBrand !== $car->brand->pluck('brand')->first()) {
                $brand = new CarBrand();
                $brand->nameOfBrand = $car->brand->pluck('brand')->first();
                $carsOnSale[] = $brand;

            }
            $lastCarBrand = $car->brand->pluck('brand')->first();
            $carInfo = new CarDetails();
            $carInfo->id = $car->id;
            $carInfo->model = $car->model;
            $carInfo->price = $car->price;
            $brand->carsOnSale[] = $carInfo;
        }

        return response()->json($carsOnSale);
    }

    public function purchase(Request $request)
    {
        $request->validate([
            'carId' => 'required|integer',
        ]);

        $dbHelper = new DbHelper();
        $car = $dbHelper->getCarInfo($request->carId);

        if ($car->available) {
            $userTokens = $dbHelper->getUserTokens(Auth::id());
            $dbHelper->updateHistoryLog(Auth::id(), $request->carId, UserActions::$purchase);
            $dbHelper->updateCarAvailability($request->carId);

            if ($car->price >= $userTokens) {
                $dif = $car->price - $userTokens;
                $dbHelper->updateUserTokens(Auth::id(), 0);
                return response()->json('Amount payed ' . $dif . ' tokens thank you for your purchase');
            }
            $dif = $userTokens - $car->price;
            $dbHelper->updateUserTokens(Auth::id(), $dif);
            return response()->json('Amount payed ' . $car->price . ' tokens you will be refunded with ' . $dif . ' tokens thank you for your purchase');

        }
        return response()->json('This car is not available anymore');
    }
}
