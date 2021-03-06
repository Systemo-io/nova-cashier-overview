<?php

namespace LimeDeck\NovaCashierOverview\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Subscription;
use Laravel\Cashier\Exceptions\PaymentFailure;
use Stripe\Plan;
use Stripe\Subscription as StripeSubscription;

class StripeSubscriptionsController extends Controller
{
    /**
     * @param $subscriptionId
     * @return array
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function show($subscriptionId)
    {
        /** @var \Laravel\Cashier\Subscription $subscription */
        $subscription = Subscription::find($subscriptionId);

        try {
            $planQuantity = $subscription->owner->getPlanQuantity();
        } catch (\Throwable $e) {
            // skip
        }

        if (! $subscription) {
            return [
                'subscription' => null,
            ];
        }

        return [
            'subscription' => $this->formatSubscription($subscription, $planQuantity ?? null),
            'plans' => $this->formatPlans(Plan::all(['limit' => 100]), $planQuantity ?? null),
            'invoices' => $this->formatInvoices($subscription->owner->invoicesIncludingPending()),
        ];
    }

    /**
     * @param $subscriptionId
     * @return \Illuminate\Http\JsonResponse
     * @throws \Laravel\Cashier\Exceptions\SubscriptionUpdateFailure
     */
    public function update($subscriptionId)
    {
        /** @var \Laravel\Cashier\Subscription $subscription */
        $subscription = Subscription::findOrFail($subscriptionId);

        try {
            $oldPlan = $subscription->stripe_plan;
            $subscription->swap($this->request->input('plan'));
        } catch (PaymentFailure $e) {
            $subscription->swap($oldPlan);

            throw new \Exception('Card could not be charged, reverting to old plan');
        }

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    /**
     * @param $subscriptionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel($subscriptionId)
    {
        /** @var \Laravel\Cashier\Subscription $subscription */
        $subscription = Subscription::findOrFail($subscriptionId);

        if ($this->request->input('now')) {
            $subscription->cancelNow();
        } else {
            $subscription->cancel();
        }

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    /**
     * @param $subscriptionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function resume($subscriptionId)
    {
        /** @var \Laravel\Cashier\Subscription $subscription */
        $subscription = Subscription::findOrFail($subscriptionId);

        $subscription->resume();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    /**
     * @param \Laravel\Cashier\Subscription $subscription
     * @return array
     * @throws \Stripe\Exception\ApiErrorException
     */
    protected function formatSubscription(Subscription $subscription, ?int $planQuantity = null)
    {
        $stripeSubscription = StripeSubscription::retrieve($subscription->stripe_id);

        if ($stripeSubscription->plan->billing_scheme === 'tiered' && $planQuantity) {
            $planAmount = $this->getTierPrice($stripeSubscription->plan, $planQuantity);
        }

        return array_merge($subscription->toArray(), [
            'plan_amount' => $planAmount ?? $stripeSubscription->plan->amount,
            'plan_interval' => $stripeSubscription->plan->interval,
            'plan_currency' => $stripeSubscription->plan->currency,
            'plan' => $subscription->stripe_plan,
            'stripe_plan' => $stripeSubscription->plan->id,
            'ended' => $subscription->ended(),
            'cancelled' => $subscription->cancelled(),
            'active' => $subscription->active(),
            'on_trial' => $subscription->onTrial(),
            'on_grace_period' => $subscription->onGracePeriod(),
            'charges_automatically' => $stripeSubscription->billing == 'charge_automatically',
            'created_at' => $this->formatDate($stripeSubscription->billing_cycle_anchor),
            'ended_at' => $this->formatDate($stripeSubscription->ended_at),
            'current_period_start' => $this->formatDate($stripeSubscription->current_period_start),
            'current_period_end' => $this->formatDate($stripeSubscription->current_period_end),
            'days_until_due' => $stripeSubscription->days_until_due,
            'cancel_at_period_end' => $stripeSubscription->cancel_at_period_end,
            'canceled_at' => $stripeSubscription->canceled_at,
        ]);
    }

    /**
     * Format the plans collection.
     *
     * @param \Stripe\Collection $plans
     * @param int|null           $planQuantity
     * @return array
     */
    protected function formatPlans($plans, ?int $planQuantity = null)
    {
        return collect($plans->data)->map(function (Plan $plan) use ($planQuantity) {
            if ($plan->billing_scheme === 'tiered' && $planQuantity) {
                $price = $this->getTierPrice($plan, $planQuantity);
            }

            return [
                'id' => $plan->id,
                'price' => $price ?? $plan->amount,
                'interval' => $plan->interval,
                'currency' => $plan->currency,
                'interval_count' => $plan->interval_count,
            ];
        })->toArray();
    }

    /**
     * @param array $tiers
     * @param int   $quantity
     * @return int
     */
    protected function getTierPrice(Plan $plan, int $quantity): int
    {
        $tiers = collect($plan->tiers)
            ->map(fn ($item) => $item->toArray())
            ->toArray();

        foreach ($tiers as $tier) {
            if (!empty($tier['up_to']) && $tier['up_to'] <= $quantity) {
                if ($tier['flat_amount']) {
                    return $tier['flat_amount'];
                }

                if ($tier['unit_price']) {
                    return $tier['unit_price'] * $quantity;
                }
            }
        }

        return 0;
    }

    /**
     * @param $invoices
     * @return array
     */
    protected function formatInvoices($invoices)
    {
        return collect($invoices)->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'total' => $invoice->total,
                'attempted' => $invoice->attempted,
                'charge_id' => $invoice->charge,
                'currency' => $invoice->currency,
                'period_start' => $this->formatDate($invoice->period_start),
                'period_end' => $this->formatDate($invoice->period_end),
                'link' => $invoice->hosted_invoice_url,
                'subscription' => $invoice->subscription,
                'status' => $invoice->status,
            ];
        })->toArray();
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    protected function formatDate($value)
    {
        return $value ? Carbon::createFromTimestamp($value)->toDateTimeString() : null;
    }
}
