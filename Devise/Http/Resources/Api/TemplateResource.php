<?php

namespace Devise\Http\Resources\Api;

use Illuminate\Http\Resources\Json\Resource;

class TemplateResource extends Resource
{
  /**
   * Transform the resource into an array.
   *
   * @param  \Illuminate\Http\Request
   * @return array
   */
  public function toArray($request)
  {
    return [
      'id'         => $this->id,
      'name'       => $this->name,
      'layout'     => $this->layout,
      'created_at' => $this->created_at->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at->format('Y-m-d H:i:s')
    ];
  }
}