<?php

namespace App\Actions;

use App\Enums\CustomerSegment;
use App\Mail\CampaignMail;
use App\Models\CampaignModel;
use App\Models\CampaignRecipientModel;
use App\Models\UserModel;
use App\Services\OneUseCoupons;
use App\Services\SampleData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Gives every customer in a segment their own coupon and queues the email.
 */
class SendCampaignAction
{
    private const TOKEN_LENGTH = 40;

    private const CODE_PREFIX = 'UUDAI';

    private const USER_ID = 'user_id';

    // Nobody gets two campaigns in a week, which also makes a double-clicked
    // send harmless: the second click finds nobody left to write to.
    public const QUIET_DAYS = 7;

    public function __construct(private readonly OneUseCoupons $coupons) {}

    /**
     * The ids only narrow the segment down: what the form sends can never add
     * someone from outside it, or someone who has opted out.
     *
     * @param  array{name: string, percent: int, min_order: int, max_discount: ?int, days: int, subject: string, message: string}  $terms
     * @param  list<int>  $recipientIds
     * @return int how many customers it went to
     */
    public function execute(CustomerSegment $segment, array $terms, array $recipientIds, int $adminId): int
    {
        $customers = $this->reachable($segment)->whereIn(self::USER_ID, $recipientIds);

        if ($customers->isEmpty()) {
            return 0;
        }

        $campaign = CampaignModel::create($terms + ['segment' => $segment->value, 'sent_by' => $adminId]);

        $delivered = $customers->filter(fn (UserModel $customer) => $this->deliver($campaign, $segment, $customer, $terms))->count();

        if ($delivered === 0) {
            $campaign->delete();
        }

        return $delivered;
    }

    /**
     * @return Collection<int, UserModel>
     */
    public function reachable(CustomerSegment $segment): Collection
    {
        return $this->reachableQuery($segment)->get();
    }

    /**
     * @return Builder<UserModel>
     */
    private function reachableQuery(CustomerSegment $segment): Builder
    {
        $query = UserModel::query()
            ->whereNotNull('email')
            ->whereIn(self::USER_ID, DB::table('customer_segments')->where('segment', $segment->value)->select(self::USER_ID))
            ->whereNotIn(self::USER_ID, DB::table('campaign_opt_outs')->select(self::USER_ID))
            ->whereNotIn(self::USER_ID, DB::table('cart_reminder_opt_outs')->select(self::USER_ID))
            ->whereNotIn(self::USER_ID, DB::table('campaign_recipients')->where('sent_at', '>', now()->subDays(self::QUIET_DAYS))->select(self::USER_ID));

        if ($segment->isOfferedOnce()) {
            $query->whereNotIn(self::USER_ID, DB::table('campaign_recipients')
                ->join('campaigns', 'campaigns.id', '=', 'campaign_recipients.campaign_id')
                ->where('campaigns.segment', $segment->value)
                ->select('campaign_recipients.user_id'));
        }

        return $query;
    }

    /**
     * @param  array{percent: int, min_order: int, max_discount: ?int, days: int}  $terms
     * @return bool false when another send reached this customer first
     */
    private function deliver(CampaignModel $campaign, CustomerSegment $segment, UserModel $customer, array $terms): bool
    {
        $recipient = DB::transaction(function () use ($campaign, $segment, $customer, $terms) {
            // Two sends can read the same list, so each customer is locked and
            // checked again before anything is written for them.
            UserModel::query()->whereKey($customer->user_id)->lockForUpdate()->first();

            if (! $this->reachableQuery($segment)->whereKey($customer->user_id)->exists()) {
                return null;
            }

            return CampaignRecipientModel::create([
                'campaign_id' => $campaign->id,
                self::USER_ID => $customer->user_id,
                'coupon_id' => $this->coupons->create('Chiến dịch #'.$campaign->id.' khách #'.$customer->user_id, self::CODE_PREFIX, [
                    'percent' => $terms['percent'],
                    'days' => $terms['days'],
                    'min_order' => $terms['min_order'],
                    'max_discount' => $terms['max_discount'],
                ], $customer->user_id)->coupon_id,
                'token' => Str::random(self::TOKEN_LENGTH),
                'sent_at' => now(),
            ]);
        });

        if ($recipient === null) {
            return false;
        }

        // A sample account's domain cannot receive mail: handing it to a real
        // SMTP server only earns the shop's address a bounce.
        if (! str_ends_with((string) $customer->email, SampleData::EMAIL_DOMAIN)) {
            Mail::to($customer->email)->queue(new CampaignMail($recipient));
        }

        return true;
    }
}
