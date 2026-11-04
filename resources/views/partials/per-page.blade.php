<div class="input-group">
    <span class="input-group-text bg-white">แสดง</span>

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