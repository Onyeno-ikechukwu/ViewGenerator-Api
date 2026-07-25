<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FlutterwaveRequest;
use App\Service\FlutterwaveService;
use App\Http\Resources\FlatterwaveResource;

/**
 * FlutterwaveBotController
 * =========================
 * 
 * PROJECT OVERVIEW:
 * ViewGenerator is a role-based content platform. Payments are handled through
 * the Flutterwave payment gateway for services used by both posters and viewers.
 * 
 * CONTROLLER ROLE:
 * Handles payment-related operations using the Flutterwave API. This controller
 * is used after users have been categorized (via CategoryController) and are
 * interacting with PosterController or ViewerController features that require
 * payment processing.
 * 
 * ARCHITECTURE FLOW:
 *   CategoryController → PosterController / ViewerController → FlutterwaveBotController
 * 
 * BUSINESS LOGIC DELEGATION:
 * Business logic is split between two service classes:
 *   - FlutterwaveRequest: Validates incoming payment data and orchestrates payment operations
 *   - FlutterwaveService: Communicates with the external Flutterwave API gateway
 * 
 * The controller formats success responses using FlatterwaveResource.
 * 
 * @see CategoryController        Role assignment (prerequisite for any user)
 * @see PosterController          Content creation (may trigger payments)
 * @see ViewerController          Content consumption (may trigger payments)
 * @see FlutterwaveRequest        Handles validation and payment orchestration
 * @see FlutterwaveService        Communicates with Flutterwave API
 * @see FlatterwaveResource       Formats payment response data
 */
class FlutterwaveBotController extends Controller
{

    /**
     * Retrieve available payment methods from Flutterwave.
     * 
     * Endpoint:  GET /api/payment/paymentmethod
     * Auth:      Required (sanctum)
     * 
     * Flow:
     *   1. FlutterwaveRequest calls FlutterwaveService to fetch available payment methods
     *   2. The result (list of payment methods) is returned directly
     * 
     * @param  FlutterwaveService  $flutterwave          Service for Flutterwave API communication
     * @param  FlutterwaveRequest  $flatterwaveRequest   Handles validation and business logic
     * 
     * @return mixed  List of available payment methods from Flutterwave
     */
    public function paymentMethod(FlutterwaveService $flutterwave, FlutterwaveRequest $flatterwaveRequest)
    {
        $paymentMethod = $flatterwaveRequest->paymentMethod($flatterwaveRequest, $flutterwave);
        return $paymentMethod;
    }

    /**
     * Create a new payment transaction via Flutterwave.
     * 
     * Endpoint:  POST /api/payment/createpayment
     * Auth:      Required (sanctum)
     * 
     * Flow:
     *   1. FlutterwaveRequest validates the incoming payment data
     *   2. FlutterwaveRequest calls FlutterwaveService to initiate the payment with Flutterwave
     *   3. The payment result is wrapped in FlatterwaveResource for consistent JSON formatting
     * 
     * @param  FlutterwaveService  $flutterwave          Service for Flutterwave API communication
     * @param  FlutterwaveRequest  $flatterwaveRequest   Handles validation and payment creation
     * 
     * @return \Illuminate\Http\JsonResponse  JSON response with payment details wrapped in FlatterwaveResource
     */
    public function createPayment(FlutterwaveService $flutterwave, FlutterwaveRequest $flatterwaveRequest)
    {
        $payment = $flatterwaveRequest->createPayment($flutterwave, $flatterwaveRequest);
        return response()->json(new FlatterwaveResource($payment));
    }
}