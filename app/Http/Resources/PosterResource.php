<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'category_id' => $this->category_id,
            'category' => $this->category,
            'video_url' => $this->video_url,
            'music_url' => $this->music_url,
            'payment_package' => $this->payment_package,
            'amount' => $this->amount,
            'status' => $this->status,
            'views' => $this->views,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
