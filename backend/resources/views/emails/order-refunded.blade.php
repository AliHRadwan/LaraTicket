<x-mail::message>
# Order Refunded

Hello {{ $order->user->name }},

Your order has been refunded. The amount will be returned to your original payment method.

<x-mail::table>
| Detail | Info |
|:-------|:-----|
| Order ID | #{{ $order->id }} |
| Event | {{ $order->event->title }} |
| Tickets | {{ $order->tickets_count }} |
| Refund Amount | {{ number_format($order->total_price, 2) }} EGP |
</x-mail::table>

Please allow 5-10 business days for the refund to appear on your statement.

If you have any questions, feel free to reach out to our support team.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
