{{--
    กรอบลายเซ็นที่ใช้ร่วมกันทุกเอกสาร (แทนแบบเส้นใต้เดิม)

    วิธีเรียกใช้:
    @include('pdf.partials.signatures', ['slots' => [
        ['party' => 'customer', 'label' => 'ผู้รับของ'],
        ['party' => 'company',  'label' => 'ผู้จัดทำ', 'user' => $doc->issuer, 'date' => $doc->issued_at],
    ]])

    แต่ละช่องรับค่า:
      label (บังคับ)  บทบาทของคนเซ็น เช่น "ผู้จัดทำ"
      party           'customer' หัวกรอบเป็น "ในนามลูกค้า"
                      'company'  หัวกรอบเป็น "ในนาม <ชื่อบริษัท>" (ค่าเริ่มต้น)
      user            อ็อบเจกต์ User ถ้ามีลายเซ็นจะแปะรูปให้
      name            ระบุชื่อเอง กรณีไม่ได้มาจากตาราง users
      position        ระบุตำแหน่งเอง
      date            วันที่เซ็น ถ้าไม่ส่งจะเป็นช่องว่างให้กรอกมือ

    ใช้ inline style ทั้งหมด จึงไม่ต้องพึ่ง CSS ของเอกสารแต่ละใบ
--}}

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

                // ชื่อกับตำแหน่ง ใช้ค่าที่ส่งมาตรง ๆ ก่อน ถ้าไม่มีค่อยเอาจาก user
                $name     = $slot['name']     ?? ($user->name     ?? null);
                $position = $slot['position'] ?? ($user->position ?? null);

                $head = ($slot['party'] ?? 'company') === 'customer'
                    ? 'ในนามลูกค้า'
                    : 'ในนาม ' . $company;

                // dompdf อ่าน URL ไม่ได้ ต้องใช้ path ในเครื่องเท่านั้น
                // เช็ค GD และ file_exists ก่อน เพื่อให้ PDF ยังออกได้แม้ GD มีปัญหาหรือไฟล์รูปหาย
                $signature = null;
                if ($user && $user->signature_path && extension_loaded('gd')) {
                    $full = public_path($user->signature_path);
                    if (file_exists($full)) {
                        $signature = $full;
                    }
                }
            @endphp

            @if ($k > 0)
                <td style="width: {{ $gap }}%; border: none;"></td>
            @endif

            <td style="width: {{ $width }}%; border: 1px solid #bfb3a8; vertical-align: top; padding: 0;">
                <div style="background-color: #efe9e3; border-bottom: 1px solid #bfb3a8; text-align: center; font-weight: bold; padding: 3px 4px;">
                    {{ $head }}
                </div>

                <div style="text-align: center; padding: 4px 8px 6px 8px; line-height: 1.5;">
                    {{-- กล่องความสูงคงที่ ให้ทุกกรอบเรียงเสมอกันไม่ว่าจะมีรูปหรือไม่ --}}
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
