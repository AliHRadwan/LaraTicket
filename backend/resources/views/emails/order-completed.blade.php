<x-mail::message>
# Order Confirmed

Hello {{ $order->user->name }},

Your payment has been received and your order is now complete.

<x-mail::table>
| Detail | Info |
|:-------|:-----|
| Order ID | #{{ $order->id }} |
| Event | {{ $order->event->title }} |
| Date | {{ $order->event->start_datetime->format('M d, Y \a\t h:i A') }} |
| Location | {{ $order->event->location }} |
| Tickets | {{ $order->tickets_count }} |
| Total Paid | {{ number_format($order->total_price, 2) }} EGP |
</x-mail::table>

Please keep this email as your receipt. Present your order ID at the event entrance.

If you have any questions, feel free to reach out to our support team.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
