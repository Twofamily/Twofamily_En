@once
    <script>
        window.ThaiAddress = (function () {
            const URL = @json(asset('data/thai-address.json'));
            let loading = null;
            const load = () => loading = loading || fetch(URL).then((r) => r.json()).catch(() => []);

            const districtsOf = (p) => (p && (p.districts || p.amphure || p.district)) || [];
            const subsOf      = (d) => (d && (d.sub_districts || d.tambon || d.sub_district || d.subdistricts)) || [];

            function reset(select, placeholder) {
                select.innerHTML = '';
                select.add(new Option(placeholder, ''));
            }

            function choose(select, value) {
                if (!value) { select.value = ''; return; }
                if (![...select.options].some((o) => o.value === value)) select.add(new Option(value, value));
                select.value = value;
            }

            function setup(root) {
                const prov = root.querySelector('[data-addr="province"]');
                const dist = root.querySelector('[data-addr="district"]');
                const sub  = root.querySelector('[data-addr="subdistrict"]');
                const zip  = root.querySelector('[data-addr="zipcode"]');
                let data = [];

                function fillDistricts() {
                    reset(dist, '— เลือกอำเภอ —');
                    reset(sub, '— เลือกตำบล —');
                    districtsOf(data.find((x) => x.name_th === prov.value))
                        .forEach((d) => dist.add(new Option(d.name_th, d.name_th)));
                    dist.disabled = !prov.value;
                    sub.disabled = true;
                }

                function fillSubs() {
                    reset(sub, '— เลือกตำบล —');
                    const d = districtsOf(data.find((x) => x.name_th === prov.value)).find((x) => x.name_th === dist.value);
                    subsOf(d).forEach((s) => {
                        const o = new Option(s.name_th, s.name_th);
                        o.dataset.zip = s.zip_code ?? s.zipcode ?? '';
                        sub.add(o);
                    });
                    sub.disabled = !dist.value;
                }

                prov.addEventListener('change', () => { fillDistricts(); zip.value = ''; });
                dist.addEventListener('change', () => { fillSubs(); zip.value = ''; });
                sub.addEventListener('change', () => {
                    const z = sub.selectedOptions[0]?.dataset.zip;
                    if (z) zip.value = z;
                });

                const ready = load().then((d) => {
                    data = d || [];
                    reset(prov, '— เลือกจังหวัด —');
                    data.forEach((p) => prov.add(new Option(p.name_th, p.name_th)));
                });

                function set(v) {
                    return ready.then(() => {
                        choose(prov, v.province || '');
                        fillDistricts();
                        choose(dist, v.district || '');
                        dist.disabled = !prov.value;
                        fillSubs();
                        choose(sub, v.subdistrict || '');
                        sub.disabled = !dist.value;
                        zip.value = v.zipcode || '';
                    });
                }

                set({
                    province: prov.dataset.value, district: dist.dataset.value,
                    subdistrict: sub.dataset.value, zipcode: zip.value,
                });

                return set;
            }

            return { setup };
        })();
    </script>
@endonce
