<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Categories;
use Illuminate\Http\Request;

/**
 * CategoryController
 * ===================
 * 
 * PROJECT OVERVIEW:
 * ViewGenerator is a role-based content platform with two user types:
 *   - "viewers"  → content consumers who browse published posts
 *   - "posters"  → content creators who publish and manage posts
 * 
 * CONTROLLER ROLE:
 * This is the ENTRY POINT of the application. After authentication, every user
 * must be assigned a role (viewer or poster) before they can access any features.
 * The role assignment is stored in the `categories` table via the Categories model.
 * 
 * ARCHITECTURE FLOW:
 *   Authentication → CategoryController (role assignment) → PosterController or ViewerController
 *                                                         → FlutterwaveBotController (payments)
 * 
 * BUSINESS LOGIC DELEGATION:
 * This controller is intentionally thin. All business logic (validation, database operations)
 * is delegated to CategoryRequest. The controller only:
 *   1. Validates incoming request data via CategoryRequest
 *   2. Calls the appropriate method on CategoryRequest
 *   3. Returns a formatted JSON response via CategoryResource
 * 
 * @see CategoryRequest    Handles validation and database logic for role assignment
 * @see CategoryResource   Formats the response data structure
 * @see Categories         Eloquent model for the categories table
 * @see PosterController   Accessed after "poster" role is assigned
 * @see ViewerController   Accessed after "viewer" role is assigned
 * @see FlutterwaveBotController  Payment processing for both roles
 */
class CategoryController extends Controller
{
    /**
     * Assign the "viewer" role to an authenticated user.
     * 
     * Endpoint:  POST /api/category/viewer
     * Auth:      Required (sanctum)
     * Throttle:  6 requests per minute
     * 
     * Flow:
     *   1. CategoryRequest validates the incoming data (expects 'category' field)
     *   2. CategoryRequest::viewer() creates a record in the `categories` table
     *      with the authenticated user's ID, name, email, and chosen category
     *   3. The created category record is returned wrapped in CategoryResource
     * 
     * @param  Request          $request            The incoming HTTP request (provides authenticated user)
     * @param  CategoryRequest  $categeoryRequest   Handles validation and database insertion
     * 
     * @return \Illuminate\Http\JsonResponse  JSON with the created category data
     */
    public function viewer(Request $request, CategoryRequest $categeoryRequest)
    {
        $profile = $categeoryRequest->validated();
        $category = $categeoryRequest->viewer($request, $profile);
        return response()->json(new CategoryResource($category));
    }

    /**
     * Assign the "poster" role to an authenticated user.
     * 
     * Endpoint:  POST /api/category/poster
     * Auth:      Required (sanctum)
     * Throttle:  6 requests per minute
     * 
     * Flow:
     *   1. CategoryRequest validates the incoming data (expects 'category' field)
     *   2. CategoryRequest::poster() creates a record in the `categories` table
     *      with the authenticated user's ID, name, email, and chosen category
     *   3. The created category record is returned wrapped in CategoryResource
     * 
     * @param  Request          $request            The incoming HTTP request (provides authenticated user)
     * @param  CategoryRequest  $categeoryRequest   Handles validation and database insertion
     * 
     * @return \Illuminate\Http\JsonResponse  JSON with the created category data
     */
    public function poster(Request $request, CategoryRequest $categeoryRequest)
    {
        $profile = $categeoryRequest->validated();
        $category = $categeoryRequest->poster($request, $profile);
        return response()->json(new CategoryResource($category));
    }
}