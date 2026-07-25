<?php

namespace App\Http\Requests;

use App\Models\Categories;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category' => 'required|string|max:255',
        ];
    }
    public function viewer($request, $profile){
        $user = $request->user();
        $data = [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'category' =>  $profile['category']
        ];
        $category = Categories::create($data);
        return $category;
    }
     public function poster($request, $profile){
        $user = $request->user();
        $data = [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'category' =>  $profile['category']
        ];
        $category = Categories::create($data);
        return $category;
    }
}
