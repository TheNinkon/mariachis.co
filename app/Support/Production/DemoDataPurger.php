<?php

namespace App\Support\Production;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class DemoDataPurger
{
    /**
     * @return array<string, mixed>
     */
    public function summarize(bool $withProfiles = false): array
    {
        $demoListingIds = $this->demoListingIds();
        $demoProfileIds = $withProfiles ? $this->demoProfileIds($demoListingIds) : collect();
        $demoUserIds = $withProfiles ? $this->demoUserIds($demoProfileIds) : collect();
        $conversationIds = $demoListingIds->isEmpty()
            ? collect()
            : DB::table('quote_conversations')->whereIn('mariachi_listing_id', $demoListingIds)->pluck('id');
        $reviewIds = $demoListingIds->isEmpty() && $demoProfileIds->isEmpty()
            ? collect()
            : DB::table('mariachi_reviews')
                ->when($demoListingIds->isNotEmpty(), fn ($query) => $query->whereIn('mariachi_listing_id', $demoListingIds))
                ->when(
                    $demoProfileIds->isNotEmpty(),
                    fn ($query) => $query->orWhereIn('mariachi_profile_id', $demoProfileIds)
                )
                ->pluck('id');
        $verificationPaymentIds = $withProfiles
            ? DB::table('profile_verification_payments')
                ->whereIn('mariachi_profile_id', $demoProfileIds)
                ->orWhere('proof_path', 'like', 'demo/%')
                ->orWhere('reference_text', 'like', 'DEMO-%')
                ->pluck('id')
            : collect();

        $filePaths = $this->demoFilePaths($demoListingIds, $demoProfileIds, $verificationPaymentIds);

        return [
            'listing_ids' => $demoListingIds->values()->all(),
            'profile_ids' => $demoProfileIds->values()->all(),
            'user_ids' => $demoUserIds->values()->all(),
            'counts' => [
                'mariachi_listings' => $demoListingIds->count(),
                'mariachi_profiles' => $demoProfileIds->count(),
                'users_candidates' => $demoUserIds->count(),
                'mariachi_listing_photos' => $this->countTable('mariachi_listing_photos', 'mariachi_listing_id', $demoListingIds),
                'mariachi_listing_videos' => $this->countTable('mariachi_listing_videos', 'mariachi_listing_id', $demoListingIds),
                'mariachi_listing_faqs' => $this->countTable('mariachi_listing_faqs', 'mariachi_listing_id', $demoListingIds),
                'mariachi_listing_service_areas' => $this->countTable('mariachi_listing_service_areas', 'mariachi_listing_id', $demoListingIds),
                'event_type_mariachi_listing' => $this->countTable('event_type_mariachi_listing', 'mariachi_listing_id', $demoListingIds),
                'mariachi_listing_service_type' => $this->countTable('mariachi_listing_service_type', 'mariachi_listing_id', $demoListingIds),
                'group_size_option_mariachi_listing' => $this->countTable('group_size_option_mariachi_listing', 'mariachi_listing_id', $demoListingIds),
                'budget_range_mariachi_listing' => $this->countTable('budget_range_mariachi_listing', 'mariachi_listing_id', $demoListingIds),
                'listing_payments' => $this->countDemoListingPayments($demoListingIds),
                'listing_info_requests' => $this->countTable('listing_info_requests', 'mariachi_listing_id', $demoListingIds),
                'client_favorites' => $this->countTable('client_favorites', 'mariachi_listing_id', $demoListingIds),
                'client_recent_views' => $this->countTable('client_recent_views', 'mariachi_listing_id', $demoListingIds),
                'quote_conversations' => $conversationIds->count(),
                'quote_messages' => $this->countTable('quote_messages', 'quote_conversation_id', $conversationIds),
                'mariachi_reviews' => $reviewIds->count(),
                'mariachi_review_photos' => $this->countTable('mariachi_review_photos', 'mariachi_review_id', $reviewIds),
                'media_hashes' => $this->countTable('media_hashes', 'mariachi_listing_id', $demoListingIds),
                'ad_promotions' => $this->countTable('ad_promotions', 'mariachi_listing_id', $demoListingIds),
                'subscription_cities' => $this->countTable('subscription_cities', 'mariachi_listing_id', $demoListingIds),
                'profile_verification_payments' => $verificationPaymentIds->count(),
                'verification_requests' => $this->countVerificationRequests($demoProfileIds, $verificationPaymentIds),
                'mariachi_photos' => $this->countTable('mariachi_photos', 'mariachi_profile_id', $demoProfileIds),
                'mariachi_videos' => $this->countTable('mariachi_videos', 'mariachi_profile_id', $demoProfileIds),
                'mariachi_service_areas' => $this->countTable('mariachi_service_areas', 'mariachi_profile_id', $demoProfileIds),
                'mariachi_profile_service_type' => $this->countTable('mariachi_profile_service_type', 'mariachi_profile_id', $demoProfileIds),
                'event_type_mariachi_profile' => $this->countTable('event_type_mariachi_profile', 'mariachi_profile_id', $demoProfileIds),
                'budget_range_mariachi_profile' => $this->countTable('budget_range_mariachi_profile', 'mariachi_profile_id', $demoProfileIds),
                'group_size_option_mariachi_profile' => $this->countTable('group_size_option_mariachi_profile', 'mariachi_profile_id', $demoProfileIds),
                'mariachi_entitlement_overrides' => $this->countTable('mariachi_entitlement_overrides', 'mariachi_profile_id', $demoProfileIds),
                'mariachi_profile_stats' => $this->countTable('mariachi_profile_stats', 'mariachi_profile_id', $demoProfileIds),
                'mariachi_profile_handle_aliases' => $this->countTable('mariachi_profile_handle_aliases', 'mariachi_profile_id', $demoProfileIds),
                'subscriptions' => $this->countTable('subscriptions', 'mariachi_profile_id', $demoProfileIds),
                'demo_public_file_paths' => $filePaths->count(),
                'demo_public_storage_root_exists' => File::isDirectory(storage_path('app/public/demo')) ? 1 : 0,
            ],
            'file_paths' => $filePaths->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function purge(bool $deleteFiles = false, bool $withProfiles = false): array
    {
        $summary = $this->summarize($withProfiles);
        $listingIds = collect($summary['listing_ids']);
        $profileIds = collect($summary['profile_ids']);
        $filePaths = collect($summary['file_paths']);

        if ($listingIds->isEmpty() && $profileIds->isEmpty()) {
            return $summary + ['deleted' => [], 'deleted_files' => 0];
        }

        $deleted = [];

        DB::transaction(function () use ($listingIds, $profileIds, &$deleted): void {
            $conversationIds = $listingIds->isEmpty()
                ? collect()
                : DB::table('quote_conversations')->whereIn('mariachi_listing_id', $listingIds)->pluck('id');
            $reviewIds = DB::table('mariachi_reviews')
                ->when($listingIds->isNotEmpty(), fn ($query) => $query->whereIn('mariachi_listing_id', $listingIds))
                ->when($profileIds->isNotEmpty(), fn ($query) => $query->orWhereIn('mariachi_profile_id', $profileIds))
                ->pluck('id');
            $verificationPaymentIds = $profileIds->isEmpty()
                ? collect()
                : DB::table('profile_verification_payments')->whereIn('mariachi_profile_id', $profileIds)->pluck('id');

            if ($profileIds->isNotEmpty()) {
                DB::table('mariachi_profiles')
                    ->whereIn('id', $profileIds)
                    ->update(['default_mariachi_listing_id' => null]);
            }

            if ($listingIds->isNotEmpty()) {
                DB::table('mariachi_profiles')
                    ->whereIn('default_mariachi_listing_id', $listingIds)
                    ->update(['default_mariachi_listing_id' => null]);
            }

            $deleted['mariachi_review_photos'] = $this->deleteWhereIn('mariachi_review_photos', 'mariachi_review_id', $reviewIds);
            $deleted['quote_messages'] = $this->deleteWhereIn('quote_messages', 'quote_conversation_id', $conversationIds);
            $deleted['client_favorites'] = $this->deleteWhereIn('client_favorites', 'mariachi_listing_id', $listingIds);
            $deleted['client_recent_views'] = $this->deleteWhereIn('client_recent_views', 'mariachi_listing_id', $listingIds);
            $deleted['listing_info_requests'] = $this->deleteWhereIn('listing_info_requests', 'mariachi_listing_id', $listingIds);
            $deleted['media_hashes'] = $this->deleteWhereIn('media_hashes', 'mariachi_listing_id', $listingIds);
            $deleted['ad_promotions'] = $this->deleteWhereIn('ad_promotions', 'mariachi_listing_id', $listingIds);
            $deleted['subscription_cities'] = $this->deleteWhereIn('subscription_cities', 'mariachi_listing_id', $listingIds);
            $deleted['event_type_mariachi_listing'] = $this->deleteWhereIn('event_type_mariachi_listing', 'mariachi_listing_id', $listingIds);
            $deleted['mariachi_listing_service_type'] = $this->deleteWhereIn('mariachi_listing_service_type', 'mariachi_listing_id', $listingIds);
            $deleted['group_size_option_mariachi_listing'] = $this->deleteWhereIn('group_size_option_mariachi_listing', 'mariachi_listing_id', $listingIds);
            $deleted['budget_range_mariachi_listing'] = $this->deleteWhereIn('budget_range_mariachi_listing', 'mariachi_listing_id', $listingIds);
            $deleted['mariachi_listing_service_areas'] = $this->deleteWhereIn('mariachi_listing_service_areas', 'mariachi_listing_id', $listingIds);
            $deleted['mariachi_listing_faqs'] = $this->deleteWhereIn('mariachi_listing_faqs', 'mariachi_listing_id', $listingIds);
            $deleted['mariachi_listing_videos'] = $this->deleteWhereIn('mariachi_listing_videos', 'mariachi_listing_id', $listingIds);
            $deleted['mariachi_listing_photos'] = $this->deleteWhereIn('mariachi_listing_photos', 'mariachi_listing_id', $listingIds);
            $deleted['mariachi_reviews'] = $this->deleteWhereIn('mariachi_reviews', 'id', $reviewIds);
            $deleted['quote_conversations'] = $this->deleteWhereIn('quote_conversations', 'id', $conversationIds);

            $deleted['listing_payments'] = $listingIds->isEmpty()
                ? 0
                : DB::table('listing_payments')
                    ->whereIn('mariachi_listing_id', $listingIds)
                    ->orWhere('proof_path', 'like', 'demo/%')
                    ->orWhere('reference_text', 'like', 'DEMO-%')
                    ->orWhere('checkout_reference', 'like', 'DEMO-%')
                    ->delete();

            $deleted['mariachi_listings'] = $this->deleteWhereIn('mariachi_listings', 'id', $listingIds);

            if ($profileIds->isNotEmpty()) {
                $deleted['verification_requests'] = DB::table('verification_requests')
                    ->whereIn('mariachi_profile_id', $profileIds)
                    ->orWhereIn('profile_verification_payment_id', $verificationPaymentIds)
                    ->delete();
                $deleted['profile_verification_payments'] = DB::table('profile_verification_payments')
                    ->whereIn('mariachi_profile_id', $profileIds)
                    ->orWhere('proof_path', 'like', 'demo/%')
                    ->orWhere('reference_text', 'like', 'DEMO-%')
                    ->delete();
                $deleted['mariachi_photos'] = $this->deleteWhereIn('mariachi_photos', 'mariachi_profile_id', $profileIds);
                $deleted['mariachi_videos'] = $this->deleteWhereIn('mariachi_videos', 'mariachi_profile_id', $profileIds);
                $deleted['mariachi_service_areas'] = $this->deleteWhereIn('mariachi_service_areas', 'mariachi_profile_id', $profileIds);
                $deleted['mariachi_profile_service_type'] = $this->deleteWhereIn('mariachi_profile_service_type', 'mariachi_profile_id', $profileIds);
                $deleted['event_type_mariachi_profile'] = $this->deleteWhereIn('event_type_mariachi_profile', 'mariachi_profile_id', $profileIds);
                $deleted['budget_range_mariachi_profile'] = $this->deleteWhereIn('budget_range_mariachi_profile', 'mariachi_profile_id', $profileIds);
                $deleted['group_size_option_mariachi_profile'] = $this->deleteWhereIn('group_size_option_mariachi_profile', 'mariachi_profile_id', $profileIds);
                $deleted['mariachi_entitlement_overrides'] = $this->deleteWhereIn('mariachi_entitlement_overrides', 'mariachi_profile_id', $profileIds);
                $deleted['mariachi_profile_stats'] = $this->deleteWhereIn('mariachi_profile_stats', 'mariachi_profile_id', $profileIds);
                $deleted['mariachi_profile_handle_aliases'] = $this->deleteWhereIn('mariachi_profile_handle_aliases', 'mariachi_profile_id', $profileIds);
                $deleted['subscriptions'] = $this->deleteWhereIn('subscriptions', 'mariachi_profile_id', $profileIds);
                $deleted['mariachi_profiles'] = $this->deleteWhereIn('mariachi_profiles', 'id', $profileIds);
            }
        });

        $deletedFiles = 0;

        if ($deleteFiles) {
            foreach ($filePaths as $path) {
                if (Storage::disk('public')->exists($path) && Storage::disk('public')->delete($path)) {
                    $deletedFiles++;
                }
            }

            if (Storage::disk('public')->exists('demo')) {
                Storage::disk('public')->deleteDirectory('demo');
            }
        }

        return $summary + [
            'deleted' => $deleted,
            'deleted_files' => $deletedFiles,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function demoListingIds(): Collection
    {
        return DB::table('mariachi_listings')
            ->where('slug', 'like', '%-demo')
            ->orWhereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('mariachi_listing_photos')
                    ->whereColumn('mariachi_listing_photos.mariachi_listing_id', 'mariachi_listings.id')
                    ->where('mariachi_listing_photos.path', 'like', 'demo/%');
            })
            ->orWhereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('listing_payments')
                    ->whereColumn('listing_payments.mariachi_listing_id', 'mariachi_listings.id')
                    ->where(function ($inner): void {
                        $inner->where('listing_payments.proof_path', 'like', 'demo/%')
                            ->orWhere('listing_payments.reference_text', 'like', 'DEMO-%')
                            ->orWhere('listing_payments.checkout_reference', 'like', 'DEMO-%');
                    });
            })
            ->pluck('id')
            ->unique()
            ->values();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $demoListingIds
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function demoProfileIds(Collection $demoListingIds): Collection
    {
        if ($demoListingIds->isEmpty()) {
            return collect();
        }

        return DB::table('mariachi_profiles as profiles')
            ->whereIn('profiles.id', function ($query) use ($demoListingIds): void {
                $query->select('mariachi_profile_id')
                    ->from('mariachi_listings')
                    ->whereIn('id', $demoListingIds);
            })
            ->whereNotExists(function ($query) use ($demoListingIds): void {
                $query->selectRaw('1')
                    ->from('mariachi_listings as listings')
                    ->whereColumn('listings.mariachi_profile_id', 'profiles.id')
                    ->whereNotIn('listings.id', $demoListingIds);
            })
            ->pluck('profiles.id')
            ->unique()
            ->values();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $demoProfileIds
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function demoUserIds(Collection $demoProfileIds): Collection
    {
        if ($demoProfileIds->isEmpty()) {
            return collect();
        }

        return DB::table('mariachi_profiles')
            ->whereIn('id', $demoProfileIds)
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $demoListingIds
     * @param  \Illuminate\Support\Collection<int, int>  $demoProfileIds
     * @param  \Illuminate\Support\Collection<int, int>  $verificationPaymentIds
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function demoFilePaths(Collection $demoListingIds, Collection $demoProfileIds, Collection $verificationPaymentIds): Collection
    {
        $paths = collect();

        if ($demoListingIds->isNotEmpty()) {
            $paths = $paths
                ->merge(DB::table('mariachi_listing_photos')->whereIn('mariachi_listing_id', $demoListingIds)->pluck('path'))
                ->merge(DB::table('listing_payments')->whereIn('mariachi_listing_id', $demoListingIds)->pluck('proof_path'));
        }

        if ($demoProfileIds->isNotEmpty()) {
            $paths = $paths
                ->merge(DB::table('mariachi_photos')->whereIn('mariachi_profile_id', $demoProfileIds)->pluck('path'))
                ->merge(DB::table('profile_verification_payments')->whereIn('mariachi_profile_id', $demoProfileIds)->pluck('proof_path'))
                ->merge(DB::table('verification_requests')->whereIn('mariachi_profile_id', $demoProfileIds)->pluck('identity_proof_path'));
        }

        if ($verificationPaymentIds->isNotEmpty()) {
            $paths = $paths->merge(
                DB::table('verification_requests')
                    ->whereIn('profile_verification_payment_id', $verificationPaymentIds)
                    ->pluck('identity_proof_path')
            );
        }

        return $paths
            ->filter(fn (?string $path): bool => is_string($path) && str_starts_with($path, 'demo/'))
            ->unique()
            ->values();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $ids
     */
    private function countTable(string $table, string $column, Collection $ids): int
    {
        if ($ids->isEmpty()) {
            return 0;
        }

        return (int) DB::table($table)->whereIn($column, $ids)->count();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $demoListingIds
     */
    private function countDemoListingPayments(Collection $demoListingIds): int
    {
        if ($demoListingIds->isEmpty()) {
            return 0;
        }

        return (int) DB::table('listing_payments')
            ->whereIn('mariachi_listing_id', $demoListingIds)
            ->orWhere('proof_path', 'like', 'demo/%')
            ->orWhere('reference_text', 'like', 'DEMO-%')
            ->orWhere('checkout_reference', 'like', 'DEMO-%')
            ->count();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $demoProfileIds
     * @param  \Illuminate\Support\Collection<int, int>  $verificationPaymentIds
     */
    private function countVerificationRequests(Collection $demoProfileIds, Collection $verificationPaymentIds): int
    {
        if ($demoProfileIds->isEmpty() && $verificationPaymentIds->isEmpty()) {
            return 0;
        }

        return (int) DB::table('verification_requests')
            ->when($demoProfileIds->isNotEmpty(), fn ($query) => $query->whereIn('mariachi_profile_id', $demoProfileIds))
            ->when($verificationPaymentIds->isNotEmpty(), fn ($query) => $query->orWhereIn('profile_verification_payment_id', $verificationPaymentIds))
            ->count();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $ids
     */
    private function deleteWhereIn(string $table, string $column, Collection $ids): int
    {
        if ($ids->isEmpty()) {
            return 0;
        }

        return DB::table($table)->whereIn($column, $ids)->delete();
    }
}
