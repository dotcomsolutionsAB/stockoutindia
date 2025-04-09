<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CityModel; // Add the CityModel import
use Illuminate\Support\Facades\Http; // To make API requests
use Illuminate\Http\Request;

class ImportController extends Controller
{
    // Method to import users from external API
    public function importUsers()
    {
        // Fetch the users from the external API
        $response = Http::get('http://sync.stockoutindia.com/get_users.php'); // Replace with your Users API URL
        
        // Check if the response is successful
        if ($response->successful()) {
            $users = $response->json()['data']; // Adjust according to the response format
            
            foreach ($users as $user) {
                // Get the phone number with +91 prefix
                $phoneWithPrefix = '+91' . $user['mobile'];
                
                // Set username to be the same as phone with +91 prefix
                $username = $phoneWithPrefix;

                // Check if the user already exists based on unique email or other unique field
                $existingUser = User::where('email', $user['email'])->first();

                // Get the city name using CityModel (assuming city_id is the city_id in the API)
                $cityName = CityModel::where('id', $user['city_id'])->first()->name ?? null;

                // If the user exists, update it. Otherwise, create a new record
                if ($existingUser) {
                    $existingUser->update([
                        'name' => $user['fullname'],
                        'email' => $user['email'],
                        'password' => bcrypt($user['password']),
                        'role' => 'user', // You can modify this based on your logic
                        'username' => $username, // Set username as phone with +91
                        'phone' => $phoneWithPrefix, // Set phone with +91
                        'address' => $user['address'],
                        'city' => $cityName, // Storing the city name here
                        'state' => $user['state_id'],
                        'company_name' => $user['company_name'],
                        'gstin' => $user['gst_no'],
                        'industry' => $user['industries'],
                        'sub_industry' => NULL,
                    ]);
                } else {
                    // If user doesn't exist, create a new user
                    User::create([
                        'name' => $user['fullname'],
                        'email' => $user['email'],
                        'password' => bcrypt($user['password']),
                        'role' => 'user', // Modify as per your logic
                        'username' => $username, // Set username as phone with +91
                        'phone' => $phoneWithPrefix, // Set phone with +91
                        'address' => $user['address'],
                        'city' => $cityName, // Storing the city name here
                        'state' => $user['state_id'],
                        'company_name' => $user['company_name'],
                        'gstin' => $user['gst_no'],
                        'industry' => $user['industries'],
                        'sub_industry' => NULL,
                    ]);
                }
            }

            return response()->json(['status' => true, 'message' => 'Users imported successfully']);
        }

        // If API call fails
        return response()->json(['status' => false, 'message' => 'Failed to fetch users from API']);
    }
}
