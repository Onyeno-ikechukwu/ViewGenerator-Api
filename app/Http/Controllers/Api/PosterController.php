<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PosterRequest;
use App\Http\Resources\PosterResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PosterController
 * =================
 * 
 * PROJECT OVERVIEW:
 * ViewGenerator is a role-based content platform. "Posters" are content creators
 * who publish and manage posts on the platform.
 * 
 * CONTROLLER ROLE:
 * Provides full CRUD operations for poster content. Only users who have been
 * assigned the "poster" role via CategoryController can access these endpoints.
 * 
 * ARCHITECTURE FLOW:
 *   CategoryController (role: poster) → PosterController (CRUD operations)
 *                                      → FlutterwaveBotController (payments for services)
 * 
 * BUSINESS LOGIC DELEGATION:
 * All business logic is delegated to PosterRequest. The controller:
 *   1. Receives the HTTP request and passes it to PosterRequest
 *   2. PosterRequest handles validation, authorization, and database operations
 *   3. The controller formats the response using PosterResource
 * 
 * @see CategoryController        Role assignment (prerequisite)
 * @see PosterRequest             Handles validation and database logic
 * @see PosterResource            Formats the response data structure
 * @see FlutterwaveBotController  Payment processing
 */
class PosterController extends Controller
{
    /**
     * Retrieve a paginated list of all posters.
     * 
     * Endpoint:  GET /api/posters
     * Auth:      Required (sanctum)
     * 
     * Flow:
     *   1. PosterRequest queries the database and returns all poster records
     *   2. Results are wrapped in PosterResource collection for consistent JSON formatting
     * 
     * @param  PosterRequest  $request  Handles the database query logic
     * 
     * @return \Illuminate\Http\JsonResponse  JSON collection of poster resources
     */
    public function index(PosterRequest $request)
    {
        $posters = $request->index();
        return response()->json(PosterResource::collection($posters));
    }

    /**
     * Create a new poster record.
     * 
     * Endpoint:  POST /api/posters
     * Auth:      Required (sanctum)
     * 
     * Flow:
     *   1. PosterRequest validates the incoming data
     *   2. PosterRequest creates the record in the database
     *   3. If the result is a JsonResponse (error case), it's returned directly
     *   4. Otherwise, the created poster is wrapped in PosterResource
     * 
     * @param  PosterRequest  $request  Handles validation and database insertion
     * 
     * @return \Illuminate\Http\JsonResponse  JSON of the created poster or error response
     */
    public function store(PosterRequest $request)
    {
        $data = $request->validated();
        $poster = $request->store($data);
        if ($poster instanceof JsonResponse) {
            return $poster;
        }
        return response()->json(new PosterResource($poster));
    }

    /**
     * Retrieve a single poster by its ID.
     * 
     * Endpoint:  GET /api/posters/{poster}
     * Auth:      Required (sanctum)
     * 
     * Flow:
     *   1. PosterRequest queries the database for the specific poster by ID
     *   2. The result is wrapped in PosterResource
     * 
     * @param  int            $id       The ID of the poster to retrieve
     * @param  PosterRequest  $request  Handles the database query logic
     * 
     * @return \Illuminate\Http\JsonResponse  JSON of the requested poster
     */
    public function show($id, PosterRequest $request)
    {
        $poster = $request->show($id);
        return response()->json(new PosterResource($poster));
    }

    /**
     * Update an existing poster record.
     * 
     * Endpoint:  PUT/PATCH /api/posters/{poster}
     * Auth:      Required (sanctum)
     * 
     * Flow:
     *   1. PosterRequest validates the incoming update data
     *   2. PosterRequest updates the record in the database
     *   3. The updated poster is wrapped in PosterResource
     * 
     * @param  int            $id       The ID of the poster to update
     * @param  PosterRequest  $request  Handles validation and database update
     * 
     * @return \Illuminate\Http\JsonResponse  JSON of the updated poster
     */
    public function update($id, PosterRequest $request)
    {
        $data = $request->validated();
        $poster = $request->update($id, $data);
        return response()->json(new PosterResource($poster));
    }
}