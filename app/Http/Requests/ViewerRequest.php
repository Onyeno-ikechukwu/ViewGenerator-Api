<?php

namespace App\Http\Requests;

use App\Models\Categories;
use App\Models\Posters;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ViewerRequest extends FormRequest
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
            //
        ];
    }
    public function index(){
        $category = Categories::where('user_id', Auth::id())->first(); 
        if($category->category == 'poster'){
           $posters = Posters::where(function ($query) {
                $query->where('payment_package', 'small')
                    ->where('views', '<', 150);
            })
            ->orWhere(function ($query) {
                $query->where('payment_package', 'medium')
                    ->where('views', '<=', 600);
            })
            ->orWhere(function ($query) {
                $query->where('payment_package', 'large')
                    ->where('views', '<=', 1200);
            })
            ->paginate();
            return $posters;
        }
        return response()->json(['message' => 'This user is not allowed']);
    }
    public function show($id){
        $category = Categories::where('user_id', Auth::id())->first();  
        if($category->category == 'poster'){
            $viewer = Posters::findOrFail($id);
            sleep(10);
            $viewer->increment('views');
            $viewer->save();
            return $viewer;
        }
    }
}
