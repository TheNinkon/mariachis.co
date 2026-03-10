<?php

namespace App\Services;

use App\Models\MariachiListing;
use App\Models\MariachiListingPhoto;
use App\Models\MediaHash;

class MediaProtectionService
{
    public function registerListingPhotoHash(
        MariachiListing $listing,
        MariachiListingPhoto $photo,
        ?string $hashValue,
        string $algorithm = 'sha256'
    ): ?MediaHash {
        if (! $hashValue) {
            return null;
        }

        $duplicateSource = MediaHash::query()
            ->where('hash_value', $hashValue)
            ->where(function ($query) use ($photo): void {
                $query->where('media_type', '!=', 'listing_photo')
                    ->orWhere('media_id', '!=', $photo->id);
            })
            ->oldest('id')
            ->first();

        $mediaHash = MediaHash::query()->updateOrCreate(
            [
                'media_type' => 'listing_photo',
                'media_id' => $photo->id,
            ],
            [
                'mariachi_profile_id' => $listing->mariachi_profile_id,
                'mariachi_listing_id' => $listing->id,
                'file_path' => (string) $photo->path,
                'hash_algorithm' => $algorithm,
                'hash_value' => $hashValue,
                'is_duplicate' => (bool) $duplicateSource,
                'duplicate_of_media_hash_id' => $duplicateSource?->id,
                'first_seen_at' => $duplicateSource ? ($duplicateSource->first_seen_at ?: now()) : now(),
                'last_seen_at' => now(),
                'metadata' => [
                    'watermark_enabled' => (bool) $listing->watermark_enabled,
                    'image_hashing_enabled' => (bool) $listing->image_hashing_enabled,
                ],
            ]
        );

        if ($duplicateSource) {
            $listing->update(['has_duplicate_images' => true]);
        }

        // Watermark pipeline hook: keep metadata ready even if rendering is deferred.
        if ($listing->watermark_enabled && ! $photo->watermark_applied_at) {
            $photo->update([
                'watermark_applied_at' => now(),
                'watermark_version' => 'v1',
            ]);
        }

        return $mediaHash;
    }
}
