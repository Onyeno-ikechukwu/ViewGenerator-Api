# ViewGenerator

A **role-based content platform** built with Laravel 13, Sanctum authentication, and Flutterwave payment integration. Users are categorized as either **viewers** (content consumers) or **posters** (content creators), each with distinct permissions and workflows.

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Tech Stack](#tech-stack)
3. [Authentication Flow](#authentication-flow)
4. [Role Assignment Flow](#role-assignment-flow)
5. [Poster Flow (Content Creators)](#poster-flow-content-creators)
6. [Viewer Flow (Content Consumers)](#viewer-flow-content-consumers)
7. [Payment Flow (Flutterwave)](#payment-flow-flutterwave)
8. [Complete API Endpoints](#complete-api-endpoints)
9. [Database Schema](#database-schema)
10. [Project Structure](#project-structure)
11. [Scramble API Documentation](#scramble-api-documentation)
12. [Installation & Setup](#installation--setup)

---

## Architecture Overview

ViewGenerator follows a **thin controller** pattern. Controllers are intentionally minimal — they only receive HTTP requests, delegate all business logic to **FormRequest classes**, and return formatted JSON responses via **API Resources**.

```
HTTP Request → Controller → FormRequest (validation + business logic) → Response (API Resource)
```

### Key Design Decisions

| Decision | Rationale |
|----------|-----------|
| Business logic in FormRequests | Keeps controllers clean and testable; FormRequests have access to validation, authorization, and dependency injection |
| API Resources for responses | Ensures consistent JSON structure across all endpoints |
| Sanctum for API auth | Lightweight token-based authentication suitable for SPAs and mobile apps |
| Scramble for API docs | Auto-generates OpenAPI documentation from PHPDoc annotations — no manual spec writing |

---

## Tech Stack

| Technology | Purpose |
|------------|---------|
| **Laravel 13** | PHP framework |
| **PHP 8.3+** | Runtime |
| **Sanctum** | API token authentication |
| **Flutterwave API** | Payment gateway |
| **Scramble** | Auto-generated OpenAPI documentation |
| **SQLite / MySQL** | Database (configurable via `.env`) |

---

## Authentication Flow

Authentication is handled by Laravel Breeze (API stack) with Sanctum tokens.

### Endpoints (defined in `routes/auth.php`)

| Method | Endpoint | Middleware | Description |
|--------|----------|------------|-------------|
| POST | `/api/register` | guest | Register a new user |
| POST | `/api/login` | guest | Login and receive Sanctum token |
| POST | `/api/logout` | auth:sanctum | Revoke current token |
| POST | `/api/forgot-password` | guest | Send password reset link |
| POST | `/api/reset-password` | guest | Reset password with token |
| GET | `/api/verify-email/{id}/{hash}` | auth:sanctum, signed | Verify email address |
| POST | `/api/email/verification-notification` | auth:sanctum | Resend verification email |

### Auth Controllers

All located in `app/Http/Controllers/Auth/`:

- **RegisteredUserController** — Handles user registration
- **LoginController** — Handles login (`store`) and logout (`destroy`)
- **PasswordResetLinkController** — Sends password reset emails
- **NewPasswordController** — Processes password reset
- **VerifyEmailController** — Verifies email signatures
- **EmailVerificationNotificationController** — Resends verification

---

## Role Assignment Flow

After authentication, every user **must** be assigned a role before accessing any features.

### Controller: `CategoryController`

**File:** `app/Http/Controllers/Api/CategoryController.php`

This is the **entry point** of the application. It assigns a role (viewer or poster) and stores it in the `categories` table.

| Method | Endpoint | Description |
|--------|----------|-------------|
| `viewer()` | POST `/api/category/viewer` | Assign "viewer" role |
| `poster()` | POST `/api/category/poster` | Assign "poster" role |

**Flow:**
1. `CategoryRequest` validates the incoming data (expects `category` field)
2. `CategoryRequest::viewer()` or `CategoryRequest::poster()` creates a record in the `categories` table with the authenticated user's ID, name, email, and chosen category
3. Response is formatted via `CategoryResource`

**Request body:**
```json
{
    "category": "viewer"
}
```

**Response:**
```json
{
    "data": {
        "id": 1,
        "user_id": 5,
        "name": "John Doe",
        "email": "john@example.com",
        "category": "viewer"
    }
}
```

### Related Files

| File | Role |
|------|------|
| `app/Http/Requests/CategoryRequest.php` | Validates `category` field, creates DB record |
| `app/Http/Resources/CategoryResource.php` | Formats response: `id`, `user_id`, `name`, `email`, `category`, `created_at` |
| `app/Models/Categories.php` | Eloquent model for `categories` table (fillable: `user_id`, `name`, `email`, `category`) |

---

## Poster Flow (Content Creators)

Users with the **"poster"** role can create, read, update, and delete content.

### Controller: `PosterController`

**File:** `app/Http/Controllers/Api/PosterController.php`

| Method | Endpoint | Description |
|--------|----------|-------------|
| `index()` | GET `/api/posters` | List all posters (paginated) |
| `store()` | POST `/api/posters` | Create a new poster |
| `show($id)` | GET `/api/posters/{poster}` | Get a single poster by ID |
| `update($id)` | PUT/PATCH `/api/posters/{poster}` | Update a poster |

**Store flow:**
1. `PosterRequest` validates the incoming data (`video_url`, `music_url`, `tx_ref`)
2. Verifies the user has the "poster" role from `categories` table
3. Validates the payment amount matches the selected package (small/medium/large)
4. Creates a record in the `posters` table with user info, content URLs, payment details, and status
5. Returns the created poster wrapped in `PosterResource`

**Request body (store):**
```json
{
    "video_url": "https://example.com/video.mp4",
    "music_url": "https://example.com/music.mp3",
    "tx_ref": "PAY-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
}
```

### Related Files

| File | Role |
|------|------|
| `app/Http/Requests/PosterRequest.php` | Validates data, checks role, validates payment, creates/updates records |
| `app/Http/Resources/PosterResource.php` | Formats poster response data |
| `app/Models/Posters.php` | Eloquent model for `posters` table (fillable: `user_id`, `category_id`, `category`, `video_url`, `music_url`, `payment_package`, `amount`, `status`) |
| `app/Jobs/IncrementPosterViews.php` | Job for incrementing view counts |

---

## Viewer Flow (Content Consumers)

Users with the **"viewer"** role can browse published content (read-only).

### Controller: `ViewerController`

**File:** `app/Http/Controllers/Api/ViewerController.php`

| Method | Endpoint | Description |
|--------|----------|-------------|
| `index()` | GET `/api/viewers` | List all published content (paginated) |
| `show($id)` | GET `/api/viewers/{viewer}` | Get a single published post by ID |

**Flow:**
1. `ViewerRequest` queries the database for published posts
2. Results are wrapped in `ViewerResource` collection

### Related Files

| File | Role |
|------|------|
| `app/Http/Requests/ViewerRequest.php` | Handles database queries for viewer content |
| `app/Http/Resources/ViewerResource.php` | Formats viewer response data |

---

## Payment Flow (Flutterwave)

Payments are processed through the **Flutterwave API** gateway. Both posters and viewers may need to make payments for services.

### Controller: `FlutterwaveBotController`

**File:** `app/Http/Controllers/Api/FlutterwaveBotController.php`

| Method | Endpoint | Description |
|--------|----------|-------------|
| `paymentMethod()` | GET `/api/payment/paymentmethod` | Retrieve available payment methods |
| `createPayment()` | POST `/api/payment/createpayment` | Create a new payment transaction |

### Payment Method Flow

1. `FlutterwaveRequest` validates card details (`card_number`, `expiry_month`, `expiry_year`, `cvv`)
2. `FlutterwaveService::getPaymentMethods()`:
   - Obtains an OAuth2 token from Flutterwave's identity provider
   - Encrypts card details using AES-256-GCM with a generated nonce
   - Sends encrypted card data to Flutterwave's payment-methods endpoint
   - Returns the payment method ID

### Create Payment Flow

1. `FlutterwaveRequest` validates `amount`, `payment_package`, and `payment_method_id`
2. Validates amount-to-package mapping:
   - 1000–4999 → "small" package
   - 5000–9999 → "medium" package
   - 10000+ → "large" package
3. `FlutterwaveService::createCustomer()` creates a customer on Flutterwave
4. `FlutterwaveService::createPayment()` creates an order on Flutterwave
5. On success, `FlutterwaveService::verify()` confirms the transaction
6. A `Payment` record is created in the local database
7. Response is formatted via `FlatterwaveResource`

### Service Layer: `FlutterwaveService`

**File:** `app/Service/FlutterwaveService.php`

| Method | Description |
|--------|-------------|
| `getToken()` | Obtains OAuth2 access token from Flutterwave |
| `encryptAES($data, $nonce)` | Encrypts sensitive card data using AES-256-GCM |
| `generateNonce()` | Generates a 12-character random nonce |
| `createCustomer($email, $name)` | Creates a customer on Flutterwave |
| `getPaymentMethods($cardNumber, $expiryMonth, $expiryYear, $cvv)` | Gets available payment methods for a card |
| `createPayment($amount, $email, $name, $txRef, $package, $customerId, $paymentMethod)` | Creates a payment order |
| `verify($transactionId)` | Verifies a completed transaction |

### Related Files

| File | Role |
|------|------|
| `app/Http/Requests/FlutterwaveRequest.php` | Validates payment data, orchestrates payment flow |
| `app/Service/FlutterwaveService.php` | Communicates with Flutterwave API |
| `app/Http/Resources/FlatterwaveResource.php` | Formats payment response: `id`, `user_id`, `tx_ref`, `transaction_id`, `amount`, `currency`, `status`, `plan`, `views`, `created_at`, `updated_at` |
| `app/Models/Payment.php` | Eloquent model for `payment` table (fillable: `user_id`, `tx_ref`, `transaction_id`, `amount`, `currency`, `status`, `plan`) |

---

## Complete API Endpoints

All API routes are defined in `routes/api.php` and `routes/auth.php`.

### Authentication Routes (prefix: `/api`)

| Method | Endpoint | Middleware | Controller |
|--------|----------|------------|------------|
| POST | `/register` | guest | `RegisteredUserController@store` |
| POST | `/login` | guest | `LoginController@store` |
| POST | `/logout` | auth:sanctum | `LoginController@destroy` |
| POST | `/forgot-password` | guest | `PasswordResetLinkController@store` |
| POST | `/reset-password` | guest | `NewPasswordController@store` |
| GET | `/verify-email/{id}/{hash}` | auth:sanctum, signed | `VerifyEmailController` |
| POST | `/email/verification-notification` | auth:sanctum | `EmailVerificationNotificationController@store` |

### Protected Routes (prefix: `/api`, middleware: `auth:sanctum`, throttle: `6,1`)

| Method | Endpoint | Controller |
|--------|----------|------------|
| GET | `/user` | Returns authenticated user |
| POST | `/category/viewer` | `CategoryController@viewer` |
| POST | `/category/poster` | `CategoryController@poster` |
| GET | `/payment/paymentmethod` | `FlutterwaveBotController@paymentMethod` |
| POST | `/payment/createpayment` | `FlutterwaveBotController@createPayment` |
| GET | `/posters` | `PosterController@index` |
| POST | `/posters` | `PosterController@store` |
| GET | `/posters/{poster}` | `PosterController@show` |
| PUT/PATCH | `/posters/{poster}` | `PosterController@update` |
| DELETE | `/posters/{poster}` | `PosterController@destroy` |
| GET | `/viewers` | `ViewerController@index` |
| POST | `/viewers` | `ViewerController@store` |
| GET | `/viewers/{viewer}` | `ViewerController@show` |
| PUT/PATCH | `/viewers/{viewer}` | `ViewerController@update` |
| DELETE | `/viewers/{viewer}` | `ViewerController@destroy` |

### Complete Application Flow

```
                    ┌─────────────────┐
                    │   /api/register  │
                    │   /api/login     │
                    └────────┬────────┘
                             │
                             ▼
                    ┌─────────────────┐
                    │  Authenticated   │
                    │      User        │
                    └────────┬────────┘
                             │
                             ▼
                    ┌─────────────────┐
                    │  CategoryController │
                    │  (Role Assignment)  │
                    └────────┬────────┘
                             │
              ┌──────────────┴──────────────┐
              │                              │
              ▼                              ▼
    ┌──────────────────┐          ┌──────────────────┐
    │  "poster" role   │          │  "viewer" role   │
    │ PosterController │          │ ViewerController │
    │  (CRUD content)  │          │ (read-only browse)│
    └────────┬─────────┘          └────────┬─────────┘
              │                              │
              └──────────────┬──────────────┘
                             │
                             ▼
                    ┌─────────────────┐
                    │FlutterwaveBot   │
                    │  Controller     │
                    │  (Payments)     │
                    └─────────────────┘
```

---

## Database Schema

### `users` table (Laravel default)
| Column | Type | Notes |
|--------|------|-------|
| id | bigint AI | Primary key |
| name | string | |
| email | string | Unique |
| email_verified_at | timestamp | Nullable |
| password | string | Hashed |
| remember_token | string | Nullable |

### `categories` table
| Column | Type | Notes |
|--------|------|-------|
| id | bigint AI | Primary key |
| user_id | bigint | Foreign key to users |
| name | string | User's name |
| email | string | User's email |
| category | string | "viewer" or "poster" |

### `posters` table
| Column | Type | Notes |
|--------|------|-------|
| id | bigint AI | Primary key |
| user_id | bigint | Foreign key to users |
| category_id | bigint | Foreign key to categories |
| category | string | "poster" |
| video_url | string | Content video URL |
| music_url | string | Content music URL |
| payment_package | string | "small", "medium", or "large" |
| amount | decimal | Payment amount |
| status | string | "paid" |

### `payment` table
| Column | Type | Notes |
|--------|------|-------|
| id | bigint AI | Primary key |
| user_id | bigint | Foreign key to users |
| tx_ref | string | Transaction reference (UUID) |
| transaction_id | string | Flutterwave transaction ID |
| amount | decimal | Payment amount |
| currency | string | e.g., "NGN" |
| status | string | Payment status |
| plan | string | "small", "medium", or "large" |

### `personal_access_tokens` table (Sanctum)
Stores API tokens for Sanctum authentication.

### `jobs` table
Stores queued jobs (e.g., `IncrementPosterViews`).

---

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── CategoryController.php       # Role assignment (viewer/poster)
│   │   │   ├── FlutterwaveBotController.php  # Payment processing
│   │   │   ├── PosterController.php          # Content CRUD for posters
│   │   │   └── ViewerController.php          # Read-only browsing for viewers
│   │   ├── Auth/
│   │   │   ├── EmailVerificationNotificationController.php
│   │   │   ├── LoginController.php
│   │   │   ├── NewPasswordController.php
│   │   │   ├── PasswordResetLinkController.php
│   │   │   ├── RegisteredUserController.php
│   │   │   └── VerifyEmailController.php
│   │   └── Controller.php                    # Base controller
│   ├── Requests/
│   │   ├── Auth/LoginRequest.php
│   │   ├── CategoryRequest.php               # Role assignment logic
│   │   ├── FlutterwaveRequest.php            # Payment orchestration
│   │   ├── PosterRequest.php                 # Poster CRUD logic
│   │   └── ViewerRequest.php                 # Viewer query logic
│   └── Resources/
│       ├── CategoryResource.php              # Category response format
│       ├── FlatterwaveResource.php           # Payment response format
│       ├── PosterResource.php                # Poster response format
│       └── ViewerResource.php                # Viewer response format
├── Jobs/
│   └── IncrementPosterViews.php              # Queued view counter
├── Models/
│   ├── Categories.php                        # Categories Eloquent model
│   ├── Payment.php                           # Payment Eloquent model
│   ├── Posters.php                           # Posters Eloquent model
│   └── User.php                              # User Eloquent model
└── Service/
    └── FlutterwaveService.php                # Flutterwave API integration
routes/
├── api.php                                   # API route definitions
└── auth.php                                  # Auth route definitions
config/
└── services.php                              # Flutterwave API credentials
```

---

## Scramble API Documentation

[Scramble](https://scramble.dedoc.co/) is installed as `dedoc/scramble: ^0.13.35`. It automatically generates interactive OpenAPI (Swagger) documentation from your Laravel application's PHPDoc annotations and route definitions.

### How Scramble Works

1. **Route Discovery** — Scramble scans all routes defined in `routes/api.php` and `routes/auth.php`
2. **PHPDoc Parsing** — It reads the PHPDoc blocks on your controllers and methods to extract:
   - Endpoint descriptions
   - Request parameters and validation rules (from FormRequest classes)
   - Response schemas (from API Resource classes)
   - Authentication requirements
3. **OpenAPI Generation** — It compiles everything into an OpenAPI 3.0 specification
4. **Interactive UI** — Serves the documentation at `/docs/api` with a Swagger UI interface

### What Scramble Extracts

| Source | What Gets Documented |
|--------|---------------------|
| Route definitions | HTTP method, URL path, middleware |
| Controller PHPDoc `@see` | Related class references |
| Method PHPDoc `@param` | Parameter names, types, descriptions |
| Method PHPDoc `@return` | Return types and descriptions |
| FormRequest `rules()` | Request body validation rules |
| API Resource `toArray()` | Response schema structure |
| Route names | Named routes for easy reference |

### Accessing the Documentation

```bash
# Start the development server
php artisan serve

# Visit in your browser
http://localhost:8000/docs/api
```

### Configuration

Scramble works out of the box with zero configuration. For advanced configuration, you can publish the config:

```bash
php artisan vendor:publish --provider="Dedoc\Scramble\ScrambleServiceProvider"
```

This creates `config/scramble.php` where you can customize:
- API title and description
- API version
- Server URLs
- Authentication schemes

### Writing PHPDoc for Scramble

Scramble reads the PHPDoc blocks you've already written on your controllers. Here's what it looks for:

```php
/**
 * Brief description of the method.
 * 
 * Longer description explaining the flow.
 * 
 * @param  SomeType  $paramName  Description of the parameter
 * @return \Illuminate\Http\JsonResponse  Description of the response
 */
```

The more detailed your PHPDoc, the better your generated documentation will be.

---

## Installation & Setup

### Prerequisites

- PHP 8.3+
- Composer
- Node.js & npm
- SQLite or MySQL

### Steps

```bash
# 1. Clone the repository
git clone <repository-url>
cd ViewGenerator

# 2. Install PHP dependencies
composer install

# 3. Install Node dependencies
npm install

# 4. Environment setup
cp .env.example .env
php artisan key:generate

# 5. Configure your .env file
#    - Database connection (SQLite is default)
#    - Flutterwave API credentials:
#      FLW_ENCRYPTION_KEY=
#      SERVICES_FLUTTERWAVE_CLIENT_ID=
#      SERVICES_FLUTTERWAVE_CLIENT_SECRET=

# 6. Run migrations
php artisan migrate

# 7. Build frontend assets
npm run build

# 8. Start the development server
php artisan serve
```

### Development Server

```bash
# Run all services concurrently (server, queue, logs, Vite)
php artisan dev
```

### Testing

```bash
php artisan test
```

---

## Environment Variables

| Variable | Description |
|----------|-------------|
| `FLW_ENCRYPTION_KEY` | Flutterwave AES encryption key |
| `SERVICES_FLUTTERWAVE_CLIENT_ID` | Flutterwave OAuth2 client ID |
| `SERVICES_FLUTTERWAVE_CLIENT_SECRET` | Flutterwave OAuth2 client secret |

These are configured in `config/services.php` under the `flutterwave` key.