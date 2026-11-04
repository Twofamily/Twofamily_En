@php
    $slots   = $slots ?? [];
    $count   = max(count($slots), 1);
    $gap     = 2;
    $width   = floor((100 - $gap * ($count - 1)) / $count);
    $dots    = str_repeat('.', $count >= 3 ? 24 : 40);
    $company = setting('company_name') ?: 'บริษัท';
@endphp

<table style="width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 11.5px;">
    <tr>
        @foreach ($slots as $k => $slot)
            @php
                $user = $slot['user'] ?? null;

                $name     = $slot['name']     ?? ($user->name     ?? null);
                $position = $slot['position'] ?? ($user->position ?? null);

                $head = ($slot['party'] ?? 'company') === 'customer'
                    ? 'ในนามลูกค้า'
                    : 'ในนาม ' . $company;

                $signature = $user?->signatureForPdf();
            @endphp

            @if ($k > 0)
                <td style="width: {{ $gap }}%; border: none;"></td>
            @endif

            <td style="width: {{ $width }}%; border: 1px solid #bfb3a8; vertical-align: top; padding: 0;">
                <div style="background-color: #efe9e3; border-bottom: 1px solid #bfb3a8; text-align: center; font-weight: bold; padding: 3px 4px;">
                    {{ $head }}
                </div>

                <div style="text-align: center; padding: 4px 8px 6px 8px; line-height: 1.5;">
                    <div style="height: 34px;">
                        @if ($signature)
                            <img src="{{ $signature }}" style="max-height: 34px; max-width: 90%;">
                        @endif
                    </div>

                    ลงชื่อ {{ $dots }} {{ $slot['label'] ?? '' }}<br>

                    ({{ $name ?: $dots }})<br>

                    @if ($position)
                        <span style="color: #666;">{{ $position }}</span><br>
                    @endif

                    วันที่
                    {{ !empty($slot['date'])
                        ? \Carbon\Carbon::parse($slot['date'])->format('d/m/Y')
                        : '......../......../........' }}
                </div>
            </td>
        @endforeach
    </tr>
</table>
