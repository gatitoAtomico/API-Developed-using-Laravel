<?php


namespace App\CustomClasses;

use App\Brands;
use App\Car_brands;
use App\Cars;
use App\HistoryLog;
use App\Role_user;
use App\User;

class DbHelper
{

    public function registerUser($name, $email, $password)
    {
        $user = new User([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password)
        ]);

        $user->save();

    }

    public function addCar($model, $price, $brand)
    {
        $car = Cars::create(['model' => $model, 'price' => $price]);
        $brand = Brands::firstOrCreate(['brand' => $brand]);
        Car_brands::insert(['car_id' => $car->id, 'brand_id' => $brand->id]);

        return $car;
    }

    public function updateHistoryLog($userId, $carId, $actionId)
    {
        HistoryLog::create(['user_id' => $userId, 'car_id' => $carId, 'action_id' => $actionId]);
    }

    public function addUserTokens($userId, $carPrice)
    {
        User::find($userId)->increment('tokens', $carPrice);
        return User::where('id', $userId)->get()->first();
    }

    public function getUserRole($userId)
    {
        //get the id of the user trying to remove a client
        return Role_user::where('user_id', '=', $userId)->pluck('role_id')->first();
    }

    public function getClientToBeRemoved($email)
    {
        return User::where('email', '=', $email)->first();
    }

    public function removeClient($clientId)
    {
        User::findOrFail($clientId)->delete();
    }

    public function getCarsForSale()
    {
        return Cars::where('available', '=', true)
            ->join('car_brands', 'cars.id', '=', 'car_brands.car_id')
            ->select('cars.id', 'cars.model', 'car_brands.brand_id', 'cars.price')
            ->orderBy('car_brands.brand_id', 'asc')->get();
    }

    public function getCarInfo($carId)
    {
        return Cars::where('id', $carId)->get()->first();
    }

    public function getUserTokens($userId)
    {
        return User::where('id', $userId)->pluck('tokens')->first();
    }

    public function updateCarAvailability($carId)
    {
        Cars::where('id', $carId)->update(['available' => false]);
    }

    public function updateUserTokens($userId, $availableTokens)
    {
        User::where('id', $userId)->update(['tokens' => $availableTokens]);
    }


}
