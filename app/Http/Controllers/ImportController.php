<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ProductModel;
use App\Models\UploadModel; // Assuming you have a model for file uploads
use App\Models\CityModel; // Add the CityModel import
use Illuminate\Support\Facades\Http; // To make API requests
use Illuminate\Support\Facades\Storage;
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
            
                // Check if the mobile or email exists, update the user
                if ($existingUserByEmail || $existingUserByMobile) {
                    // If the username is the same as the existing username, don't update it
                    $username = $existingUserByEmail ? $existingUserByEmail->username : $phoneWithPrefix;

                    // Handle "gstin" being a space, set it to NULL
                    $gstin = ($user['gst_no'] === " " || $user['gst_no'] === "test") ? null : $user['gst_no'];

                    // Update the existing user
                    $existingUser = $existingUserByEmail ?? $existingUserByMobile;
                    $existingUser->update([
                        'user_id' => $user['id'],
                        'name' => $user['fullname'],
                        'email' => $user['email'],
                        'password' => bcrypt($user['password']),
                        'role' => 'user', // You can modify this based on your logic
                        'username' => $username, // Set username as phone with +91, or keep existing one if it already exists
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
                else {
                    // Handle "gstin" being a space, set it to NULL
                    $gstin = ($user['gst_no'] === " " || $user['gst_no'] === "test") ? null : $user['gst_no'];

                    // If user doesn't exist by email or mobile, create a new user
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


    // Method to import products from external API
    public function importProducts()
    {
        // Fetch the products from the external API
        $response = Http::get('http://sync.stockoutindia.com/get_products.php');

        // Check if the response is successful
        if ($response->successful()) {
            $products = $response->json()['data']; // Adjust according to the response format

            foreach ($products as $product) {
                // Map the user_id from the old table (API user_id) to the new user_id from the 'users' table
                $user = User::where('user_id', $product['user_id'])->first(); // Mapping old user_id to new user_id
                
                if (!$user) {
                    continue; // Skip if the user does not exist in the new users table
                }

                // Check if the product already exists based on product name (uniqueness)
                $existingProduct = ProductModel::where('product_id', $product['id'])
                    ->where('user_id', $user->id) // Ensure it belongs to the correct user
                    ->first();

                // Prepare product data
                $productData = [
                    'product_id' => $product['id'], // Use the mapped user_id from the User model
                    'user_id' => $user->id, // Use the mapped user_id from the User model
                    'product_name' => $product['name'],
                    'original_price' => $product['original_price'] ?? 1,
                    'selling_price' => $product['price'],
                    'offer_quantity' => $product['offer_qty'] ?? 1,
                    'minimum_quantity' => $product['min_qty'] ?? 1,
                    'unit' => $product['units'],
                    'industry' => $product['industry'],
                    'sub_industry' => $product['sub_industry'],
                    'status' => $product['is_active'], // Assuming status is 1 for active
                    'description' => $product['description'],
                    'image' => null, // We will handle images separately
                ];

                // If the product exists, update it. Otherwise, create a new record
                if ($existingProduct) {
                    $existingProduct->update($productData);
                    $productModel = $existingProduct;
                } else {
                    $productModel = ProductModel::create($productData);
                }

                // Now handle the product images
                if (isset($product['images']) && count($product['images']) > 0) {
                    $uploadIds = [];

                    foreach ($product['images'] as $imageUrl) {
                        // Get the image name from the URL
                        $imageName = basename($imageUrl);
                        $imagePath = 'uploads/products/product_images/' . $imageName;

                        // Check if the file already exists in public storage
                        if (Storage::disk('public')->exists($imagePath)) {
                            // If the file exists, skip downloading and saving it
                            // Get the existing upload record and add its ID
                            $existingUpload = UploadModel::where('file_url', Storage::url($imagePath))->first();
                            if ($existingUpload) {
                                $uploadIds[] = $existingUpload->id;
                                continue; // Skip the current image if it already exists
                            }
                        }

                        // If the image doesn't exist, download it using cURL
                        $imageContents = $this->downloadImageUsingCurl($imageUrl);

                        if ($imageContents === false) {
                            // Handle the error if the image download fails
                            continue; // Skip the image if the download failed
                        }

                        // Save the image to public storage
                        Storage::disk('public')->put($imagePath, $imageContents);

                        // Generate the image URL (for saving in DB)
                        $storedPath = Storage::url($imagePath);

                        // Create an upload record in the UploadModel (if you have one)
                        $upload = UploadModel::create([
                            'file_name' => $imageName,
                            'file_url' => $storedPath,
                            'file_size' => strlen($imageContents), // Image size in bytes
                        ]);

                        // Add the upload ID to the list of image IDs for this product
                        $uploadIds[] = $upload->id;
                    }

                    // Update the product's image column with the new comma-separated upload IDs
                    $productModel->image = implode(',', $uploadIds);
                    $productModel->save();
                }

            }

            return response()->json(['status' => true, 'message' => 'Products imported successfully']);
        }

        return response()->json(['status' => false, 'message' => 'Failed to fetch products from API']);
    }

    private function downloadImageUsingCurl($url)
    {
        // Initialize cURL session
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects if needed
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification (if necessary)

        // Execute cURL request
        $data = curl_exec($ch);

        // Check for errors
        if (curl_errno($ch)) {
            // If there's an error, return false
            curl_close($ch);
            return false;
        }

        // Close cURL session
        curl_close($ch);

        return $data; // Return the image contents
    }
}


