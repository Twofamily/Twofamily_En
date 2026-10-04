{{--
    แสดง 1 ช่องตั้งค่า ตาม type ในทะเบียน config/settings.php
    ใช้: @include('settings._field', ['key' => 'doc.vat_rate'])

    ชื่อ input ต้องเป็นแบบ array: doc.vat_rate → doc[vat_rate]
    ห้ามใช้ name="doc.vat_rate" เพราะ PHP จะแปลงจุดเป็นขีดล่างให้เอง
--}}
@php
    $def   = $definitions[$key];
    $parts = explode('.', $key);
    $name  = array_shift($parts) . collect($parts)->map(fn ($p) => "[{$p}]")->implode('');
    $id    = 'f_' . str_replace('.', '_', $key);
    $value = old($key, $values[$key] ?? $def['default']);
    $error = $errors->first($key);
    $hint  = $hint ?? null;
@endphp

@if ($def['type'] === 'bool')
    {{-- hidden ส่ง 0 ไว้ก่อน ถ้าติ๊ก checkbox จะส่ง 1 ทับ (checkbox ที่ไม่ติ๊กจะไม่ถูกส่งมาเลย) --}}
    <input type="hidden" name="{{ $name }}" value="0">
    <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" role="switch" id="{{ $id }}" name="{{ $name }}"
            value="1" @checked($value)>
        <label class="form-check-label" for="{{ $id }}">{{ $def['label'] }}</label>
    </div>
@else
    <label for="{{ $id }}" class="form-label">{{ $def['label'] }}</label>

    @if ($def['type'] === 'text')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="3"
            class="form-control {{ $error ? 'is-invalid' : '' }}">{{ $value }}</textarea>

    @elseif ($def['type'] === 'select')
        <select id="{{ $id }}" name="{{ $name }}" class="form-select {{ $error ? 'is-invalid' : '' }}">
            @foreach ($def['options'] as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>
                    {{ $optionLabel }}
                </option>
            @endforeach
        </select>

    @elseif ($def['type'] === 'image')
        @if ($value)
            <div class="d-flex align-items-center gap-3 mb-2">
                <img src="{{ asset('storage/' . $value) }}" alt="{{ $def['label'] }}"
                    class="border rounded p-1 bg-white" style="max-height: 80px;">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="{{ $id }}_remove"
                        name="remove_image[{{ $key }}]" value="1">
                    <label class="form-check-label text-danger" for="{{ $id }}_remove">ลบรูปนี้</label>
                </div>
            </div>
        @endif
        <input type="file" id="{{ $id }}" name="{{ $name }}" accept="image/png,image/jpeg"
            class="form-control {{ $error ? 'is-invalid' : '' }}">
        <div class="form-text">PNG หรือ JPG ไม่เกิน 2 MB</div>

    @else
        <input id="{{ $id }}" name="{{ $name }}"
            type="{{ in_array($def['type'], ['int', 'decimal'], true) ? 'number' : 'text' }}"
            @if ($def['type'] === 'decimal') step="0.01" @endif
            value="{{ $value }}" class="form-control {{ $error ? 'is-invalid' : '' }}">
    @endif

    @if ($error)
        <div class="invalid-feedback">{{ $error }}</div>
    @endif
@endif

@if ($hint)
    <div class="form-text">{{ $hint }}</div>
@endif