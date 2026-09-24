const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

const initializeSidebar = () => {
    const sidebar = document.querySelector('[data-sidebar]');
    const overlay = document.querySelector('[data-sidebar-overlay]');
    const content = document.querySelector('[data-sidebar-content]');
    const toggles = document.querySelectorAll('[data-sidebar-toggle]');

    if (!sidebar || !overlay) {
        return;
    }

    const isDesktop = () => window.matchMedia('(min-width: 1024px)').matches;

    const setOpen = (isOpen) => {
        sidebar.classList.remove('-translate-x-full', 'lg:-translate-x-full', 'translate-x-0', 'lg:translate-x-0');
        sidebar.classList.add(isOpen ? 'translate-x-0' : '-translate-x-full', isOpen ? 'lg:translate-x-0' : 'lg:-translate-x-full');

        overlay.classList.toggle('hidden', !isOpen || isDesktop());
        document.body.classList.toggle('overflow-hidden', isOpen && !isDesktop());
        content?.classList.toggle('lg:pl-[250px]', isOpen);
        content?.classList.toggle('lg:pl-0', !isOpen);

        toggles.forEach((toggle) => toggle.setAttribute('aria-expanded', String(isOpen)));
    };

    toggles.forEach((toggle) => toggle.addEventListener('click', () => {
        setOpen(sidebar.classList.contains('-translate-x-full'));
    }));

    overlay.addEventListener('click', () => setOpen(false));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !isDesktop()) {
            setOpen(false);
        }
    });
};

const initializeNavGroups = () => {
    document.querySelectorAll('[data-nav-group]').forEach((group) => {
        const toggle = group.querySelector('[data-nav-toggle]');
        const panel = group.querySelector('[data-nav-panel]');
        const chevron = group.querySelector('[data-nav-chevron]');

        if (!toggle || !panel) {
            return;
        }

        toggle.addEventListener('click', () => {
            const isOpen = panel.classList.contains('grid-rows-[1fr]');

            panel.classList.toggle('grid-rows-[1fr]', !isOpen);
            panel.classList.toggle('grid-rows-[0fr]', isOpen);
            chevron?.classList.toggle('rotate-180', !isOpen);
            toggle.setAttribute('aria-expanded', String(!isOpen));
        });
    });
};

const initializePasswordToggle = () => {
    document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
        const input = toggle.closest('form')?.querySelector('input[name="password"]') ?? document.querySelector('#password');
        const show = toggle.querySelector('[data-password-show]');
        const hide = toggle.querySelector('[data-password-hide]');

        if (!input || !show || !hide) {
            return;
        }

        toggle.addEventListener('click', () => {
            const isVisible = input.type === 'text';
            input.type = isVisible ? 'password' : 'text';
            show.classList.toggle('hidden', !isVisible);
            hide.classList.toggle('hidden', isVisible);
            toggle.setAttribute('aria-label', isVisible ? 'Tampilkan password' : 'Sembunyikan password');
        });
    });
};

const initializeLoginForm = () => {
    const form = document.querySelector('[data-login-form]');
    const label = form?.querySelector('[data-submit-label]');
    const loading = form?.querySelector('[data-submit-loading]');

    if (!form || !label || !loading) {
        return;
    }

    form.addEventListener('submit', () => {
        const submit = form.querySelector('button[type="submit"]');

        if (!submit) {
            return;
        }

        submit.disabled = true;
        label.classList.add('hidden');
        loading.classList.remove('hidden');
        loading.classList.add('flex');
    });
};

const initializeLoginAlert = () => {
    const message = document.querySelector('[data-login-form]')?.dataset.loginError;
    if (!message) return;
    Swal.fire({ title: 'Gagal masuk', text: message, icon: 'error' });
};

const initializeFlashAlert = () => {
    const { flashStatus, flashError } = document.body.dataset;
    if (flashStatus) Swal.fire({ title: 'Berhasil', text: flashStatus, icon: 'success' });
    if (flashError) Swal.fire({ title: 'Gagal', text: flashError, icon: 'error' });
};

const initializeDropdowns = () => {
    const buttons = document.querySelectorAll('[data-dropdown-button]');

    buttons.forEach((button) => {
        const menu = document.getElementById(button.dataset.dropdownButton);

        if (!menu) {
            return;
        }

        button.addEventListener('click', (event) => {
            event.stopPropagation();
            const willOpen = menu.classList.contains('hidden');

            document.querySelectorAll('[data-dropdown-menu]').forEach((item) => item.classList.add('hidden'));
            menu.classList.toggle('hidden', !willOpen);
        });
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('[data-dropdown-menu]').forEach((menu) => menu.classList.add('hidden'));
    });
};

const initializeNotifications = () => {
    document.addEventListener('click', (event) => {
        const link = event.target.closest('[data-notification-open]');
        if (!link) return;
        // Fire-and-forget: mark read without blocking the navigation the link already does.
        $.ajax({ url: `/notifications/${link.dataset.id}/read`, type: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }, dataType: 'json' });
    });

    document.querySelector('[data-notifications-read-all]')?.addEventListener('click', async (event) => {
        event.stopPropagation();
        try {
            await $.ajax({ url: '/notifications/read-all', type: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }, dataType: 'json' });
            window.location.reload();
        } catch {
            Swal.fire({ title: 'Gagal', text: 'Notifikasi tidak dapat ditandai dibaca.', icon: 'error' });
        }
    });
};

const initializeGlobalSearch = () => {
    const wrapper = document.querySelector('[data-global-search]');
    const input = wrapper?.querySelector('[data-global-search-input]');
    const results = wrapper?.querySelector('[data-global-search-results]');
    if (!wrapper || !input || !results) return;

    const groups = [
        { key: 'employees', label: 'Karyawan' },
        { key: 'products', label: 'Produk' },
        { key: 'reports', label: 'Laporan' },
    ];

    const hide = () => results.classList.add('hidden');

    const render = (data) => {
        const html = groups.map(({ key, label }) => {
            const items = data[key] ?? [];
            if (!items.length) return '';
            const rows = items.map((item) => `<a href="${item.url}" class="block rounded-lg px-3 py-2 hover:bg-slate-50"><span class="block truncate text-[13px] font-medium text-ink">${escapeHtml(item.title)}</span><span class="block truncate text-xs text-slate-500">${escapeHtml(item.subtitle)}</span></a>`).join('');
            return `<div class="mb-1 last:mb-0"><p class="px-3 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">${label}</p>${rows}</div>`;
        }).join('');

        results.innerHTML = html || '<p class="px-3 py-3 text-[13px] text-slate-500">Tidak ada hasil ditemukan.</p>';
        results.classList.remove('hidden');
    };

    const search = debounce(async (query) => {
        if (query.length < 2) {
            hide();
            return;
        }

        try {
            const response = await fetch(`/search?q=${encodeURIComponent(query)}`, { headers: { Accept: 'application/json' } });
            render(await response.json());
        } catch {
            hide();
        }
    }, 300);

    input.addEventListener('input', () => search(input.value.trim()));
    input.addEventListener('focus', () => { if (input.value.trim().length >= 2) results.classList.remove('hidden'); });
    input.addEventListener('keydown', (event) => { if (event.key === 'Escape') hide(); });
    document.addEventListener('click', (event) => { if (!wrapper.contains(event.target)) hide(); });
};

const initializeFilterPanel = () => {
    const panel = document.querySelector('[data-filter-panel]');
    const openButton = document.querySelector('[data-filter-toggle]');
    const closeButtons = document.querySelectorAll('[data-filter-close]');

    if (! panel || ! openButton) {
        return;
    }

    openButton.addEventListener('click', () => panel.classList.remove('translate-x-full'));
    closeButtons.forEach((button) => button.addEventListener('click', () => panel.classList.add('translate-x-full')));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            panel.classList.add('translate-x-full');
        }
    });
};

const formatUploadFileSize = (bytes) => {
    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
};

const replaceInputFile = (input, file) => {
    const transfer = new DataTransfer();
    transfer.items.add(file);
    input.files = transfer.files;
};

const compressImage = (file, maxBytes) => new Promise((resolve) => {
    const image = new Image();
    const objectUrl = URL.createObjectURL(file);
    const candidates = [
        { maxDimension: 2400, quality: 0.82 },
        { maxDimension: 2000, quality: 0.72 },
        { maxDimension: 1600, quality: 0.62 },
        { maxDimension: 1280, quality: 0.52 },
    ];

    image.onload = async () => {
        try {
            for (const candidate of candidates) {
                const scale = Math.min(1, candidate.maxDimension / Math.max(image.naturalWidth, image.naturalHeight));
                const canvas = document.createElement('canvas');
                canvas.width = Math.max(1, Math.round(image.naturalWidth * scale));
                canvas.height = Math.max(1, Math.round(image.naturalHeight * scale));
                const context = canvas.getContext('2d');

                if (!context) {
                    resolve(null);
                    return;
                }

                context.fillStyle = '#ffffff';
                context.fillRect(0, 0, canvas.width, canvas.height);
                context.drawImage(image, 0, 0, canvas.width, canvas.height);

                const blob = await new Promise((blobResolve) => canvas.toBlob(blobResolve, 'image/jpeg', candidate.quality));

                if (blob && blob.size <= maxBytes) {
                    const name = file.name.replace(/\.[^.]+$/, '') || 'gambar-hasil';
                    resolve(new File([blob], `${name}.jpg`, { type: 'image/jpeg', lastModified: Date.now() }));
                    return;
                }
            }

            resolve(null);
        } finally {
            URL.revokeObjectURL(objectUrl);
        }
    };

    image.onerror = () => {
        URL.revokeObjectURL(objectUrl);
        resolve(null);
    };
    image.src = objectUrl;
});

const initializeFileUploads = () => {
    document.querySelectorAll('[data-file-upload]').forEach((upload) => {
        const input = upload.querySelector('input[type="file"]');
        const fileName = upload.querySelector('[data-file-name]');
        const fileMeta = upload.querySelector('[data-file-meta]');
        const preview = upload.querySelector('[data-file-preview]');
        let previewUrl;

        if (!input || !fileName || !fileMeta || !preview) {
            return;
        }

        const clearPreview = () => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = undefined;
            }

            preview.removeAttribute('src');
            preview.classList.add('hidden');
        };

        const displayFile = (file, suffix = '') => {
            if (!file) {
                return;
            }

            fileName.textContent = file.name;
            fileMeta.textContent = `${formatUploadFileSize(file.size)}${suffix}`;

            const isImage = file.type
                ? file.type.startsWith('image/')
                : /\.(?:jpe?g|png|webp)$/i.test(file.name);

            clearPreview();

            if (isImage) {
                previewUrl = URL.createObjectURL(file);
                preview.src = previewUrl;
                preview.classList.remove('hidden');
            }
        };

        const handleFile = async (file) => {
            if (!file) {
                return;
            }

            const maxBytes = Number(upload.dataset.fileMaxSize || 0);
            const shouldCompress = upload.dataset.fileCompress === 'true';

            if (maxBytes && file.size > maxBytes && shouldCompress) {
                fileName.textContent = 'Mengompres gambar...';
                fileMeta.textContent = 'Mohon tunggu sebentar';
                const compressedFile = await compressImage(file, maxBytes);

                if (!compressedFile) {
                    input.value = '';
                    clearPreview();
                    fileName.textContent = 'Pilih file atau seret ke area ini';
                    fileMeta.textContent = `Gambar tidak dapat dikompres hingga maksimal ${formatUploadFileSize(maxBytes)}`;
                    await Swal.fire({ title: 'Gambar terlalu besar', text: 'Pilih gambar yang lebih kecil atau kurangi resolusinya.', icon: 'warning' });
                    return;
                }

                replaceInputFile(input, compressedFile);
                displayFile(compressedFile, ` • dikompres dari ${formatUploadFileSize(file.size)}`);
                return;
            }

            if (maxBytes && file.size > maxBytes) {
                input.value = '';
                clearPreview();
                fileName.textContent = 'Pilih file atau seret ke area ini';
                fileMeta.textContent = `Maksimal ${formatUploadFileSize(maxBytes)}`;
                await Swal.fire({ title: 'File terlalu besar', text: `Ukuran file maksimal ${formatUploadFileSize(maxBytes)}.`, icon: 'warning' });
                return;
            }

            displayFile(file);
        };

        input.addEventListener('change', () => { void handleFile(input.files?.[0]); });

        upload.addEventListener('dragover', (event) => {
            event.preventDefault();
            upload.classList.add('border-primary-600', 'bg-primary-50');
        });

        upload.addEventListener('dragleave', () => {
            upload.classList.remove('border-primary-600', 'bg-primary-50');
        });

        upload.addEventListener('drop', (event) => {
            event.preventDefault();
            upload.classList.remove('border-primary-600', 'bg-primary-50');

            if (!event.dataTransfer?.files.length) {
                return;
            }

            replaceInputFile(input, event.dataTransfer.files[0]);
            void handleFile(input.files[0]);
        });
    });
};

const initializeProfileAvatarPreview = () => {
    const input = document.querySelector('[data-profile-avatar-input]');
    const preview = document.querySelector('[data-profile-avatar-preview]');
    const fallback = document.querySelector('[data-profile-avatar-fallback]');
    let previewUrl;

    if (!input || !preview) {
        return;
    }

    input.addEventListener('change', () => {
        const file = input.files?.[0];

        if (!file || !file.type.startsWith('image/')) {
            return;
        }

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }

        previewUrl = URL.createObjectURL(file);
        preview.src = previewUrl;
        preview.classList.remove('hidden');
        fallback?.classList.add('hidden');
    });
};

// Reloads every server-side datatable on the page, not just the first. A page can now
// hold more than one (e.g. the product form's quick-manage modals for satuan/group/cost
// center, alongside the main products table), so a save anywhere must not miss the rest.
const reloadServerTables = () => {
    document.querySelectorAll('[data-server-table]').forEach((table) => table._dataTable?.ajax.reload(null, false));
};

// After a satuan/group/cost center is created, renamed, or deleted (from anywhere,
// including the product form's quick-manage modals), keep the product form's own
// selects in sync so a just-added record is selectable without a page reload. A no-op
// on pages that don't have these selects.
const refreshMasterSelectOptions = async () => {
    const sources = { unit_id: '/units/options', group_id: '/groups/options', cost_center_id: '/cost-centers/options' };

    await Promise.all(Object.entries(sources).map(async ([field, url]) => {
        const select = document.querySelector(`select[name="${field}"][data-tom-select]`);
        if (!select?.tomselect) return;

        try {
            const { results } = await $.ajax({ url, headers: { Accept: 'application/json' }, dataType: 'json' });
            const tomSelect = select.tomselect;
            const current = tomSelect.getValue();
            tomSelect.clearOptions();
            results.forEach((item) => tomSelect.addOption({ value: String(item.id), text: item.text }));
            tomSelect.refreshOptions(false);
            if (current && results.some((item) => String(item.id) === current)) {
                tomSelect.setValue(current, true);
            }
        } catch {
            // Leave the select's current options as-is.
        }
    }));
};

const initializeServerTables = () => {
    // Every real resource index page already marks its table with data-server-table.
    // Do NOT widen this to `, body table` — it grabs unrelated static tables too
    // (e.g. the dashboard's demo tables), which breaks them with a DataTables
    // "Incorrect column count" alert. This has regressed more than once.
    document.querySelectorAll('[data-server-table]').forEach((table) => {
        // A table normally infers its resource from the page URL. A page that embeds more
        // than one table (e.g. the product form's quick-manage modals) must say explicitly
        // which resource each one lists, since they all share that one URL.
        const resource = table.dataset.serverTableResource || (window.location.pathname.includes('groups') ? 'groups' : window.location.pathname.includes('shifts') ? 'shifts' : window.location.pathname.includes('cost-centers') ? 'cost-centers' : window.location.pathname.includes('employees') ? 'employees' : window.location.pathname.includes('products') ? 'products' : window.location.pathname.includes('realizations') ? 'realizations' : window.location.pathname.includes('deductions') ? 'deductions' : window.location.pathname.includes('reports/payroll') ? 'reports/payroll' : window.location.pathname.includes('reports/work') ? 'reports/work' : window.location.pathname.includes('audit-logs') ? 'audit-logs' : window.location.pathname.includes('settings/access') ? 'settings/access' : window.location.pathname.includes('clients') ? 'clients' : 'units');
        const auditFilters = resource === 'audit-logs' ? document.querySelector('[data-audit-filters]') : null;
        const workFilters = resource === 'reports/work' ? document.querySelector('[data-work-filters]') : null;
        const payrollFilters = resource === 'reports/payroll' ? document.querySelector('[data-payroll-filters]') : null;
        const initialSearch = new URLSearchParams(window.location.search).get('q') ?? '';
        const headerRow = table.querySelector('thead tr');
        const numberHeader = document.createElement('th');
        numberHeader.className = 'w-14 px-5 py-3';
        numberHeader.textContent = 'No';
        headerRow?.prepend(numberHeader);
        const dataTable = new DataTable(table, {
            processing: true,
            serverSide: true,
            ajax: { url: `/${resource}`, dataSrc: 'data', data: (params) => { if (auditFilters) { params.date_from = auditFilters.querySelector('[data-audit-date-from]')?.value; params.date_to = auditFilters.querySelector('[data-audit-date-to]')?.value; params.action = auditFilters.querySelector('[data-audit-action]')?.value; } if (workFilters) { params.date_from = workFilters.querySelector('[data-work-date-from]')?.value; params.date_to = workFilters.querySelector('[data-work-date-to]')?.value; } if (payrollFilters) { params.date_from = payrollFilters.querySelector('[data-payroll-date-from]')?.value; params.date_to = payrollFilters.querySelector('[data-payroll-date-to]')?.value; } } },
            pageLength: 10,
            pagingType: 'simple_numbers',
            order: [[1, 'asc']],
            search: { search: initialSearch },
            columns: [{ data: null, orderable: false, searchable: false, className: 'w-14 px-5 py-4 text-slate-500', render: (_data, _type, _row, meta) => Number(meta?.row ?? 0) + Number(meta?.settings?._iDisplayStart ?? meta?.settings?._displayStart ?? 0) + 1 }, ...(resource === 'shifts' ? [
                { data: 'code' }, { data: 'name' }, { data: 'start_time' }, { data: 'end_time' }, { data: 'status' }, { data: 'action', orderable: false, searchable: false },
            ] : resource === 'employees' ? [
                { data: 'employee_no' }, { data: 'full_name' }, { data: 'group_name' }, { data: 'status' }, { data: 'action', orderable: false, searchable: false },
            ] : resource === 'products' ? [
                { data: 'client_name' }, { data: 'sku' }, { data: 'name' }, { data: 'unit_name' }, { data: 'status' }, { data: 'action', orderable: false, searchable: false },
            ] : resource === 'realizations' ? [
                { data: 'work_date' }, { data: 'shift_name' }, { data: 'batch_label' }, { data: 'sku_snapshot' }, { data: 'product_label' }, { data: 'total_output' }, { data: 'total_price' }, { data: 'assignment_count' }, { data: 'action', orderable: false, searchable: false },
            ] : resource === 'reports/work' ? [
                { data: 'work_date' }, { data: 'sim_id' }, { data: 'full_name' }, { data: 'shift_name' }, { data: 'sku_snapshot' }, { data: 'product_name' },
                { data: 'target', orderable: false, searchable: false }, { data: 'target_price', orderable: false, searchable: false },
                { data: 'actual' }, { data: 'actual_price', orderable: false, searchable: false }, { data: 'description', defaultContent: '—' },
            ] : resource === 'reports/payroll' ? [
                { data: 'employee_no' }, { data: 'full_name' }, { data: 'gender' }, { data: 'attendance_days' },
                { data: 'net_salary', render: (value) => `Rp ${Number(value ?? 0).toLocaleString('id-ID')}` },
                { data: 'gross_salary', render: (value) => `Rp ${Number(value ?? 0).toLocaleString('id-ID')}` },
                { data: 'bpjs_health', render: (value) => `Rp ${Number(value ?? 0).toLocaleString('id-ID')}` },
                { data: 'bpjs_employment', render: (value) => `Rp ${Number(value ?? 0).toLocaleString('id-ID')}` },
                { data: 'uniform_amount', render: (value) => `Rp ${Number(value ?? 0).toLocaleString('id-ID')}` },
                { data: 'equipment_amount', render: (value) => `Rp ${Number(value ?? 0).toLocaleString('id-ID')}` },
                { data: 'meal_amount', render: (value) => `Rp ${Number(value ?? 0).toLocaleString('id-ID')}` },
                { data: 'salary_advance_value', render: (value) => `Rp ${Number(value ?? 0).toLocaleString('id-ID')}` },
                { data: 'correction_minus', render: (value) => `Rp ${Number(value ?? 0).toLocaleString('id-ID')}` },
                { data: 'correction_plus', render: (value) => `Rp ${Number(value ?? 0).toLocaleString('id-ID')}` },
            ] : resource === 'deductions' ? [
                { data: 'created_at' }, { data: 'periode' }, { data: 'sim_id' }, { data: 'full_name' },
                { data: 'bpjs_health' }, { data: 'bpjs_employment' },
                { data: 'correction_minus', render: (value) => `Rp ${Number(value ?? 0).toLocaleString('id-ID')}` },
                { data: 'correction_plus', render: (value) => `Rp ${Number(value ?? 0).toLocaleString('id-ID')}` },
                { data: 'action', orderable: false, searchable: false },
            ] : resource === 'audit-logs' ? [
                { data: 'created_at' }, { data: 'description' }, { data: 'ip_address' }, { data: 'user_name' },
            ] : resource === 'settings/access' ? [
                { data: 'name' }, { data: 'email' }, { data: 'role_name' }, { data: 'status' }, { data: 'action', orderable: false, searchable: false },
            ] : resource === 'clients' ? [
                { data: 'code' }, { data: 'name' }, { data: 'login_email', defaultContent: '—' }, { data: 'status' }, { data: 'action', orderable: false, searchable: false },
            ] : [
                { data: 'code', defaultContent: '—', createdCell: (cell) => cell.classList.add('px-5', 'py-4', 'font-medium', 'text-slate-900') },
                { data: 'name', createdCell: (cell) => cell.classList.add('px-5', 'py-4', 'text-slate-600') },
                { data: 'status', createdCell: (cell) => cell.classList.add('px-5', 'py-4') },
                { data: 'action', orderable: false, searchable: false, createdCell: (cell) => cell.classList.add('px-5', 'py-4', 'text-right') },
            ])],
            language: {
                searchPlaceholder: table.dataset.searchPlaceholder ?? 'Cari data...',
                search: '',
                lengthMenu: 'Tampilkan _MENU_ data',
                info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
                infoEmpty: 'Tidak ada data',
                infoFiltered: '(disaring dari _MAX_ total data)',
                zeroRecords: 'Data tidak ditemukan.',
                emptyTable: table.dataset.emptyMessage ?? 'Belum ada data.',
                processing: 'Memuat data…',
                paginate: { next: 'Berikutnya', previous: 'Sebelumnya' },
            },
        });
        table._dataTable = dataTable;
        auditFilters?.querySelector('[data-audit-filter-submit]')?.addEventListener('click', () => dataTable.ajax.reload());
        workFilters?.querySelector('[data-work-filter-submit]')?.addEventListener('click', () => dataTable.ajax.reload());
        const updatePayrollExportLinks = () => {
            const dateFrom = payrollFilters?.querySelector('[data-payroll-date-from]')?.value ?? '';
            const dateTo = payrollFilters?.querySelector('[data-payroll-date-to]')?.value ?? '';

            ['[data-payroll-export-excel]', '[data-payroll-export-pdf]'].forEach((selector) => {
                const link = document.querySelector(selector);
                if (!link) return;

                const url = new URL(link.href, window.location.origin);
                url.searchParams.set('date_from', dateFrom);
                url.searchParams.set('date_to', dateTo);
                link.href = url.toString();
            });
        };
        payrollFilters?.querySelector('[data-payroll-filter-submit]')?.addEventListener('click', () => {
            dataTable.ajax.reload();
            updatePayrollExportLinks();
        });
    });
};

const initializeAjaxDeletes = () => {
    document.addEventListener('submit', async (event) => {
        const form = event.target.closest?.('[data-ajax-delete]');
        if (!form) return;
        event.preventDefault();
        const result = await Swal.fire({ title: 'Hapus data?', text: 'Data yang dihapus tidak dapat dikembalikan.', icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal' });
        if (!result.isConfirmed) return;
        try {
            await $.ajax({ url: form.action, type: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }, dataType: 'json' });
        } catch (error) {
            await Swal.fire({ title: 'Gagal', text: error.responseJSON?.message ?? 'Data tidak dapat dihapus.', icon: 'error' });
            return;
        }
        await Swal.fire({ title: 'Berhasil', text: 'Data berhasil dihapus.', icon: 'success' });
        reloadServerTables();
        refreshMasterSelectOptions();
    });
};

const initializeRoleAccessForms = () => {
    document.addEventListener('submit', async (event) => {
        const form = event.target.closest?.('[data-role-access-form]');
        if (!form) return;
        event.preventDefault();
        try {
            const response = await $.ajax({ url: `/settings/access/roles/${form.dataset.roleId}`, type: 'PUT', data: $(form).serialize(), headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }, dataType: 'json' });
            await Swal.fire({ title: 'Berhasil', text: response.message ?? 'Hak akses menu berhasil diperbarui.', icon: 'success', timer: 1500, showConfirmButton: false });
        } catch (error) {
            await Swal.fire({ title: 'Gagal', text: error.responseJSON?.message ?? 'Hak akses menu tidak dapat disimpan.', icon: 'error' });
        }
    });
};

const initializeUserStatusToggle = () => {
    document.addEventListener('click', async (event) => {
        const button = event.target.closest?.('[data-user-status-toggle]');
        if (!button) return;

        const isActive = button.dataset.status === 'active';
        const action = isActive ? 'menonaktifkan' : 'mengaktifkan';
        const result = await Swal.fire({
            title: `${isActive ? 'Nonaktifkan' : 'Aktifkan'} user?`,
            text: `Anda akan ${action} ${button.dataset.userName ?? 'user ini'}.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: isActive ? 'Ya, nonaktifkan' : 'Ya, aktifkan',
            cancelButtonText: 'Batal',
        });
        if (!result.isConfirmed) return;

        button.disabled = true;
        try {
            const response = await $.ajax({
                url: button.dataset.url,
                type: 'PUT',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
                dataType: 'json',
            });
            reloadServerTables();
            await Swal.fire({ title: 'Berhasil', text: response.message ?? 'Status user berhasil diperbarui.', icon: 'success', timer: 1500, showConfirmButton: false });
        } catch (error) {
            await Swal.fire({ title: 'Gagal', text: error.responseJSON?.message ?? 'Status user tidak dapat diperbarui.', icon: 'error' });
        } finally {
            button.disabled = false;
        }
    });
};

const initializeAccessRolePicker = () => {
    const modal = document.querySelector('[data-access-role-picker]');
    const trigger = document.querySelector('[data-access-create]');
    if (!modal || !trigger) return;

    const close = () => { modal.classList.add('hidden'); modal.classList.remove('grid'); };
    trigger.addEventListener('click', () => {
        modal.classList.remove('hidden');
        modal.classList.add('grid');
    });
    modal.querySelectorAll('[data-access-role-picker-close]').forEach((button) => button.addEventListener('click', close));
    modal.addEventListener('click', (event) => { if (event.target === modal) close(); });
    modal.querySelectorAll('[data-access-role-option]').forEach((button) => button.addEventListener('click', close));
};

const initializeSuperAdminModal = () => {
    const modal = document.querySelector('[data-super-admin-modal]');
    const form = document.querySelector('[data-super-admin-form]');
    if (!modal || !form) return;

    const title = modal.querySelector('[data-super-admin-modal-title]');
    const close = () => { modal.classList.add('hidden'); modal.classList.remove('grid'); };
    const open = (item = null, url = '/settings/access/super-admins') => {
        form.reset();
        form.action = url;
        form.elements._method.value = item ? 'PUT' : 'POST';
        ['name', 'username', 'email', 'status'].forEach((field) => {
            if (item?.[field] !== undefined) form.elements[field].value = item[field] ?? '';
        });
        form.querySelectorAll('[data-required-on-create]').forEach((input) => { input.required = !item; });
        modal.querySelectorAll('[data-password-required-mark]').forEach((mark) => mark.classList.toggle('hidden', Boolean(item)));
        if (title) title.textContent = item ? 'Edit Super Admin' : 'Tambah Super Admin';
        modal.classList.remove('hidden');
        modal.classList.add('grid');
    };

    document.querySelector('[data-super-admin-create]')?.addEventListener('click', () => open());
    document.addEventListener('click', (event) => {
        const button = event.target.closest?.('[data-super-admin-edit]');
        if (!button) return;
        try { open(JSON.parse(button.dataset.superAdmin), button.dataset.url); } catch { Swal.fire({ title: 'Gagal', text: 'Data Super Admin tidak dapat dibaca.', icon: 'error' }); }
    });
    modal.querySelectorAll('[data-super-admin-close]').forEach((button) => button.addEventListener('click', close));
    modal.addEventListener('click', (event) => { if (event.target === modal) close(); });
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const isEdit = form.elements._method.value === 'PUT';
        const result = await Swal.fire({ title: `${isEdit ? 'Perbarui' : 'Simpan'} Super Admin?`, icon: 'question', showCancelButton: true, confirmButtonText: isEdit ? 'Perbarui' : 'Simpan', cancelButtonText: 'Batal' });
        if (!result.isConfirmed) return;
        try {
            await $.ajax({ url: form.action, type: 'POST', data: new FormData(form), processData: false, contentType: false, headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }, dataType: 'json' });
            close();
            reloadServerTables();
            await Swal.fire({ title: 'Berhasil', text: isEdit ? 'Super Admin berhasil diperbarui.' : 'Super Admin berhasil ditambahkan.', icon: 'success', timer: 1500, showConfirmButton: false });
        } catch (error) {
            const validation = Object.values(error.responseJSON?.errors ?? {}).flat().join('\n');
            await Swal.fire({ title: 'Gagal', text: validation || error.responseJSON?.message || 'Data Super Admin tidak valid.', icon: 'error' });
        }
    });
};

const initializeUserPasswordReset = () => {
    const modal = document.querySelector('[data-user-reset-modal]');
    const form = document.querySelector('[data-user-reset-form]');
    if (!modal || !form) return;

    const close = () => { modal.classList.add('hidden'); modal.classList.remove('grid'); };
    document.addEventListener('click', (event) => {
        const button = event.target.closest?.('[data-user-reset]');
        if (!button) return;
        form.reset();
        form.action = button.dataset.url;
        modal.querySelector('[data-user-reset-name]').textContent = button.dataset.userName ?? 'user';
        modal.classList.remove('hidden');
        modal.classList.add('grid');
        form.elements.password.focus();
    });
    modal.querySelectorAll('[data-user-reset-close]').forEach((button) => button.addEventListener('click', close));
    modal.addEventListener('click', (event) => { if (event.target === modal) close(); });
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const result = await Swal.fire({ title: 'Reset password user?', text: 'Password lama tidak dapat digunakan lagi.', icon: 'question', showCancelButton: true, confirmButtonText: 'Reset password', cancelButtonText: 'Batal' });
        if (!result.isConfirmed) return;
        try {
            const response = await $.ajax({ url: form.action, type: 'POST', data: new FormData(form), processData: false, contentType: false, headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }, dataType: 'json' });
            close();
            await Swal.fire({ title: 'Berhasil', text: response.message ?? 'Password berhasil direset.', icon: 'success', timer: 1500, showConfirmButton: false });
        } catch (error) {
            const validation = Object.values(error.responseJSON?.errors ?? {}).flat().join('\n');
            await Swal.fire({ title: 'Gagal', text: validation || error.responseJSON?.message || 'Password tidak dapat direset.', icon: 'error' });
        }
    });
};

const initializeAccessTabs = () => {
    const tabsContainer = document.querySelector('[data-access-tabs]');
    if (!tabsContainer) return;

    const tabs = [...tabsContainer.querySelectorAll('[data-access-tab]')];
    const panels = [...tabsContainer.querySelectorAll('[data-access-panel]')];

    const activate = (key, focus = false) => {
        tabs.forEach((tab) => {
            const isActive = tab.dataset.accessTab === key;
            tab.setAttribute('aria-selected', String(isActive));
            tab.classList.toggle('bg-white', isActive);
            tab.classList.toggle('shadow-sm', isActive);
            tab.classList.toggle('ring-1', isActive);
            tab.classList.toggle('ring-slate-200/70', isActive);
            tab.classList.toggle('text-primary-600', isActive);
            tab.classList.toggle('bg-transparent', !isActive);
            tab.classList.toggle('text-slate-500', !isActive);

            if (isActive && focus) tab.focus();
        });

        panels.forEach((panel) => {
            panel.hidden = panel.dataset.accessPanel !== key;
        });
    };

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => activate(tab.dataset.accessTab));
        tab.addEventListener('keydown', (event) => {
            const direction = event.key === 'ArrowRight' ? 1 : event.key === 'ArrowLeft' ? -1 : 0;
            if (event.key === 'Home' || event.key === 'End') {
                event.preventDefault();
                const target = tabs[event.key === 'Home' ? 0 : tabs.length - 1];
                activate(target.dataset.accessTab, true);
                return;
            }
            if (!direction) return;

            event.preventDefault();
            const nextIndex = (index + direction + tabs.length) % tabs.length;
            activate(tabs[nextIndex].dataset.accessTab, true);
        });
    });

    activate(tabs.find((tab) => tab.getAttribute('aria-selected') === 'true')?.dataset.accessTab ?? tabs[0]?.dataset.accessTab);
};

const initializeProductDetailModal = () => {
    const modal = document.querySelector('[data-product-detail-modal]');
    if (!modal) return;

    const close = () => { modal.classList.add('hidden'); modal.classList.remove('grid'); };
    modal.querySelectorAll('[data-product-detail-close]').forEach((button) => button.addEventListener('click', close));
    modal.addEventListener('click', (event) => { if (event.target === modal) close(); });

    const formatRupiah = (value) => `Rp ${Number(value ?? 0).toLocaleString('id-ID', { maximumFractionDigits: 3 })}`;
    const set = (attribute, value) => { const el = modal.querySelector(`[data-product-detail-${attribute}]`); if (el) el.textContent = value || '—'; };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-product-detail]');
        if (!trigger) return;

        const data = JSON.parse(trigger.dataset.productDetail);
        set('client', data.client_name);
        set('client-code', data.client_code);
        modal.querySelector('[data-product-detail-client-status]').innerHTML = `<span class="inline-flex items-center rounded-md border px-2 py-1 text-[11px] font-semibold ${data.client_status === 'active' ? 'border-green-200 bg-green-50 text-green-700' : 'border-slate-200 bg-slate-50 text-slate-600'}">${data.client_status === 'active' ? 'Aktif' : 'Nonaktif'}</span>`;
        set('sku', data.sku);
        set('name', data.name);
        set('unit', data.unit_name);
        set('group', data.group_name);
        set('cost-center', data.cost_center_name);
        set('po-price', formatRupiah(data.po_price));
        set('employee-rate', formatRupiah(data.employee_rate));
        set('estimate', data.estimated_output_per_hour ? `${Number(data.estimated_output_per_hour).toLocaleString('id-ID')} / jam` : '');
        modal.querySelector('[data-product-detail-status]').innerHTML = `<span class="inline-flex items-center rounded-md border px-2 py-1 text-[11px] font-semibold ${data.status === 'active' ? 'border-green-200 bg-green-50 text-green-700' : 'border-slate-200 bg-slate-50 text-slate-600'}">${data.status === 'active' ? 'Aktif' : 'Nonaktif'}</span>`;

        modal.classList.remove('hidden');
        modal.classList.add('grid');
    });
};

const initializeEmployeeDetailModal = () => {
    const modal = document.querySelector('[data-employee-detail-modal]');
    if (!modal) return;

    const close = () => { modal.classList.add('hidden'); modal.classList.remove('grid'); };
    modal.querySelectorAll('[data-employee-detail-close]').forEach((button) => button.addEventListener('click', close));
    modal.addEventListener('click', (event) => { if (event.target === modal) close(); });

    const set = (attribute, value) => { const el = modal.querySelector(`[data-employee-detail-${attribute}]`); if (el) el.textContent = value || '—'; };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-employee-detail]');
        if (!trigger) return;

        const data = JSON.parse(trigger.dataset.employeeDetail);
        set('employee-no', data.employee_no);
        set('sim-id', data.sim_id);
        set('full-name', data.full_name);
        set('email', data.email);
        set('phone', data.phone);
        set('join-date', data.join_date);
        set('gender', data.gender);
        set('employee-status', data.employee_status);
        set('marital-status', data.marital_status);
        set('group', data.group_name);
        modal.querySelector('[data-employee-detail-status]').innerHTML = `<span class="inline-flex items-center rounded-md border px-2 py-1 text-[11px] font-semibold ${data.status === 'active' ? 'border-green-200 bg-green-50 text-green-700' : 'border-slate-200 bg-slate-50 text-slate-600'}">${data.status === 'active' ? 'Aktif' : 'Nonaktif'}</span>`;

        modal.classList.remove('hidden');
        modal.classList.add('grid');
    });
};

const initializeClientDetailModal = () => {
    const modal = document.querySelector('[data-client-detail-modal]');
    if (!modal) return;

    const close = () => { modal.classList.add('hidden'); modal.classList.remove('grid'); };
    modal.querySelectorAll('[data-client-detail-close]').forEach((button) => button.addEventListener('click', close));
    modal.addEventListener('click', (event) => { if (event.target === modal) close(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !modal.classList.contains('hidden')) close(); });

    const set = (attribute, value) => { const el = modal.querySelector(`[data-client-detail-${attribute}]`); if (el) el.textContent = value || '—'; };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-client-detail]');
        if (!trigger) return;

        try {
            const data = JSON.parse(trigger.dataset.clientDetail);
            set('name', data.name);
            set('code', data.code);
            set('code-repeat', data.code);
            set('account-name', data.account_name);
            set('login-username', data.login_username);
            set('login-email', data.login_email);
            modal.querySelector('[data-client-detail-status]').innerHTML = `<span class="inline-flex items-center rounded-md border px-2.5 py-1 text-xs font-semibold ${data.status === 'active' ? 'border-green-200 bg-green-50 text-green-700' : 'border-slate-200 bg-slate-50 text-slate-600'}">${data.status === 'active' ? 'Aktif' : 'Nonaktif'}</span>`;
            modal.classList.remove('hidden');
            modal.classList.add('grid');
        } catch {
            Swal.fire({ title: 'Gagal', text: 'Detail client tidak dapat dibaca.', icon: 'error' });
        }
    });
};

const initializeUnitModal = () => {
    const modal = document.querySelector('[data-unit-modal]');
    const form = modal?.querySelector('[data-unit-form]');
    const title = modal?.querySelector('[data-unit-modal-title]');
    const submitLabel = modal?.querySelector('[data-unit-submit-label]');
    const submitButton = modal?.querySelector('[data-unit-submit]');
    const createButton = document.querySelector('[data-unit-create]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    if (!modal || !form || !submitButton) {
        return;
    }

    const clearErrors = () => {
        form.querySelectorAll('[data-error-for]').forEach((el) => {
            el.textContent = '';
            el.classList.add('hidden');
        });
        form.querySelectorAll('input, select').forEach((el) => el.classList.remove('border-red-400'));
    };

    const showErrors = (errors) => {
        Object.entries(errors ?? {}).forEach(([field, messages]) => {
            form.querySelector(`[data-error-for="${field}"]`)?.replaceChildren(messages[0]);
            form.querySelector(`[data-error-for="${field}"]`)?.classList.remove('hidden');
            form.querySelector(`[name="${field}"]`)?.classList.add('border-red-400');
        });
    };

    const open = (mode, data = null, updateUrl = null) => {
        clearErrors();
        form.reset();
        form.dataset.mode = mode;
        form.dataset.updateUrl = updateUrl ?? '';
        title.textContent = mode === 'edit' ? 'Edit satuan' : 'Tambah satuan';
        submitLabel.textContent = mode === 'edit' ? 'Simpan perubahan' : 'Simpan satuan';

        if (data) {
            form.elements.code.value = data.code ?? '';
            form.elements.name.value = data.name ?? '';
            form.elements.status.value = data.status ?? 'active';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        form.elements.code.focus();
    };

    const close = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    createButton?.addEventListener('click', () => open('create'));

    modal.querySelectorAll('[data-unit-modal-close]').forEach((button) => button.addEventListener('click', close));
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            close();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            close();
        }
    });

    document.addEventListener('click', async (event) => {
        const trigger = event.target.closest('[data-unit-edit]');
        if (!trigger) {
            return;
        }

        try {
            const unit = await $.ajax({ url: trigger.dataset.editUrl, headers: { Accept: 'application/json' }, dataType: 'json' });
            open('edit', unit, trigger.dataset.updateUrl);
        } catch (error) {
            await Swal.fire({ title: 'Gagal', text: error.responseJSON?.message ?? 'Data satuan tidak ditemukan.', icon: 'error' });
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors();

        const isEdit = form.dataset.mode === 'edit';
        const url = isEdit ? form.dataset.updateUrl : form.action;
        const data = {
            _token: csrfToken,
            code: form.elements.code.value,
            name: form.elements.name.value,
            status: form.elements.status.value,
        };

        if (isEdit) {
            data._method = 'PUT';
        }

        submitButton.disabled = true;

        try {
            await $.ajax({ url, type: 'POST', data, dataType: 'json' });
            close();
            await Swal.fire({ title: 'Berhasil', text: isEdit ? 'Satuan berhasil diperbarui.' : 'Satuan berhasil ditambahkan.', icon: 'success' });
            reloadServerTables();
            refreshMasterSelectOptions();
        } catch (error) {
            if (error.status === 422) {
                showErrors(error.responseJSON?.errors);
            } else {
                await Swal.fire({ title: 'Gagal', text: error.responseJSON?.message ?? 'Data tidak dapat disimpan.', icon: 'error' });
            }
        } finally {
            submitButton.disabled = false;
        }
    });
};

const initializeDatepickers = () => {
    document.querySelectorAll('[data-datepicker], input[type="date"], input[type="month"]').forEach((input) => {
        flatpickr(input, {
            locale: Indonesian,
            mode: input.dataset.datepickerMode ?? 'single',
            dateFormat: input.type === 'month' ? 'Y-m' : 'Y-m-d',
            altInput: true,
            altFormat: input.type === 'month' ? 'F Y' : 'j F Y',
            allowInput: true,
            onReady: (_selectedDates, _dateStr, instance) => {
                instance.altInput.placeholder = input.type === 'month' ? 'Pilih bulan' : 'Pilih tanggal';
                instance.altInput.className = input.className;
            },
        });
    });
};

// Switching the active client submits the header form straight away, so the page reloads
// scoped to the newly picked client. CurrentClientController redirects back to the same page.
const initializeClientSwitcher = () => {
    document.addEventListener('change', (event) => {
        const select = event.target.closest?.('[data-client-switcher]');
        select?.form?.submit();
    });
};

const initializeTomSelect = () => {
    document.querySelectorAll('[data-tom-select]').forEach((select) => {
        if (select.tomselect) {
            return;
        }

        const remoteUrl = select.dataset.tomSelectRemote;
        const dependsOnParam = select.dataset.tomSelectDependsOn;
        const dependsOnSelector = select.dataset.tomSelectDependsOnSelector;

        new TomSelect(select, {
            placeholder: select.dataset.tomSelectPlaceholder ?? 'Pilih...',
            create: false,
            ...(remoteUrl ? {
                valueField: 'id',
                labelField: 'text',
                searchField: [],
                // Without this the control opens empty and only fills once the user types,
                // which reads as a broken dropdown. Loading the first page on focus keeps
                // the search itself server-side, so a large catalog is still never dumped.
                preload: 'focus',
                load: (query, callback) => {
                    const params = new URLSearchParams({ q: query });
                    const dependsOnValue = dependsOnParam && dependsOnSelector ? document.querySelector(dependsOnSelector)?.value : '';
                    if (dependsOnValue) params.set(dependsOnParam, dependsOnValue);
                    fetch(`${remoteUrl}?${params}`, { headers: { Accept: 'application/json' } })
                        .then((response) => response.json())
                        .then((data) => callback(data.results ?? []))
                        .catch(() => callback());
                },
                render: {
                    no_results: () => '<div class="no-results">Tidak ada hasil.</div>',
                },
            } : {}),
        });
    });
};

const initializeRupiahInputs = () => {
    document.querySelectorAll('[data-rupiah-field]').forEach((field) => {
        const raw = field.querySelector('[data-rupiah-raw]');
        const display = field.querySelector('[data-rupiah-display]');
        if (!raw || !display || display.dataset.rupiahBound) return;
        display.dataset.rupiahBound = 'true';

        const decimals = Number(field.dataset.rupiahDecimals ?? 0);
        const format = (value) => {
            const number = Number(value);
            if (value === '' || value === null || value === undefined || Number.isNaN(number)) return '';
            return number.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: decimals });
        };
        const paint = () => { display.value = format(raw.value); };

        display.addEventListener('input', () => {
            const cleaned = display.value.replace(/[^0-9,]/g, '');
            const [intPart, ...rest] = cleaned.split(',');
            const decimalPart = rest.join('').slice(0, decimals);
            raw.value = decimalPart ? `${intPart || '0'}.${decimalPart}` : intPart;

            // Repaint on every keystroke so thousand separators appear immediately.
            // Keep a trailing comma visible while the user is starting the decimal part.
            if (decimals > 0 && cleaned.endsWith(',') && !decimalPart) {
                display.value = `${format(intPart || '0')},`;
                return;
            }

            paint();
        });
        display.addEventListener('blur', paint);
        // Programmatic value assignment (e.g. edit-modal prefill) doesn't fire native
        // input events, so react to a dispatched one instead — see initializeMasterModal.
        raw.addEventListener('input', paint);

        paint();
    });
};

const initializeSelect2 = () => {
    if (!$.fn.select2) return;

    // Selector/marker is "select2-select", not "select2" — Select2's own Utils.GetData()
    // falls back to reading element.dataset.select2 when its internal cache is still empty,
    // so a bare `data-select2` attribute is read as "" (not null) on the very first init and
    // crashes ("".destroy is not a function). Keep this attribute name off the "select2" key.
    $('[data-select2-select]').each(function () {
        const select = $(this);
        if (select.hasClass('select2-hidden-accessible')) return;

        const remoteUrl = select.data('select2-remote');

        select.select2({
            width: '100%',
            placeholder: select.data('select2-placeholder') ?? 'Pilih...',
            allowClear: true,
            ...(remoteUrl ? { minimumInputLength: 1 } : {}),
            ...(remoteUrl ? {
                ajax: {
                    url: remoteUrl,
                    dataType: 'json',
                    delay: 250,
                    data: (params) => ({ q: params.term ?? '', page: params.page ?? 1 }),
                    processResults: (data) => ({
                        // Select2's bundled AJAX adapter normalizes remote results
                        // without its adapter context; pre-seeding the result id
                        // keeps that normalization path context-independent.
                        results: (data.results ?? []).map((item) => ({
                            ...item,
                            _resultId: `select2-result-${select.attr('id') ?? 'remote'}-${item.id}`,
                        })),
                        pagination: data.pagination ?? { more: false },
                    }),
                    cache: true,
                },
            } : {}),
        });
    });
};

const initializeRichTextEditors = () => {
    document.querySelectorAll('[data-rich-editor]').forEach((mount) => {
        const input = document.getElementById(mount.dataset.richEditor);
        if (!input) return;

        const quill = new Quill(mount, {
            theme: 'snow',
            placeholder: mount.dataset.richEditorPlaceholder ?? '',
            modules: {
                toolbar: [['bold', 'italic', 'underline', 'strike'], [{ list: 'ordered' }, { list: 'bullet' }], ['blockquote', 'link'], ['clean']],
            },
        });

        if (input.value) quill.root.innerHTML = input.value;
        quill.on('text-change', () => { input.value = quill.getText().trim() === '' ? '' : quill.root.innerHTML; });
    });
};

const initializeMasterModal = ({ name, plural, fields, confirmTitle, successCreate, successUpdate }) => {
    const modal = document.querySelector(`[data-${name}-modal]`);
    const form = document.querySelector(`[data-${name}-form]`);
    if (!modal || !form) return;
    const title = modal.querySelector(`[data-${name}-modal-title]`);
    const dataKey = name.replace(/-([a-z])/g, (_, letter) => letter.toUpperCase());
    const addIcon = (button, icon) => {
        if (!button || button.querySelector('svg')) return;
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('aria-hidden', 'true');
        svg.setAttribute('class', 'size-4 fill-none stroke-current');
        const use = document.createElementNS('http://www.w3.org/2000/svg', 'use');
        use.setAttribute('href', `/images/heroicons.svg#${icon}`);
        svg.append(use);
        button.prepend(svg);
    };
    modal.querySelectorAll(`[data-${name}-close]`).forEach((button) => {
        button.classList.add('inline-flex', 'items-center', 'gap-2', 'whitespace-nowrap', 'border-red-200', 'bg-red-50', 'text-red-700', 'hover:bg-red-100');
        addIcon(button, 'x-mark');
    });
    addIcon(form.querySelector('button[type="submit"]'), 'check-circle');
    const close = () => { modal.classList.add('hidden'); modal.classList.remove('grid'); };
    const open = (item = null, url = `/${plural}`) => {
        form.reset();
        form.querySelectorAll('[data-rupiah-field] [data-rupiah-raw]').forEach((input) => input.dispatchEvent(new Event('input', { bubbles: true })));
        form.querySelector('[data-product-name]')?.setAttribute('value', '');
        if (form.querySelector('[data-product-name]')) form.querySelector('[data-product-name]').value = '';
        form.querySelector('[data-product-select]')?.dispatchEvent(new Event('change'));
        form.querySelectorAll('[data-datepicker], input[type="date"], input[type="month"]').forEach((input) => input._flatpickr?.clear());
        form.action = url;
        form.elements._method.value = item ? 'PUT' : 'POST';
        fields.forEach((field) => {
            const input = item && form.elements[field];
            if (!input) return;
            input.value = item[field] ?? '';
            input.dispatchEvent(new Event('input', { bubbles: true })); // repaint dependents like rupiah-input displays
        });
        form.querySelectorAll('[data-required-on-create]').forEach((input) => {
            input.required = !item;
        });
        form.querySelectorAll('[data-default-on-create]').forEach((input) => {
            input.value = item ? '' : input.dataset.defaultOnCreate;
        });
        form.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
            const input = toggle.closest('form')?.querySelector('input[name="password"]');
            const show = toggle.querySelector('[data-password-show]');
            const hide = toggle.querySelector('[data-password-hide]');

            if (!input || !show || !hide) return;

            input.type = 'password';
            show.classList.remove('hidden');
            hide.classList.add('hidden');
            toggle.setAttribute('aria-label', 'Tampilkan password');
        });
        form.querySelectorAll('[data-datepicker], input[type="date"], input[type="month"]').forEach((input) => { if (input.value) input._flatpickr?.setDate(input.value, false); });
        form.querySelectorAll('[data-tom-select]').forEach((select) => select.tomselect?.setValue(select.value, true));
        form.querySelectorAll('[data-select2-select]').forEach((select) => $(select).val(select.value).trigger('change'));
        if (title) title.textContent = item ? `Edit ${confirmTitle}` : `Tambah ${confirmTitle}`;
        modal.classList.remove('hidden');
        modal.classList.add('grid');
    };
    document.querySelector(`[data-${name}-create]`)?.addEventListener('click', () => open());
    document.addEventListener('click', (event) => {
        const button = event.target.closest(`[data-${name}-edit]`);
        if (!button) return;
        try { open(JSON.parse(button.dataset[dataKey]), button.dataset.url); } catch { Swal.fire({ title: 'Gagal', text: 'Data tidak dapat dibaca.', icon: 'error' }); }
    });
    modal.querySelectorAll(`[data-${name}-close]`).forEach((button) => button.addEventListener('click', close));
    modal.querySelector('[data-default-password]')?.addEventListener('click', () => {
        const input = form.elements.password;
        if (!input) return;

        input.value = input.closest('label')?.querySelector('[data-default-password]')?.dataset.defaultPassword ?? 'password';
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.focus();
    });
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const isEdit = form.elements._method.value === 'PUT';
        const result = await Swal.fire({ title: `${isEdit ? 'Perbarui' : 'Simpan'} ${confirmTitle}?`, icon: 'question', showCancelButton: true, confirmButtonText: isEdit ? 'Perbarui' : 'Simpan', cancelButtonText: 'Batal' });
        if (!result.isConfirmed) return;
        try {
            if (!(await validateRealizationImage(form))) return;
            await $.ajax({ url: form.action, type: 'POST', data: new FormData(form), processData: false, contentType: false, headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }, dataType: 'json' });
            close();
            reloadServerTables();
            refreshMasterSelectOptions();
            await Swal.fire({ title: 'Berhasil', text: isEdit ? successUpdate : successCreate, icon: 'success' });
        } catch (error) {
            const validation = Object.values(error.responseJSON?.errors ?? {}).flat().join('\n');
            await Swal.fire({ title: 'Gagal', text: validation || error.responseJSON?.message || 'Data tidak valid.', icon: 'error' });
        }
    });
};

// Large "quick-manage" modals opened from within another form (e.g. Satuan/Group/Cost
// center from the product form), so their own datatable + add/edit modal never require
// leaving the page. Sits at a higher z-index than the modal it was opened from.
const initializeManageModals = () => {
    document.querySelectorAll('[data-manage-modal]').forEach((modal) => {
        const key = modal.dataset.manageModal;

        const open = () => {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            const table = modal.querySelector('[data-server-table]');
            if (table?._dataTable) {
                table._dataTable.ajax.reload(null, false);
                table._dataTable.columns.adjust();
            }
        };

        const close = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };

        document.querySelectorAll(`[data-manage-modal-open="${key}"]`).forEach((button) => button.addEventListener('click', open));
        modal.querySelectorAll('[data-manage-modal-close]').forEach((button) => button.addEventListener('click', close));
        modal.addEventListener('click', (event) => {
            if (event.target === modal) close();
        });
    });
};

const initializeActionButtonStyles = () => {
    document.querySelectorAll('button, a').forEach((button) => {
        const label = button.textContent?.trim().toLowerCase() ?? '';
        const isCancel = label === 'batal' || label.includes('cancel');
        const isSave = label === 'simpan' || label.includes('simpan perubahan');
        if (!isCancel && !isSave) return;
        if (!button.querySelector('svg')) {
            const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.setAttribute('aria-hidden', 'true');
            svg.setAttribute('class', 'size-4 fill-none stroke-current');
            const use = document.createElementNS('http://www.w3.org/2000/svg', 'use');
            use.setAttribute('href', `/images/heroicons.svg#${isCancel ? 'x-mark' : 'check-circle'}`);
            svg.append(use);
            button.prepend(svg);
        }
        button.classList.add('inline-flex', 'items-center', 'gap-2', 'whitespace-nowrap');
        if (isCancel) button.classList.add('border-red-200', 'bg-red-50', 'text-red-700', 'hover:bg-red-100');
    });
};

const initializeRealizationProductPreview = () => {
    const select = document.querySelector('[data-product-select]');
    const batchSelect = document.querySelector('[data-batch-select]');
    const nameInput = document.querySelector('[data-product-name]');
    const unitInput = document.querySelector('[data-product-unit]');
    const estimateInput = document.querySelector('[data-product-estimate]');
    const unitLabel = document.querySelector('[data-product-unit-label]');

    if (!select || !nameInput) {
        return;
    }

    const update = () => {
        const selectedOption = select.selectedOptions[0];
        const selectedTomOption = select.tomselect?.options?.[select.value];
        nameInput.value = selectedOption?.dataset.productName ?? selectedTomOption?.name ?? selectedTomOption?.text?.split(' — ').slice(1).join(' — ') ?? '';
        const unitName = selectedTomOption?.unit_name ?? '';
        if (unitInput) unitInput.value = unitName;
        if (unitLabel) unitLabel.textContent = unitName || 'Karton/Kg';
        if (estimateInput) {
            const estimate = selectedTomOption?.estimated_output_per_hour;
            estimateInput.value = estimate ? `${Number(estimate).toLocaleString('id-ID')} / jam` : '';
        }
        if (!batchSelect?.tomselect) return;
        const productId = select.value;
        if (batchSelect.dataset.lastProductFilter !== productId) {
            const selectedBatchId = batchSelect.tomselect.getValue();
            const selectedBatch = selectedBatchId ? batchSelect.tomselect.options[selectedBatchId] : null;
            const selectedBatchBelongsToProduct = selectedBatch && String(selectedBatch.product_id ?? '') === String(productId);

            batchSelect.dataset.lastProductFilter = productId;
            if (!selectedBatchBelongsToProduct) {
                batchSelect.tomselect.clear(true);
            }
            batchSelect.tomselect.clearOptions();
            if (productId) {
                batchSelect.tomselect.load('');
            }
        }
    };

    select.addEventListener('change', update);
    $(select).on('select2:select', (_event, payload) => {
        nameInput.value = payload?.params?.data?.name ?? payload?.params?.data?.text?.split(' — ').slice(1).join(' — ') ?? '';
    });

    batchSelect?.addEventListener('change', () => {
        const batchId = batchSelect.tomselect?.getValue();
        const batch = batchId ? batchSelect.tomselect?.options?.[batchId] : null;
        const product = batch?.product;

        if (!product || !select.tomselect) {
            return;
        }

        if (!select.tomselect.options[product.id]) {
            select.tomselect.addOption(product);
        }

        if (String(select.tomselect.getValue()) !== String(product.id)) {
            select.tomselect.setValue(String(product.id));
        }
    });

    update();
};

const initializeRealizationPricePreview = () => {
    const form = document.querySelector('[data-realization-form]');
    const input = document.querySelector('[data-total-output-input]');
    const preview = document.querySelector('[data-total-price-preview]');
    const productSelect = document.querySelector('[data-product-select]');

    if (!form || !input || !preview) return;

    const update = () => {
        const selectedTomOption = productSelect?.tomselect?.options?.[productSelect.value];
        const output = Number(input.value || 0);
        const selectedEmployeeCount = [...document.querySelectorAll('[data-assignment-employee]')]
            .filter((select) => select.value)
            .length;
        const employeeCount = selectedEmployeeCount || 1;
        const rate = Number(selectedTomOption?.employee_rate ?? 0);
        const totalPrice = Number.isFinite(output) ? Math.round(output * rate * employeeCount) : 0;

        preview.value = `Rp ${totalPrice.toLocaleString('id-ID')}`;
    };

    input.addEventListener('input', update);
    productSelect?.addEventListener('change', update);
    document.querySelector('[data-assignment-section]')?.addEventListener('change', update);
    update();
};

const initializeRealizationAssignments = () => {
    const section = document.querySelector('[data-assignment-section]');
    const rows = section?.querySelector('[data-assignment-rows]');
    const groupSelect = section?.querySelector('[data-assignment-group]');
    const form = document.querySelector('[data-realization-form]');

    if (!section || !rows || !form) return;

    // Captured before any row gets wrapped by TomSelect — cloning a live,
    // already-wrapped row would duplicate TomSelect's injected UI instead of
    // giving us a plain <select> to re-initialize.
    const pristineRow = rows.querySelector('[data-assignment-row]').cloneNode(true);

    const initializeRowSelect = (row) => {
        const select = row.querySelector('[data-assignment-employee]');
        if (!select || select.tomselect) return;
        new TomSelect(select, { placeholder: 'Pilih karyawan...', create: false });
    };

    const refreshButtons = () => {
        const items = [...rows.querySelectorAll('[data-assignment-row]')];
        items.forEach((row, index) => {
            row.querySelector('[data-assignment-remove]')?.classList.toggle('hidden', items.length === 1);
            row.querySelector('[data-assignment-add]')?.classList.toggle('hidden', index !== items.length - 1);
        });
    };
    const addRow = (value = '') => {
        const row = pristineRow.cloneNode(true);
        const select = row.querySelector('[data-assignment-employee]');
        select.value = value;
        rows.append(row);
        initializeRowSelect(row);
        refreshButtons();
    };
    rows.querySelectorAll('[data-assignment-row]').forEach(initializeRowSelect);
    rows.addEventListener('click', (event) => {
        const add = event.target.closest('[data-assignment-add]');
        const remove = event.target.closest('[data-assignment-remove]');
        if (add) addRow();
        if (remove) {
            const row = remove.closest('[data-assignment-row]');
            row?.querySelector('[data-assignment-employee]')?.tomselect?.destroy();
            row?.remove();
            refreshButtons();
        }
    });
    section.querySelector('[data-assignment-add-group]')?.addEventListener('click', () => {
        const groupId = groupSelect?.value;
        if (!groupId) {
            Swal.fire({ title: 'Pilih group dulu', text: 'Pilih salah satu group pada dropdown sebelum menekan tombol Group.', icon: 'warning' });
            return;
        }
        const selected = new Set([...rows.querySelectorAll('[data-assignment-employee]')].map((select) => select.value).filter(Boolean));
        const toAdd = [...document.querySelectorAll('[data-assignment-employee] option[data-employee-group]')]
            .filter((option) => option.dataset.employeeGroup === groupId && !selected.has(option.value));
        if (toAdd.length === 0) {
            Swal.fire({ title: 'Tidak ada karyawan ditambahkan', text: 'Group ini tidak memiliki karyawan aktif, atau semua anggotanya sudah ada di daftar.', icon: 'info' });
            return;
        }
        toAdd.forEach((option) => { addRow(option.value); });
        if (groupSelect?.tomselect) groupSelect.tomselect.clear();
        else if (groupSelect) groupSelect.value = '';
    });
    refreshButtons();
};

const initializeRealizationCreatePage = () => {
    const form = document.querySelector('[data-realization-form]');
    if (!form) return;
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const result = await Swal.fire({ title: 'Simpan realisasi?', text: 'Assignment karyawan dapat diisi sekarang atau nanti.', icon: 'question', showCancelButton: true, confirmButtonText: 'Simpan', cancelButtonText: 'Batal' });
        if (!result.isConfirmed) return;
        try {
            await $.ajax({ url: form.action, type: 'POST', data: $(form).serialize(), headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }, dataType: 'json' });
            await Swal.fire({ title: 'Berhasil', text: 'Realisasi berhasil disimpan.', icon: 'success' });
            window.location.href = form.dataset.redirect;
        } catch (error) {
            const validation = Object.values(error.responseJSON?.errors ?? {}).flat().join('\n');
            await Swal.fire({ title: 'Gagal', text: validation || error.responseJSON?.message || 'Data tidak valid.', icon: 'error' });
        }
    });
};

const validateRealizationImage = async (form) => {
    const file = form.querySelector('input[name="result_image"]')?.files?.[0];

    if (!file) {
        return true;
    }

    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    const maxSize = 3 * 1024 * 1024;

    if (!allowedTypes.includes(file.type) || file.size > maxSize) {
        await Swal.fire({ title: 'File tidak valid', text: 'Pilih gambar JPG, PNG, atau WEBP dengan ukuran maksimal 3 MB.', icon: 'warning' });
        return false;
    }

    return true;
};

const initializeRealizationSubmitPage = () => {
    const form = document.querySelector('[data-realization-submit-form]');
    if (!form) return;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const result = await Swal.fire({ title: 'Kirim hasil pekerjaan?', icon: 'question', showCancelButton: true, confirmButtonText: 'Kirim', cancelButtonText: 'Batal' });
        if (!result.isConfirmed) return;
        if (!(await validateRealizationImage(form))) return;

        try {
            await $.ajax({ url: form.action, type: 'POST', data: new FormData(form), processData: false, contentType: false, headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }, dataType: 'json' });
            await Swal.fire({ title: 'Berhasil', text: 'Realisasi berhasil dikirim.', icon: 'success' });
            window.location.href = form.dataset.redirect;
        } catch (error) {
            const validation = Object.values(error.responseJSON?.errors ?? {}).flat().join('\n');
            await Swal.fire({ title: 'Gagal', text: validation || error.responseJSON?.message || 'Data tidak valid.', icon: 'error' });
        }
    });
};

const initializeRealizationInlineAssignPage = () => {
    const form = document.querySelector('[data-realization-assign-form]');
    if (!form || document.querySelector('[data-realization-assign-modal]')) return;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!form.querySelector('input[name="employee_ids[]"]:checked')) {
            await Swal.fire({ title: 'Pilih karyawan', text: 'Pilih minimal satu karyawan untuk assignment.', icon: 'warning' });
            return;
        }

        try {
            await $.ajax({ url: form.action, type: 'POST', data: $(form).serialize(), headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }, dataType: 'json' });
            await Swal.fire({ title: 'Berhasil', text: 'Karyawan berhasil di-assign.', icon: 'success' });
            window.location.reload();
        } catch (error) {
            const validation = Object.values(error.responseJSON?.errors ?? {}).flat().join('\n');
            await Swal.fire({ title: 'Gagal', text: validation || error.responseJSON?.message || 'Assignment tidak dapat disimpan.', icon: 'error' });
        }
    });
};

const initializeRealizationAssignModal = () => {
    const modal = document.querySelector('[data-realization-assign-modal]');
    const form = modal?.querySelector('[data-realization-assign-form]');
    const rows = modal?.querySelector('[data-realization-assign-rows]');
    const groupSelect = modal?.querySelector('[data-realization-assign-group]');

    if (!modal || !form || !rows) return;

    const pristineRow = rows.querySelector('[data-realization-assign-row]')?.cloneNode(true);
    if (!pristineRow) return;

    const initializeRowSelect = (row) => {
        const select = row.querySelector('[data-realization-assign-employee]');
        if (!select || select.tomselect) return;
        new TomSelect(select, { placeholder: 'Pilih karyawan...', create: false });
    };

    const refreshButtons = () => {
        const items = [...rows.querySelectorAll('[data-realization-assign-row]')];
        items.forEach((row, index) => {
            row.querySelector('[data-realization-assign-remove]')?.classList.toggle('hidden', items.length === 1);
            row.querySelector('[data-realization-assign-add]')?.classList.toggle('hidden', index !== items.length - 1);
        });
    };

    const addRow = (value = '') => {
        const row = pristineRow.cloneNode(true);
        const select = row.querySelector('[data-realization-assign-employee]');
        select.value = value;
        rows.append(row);
        initializeRowSelect(row);
        refreshButtons();
    };

    const resetRows = (assignedEmployeeIds = []) => {
        rows.querySelectorAll('[data-realization-assign-row]').forEach((row) => row.querySelector('[data-realization-assign-employee]')?.tomselect?.destroy());
        rows.replaceChildren();
        const employeeIds = assignedEmployeeIds.length > 0 ? assignedEmployeeIds : [''];
        employeeIds.forEach((employeeId) => addRow(employeeId));
        if (groupSelect?.tomselect) groupSelect.tomselect.clear();
        else if (groupSelect) groupSelect.value = '';
        refreshButtons();
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('grid');
        document.body.classList.remove('overflow-hidden');
    };

    rows.addEventListener('click', (event) => {
        const add = event.target.closest('[data-realization-assign-add]');
        const remove = event.target.closest('[data-realization-assign-remove]');
        if (add) addRow();
        if (remove) {
            const row = remove.closest('[data-realization-assign-row]');
            row?.querySelector('[data-realization-assign-employee]')?.tomselect?.destroy();
            row?.remove();
            refreshButtons();
        }
    });

    modal.querySelector('[data-realization-assign-add-group]')?.addEventListener('click', () => {
        const groupId = groupSelect?.value;
        if (!groupId) {
            Swal.fire({ title: 'Pilih group dulu', text: 'Pilih salah satu group pada dropdown sebelum menekan tombol Group.', icon: 'warning' });
            return;
        }
        const selected = new Set([...rows.querySelectorAll('[data-realization-assign-employee]')].map((select) => select.value).filter(Boolean));
        const toAdd = [...pristineRow.querySelectorAll('option[data-employee-group]')].filter((option) => option.dataset.employeeGroup === groupId && !selected.has(option.value));
        if (toAdd.length === 0) {
            Swal.fire({ title: 'Tidak ada karyawan ditambahkan', text: 'Group ini tidak memiliki karyawan aktif, atau semua anggotanya sudah ada di daftar.', icon: 'info' });
            return;
        }
        toAdd.forEach((option) => addRow(option.value));
        if (groupSelect.tomselect) groupSelect.tomselect.clear();
        else groupSelect.value = '';
    });

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-realization-assign-open]');
        if (!trigger) return;
        event.preventDefault();
        form.action = trigger.dataset.url;
        let assignedEmployeeIds = [];
        try {
            assignedEmployeeIds = JSON.parse(trigger.dataset.assignedEmployeeIds ?? '[]');
        } catch {
            assignedEmployeeIds = [];
        }
        resetRows(assignedEmployeeIds);
        modal.classList.remove('hidden');
        modal.classList.add('grid');
        document.body.classList.add('overflow-hidden');
    });
    modal.querySelectorAll('[data-realization-assign-close]').forEach((button) => button.addEventListener('click', closeModal));
    modal.addEventListener('click', (event) => { if (event.target === modal) closeModal(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeModal(); });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const selected = [...form.querySelectorAll('[data-realization-assign-employee]')].filter((select) => select.value);
        if (selected.length === 0) {
            await Swal.fire({ title: 'Pilih karyawan', text: 'Pilih minimal satu karyawan untuk assignment.', icon: 'warning' });
            return;
        }

        try {
            await $.ajax({ url: form.action, type: 'POST', data: $(form).serializeArray().filter((field) => field.name !== 'employee_ids[]' || field.value), headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }, dataType: 'json' });
            await Swal.fire({ title: 'Berhasil', text: 'Karyawan berhasil di-assign.', icon: 'success' });
            closeModal();
            document.querySelector('[data-server-table]')?._dataTable?.ajax.reload(null, false);
        } catch (error) {
            const validation = Object.values(error.responseJSON?.errors ?? {}).flat().join('\n');
            await Swal.fire({ title: 'Gagal', text: validation || error.responseJSON?.message || 'Assignment tidak dapat disimpan.', icon: 'error' });
        }
    });

    initializeRowSelect(rows.querySelector('[data-realization-assign-row]'));
    refreshButtons();
};

// Quick-fill modal opened from the "Belum dikerjakan" button in the realization list, so an
// employee can submit their work result without leaving the list for the Detail page.
const initializeRealizationFillModal = () => {
    const modal = document.querySelector('[data-realization-fill-modal]');
    const form = modal?.querySelector('[data-realization-fill-form]');
    const unitLabel = modal?.querySelector('[data-realization-fill-unit]');
    if (!modal || !form) return;

    const close = () => {
        modal.classList.add('hidden');
        modal.classList.remove('grid');
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-realization-fill-open]');
        if (!trigger) return;
        form.reset();
        form.action = trigger.dataset.url;
        if (unitLabel) unitLabel.textContent = trigger.dataset.unit || 'Pcs';
        modal.classList.remove('hidden');
        modal.classList.add('grid');
    });
    modal.querySelectorAll('[data-realization-fill-close]').forEach((button) => button.addEventListener('click', close));
    modal.addEventListener('click', (event) => { if (event.target === modal) close(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !modal.classList.contains('hidden')) close(); });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const result = await Swal.fire({ title: 'Kirim hasil pekerjaan?', icon: 'question', showCancelButton: true, confirmButtonText: 'Kirim', cancelButtonText: 'Batal' });
        if (!result.isConfirmed) return;
        if (!(await validateRealizationImage(form))) return;

        try {
            await $.ajax({ url: form.action, type: 'POST', data: new FormData(form), processData: false, contentType: false, headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }, dataType: 'json' });
            close();
            await Swal.fire({ title: 'Berhasil', text: 'Realisasi berhasil dikirim.', icon: 'success' });
            // This modal appears both on the DataTable-driven realization list and on the
            // employee dashboard's plain server-rendered "pending tasks" list — a full
            // reload is the one refresh that's correct on both.
            window.location.reload();
        } catch (error) {
            const validation = Object.values(error.responseJSON?.errors ?? {}).flat().join('\n');
            await Swal.fire({ title: 'Gagal', text: validation || error.responseJSON?.message || 'Data tidak valid.', icon: 'error' });
        }
    });
};

const initializeDeductionCreatePage = () => {
    const form = document.querySelector('[data-deduction-create-form]');
    if (!form) return;
    const selectAll = form.querySelector('[data-deduction-select-all]');
    const employees = [...form.querySelectorAll('[data-deduction-employee]')];
    selectAll?.addEventListener('change', () => employees.forEach((checkbox) => { checkbox.checked = selectAll.checked; }));
    employees.forEach((checkbox) => checkbox.addEventListener('change', () => { if (!checkbox.checked) selectAll.checked = false; else selectAll.checked = employees.every((item) => item.checked); }));
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const result = await Swal.fire({ title: 'Simpan potongan gaji?', icon: 'question', showCancelButton: true, confirmButtonText: 'Simpan', cancelButtonText: 'Batal' });
        if (!result.isConfirmed) return;
        try {
            await $.ajax({ url: form.action, type: 'POST', data: $(form).serialize(), headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }, dataType: 'json' });
            await Swal.fire({ title: 'Berhasil', text: 'Potongan gaji berhasil disimpan.', icon: 'success' });
            window.location.href = form.dataset.redirect;
        } catch (error) {
            const validation = Object.values(error.responseJSON?.errors ?? {}).flat().join('\n');
            await Swal.fire({ title: 'Gagal', text: validation || error.responseJSON?.message || 'Data tidak valid.', icon: 'error' });
        }
    });
};

const initializeSalaryAdvanceFields = () => {
    document.querySelectorAll('[data-salary-advance-fields]').forEach((container) => {
        const typeSelect = container.querySelector('select[name="salary_advance_type"]');
        const valueField = container.querySelector('[data-salary-advance-value]');
        const valueInput = container.querySelector('[data-salary-advance-value-input]');
        const valueLabel = container.querySelector('[data-salary-advance-value-label]');
        const prefix = container.querySelector('[data-rupiah-prefix]');
        const suffix = container.querySelector('[data-salary-advance-suffix]');

        if (!typeSelect || !valueField || !valueInput) return;

        const update = () => {
            const type = typeSelect.value;
            const hasValue = type === 'fixed' || type === 'percentage';
            const isPercentage = type === 'percentage';

            valueField.classList.toggle('hidden', !hasValue);
            valueInput.placeholder = isPercentage ? 'Contoh: 10' : 'Masukkan nilai DP';
            valueLabel.textContent = isPercentage ? 'Nilai DP Gaji (%)' : 'Nilai DP Gaji (Rupiah)';
            prefix?.classList.toggle('hidden', isPercentage);
            suffix?.classList.toggle('hidden', !isPercentage);
        };

        typeSelect.addEventListener('change', update);
        typeSelect.addEventListener('input', update);
        update();
    });
};

const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]);

const initializeDeductionImport = () => {
    const modal = document.querySelector('[data-deduction-import-modal]');
    const form = modal?.querySelector('[data-deduction-import-form]');
    if (!modal || !form) return;

    const close = () => { modal.classList.add('hidden'); modal.classList.remove('grid'); form.reset(); };
    document.querySelector('[data-deduction-import-open]')?.addEventListener('click', () => { modal.classList.remove('hidden'); modal.classList.add('grid'); });
    modal.querySelectorAll('[data-deduction-import-close]').forEach((button) => button.addEventListener('click', close));

    const failuresHtml = (failures) => `<div class="mt-3 max-h-48 overflow-y-auto rounded-lg border border-line text-left text-xs"><table class="w-full">${failures.map((failure) => `<tr class="border-b border-line last:border-0"><td class="whitespace-nowrap px-2 py-1.5 align-top font-semibold text-slate-500">Baris ${escapeHtml(failure.row)}</td><td class="px-2 py-1.5 text-red-600">${escapeHtml(failure.errors.join(', '))}</td></tr>`).join('')}</table></div>`;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        try {
            const response = await $.ajax({ url: form.action, type: 'POST', data: new FormData(form), processData: false, contentType: false, headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }, dataType: 'json' });
            close();
            document.querySelector('[data-server-table]')?._dataTable?.ajax.reload(null, false);
            await Swal.fire({ title: response.failures?.length ? 'Sebagian berhasil' : 'Berhasil', html: `<p class="text-sm">${escapeHtml(response.message)}</p>${response.failures?.length ? failuresHtml(response.failures) : ''}`, icon: response.failures?.length ? 'warning' : 'success' });
        } catch (error) {
            const failures = error.responseJSON?.failures ?? [];
            await Swal.fire({ title: 'Gagal', html: `<p class="text-sm">${escapeHtml(error.responseJSON?.message ?? 'File tidak dapat diimpor.')}</p>${failures.length ? failuresHtml(failures) : ''}`, icon: 'error' });
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    initializeSidebar();
    initializeNavGroups();
    initializePasswordToggle();
    initializeLoginForm();
    initializeLoginAlert();
    initializeFlashAlert();
    initializeDropdowns();
    initializeNotifications();
    initializeGlobalSearch();
    initializeFilterPanel();
    initializeFileUploads();
    initializeProfileAvatarPreview();
    initializeServerTables();
    initializeAjaxDeletes();
    initializeRoleAccessForms();
    initializeUserStatusToggle();
    initializeAccessRolePicker();
    initializeSuperAdminModal();
    initializeUserPasswordReset();
    initializeAccessTabs();
    initializeDatepickers();
    initializeRupiahInputs();
    initializeClientSwitcher();
    initializeTomSelect();
    initializeSelect2();
    initializeRichTextEditors();
    initializeUnitModal();
    initializeManageModals();
    initializeProductDetailModal();
    initializeEmployeeDetailModal();
    initializeClientDetailModal();
    initializeMasterModal({ name: 'cost-center', plural: 'cost-centers', fields: ['code', 'name', 'status'], confirmTitle: 'cost center', successCreate: 'Cost center berhasil ditambahkan.', successUpdate: 'Cost center berhasil diperbarui.' });
    initializeMasterModal({ name: 'group', plural: 'groups', fields: ['code', 'name', 'status'], confirmTitle: 'group', successCreate: 'Group berhasil ditambahkan.', successUpdate: 'Group berhasil diperbarui.' });
    initializeMasterModal({ name: 'shift', plural: 'shifts', fields: ['code', 'name', 'start_time', 'end_time', 'status'], confirmTitle: 'shift', successCreate: 'Shift berhasil ditambahkan.', successUpdate: 'Shift berhasil diperbarui.' });
    initializeMasterModal({ name: 'employee', plural: 'employees', fields: ['employee_no', 'sim_id', 'full_name', 'email', 'phone', 'join_date', 'gender', 'employee_status', 'marital_status', 'group_id'], confirmTitle: 'karyawan', successCreate: 'Karyawan berhasil ditambahkan. Password awal menggunakan password.', successUpdate: 'Karyawan berhasil diperbarui.' });
    initializeMasterModal({ name: 'product', plural: 'products', fields: ['client_id', 'sku', 'name', 'unit_id', 'group_id', 'cost_center_id', 'po_price', 'employee_rate', 'estimated_output_per_hour', 'status'], confirmTitle: 'produk', successCreate: 'Produk berhasil ditambahkan.', successUpdate: 'Produk berhasil diperbarui.' });
    initializeMasterModal({ name: 'client', plural: 'clients', fields: ['code', 'name', 'account_name', 'login_username', 'login_email', 'password', 'password_confirmation', 'status'], confirmTitle: 'client', successCreate: 'Client dan akun login berhasil ditambahkan.', successUpdate: 'Client dan akun login berhasil diperbarui.' });
    initializeRealizationProductPreview();
    initializeRealizationPricePreview();
    initializeRealizationAssignments();
    initializeRealizationCreatePage();
    initializeRealizationSubmitPage();
    initializeRealizationInlineAssignPage();
    initializeRealizationAssignModal();
    initializeRealizationFillModal();
    initializeDeductionCreatePage();
    initializeSalaryAdvanceFields();
    initializeMasterModal({ name: 'deduction-row', plural: 'deductions', fields: ['uniform_amount', 'equipment_amount', 'meal_amount', 'bpjs_health_percent', 'bpjs_employment_percent', 'salary_advance_type', 'salary_advance_value', 'correction_minus', 'correction_plus', 'notes'], confirmTitle: 'potongan gaji', successCreate: 'Potongan gaji berhasil diperbarui.', successUpdate: 'Potongan gaji berhasil diperbarui.' });
    initializeDeductionImport();
    initializeActionButtonStyles();
});
import $ from './jquery-global';
import DataTable from 'datatables.net-dt';
import 'datatables.net-dt/css/dataTables.dataTables.css';
import Swal from 'sweetalert2';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
import { Indonesian } from 'flatpickr/dist/l10n/id.js';
import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.css';
import select2Factory from 'select2/dist/js/select2.js';
import 'select2/dist/css/select2.min.css';
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

select2Factory(window, $);
