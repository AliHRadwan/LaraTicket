<x-mail::message>
# Order Cancelled

Hello {{ $order->user->name }},

We're writing to let you know that your order has been cancelled.

<x-mail::table>
| Detail | Info |
|:-------|:-----|
| Order ID | #{{ $order->id }} |
| Event | {{ $order->event->title }} |
| Tickets | {{ $order->tickets_count }} |
| Amount | {{ number_format($order->total_price, 2) }} EGP |
</x-mail::table>

If you did not request this cancellation or believe this is a mistake, please contact our support team immediately.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
