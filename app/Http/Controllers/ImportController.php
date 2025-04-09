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
            
                // Check if the email already exists in the database
                $existingUserByEmail = User::where('email', $user['email'])->first();
                
                // Check if the mobile already exists in the database
                $existingUserByMobile = User::where('phone', $phoneWithPrefix)->first();
            
                // Get the city name using CityModel (assuming city_id is the city_id in the API)
                $cityName = CityModel::where('id', $user['city_id'])->first()->name ?? null;
            
                // Skip if mobile number is duplicate, and email is not duplicate
                if ($existingUserByMobile && !$existingUserByEmail) {
                    // Skip the current iteration (do not insert or update)
                    continue;
                }
            
                // If the user exists by email, update it. Otherwise, create a new record
                if ($existingUserByEmail) {
                    // Handle "gstin" being a space, set it to NULL
                    $gstin = $user['gst_no'] === " " ? null : $user['gst_no'];

                    $existingUserByEmail->update([
                        'user_id' => $user['id'],
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
                        'gstin' => $gstin,
                        'industry' => $user['industries'],
                        'sub_industry' => NULL,
                    ]);
                } else {
                    // Handle "gstin" being a space, set it to NULL
                    $gstin = $user['gst_no'] === " " ? null : $user['gst_no'];

                    // If user doesn't exist by email, create a new user
                    User::create([
                        'user_id' => $user['id'],
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
                        'gstin' => $gstin,
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
