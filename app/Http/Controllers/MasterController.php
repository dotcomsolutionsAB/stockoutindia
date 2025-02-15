<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CountryModel;
use App\Models\StateModel;
use App\Models\CityModel;

class MasterController extends Controller
{
    //for country
    public function fetchAllCountries()
    {
        try {
            $countries = CountryModel::all();

            return response()->json([
                'success' => true,
                'message' => 'Countries fetched successfully!',
                'data' => $countries->makeHidden(['created_at', 'updated_at']),
                'total_record' => count($countries),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching countries!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    //for state
    public function fetchAllStates($stateId = null)
    {
        try {
            // $states = StateModel::with('get_country:id,name')->get();

            // // Transform response to replace nested "country" with "country_name"
            // $states->transform(function ($state) {
            //     return [
            //         'id' => $state->id,
            //         'name' => $state->name,
            //         'country_name' => optional($state->get_country)->name, // Avoids errors if country is null
            //     ];
            // });            

            // Query builder with conditions
            $query = CityModel::with(['stateDetails' => function ($query) {
                $query->where('country_code', 101);
            }]);

            // If state_id is provided, filter cities by that state
            if (!empty($stateId)) {
                $query->where('state_id', $stateId);
            } else {
                // Ensure only cities from states where country_code is 101 are included
                $query->whereHas('stateDetails', function ($query) {
                    $query->where('country_code', 101);
                });
            }

            // Fetch cities
            $cities = $query->get();

            // Transform response
            $cities->transform(function ($city) {
                return [
                    'id' => $city->id,
                    'name' => $city->name,
                    'state_name' => optional($city->stateDetails)->name, // Avoids null errors
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'States fetched successfully!',
                'data' => $states,
                'total_record' => count($states),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching states!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // for city
    public function fetchAllCities()
    {
        try {
            // Fetch cities with their related state
            $cities = CityModel::with('stateDetails:id,name')->get();

            // Transform response to return state name instead of nested object
            $cities->transform(function ($city) {
                return [
                    'id' => $city->id,
                    'name' => $city->name,
                    'state_name' => optional($city->stateDetails)->name, // Avoids null errors
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Cities fetched successfully!',
                'data' => $cities,
                'total_record' => $cities->count(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching cities!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


}
