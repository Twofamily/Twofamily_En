{{--
    หน้าต่าง "ดู" ของรุ่นรถ — ใช้ตัวเดียวทั้งหน้า
    เมนู "ดู" ในแต่ละแถวส่งข้อมูลของรุ่นนั้นมาทาง data-model (JSON)
    แล้วสคริปต์ด้านล่างเติมลงหน้าต่างตอนเปิด

    ใช้คู่กับ:  @include('truck_models._view-item', ['m' => $m, 'canEdit' => ...])  ในเมนูของแต่ละแถว
--}}
<div class="modal fade" id="modelDetailModal" tabindex="-1" aria-labelledby="modelDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="modelDetailTitle">รุ่นรถ</h5>
                    <span class="badge mt-1" data-field="status"></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>

            <div class="modal-body">
                <h6 class="text-muted small fw-semibold mb-2">สเปครุ่น</h6>
                <dl class="row g-0 mb-4 model-specs" data-field="specs"></dl>

                <h6 class="text-muted small fw-semibold mb-2">
                    รถที่ใช้รุ่นนี้ <span data-field="truck-count"></span>
                </h6>
                <div class="d-flex flex-wrap gap-2" data-field="trucks"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ปิด</button>
                <a href="#" class="btn btn-dark d-none" data-field="edit">
                    <i class="bi bi-pencil-square me-1"></i>แก้ไขรุ่นนี้
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    #modelDetailModal .model-specs > div {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: .55rem .25rem;
        border-bottom: 1px solid rgba(0, 0, 0, .06);
    }

    #modelDetailModal .model-specs dt { font-weight: 400; color: #737373; }
    #modelDetailModal .model-specs dd { margin: 0; font-weight: 600; text-align: end; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('modelDetailModal');
        if (!modalEl) return;

        const field = (name) => modalEl.querySelector('[data-field="' + name + '"]');

        // ใช้ textContent ทั้งหมด (ไม่ใช้ innerHTML กับค่าจากฐานข้อมูล)
        modalEl.addEventListener('show.bs.modal', function (e) {
            const trigger = e.relatedTarget;
            if (!trigger || !trigger.dataset.model) return;

            const data = JSON.parse(trigger.dataset.model);

            document.getElementById('modelDetailTitle').textContent = data.title;

            const status = field('status');
            status.textContent = data.active ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
            status.className = 'badge mt-1 ' + (data.active ? 'text-bg-success' : 'text-bg-secondary');

            // สเปค: แบ่ง 2 คอลัมน์บนจอใหญ่
            const specs = field('specs');
            specs.replaceChildren();
            Object.entries(data.specs).forEach(([label, value]) => {
                const row = document.createElement('div');
                row.className = 'col-12 col-md-6 px-md-2';
                const dt = document.createElement('dt');
                dt.textContent = label;
                const dd = document.createElement('dd');
                dd.textContent = value;
                row.append(dt, dd);
                specs.append(row);
            });

            // รถที่ใช้รุ่นนี้: กดแล้วไปหน้ารายละเอียดรถ
            const trucks = field('trucks');
            trucks.replaceChildren();
            field('truck-count').textContent = '(' + data.trucks.length + ' คัน)';

            if (data.trucks.length === 0) {
                const none = document.createElement('span');
                none.className = 'text-muted small';
                none.textContent = 'ยังไม่มีรถใช้รุ่นนี้';
                trucks.append(none);
            }

            data.trucks.forEach((t) => {
                const a = document.createElement('a');
                a.href = t.url;
                a.className = 'btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2';
                const plate = document.createElement('span');
                plate.className = 'fw-semibold';
                plate.textContent = t.id;
                const badge = document.createElement('span');
                badge.className = 'badge bg-' + t.color;
                badge.textContent = t.status;
                a.append(plate, badge);
                trucks.append(a);
            });

            const edit = field('edit');
            edit.classList.toggle('d-none', !data.edit);
            edit.href = data.edit || '#';
        });
    });
</script>
