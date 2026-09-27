{{--
    Dropdown จำนวนรายการต่อหน้า — ใช้ร่วมกันทุกหน้ารายการ
    ต้องวางไว้ "ภายใน" form ค้นหา เพื่อให้ค่าค้นหา/สถานะถูกส่งไปพร้อมกัน

    ใช้:  @include('partials.per-page', ['perPage' => $perPage])
--}}
<div class="input-group">
    <span class="input-group-text bg-white">แสดง</span>

    {{-- เปลี่ยนแล้ว submit ทันที และรีเซ็ตกลับหน้า 1 (ไม่งั้นอาจค้างอยู่หน้าที่ไม่มีข้อมูล) --}}
    <select name="per_page"
        class="form-select"
        onchange="if (this.form.page) this.form.page.value = 1; this.form.submit();">
        @foreach (\App\Http\Controllers\Controller::PER_PAGE_OPTIONS as $option)
            <option value="{{ $option }}" @selected((int) $perPage === $option)>
                {{ $option }}
            </option>
        @endforeach
    </select>

    <span class="input-group-text bg-white">รายการ</span>
</div>