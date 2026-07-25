<?php

namespace App\Http\Requests;

use App\Jobs\IncrementPosterViews;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Categories;
use App\Models\Payment;
use App\Models\Posters;

class PosterRequest extends FormRequest
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
        if ($this->isMethod('get')) {
            return [];
        }
        if ($this->isMethod('put')) {
            return [
                'video_url' => 'string|max:255',
                'music_url' => 'string|max:255',
            ];
        }
        return [
            'video_url' => 'string|max:255',
            'music_url' => 'string|max:255',
            'tx_ref' => 'string|max:255',
        ];
    }
    public function store($data){
        $category = Categories::where('user_id', Auth::id())->first();  
        $payments = Payment::where('user_id', Auth::id())->where('tx_ref', $data['tx_ref'])->first();
        if($category->category == 'poster'){
            if($payments['amount'] < 1000){
                return response()->json(['message' => 'Amount must be above 1000 or more for any package']);
            }
            if($payments['amount'] >= 1000 && $payments['amount'] <= 4999 && $payments['plan'] != 'small'){
                return response()->json(['message' => 'Amount between 1000–4999 must use small package']);
            }

            if($payments['amount'] >= 5000 && $payments['amount'] <= 9999 && $payments['plan'] != 'medium'){
                return response()->json(['message' => 'Amount between 5000–9999 must use medium package']);
            }

            if($payments['amount'] >= 10000 && $payments['plan'] != 'large'){
                return response()->json(['message' => 'Amount 10000+ must use large package']);
            }

            $file =[
                'user_id' => $category->user_id,
                'category_id' => $category->id,
                'category' => $category->category,
                'video_url' => $data['video_url'],  
                'music_url' => $data['music_url'],
                'payment_package' => $payments->plan,
                'amount' => $payments->amount,
                'status' => 'paid'
            ];
            $poster = Posters::create($file);
            return $poster;
        }else{
            return response()->json(['message' => 'This user is not allowed to create poster']);
        }
        
    }
    public function index(){
        $category = Categories::where('user_id', Auth::id())->first();  
        if($category->category == 'poster'){
            $posters = Posters::where(function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->paginate();
            return $posters;
        }
    }
    public function show($id){
        $category = Categories::where('user_id', Auth::id())->first();  
        if($category->category == 'poster'){
            $poster = Posters::findOrFail($id);
            return $poster;
        }
    }
    public function update($id, $data){
        $category = Categories::where('user_id', Auth::id())->first();  
        if($category->category == 'poster'){
            $poster = Posters::findOrFail($id);
            $poster->update($data);
            return $poster;
        }
    }
    
}
