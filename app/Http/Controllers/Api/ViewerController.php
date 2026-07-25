<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ViewerRequest;
use App\Http\Resources\ViewerResource;
use Illuminate\Http\Request;

/**
 * ViewerController
 * =================
 * 
 * PROJECT OVERVIEW:
 * ViewGenerator is a role-based content platform. "Viewers" are content consumers
 * who browse and read published posts on the platform.
 * 
 * CONTROLLER ROLE:
 * Provides read-only access to published content. Only users who have been
 * assigned the "viewer" role via CategoryController can access these endpoints.
 * Viewers can list all available content and view individual posts.
 * 
 * ARCHITECTURE FLOW:
 *   CategoryController (role: viewer) → ViewerController (read-only browsing)
 * 
 * BUSINESS LOGIC DELEGATION:
 * All business logic is delegated to ViewerRequest. The controller:
 *   1. Receives the HTTP request and passes it to ViewerRequest
 *   2. ViewerRequest handles authorization and database queries
 *   3. The controller formats the response using ViewerResource
 * 
 * @see CategoryController  Role assignment (prerequisite)
 * @see ViewerRequest       Handles authorization and database query logic
 * @see ViewerResource      Formats the response data structure
 */
class ViewerController extends Controller
{
    /**
     * Retrieve a paginated list of all published content available for viewers.
     * 
     * Endpoint:  GET /api/viewers
     * Auth:      Required (sanctum)
     * 
     * Flow:
     *   1. ViewerRequest queries the database for all published posts
     *   2. Results are wrapped in ViewerResource collection for consistent JSON formatting
     * 
     * @param  ViewerRequest  $request  Handles the database query logic
     * 
     * @return \Illuminate\Http\JsonResponse  JSON collection of published content
     */
    public function index(ViewerRequest $request)
    {
        $posters = $request->index();
        return response()->json(ViewerResource::collection($posters));
    }

    /**
     * Retrieve a single published post by its ID.
     * 
     * Endpoint:  GET /api/viewers/{viewer}
     * Auth:      Required (sanctum)
     * 
     * Flow:
     *   1. ViewerRequest queries the database for the specific published post by ID
     *   2. The result is wrapped in ViewerResource
     * 
     * @param  int            $id       The ID of the published post to retrieve
     * @param  ViewerRequest  $request  Handles the database query logic
     * 
     * @return \Illuminate\Http\JsonResponse  JSON of the requested published post
     */
    public function show($id, ViewerRequest $request)
    {
        $poster = $request->show($id);
        return response()->json(new ViewerResource($poster));
    }
}