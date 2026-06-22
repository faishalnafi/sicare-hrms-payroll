<?php
/**
 * MVC Architecture: View Component
 * OOP Concept: Template rendering inside Custom MVC Framework.
 * 
 * File Connections:
 * - Controller: Loaded dynamically by App\Controllers\DashboardController::genericPage()
 * - Router: Mapped in public/index.php (route prefix /superadmin/)
 * - Layout: Rendered inside resources/views/layouts/app.php
 * - Database: Queries Level 1 departments from the 'departments' table via App\Config\Database
 */
$db = \App\Config\Database::getInstance()->getConnection();
$level1Depts = [];
try {
    $level1Depts = $db->query("SELECT name FROM departments WHERE parent_id IS NULL OR level = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (\Exception $e) {
    // Fail-safe fallback if DB connection fails
}
?>
<div class="space-y-6 animate-fade-in font-body">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Global Approval Center</h1>
            <p class="text-on-surface-variant font-medium text-sm">Pusat validasi dan persetujuan lintas direktorat untuk Superadmin.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="bg-primary/5 text-primary text-xs font-extrabold px-3.5 py-2 rounded-full border border-primary/10 flex items-center gap-1.5 shadow-sm">
                <span class="w-1.5 h-1.5 bg-primary rounded-full animate-pulse"></span>
                Otoritas Superadmin
            </span>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="bg-surface-container-lowest border border-outline-variant/15 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col md:flex-row gap-5 items-end">
            <!-- Left: Search (70%) -->
            <div class="w-full md:w-[70%] space-y-2">
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">Cari Pengajuan</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                    <input type="text" id="approvalSearchInput" oninput="window.filterApprovals()" placeholder="Cari nama pemohon, NIK, atau ID tiket..." class="w-full pl-12 pr-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl text-sm text-on-surface placeholder-on-surface-variant/50 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all duration-200">
                </div>
            </div>

            <!-- Right: Filter (30%) -->
            <div class="w-full md:w-[30%] space-y-2">
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">Filter Direktorat/Konteks</label>
                <div class="relative">
                    <select id="approvalContextFilter" onchange="window.filterApprovals()" class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all duration-200 appearance-none cursor-pointer">
                        <option value="ALL">Semua Approval</option>
                        <?php foreach ($level1Depts as $deptName): ?>
                            <option value="<?php echo htmlspecialchars($deptName); ?>"><?php echo htmlspecialchars($deptName); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-on-surface-variant">keyboard_arrow_down</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="bg-surface-container-lowest border border-outline-variant/15 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant/10 text-on-surface-variant">
                        <th class="py-4 px-6 text-[11px] font-extrabold uppercase tracking-wider">Pemohon</th>
                        <th class="py-4 px-6 text-[11px] font-extrabold uppercase tracking-wider">Tipe Pengajuan</th>
                        <th class="py-4 px-6 text-[11px] font-extrabold uppercase tracking-wider">Tanggal</th>
                        <th class="py-4 px-6 text-[11px] font-extrabold uppercase tracking-wider text-center">Status</th>
                        <th class="py-4 px-6 text-[11px] font-extrabold uppercase tracking-wider text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="approvalTableBody" class="divide-y divide-outline-variant/10">
                    <!-- Loaded dynamically via JS -->
                </tbody>
            </table>
        </div>
        
        <!-- Empty State -->
        <div id="approvalEmptyState" class="hidden text-center py-16 px-4 space-y-3 bg-surface-container-lowest">
            <span class="material-symbols-outlined text-4xl text-on-surface-variant/40">find_in_page</span>
            <h3 class="text-base font-bold text-on-surface">Tidak Ada Pengajuan</h3>
            <p class="text-xs text-on-surface-variant max-w-xs mx-auto">Kami tidak menemukan pengajuan persetujuan yang sesuai dengan filter pencarian Anda.</p>
        </div>
    </div>

    <!-- Unified Detail Modal -->
    <div id="approvalDetailModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-on-surface/60 backdrop-blur-[3px] transition-opacity"></div>

        <!-- Modal Wrapper -->
        <div class="flex min-h-screen items-center justify-center p-4 sm:p-6 lg:p-8">
            <div class="relative transform overflow-hidden rounded-2xl bg-surface-container-lowest border border-outline-variant/15 shadow-2xl transition-all w-full max-w-3xl animate-scale-in">
                
                <!-- Close Button -->
                <button onclick="window.closeApprovalModal()" class="absolute top-4 right-4 p-1.5 hover:bg-surface-container-low text-on-surface-variant rounded-full transition-colors z-10">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>

                <!-- Grid Layout (2 Columns) -->
                <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-outline-variant/10">
                    
                    <!-- Left Column: Read-Only Info -->
                    <div class="p-6 sm:p-8 space-y-6">
                        <div>
                            <h3 class="font-headline text-xl font-extrabold text-on-surface" id="modalTitle">Detail Pengajuan</h3>
                            <p class="text-xs text-on-surface-variant mt-1" id="modalTicketId">Ticket ID: #---</p>
                        </div>

                        <!-- Requester Info -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-extrabold uppercase tracking-wider text-primary">Informasi Pemohon</h4>
                            <div class="flex items-center gap-3 bg-surface-container-low/55 p-3 rounded-xl border border-outline-variant/10">
                                <div class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-sm" id="modalAvatar">
                                    --
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-on-surface truncate" id="modalRequesterName">---</p>
                                    <p class="text-xs text-on-surface-variant truncate" id="modalRequesterEmail">---</p>
                                </div>
                            </div>
                        </div>

                        <!-- Request Content -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-extrabold uppercase tracking-wider text-primary">Detail Konteks</h4>
                            <div class="space-y-2 text-sm text-on-surface">
                                <div>
                                    <span class="text-xs text-on-surface-variant block">Deskripsi:</span>
                                    <p class="font-medium text-xs leading-relaxed text-on-surface-variant" id="modalDescription">---</p>
                                </div>
                                <div class="pt-1 flex items-center justify-between" id="modalAmountContainer">
                                    <span class="text-xs text-on-surface-variant">Nominal Pengajuan:</span>
                                    <span class="font-extrabold text-primary" id="modalAmount">Rp 0</span>
                                </div>
                                <div class="pt-2">
                                    <span class="text-xs text-on-surface-variant block mb-1.5">Lampiran Dokumen:</span>
                                    <div class="flex items-center gap-2 p-2.5 bg-surface-container-low hover:bg-surface-container-high border border-outline-variant/15 rounded-lg cursor-pointer transition-colors">
                                        <span class="material-symbols-outlined text-primary text-lg">description</span>
                                        <span class="text-xs font-bold text-on-surface truncate flex-grow" id="modalAttachment">dokumen_lampiran.pdf</span>
                                        <span class="material-symbols-outlined text-xs text-on-surface-variant">download</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Action Area -->
                    <div class="p-6 sm:p-8 space-y-6 flex flex-col justify-between bg-surface-container-low/30">
                        <div class="space-y-4">
                            <h4 class="text-xs font-extrabold uppercase tracking-wider text-primary">Keputusan Superadmin</h4>
                            
                            <!-- Textarea Notes -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-on-surface-variant">Catatan / Evaluasi</label>
                                <textarea id="modalNotes" rows="5" placeholder="Tuliskan catatan persetujuan atau alasan penolakan di sini..." class="w-full p-3 bg-surface-container-lowest border border-outline-variant/30 rounded-xl text-xs text-on-surface placeholder-on-surface-variant/40 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all duration-200 resize-none"></textarea>
                            </div>
                        </div>

                        <!-- Buttons Group -->
                        <div class="space-y-2.5 pt-4">
                            <button onclick="window.submitApprovalAction('approved')" class="w-full py-3 bg-green-600 hover:bg-green-700 active:bg-green-800 text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center justify-center gap-1.5">
                                <span class="material-symbols-outlined text-base">check_circle</span>
                                Setujui (Approve)
                            </button>
                            <button onclick="window.submitApprovalAction('rejected')" class="w-full py-3 bg-transparent hover:bg-red-50 text-red-600 hover:text-red-700 active:bg-red-100 border border-red-200/80 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5">
                                <span class="material-symbols-outlined text-base">cancel</span>
                                Tolak (Reject)
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    // Static Mock Data representing different directorates
    var mockApprovals = [
        {
            id: "TKT-2026-0001",
            requester: "Dian Yunita Windari",
            email: "datas.nafi@gmail.com",
            nik: "EMP-002",
            type: "Pencairan Anggaran Q3",
            category: "Finance & Investment",
            date: "2026-06-22",
            status: "Pending",
            amount: "Rp 150.000.000",
            description: "Permintaan pencairan dana operasional triwulan ketiga (Q3) untuk operasional divisi Keuangan & Investasi.",
            attachment: "budget_proposal_q3_signed.pdf"
        },
        {
            id: "TKT-2026-0002",
            requester: "Acaa",
            email: "familys.nafi@gmail.com",
            nik: "EMP-003",
            type: "Validasi SOP Pabrik",
            category: "Operational & Corporate",
            date: "2026-06-21",
            status: "Pending",
            amount: null,
            description: "Permintaan validasi dokumen SOP keselamatan kerja dan operasional pabrik perakitan sektor Production & Operations.",
            attachment: "sop_factory_v4.2.pdf"
        },
        {
            id: "TKT-2026-0003",
            requester: "Safa Nabilla Brilian Wihardi",
            email: "safanabilla004@gmail.com",
            nik: "EMP-001",
            type: "Rekrutmen Staf IT",
            category: "Commercial & Marketing",
            date: "2026-06-20",
            status: "Pending",
            amount: null,
            description: "Permintaan persetujuan pembukaan lowongan pekerjaan (requisition) untuk penambahan 3 orang Software Developer divisi Teknologi.",
            attachment: "it_staffing_requisition_rec.pdf"
        },
        {
            id: "TKT-2026-0004",
            requester: "CTO siCare",
            email: "cto@mail.com",
            nik: "EXC-003",
            type: "Akses Server AWS",
            category: "Technology & Engineering",
            date: "2026-06-19",
            status: "Pending",
            amount: null,
            description: "Pemberian hak akses root (administrative access) ke klaster cloud infrastructure AWS produksi untuk tim IT Infrastructure & SecOps.",
            attachment: "aws_access_request_signed.pdf"
        }
    ];

    var activeModalIndex = null;

    /**
     * MVC View Helper: Renders the table body using the approval data array.
     * OOP Concept: Data binding to DOM elements dynamically.
     * Keterhubungan:
     * - Menyambungkan data array mockApprovals ke DOM `#approvalTableBody`
     * - Menyambungkan event klik ke window.openApprovalModal
     * 
     * @param {Array} data
     */
    window.renderApprovalTable = function(data) {
        var tbody = document.getElementById("approvalTableBody");
        var emptyState = document.getElementById("approvalEmptyState");
        if (!tbody) return;

        tbody.innerHTML = "";

        if (data.length === 0) {
            emptyState.classList.remove("hidden");
            return;
        }
        emptyState.classList.add("hidden");

        data.forEach(function(item, idx) {
            var tr = document.createElement("tr");
            tr.className = "hover:bg-surface-container-low/30 transition-colors group";

            // Badge Color Configurations mapping to level 1 departments
            var badgeStyles = {
                "Finance & Investment": "bg-green-100 text-green-700 border-green-200/50",
                "Operational & Corporate": "bg-blue-100 text-blue-700 border-blue-200/50",
                "Commercial & Marketing": "bg-purple-100 text-purple-700 border-purple-200/50",
                "Technology & Engineering": "bg-slate-200 text-slate-700 border-slate-300/50"
            };
            var badgeClass = badgeStyles[item.category] || "bg-primary/5 text-primary border-primary/10";

            // Status Badge Configurations
            var statusClass = "bg-amber-100 text-amber-700 border-amber-200/50";
            if (item.status === "Approved") statusClass = "bg-green-100 text-green-700 border-green-200/50";
            if (item.status === "Rejected") statusClass = "bg-red-100 text-red-700 border-red-200/50";

            tr.innerHTML = 
                "<td class=\"py-4 px-6\">" +
                    "<div class=\"flex items-center gap-3\">" +
                        "<div class=\"w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs flex-shrink-0\">" +
                            item.requester.charAt(0).toUpperCase() +
                        "</div>" +
                        "<div class=\"min-w-0\">" +
                            "<p class=\"text-xs font-bold text-on-surface truncate\">" + escapeHtml(item.requester) + "</p>" +
                            "<p class=\"text-[10px] text-on-surface-variant font-mono mt-0.5\">" + escapeHtml(item.nik) + "</p>" +
                        "</div>" +
                    "</div>" +
                "</td>" +
                "<td class=\"py-4 px-6\">" +
                    "<div class=\"flex flex-col gap-1 items-start\">" +
                        "<span class=\"text-xs font-semibold text-on-surface\">" + escapeHtml(item.type) + "</span>" +
                        "<span class=\"text-[9px] font-extrabold border px-2 py-0.5 rounded-full uppercase " + badgeClass + "\">" + item.category + "</span>" +
                    "</div>" +
                "</td>" +
                "<td class=\"py-4 px-6\">" +
                    "<span class=\"text-xs font-semibold text-on-surface-variant\">" + escapeHtml(item.date) + "</span>" +
                "</td>" +
                "<td class=\"py-4 px-6 text-center\">" +
                    "<span class=\"text-[10px] font-bold border px-2.5 py-1 rounded-full uppercase inline-block " + statusClass + "\">" + item.status + "</span>" +
                "</td>" +
                "<td class=\"py-4 px-6 text-center\">" +
                    "<button onclick=\"window.openApprovalModal('" + item.id + "')\" class=\"px-3.5 py-2 text-xs font-bold text-primary hover:text-white bg-transparent hover:bg-primary border border-primary/20 rounded-xl transition-all shadow-sm flex items-center gap-1.5 mx-auto\">" +
                        "<span class=\"material-symbols-outlined text-[14px]\">visibility</span> Tinjau Detail" +
                    "</button>" +
                "</td>";
            
            tbody.appendChild(tr);
        });
    };

    /**
     * MVC View Logic: Handles client-side filtering based on search text and selected Level 1 department.
     * OOP Concept: Method binding on window scope to handle layout events.
     * Keterhubungan:
     * - Berikatan dengan input `#approvalSearchInput` (event oninput)
     * - Berikatan dengan select `#approvalContextFilter` (event onchange)
     */
    window.filterApprovals = function() {
        var query = (document.getElementById("approvalSearchInput").value || "").trim().toLowerCase();
        var context = document.getElementById("approvalContextFilter").value;

        var filtered = mockApprovals.filter(function(item) {
            var matchQuery = item.requester.toLowerCase().includes(query) || 
                             item.nik.toLowerCase().includes(query) || 
                             item.type.toLowerCase().includes(query) || 
                             item.id.toLowerCase().includes(query);

            var matchContext = true;
            if (context !== "ALL") {
                matchContext = (item.category === context);
            }

            return matchQuery && matchContext;
        });

        window.renderApprovalTable(filtered);
    };

    /**
     * MVC View UI Action: Fills data and displays the detailed review modal.
     * OOP Concept: DOM state mutation.
     * Keterhubungan:
     * - Dipanggil saat tombol Tinjau Detail di baris tabel ditekan
     * - Membuka `#approvalDetailModal` dan memetakan data detail pengajuan
     * 
     * @param {string} id
     */
    window.openApprovalModal = function(id) {
        var itemIdx = mockApprovals.findIndex(function(x) { return x.id === id; });
        if (itemIdx === -1) return;
        
        var item = mockApprovals[itemIdx];
        activeModalIndex = itemIdx;
        
        document.getElementById("modalTitle").textContent = item.type;
        document.getElementById("modalTicketId").textContent = "Ticket ID: #" + item.id;
        document.getElementById("modalAvatar").textContent = item.requester.charAt(0).toUpperCase();
        document.getElementById("modalRequesterName").textContent = item.requester;
        document.getElementById("modalRequesterEmail").textContent = item.email;
        document.getElementById("modalDescription").textContent = item.description;
        document.getElementById("modalAttachment").textContent = item.attachment;
        document.getElementById("modalNotes").value = "";

        var amtContainer = document.getElementById("modalAmountContainer");
        if (item.amount) {
            amtContainer.classList.remove("hidden");
            document.getElementById("modalAmount").textContent = item.amount;
        } else {
            amtContainer.classList.add("hidden");
        }

        // Show Modal
        document.getElementById("approvalDetailModal").classList.remove("hidden");
    };

    /**
     * MVC View UI Action: Closes and resets the detailed review modal.
     * OOP Concept: DOM state mutation.
     * Keterhubungan:
     * - Dipanggil oleh tombol close (X) dan penutup modal
     * - Menyembunyikan `#approvalDetailModal`
     */
    window.closeApprovalModal = function() {
        document.getElementById("approvalDetailModal").classList.add("hidden");
        activeModalIndex = null;
    };

    /**
     * MVC Controller Call Simulation: Processes approval or rejection decisions.
     * OOP Concept: Procedural callback with modal notification validation.
     * Keterhubungan:
     * - Dipanggil dari tombol Setujui / Tolak di dalam modal
     * - Terhubung dengan SweetAlert2 untuk feedback modal
     * 
     * @param {string} action
     */
    window.submitApprovalAction = function(action) {
        if (activeModalIndex === null) return;
        var item = mockApprovals[activeModalIndex];
        var notes = document.getElementById("modalNotes").value.trim();

        if (action === "rejected" && notes === "") {
            Swal.fire({
                title: "Alasan Penolakan Wajib",
                text: "Harap tuliskan catatan alasan penolakan di area keputusan.",
                icon: "warning",
                confirmButtonColor: "#000666"
            });
            return;
        }

        // Apply visual updates to our static mock data
        item.status = (action === "approved") ? "Approved" : "Rejected";

        Swal.fire({
            title: action === "approved" ? "Persetujuan Berhasil" : "Pengajuan Ditolak",
            text: "Pengajuan \"" + item.type + "\" telah berhasil diproses.",
            icon: "success",
            confirmButtonColor: "#000666"
        }).then(function() {
            window.closeApprovalModal();
            window.filterApprovals(); // Refresh table view
        });
    };

    /**
     * Utility Helper: Escapes HTML strings to prevent Cross-Site Scripting (XSS).
     * OOP Concept: Pure utility function.
     * 
     * @param {string} str
     * @return {string}
     */
    function escapeHtml(str) {
        if (!str) return "";
        return str.replace(/&/g, "&amp;")
                  .replace(/</g, "&lt;")
                  .replace(/>/g, "&gt;")
                  .replace(/"/g, "&quot;")
                  .replace(/'/g, "&#039;");
    }

    // Auto-render table data on page load
    setTimeout(function() {
        window.renderApprovalTable(mockApprovals);
    }, 100);

})();
</script>
