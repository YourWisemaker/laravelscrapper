<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class OnlyFansProfile extends Model
{
    use Searchable;
    
    protected $table = 'onlyfans_profiles';

    protected $fillable = [
        'username',
        'multilogin_profile_id',
        'name',
        'bio',
        'likes_count',
        'avatar_url',
        'cover_url',
        'posts_count',
        'followers_count',
        'following_count',
        'last_scraped_at',
        'last_browser_session',
        'browser_cookies'
    ];

    protected $casts = [
        'last_scraped_at' => 'datetime',
        'last_browser_session' => 'datetime',
        'browser_cookies' => 'array',
    ];

    public function toSearchableArray()
    {
        return [
            'username' => $this->username,
            'name' => $this->name,
            'bio' => $this->bio,
        ];
    }

    public function shouldBeSearchable()
    {
        return true;
    }
}