<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutoReply extends Model
{
    protected $fillable = ['keyword', 'reply', 'match_type', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function matches(string $text): bool
    {
        $t = mb_strtolower(trim($text));
        $k = mb_strtolower(trim($this->keyword));
        return match ($this->match_type) {
            'exact' => $t === $k,
            'starts_with' => str_starts_with($t, $k),
            default => str_contains($t, $k),
        };
    }
}
