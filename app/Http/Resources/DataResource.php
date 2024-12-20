<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DataResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'order_id' => $this->ORDER_ID,
            'regional' => $this->REGIONAL,
            'witel' => $this->WITEL,
            'datel' => $this->DATEL,
            'sto' => $this->STO,
        ];
    }
}