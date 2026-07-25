<script setup>
import "vue3-toastify/dist/index.css";
import { ref, watch, computed } from "vue";
import { Head, router, Link, usePage, useForm } from "@inertiajs/vue3";
import { toast } from "vue3-toastify";
import { debounce } from "lodash";
import axios from "axios";
import dayjs from "dayjs";
import "dayjs/locale/id";

dayjs.locale("id");

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    roles: {
        type: Object,
        default: () => ({ data: [], links: [], per_page: 10 }),
    },
    permissions: {
        type: Array,
        default: () => [],
    },
});

const searchQuery = ref("");
const selectedIds = ref([]);
const isRefreshing = ref(false);
const showConfirmModal = ref(false);
const isSearching = ref(false);
const deleteReason = ref("");
const isHistoryModalOpen = ref(false);
const historyLogs = ref([]);
const isLoadingHistory = ref(false);
const selectedRoleName = ref("");

const deleteForm = useForm({
    ids: [],
    remark: "",
});

const formatLogDate = (date) => {
    if (!date) return "-";
    return dayjs(date).format("DD MMM YYYY, HH:mm:ss [WIB]");
};

// Setup untuk "Per Page"
const perPage = ref(props.roles?.per_page || 10);

// State untuk Drawer
const isDrawerOpen = ref(false);
const selectedRole = ref(null);
const isEditMode = computed(() => selectedRole.value !== null);

// State Pencarian Modul di Drawer
const moduleSearchQuery = ref("");

const form = useForm({
    name: "",
    permissions: [],
    remark: "",
});

// ==========================================
// COMPUTED & METHODS UNTUK GROUPED PERMISSION
// ==========================================
const groupedPermissions = computed(() => {
    const groups = {};
    props.permissions.forEach((permission) => {
        const parts = permission.name.split(" ");
        const action = parts[0];
        const module = parts.slice(1).join(" ");

        if (!groups[module]) {
            groups[module] = [];
        }
        groups[module].push(permission);
    });
    return groups;
});

// Filter Modul berdasarkan Kotak Pencarian
const filteredGroupedPermissions = computed(() => {
    if (!moduleSearchQuery.value) return groupedPermissions.value;

    const query = moduleSearchQuery.value.toLowerCase();
    const filtered = {};

    for (const [moduleName, perms] of Object.entries(groupedPermissions.value)) {
        if (moduleName.toLowerCase().includes(query)) {
            filtered[moduleName] = perms;
        }
    }

    return filtered;
});

const toggleModulePermissions = (moduleName, event) => {
    const modulePerms = groupedPermissions.value[moduleName].map((p) => p.name);

    if (event.target.checked) {
        modulePerms.forEach((p) => {
            if (!form.permissions.includes(p)) form.permissions.push(p);
        });
    } else {
        form.permissions = form.permissions.filter((p) => !modulePerms.includes(p));
    }
};

const isModuleAllSelected = (moduleName) => {
    if (!groupedPermissions.value[moduleName]) return false;
    const modulePerms = groupedPermissions.value[moduleName].map((p) => p.name);
    return modulePerms.length > 0 && modulePerms.every((p) => form.permissions.includes(p));
};
// ==========================================

const refreshTable = () => {
    isRefreshing.value = true;
    router.reload({
        only: ["roles"],
        onSuccess: () => {
            isRefreshing.value = false;
        },
        onError: () => {
            isRefreshing.value = false;
            toast.error("Failed to refresh data");
        },
    });
};

const toggleSelectAll = (event) => {
    selectedIds.value = event.target.checked
        ? props.roles.data.map((r) => r.id)
        : [];
};

const openCreateDrawer = () => {
    selectedRole.value = null;
    form.reset();
    form.clearErrors();
    moduleSearchQuery.value = "";
    isDrawerOpen.value = true;
};

const openEditDrawer = (role) => {
    selectedRole.value = role;
    form.clearErrors();

    form.name = role.name ?? "";
    form.permissions = role.permissions ? role.permissions.map((p) => p.name) : [];
    form.remark = "";
    moduleSearchQuery.value = "";

    isDrawerOpen.value = true;
};

const submitForm = () => {
    if (isEditMode.value) {
        if (!form.remark) {
            form.setError("remark", "Please fill the update change reason");
            return;
        }
        form.put(`/roles/${selectedRole.value.id}`, {
            onSuccess: () => {
                isDrawerOpen.value = false;
                form.reset();
            },
            onError: () => toast.error("Please check the form errors"),
        });
    } else {
        form.post("/roles", {
            onSuccess: () => {
                isDrawerOpen.value = false;
                form.reset();
            },
            onError: () => toast.error("Please check the form errors"),
        });
    }
};

const deleteSelected = () => {
    if (selectedIds.value.length > 0) showConfirmModal.value = true;
};

const confirmAction = () => {
    deleteForm.clearErrors();

    if (!deleteReason.value || deleteReason.value.trim() === "") {
        deleteForm.setError("remark", "This field is required");
        return;
    }

    deleteForm.ids = selectedIds.value;
    deleteForm.remark = deleteReason.value;

    deleteForm.post("/roles/mass-delete", {
        preserveScroll: true,
        onSuccess: () => {
            selectedIds.value = [];
            showConfirmModal.value = false;
            deleteReason.value = "";
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            toast.error(firstError || "Failed to delete items");
        },
    });
};

const updatePerPage = () => {
    router.get(
        window.location.pathname,
        {
            per_page: perPage.value,
            search: searchQuery.value,
        },
        {
            preserveState: true,
            onStart: () => (isSearching.value = true),
            onFinish: () => (isSearching.value = false),
        }
    );
};

watch(
    searchQuery,
    debounce((value) => {
        router.get(
            window.location.pathname,
            {
                search: value,
                per_page: perPage.value,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onStart: () => (isSearching.value = true),
                onFinish: () => (isSearching.value = false),
            }
        );
    }, 300)
);

const openHistoryModal = async (id, name) => {
    isHistoryModalOpen.value = true;
    isLoadingHistory.value = true;
    selectedRoleName.value = name;
    historyLogs.value = [];

    try {
        const response = await axios.get(`/roles/${id}/logs`);
        historyLogs.value = response.data;
    } catch (error) {
        toast.error("Failed to load revision history data.");
    } finally {
        isLoadingHistory.value = false;
    }
};

const closeHistoryModal = () => {
    isHistoryModalOpen.value = false;
    selectedRoleName.value = "";
    historyLogs.value = [];
};

const formatDisplayValue = (val) => {
    if (val === null || val === undefined || val === '') return '-';
    if (typeof val === 'object' && !Array.isArray(val)) return JSON.stringify(val);
    return String(val);
};

const getChangedFields = (log) => {
    const ignoredKeys = ["id", "uuid", "created_at", "updated_at", "deleted_at", "guard_name", "revision", "pivot"];
    const changes = [];

    const beforeData = Array.isArray(log.before) ? log.before[0] : (log.before || {});
    const afterData = log.after || {};

    if (log.event_name === "update") {
        Object.keys(afterData).forEach((key) => {
            if (ignoredKeys.includes(key)) return;

            const valBefore = beforeData[key];
            const valAfter = afterData[key];

            if (JSON.stringify(valBefore) !== JSON.stringify(valAfter)) {
                changes.push({ field: key, before: valBefore, after: valAfter });
            }
        });
    } else if (log.event_name === "delete") {
        Object.keys(beforeData).forEach((key) => {
            if (!ignoredKeys.includes(key) && beforeData[key] !== null) {
                changes.push({ field: key, before: beforeData[key], after: null });
            }
        });
    } else if (log.event_name === "create") {
        Object.keys(afterData).forEach((key) => {
            if (!ignoredKeys.includes(key) && afterData[key] !== null) {
                changes.push({ field: key, before: null, after: afterData[key] });
            }
        });
    }

    return changes;
};

const formatFieldName = (text) => {
    if (!text) return "";
    return text
        .split("_")
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(" ");
};
</script>

<template>
    <Head title="Role & Permission Management" />
    <div class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800 p-6">
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
            <!-- HEADER SECTION -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                        Role & Permission Management
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Manage user security roles and authorization privileges.
                    </p>
                </div>
                <div class="sticky top-0 z-30 bg-white/95 backdrop-blur-sm border border-slate-200 px-3 py-2 shadow-sm">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-1.5">
                            <!-- Button New -->
                            <button
                                @click="openCreateDrawer"
                                type="button"
                                class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-blue-600 bg-blue-50 border border-blue-100 transition-all hover:bg-blue-100 active:scale-95 shadow-sm"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-3.5 w-3.5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                    />
                                </svg>
                                <span>New Role</span>
                            </button>

                            <!-- Button Refresh -->
                            <button
                                type="button"
                                @click="refreshTable"
                                :disabled="isRefreshing"
                                class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-slate-600 bg-orange-50 border border-slate-200 transition-all hover:bg-orange-100 active:scale-95 shadow-sm disabled:opacity-60"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    :class="isRefreshing ? 'animate-spin text-blue-600' : 'text-slate-500'"
                                    class="h-3.5 w-3.5 transition-colors duration-150"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"
                                    />
                                </svg>
                                <span>{{ isRefreshing ? "Refreshing..." : "Refresh" }}</span>
                            </button>

                            <!-- Button Delete -->
                            <button
                                type="button"
                                @click="deleteSelected"
                                :disabled="selectedIds.length === 0"
                                class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-rose-600 bg-rose-50 border border-rose-100 transition-all hover:bg-rose-100 active:scale-95 shadow-sm disabled:opacity-40 disabled:active:scale-100"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    />
                                </svg>
                                <span>Delete ({{ selectedIds.length }})</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-1 flex flex-col">
                <!-- SEARCH & FILTER SECTION -->
                <div class="bg-white border border-slate-200/80 shadow-sm p-4 mb-6 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
                    <div class="relative flex-1 max-w-md">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <svg
                                v-if="isSearching"
                                class="animate-spin h-4 w-4 text-blue-600"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg
                                v-else
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4 transition-all"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input
                            type="text"
                            placeholder="Search roles..."
                            v-model="searchQuery"
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 focus:border-blue-500 text-xs font-semibold focus:outline-none transition"
                        />
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Per Page:</label>
                            <select
                                v-model="perPage"
                                @change="updatePerPage"
                                class="px-3 py-2.5 bg-white border border-slate-200 text-slate-700 text-xs font-semibold focus:outline-none focus:border-blue-500 cursor-pointer transition"
                            >
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- TABLE SECTION -->
                <div class="bg-white border border-slate-200/80 shadow-sm overflow-hidden mb-8">
                    <div class="overflow-auto max-h-[calc(100vh-320px)]">
                        <table class="w-full text-left border-collapse bg-white whitespace-nowrap">
                            <thead class="bg-blue-300 text-slate-700 uppercase tracking-wider text-[11px] font-bold border-b border-slate-200 sticky top-0 z-10">
                                <tr>
                                    <th class="px-4 py-3 w-12 text-center">
                                        <input
                                            type="checkbox"
                                            @change="toggleSelectAll"
                                            class="border-slate-300 text-blue-600 h-4 w-4 cursor-pointer"
                                        />
                                    </th>
                                    <th class="px-4 py-3">Role Name</th>
                                    <th class="px-4 py-3">Assigned Permissions</th>
                                    <th class="px-4 py-3 w-16 text-center">Logs</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs text-slate-600">
                                <tr
                                    v-for="role in roles.data"
                                    :key="role.id"
                                    @click="openEditDrawer(role)"
                                    class="hover:bg-blue-50 transition-colors cursor-pointer"
                                >
                                    <td class="px-4 py-3 text-center" @click.stop>
                                        <input
                                            type="checkbox"
                                            v-model="selectedIds"
                                            :value="role.id"
                                            class="border-slate-300 text-blue-600 h-4 w-4 cursor-pointer"
                                        />
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-slate-800">
                                        {{ role.name }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-1 max-w-xl">
                                            <span
                                                v-for="p in role.permissions"
                                                :key="p.id"
                                                class="px-2 py-0.5 bg-slate-100 text-slate-700 text-[10px] font-medium border border-slate-200"
                                            >
                                                {{ p.name }}
                                            </span>
                                            <span v-if="!role.permissions || role.permissions.length === 0" class="text-slate-400 italic text-[11px]">
                                                No permissions assigned
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center" @click.stop>
                                        <button
                                            type="button"
                                            @click="openHistoryModal(role.id, role.name)"
                                            class="text-xs font-semibold text-blue-600 hover:text-blue-800 hover:underline hover:cursor-pointer transition-colors"
                                        >
                                            View Logs
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="roles.data.length === 0">
                                    <td colspan="4" class="px-4 py-8 text-center text-slate-400 font-medium">
                                        No role data found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINATION -->
                    <div
                        v-if="roles.total > 0"
                        class="px-6 py-4 bg-white border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4"
                    >
                        <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                            Showing {{ roles.from ?? 0 }} to {{ roles.to ?? 0 }} of {{ roles.total ?? 0 }}
                        </div>

                        <div class="flex items-center gap-1">
                            <template v-for="(link, index) in roles.links" :key="index">
                                <Link
                                    v-if="link.label.includes('Previous') || link.label.includes('Next')"
                                    :href="link.url ?? '#'"
                                    v-html="link.label"
                                    preserve-scroll
                                    class="px-2 py-1 text-xs font-bold text-slate-500 hover:text-slate-900 transition"
                                    :class="{ 'opacity-30 cursor-not-allowed': !link.url }"
                                />
                                <Link
                                    v-else
                                    :href="link.url ?? '#'"
                                    v-html="link.label"
                                    preserve-scroll
                                    class="px-3 py-1.5 text-xs font-bold border transition-all"
                                    :class="
                                        link.active
                                            ? 'bg-blue-600 text-white border-blue-600'
                                            : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100'
                                    "
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- SLIDEOVER / DRAWER FORM                    -->
    <!-- ========================================== -->
    <div v-show="isDrawerOpen" class="fixed inset-0 z-40 overflow-hidden" role="dialog" aria-modal="true">
        <div class="absolute inset-0 overflow-hidden">
            <Transition
                enter-active-class="ease-in-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in-out duration-300"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-show="isDrawerOpen" @click="isDrawerOpen = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
            </Transition>

            <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                <Transition
                    enter-active-class="transform transition ease-in-out duration-300"
                    enter-from-class="translate-x-full"
                    enter-to-class="translate-x-0"
                    leave-active-class="transform transition ease-in-out duration-300"
                    leave-from-class="translate-x-0"
                    leave-to-class="translate-x-full"
                >
                    <div v-show="isDrawerOpen" class="pointer-events-auto w-screen max-w-2xl bg-white shadow-2xl flex flex-col h-full border-l border-slate-200">
                        <div class="bg-slate-900 px-6 py-5 flex items-center justify-between shrink-0">
                            <div>
                                <h2 class="text-base font-black text-white tracking-tight">
                                    {{ isEditMode ? "Edit User Role" : "Register New Role" }}
                                </h2>
                                <p class="text-[10px] text-slate-400 mt-0.5">Authorization & Security Control</p>
                            </div>
                            <button
                                type="button"
                                @click="isDrawerOpen = false"
                                class="text-slate-400 hover:text-white hover:bg-slate-800 p-1.5 transition"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <form @submit.prevent="submitForm" class="flex-1 p-6 overflow-y-auto space-y-6 bg-slate-50/50">
                            <!-- Section 1: Role Details -->
                            <div class="space-y-4">
                                <h4 class="text-[10px] font-bold text-blue-600 uppercase tracking-wide border-b border-slate-300 pb-1">
                                    1. Role Information
                                </h4>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                        Role Name <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        v-model="form.name"
                                        required
                                        placeholder="e.g. supervisor, manager..."
                                        :class="[
                                            'w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all',
                                            form.errors.name
                                                ? 'border-rose-500 focus:border-rose-500 text-rose-600'
                                                : 'border-slate-300 focus:border-blue-500 text-slate-800',
                                        ]"
                                    />
                                    <p v-if="form.errors.name" class="mt-1 text-[10px] font-bold text-rose-500">
                                        {{ form.errors.name }}
                                    </p>
                                </div>
                            </div>

                            <!-- Section 2: Assign Permissions -->
                            <div class="space-y-3">
                                <h4 class="text-[10px] font-bold text-blue-600 uppercase tracking-wide border-b border-slate-300 pb-1">
                                    2. Assign Permissions
                                </h4>

                                <!-- SEARCH BAR KHUSUS MODULE -->
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </span>
                                    <input
                                        type="text"
                                        v-model="moduleSearchQuery"
                                        placeholder="Cari modul (e.g. users, projects)..."
                                        class="w-full pl-8 pr-3 py-1.5 bg-white border border-slate-300 text-[11px] font-medium focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-slate-800 transition-all placeholder:text-slate-400"
                                    />
                                </div>

                                <!-- Kotak Scroll yang Berisi Tabel Modul -->
                                <div class="bg-white border border-slate-200 divide-y divide-slate-100 max-h-80 overflow-y-auto shadow-inner relative">

                                    <!-- Pesan Jika Modul Tidak Ditemukan -->
                                    <div v-if="Object.keys(filteredGroupedPermissions).length === 0" class="p-4 text-center text-[11px] text-slate-400 font-medium">
                                        Modul "{{ moduleSearchQuery }}" tidak ditemukan.
                                    </div>

                                    <!-- Looping berdasarkan Modul yang sudah di-filter -->
                                    <div
                                        v-for="(perms, moduleName) in filteredGroupedPermissions"
                                        :key="moduleName"
                                        class="p-4 hover:bg-slate-50 transition-colors"
                                    >
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="text-xs font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                                </svg>
                                                Modul: {{ moduleName }}
                                            </span>

                                            <!-- Checkbox Select All -->
                                            <label class="flex items-center gap-1.5 cursor-pointer bg-slate-100 px-2 py-1 rounded border border-slate-200 hover:bg-slate-200 transition">
                                                <input
                                                    type="checkbox"
                                                    class="border-slate-300 text-blue-600 h-3.5 w-3.5 cursor-pointer rounded-sm focus:ring-blue-500"
                                                    :checked="isModuleAllSelected(moduleName)"
                                                    @change="toggleModulePermissions(moduleName, $event)"
                                                />
                                                <span class="text-[9px] font-bold text-slate-600 uppercase">Select All</span>
                                            </label>
                                        </div>

                                        <!-- Daftar Checkbox Permission per Modul -->
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 ml-1">
                                            <label
                                                v-for="permission in perms"
                                                :key="permission.id"
                                                class="flex items-center space-x-2 text-[11px] text-slate-700 cursor-pointer p-1 rounded hover:bg-slate-100"
                                            >
                                                <input
                                                    type="checkbox"
                                                    :value="permission.name"
                                                    v-model="form.permissions"
                                                    class="border-slate-300 text-blue-600 h-3.5 w-3.5 cursor-pointer rounded-sm focus:ring-blue-500"
                                                />
                                                <span class="font-semibold capitalize text-slate-600">
                                                    {{ permission.name.split(' ')[0].replace(/_/g, ' ') }}
                                                </span>
                                            </label>
                                        </div>
                                    </div>

                                </div>
                                <p v-if="form.errors.permissions" class="text-[10px] font-bold text-rose-500">
                                    {{ form.errors.permissions }}
                                </p>
                            </div>

                            <!-- Section 3: Audit Remark -->
                            <div class="space-y-4 pb-4">
                                <h4 class="text-[10px] font-bold text-emerald-600 uppercase tracking-wide border-b border-slate-300 pb-1">
                                    3. Audit Trail & Remarks
                                </h4>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                        Change Reason / Remark
                                        <span v-if="isEditMode" class="text-rose-500 font-black">* (Required)</span>
                                        <span v-else class="text-slate-400 font-medium italic">(Optional)</span>
                                    </label>
                                    <textarea
                                        v-model="form.remark"
                                        rows="2"
                                        :required="isEditMode"
                                        :placeholder="isEditMode ? 'Wajib isi alasan perubahan role...' : 'Masukkan catatan tambahan...'"
                                        :class="[
                                            'w-full pl-3 pr-3 py-2 bg-white border text-xs font-medium focus:outline-none transition-all',
                                            form.errors.remark
                                                ? 'border-rose-500 text-rose-600'
                                                : 'border-slate-300 focus:border-blue-500 text-slate-800',
                                        ]"
                                    ></textarea>
                                    <p v-if="form.errors.remark" class="mt-1 text-[10px] font-bold text-rose-500">
                                        {{ form.errors.remark }}
                                    </p>
                                </div>
                            </div>
                        </form>

                        <div class="px-6 py-4 bg-white border-t border-slate-200 flex justify-end gap-3 shrink-0">
                            <button
                                type="button"
                                @click="isDrawerOpen = false"
                                class="px-5 py-2.5 text-xs font-bold text-slate-600 bg-white border border-slate-300 hover:bg-slate-50 active:scale-95 transition"
                            >
                                Cancel
                            </button>
                            <button
                                @click="submitForm"
                                :disabled="form.processing"
                                class="px-6 py-2.5 bg-blue-600 text-white font-bold text-xs shadow-md hover:bg-blue-700 transition-all active:scale-95 disabled:opacity-50 flex items-center gap-2"
                            >
                                <svg v-if="form.processing" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ form.processing ? "Saving..." : isEditMode ? "Apply Changes" : "Save Role" }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>
    </div>

    <!-- Modal History Change Logs -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
    >
        <div v-show="isHistoryModalOpen" @click.self="closeHistoryModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
            <div class="bg-white w-full max-w-3xl border border-slate-200 shadow-2xl flex flex-col max-h-[90vh] overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-white shrink-0">
                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 text-blue-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Role Revision History
                    </h3>
                    <button @click="closeHistoryModal" class="text-slate-400 hover:text-rose-600 p-1 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="max-h-[65vh] overflow-y-auto flex-1 px-6 py-6 bg-slate-50/60 divide-y divide-slate-200/60">
                    <div v-if="isLoadingHistory" class="flex flex-col items-center justify-center py-12 gap-3">
                        <div class="animate-spin h-7 w-7 border-b-2 border-blue-600"></div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Loading system logs...</span>
                    </div>

                    <div v-else-if="historyLogs.length === 0" class="text-center py-12 border border-dashed border-slate-200 bg-white p-8">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">No History Records</span>
                        <p class="text-[11px] text-slate-400 mt-0.5">This role profile has no recorded changes.</p>
                    </div>

                    <div v-else class="relative border-l-2 border-slate-200 ml-3 space-y-8 pb-4">
                        <div v-for="(log, index) in historyLogs" :key="log.id" class="relative pl-6">
                            <div
                                :class="{
                                    'bg-emerald-500 border-emerald-100 ring-4 ring-emerald-50': log.event_name === 'create',
                                    'bg-blue-600 border-blue-100 ring-4 ring-blue-50': log.event_name === 'update' && index === 0,
                                    'bg-slate-400 border-white': log.event_name === 'update' && index !== 0,
                                    'bg-rose-500 border-rose-100 ring-4 ring-rose-50': log.event_name === 'delete',
                                }"
                                class="absolute w-3.5 h-3.5 -left-[8px] top-1 border-2 shadow-sm transition-all"
                            ></div>

                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-2 gap-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-mono font-bold uppercase px-2 py-0.5 border bg-white shadow-sm text-slate-700">
                                        Rev. {{ log.revision ?? 1 }}
                                    </span>
                                    <span
                                        :class="{
                                            'bg-emerald-50 text-emerald-700 border-emerald-200': log.event_name === 'create',
                                            'bg-blue-50 text-blue-700 border-blue-200': log.event_name === 'update',
                                            'bg-rose-50 text-rose-700 border-rose-200': log.event_name === 'delete',
                                        }"
                                        class="text-[9px] font-bold uppercase px-1.5 py-0.5 border tracking-wide"
                                    >
                                        {{ log.event_name }}
                                    </span>
                                    <span class="text-xs font-bold text-slate-900">
                                        {{ log.creator?.name || 'System Auto' }}
                                    </span>
                                </div>
                                <span class="text-[10px] font-mono font-bold text-slate-400 uppercase tracking-wider">
                                    {{ formatLogDate(log.created_at) }}
                                </span>
                            </div>

                            <div class="bg-white p-4 border border-slate-200 shadow-sm space-y-3">
                                <div>
                                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block mb-0.5">Change Reason</span>
                                    <p class="text-xs font-bold text-slate-800 leading-relaxed whitespace-pre-line">
                                        {{ log.change_reason || 'No description provided.' }}
                                    </p>
                                </div>

                                <div v-if="getChangedFields(log).length > 0" class="pt-2 border-t border-slate-100 overflow-x-auto">
                                    <table class="min-w-full text-[11px] font-mono">
                                        <thead>
                                            <tr class="text-slate-400 border-b border-slate-100 text-left font-bold uppercase tracking-wider text-[10px]">
                                                <th class="pb-1.5 w-1/4">Field Data</th>
                                                <th class="pb-1.5 w-3/8 text-rose-600" v-if="log.event_name !== 'create'">Data Before</th>
                                                <th class="pb-1.5 w-3/8 text-emerald-600" v-if="log.event_name !== 'delete'">Data After</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-50 text-slate-600 font-medium">
                                            <tr v-for="item in getChangedFields(log)" :key="item.field" class="hover:bg-slate-50/50">
                                                <td class="py-2 font-bold text-slate-500 w-1/4">
                                                    {{ formatFieldName(item.field) }}
                                                </td>
                                                <td class="py-2 pr-2 w-3/8" v-if="log.event_name !== 'create'">
                                                    <div class="flex flex-wrap gap-1">
                                                        <template v-if="Array.isArray(item.before)">
                                                            <span v-for="val in item.before" :key="val.id || val" class="bg-slate-100 text-slate-500 px-1.5 py-0.5 text-[9px] font-bold uppercase line-through border border-slate-200">
                                                                {{ (val.name || val).toString().replace(/_/g, ' ') }}
                                                            </span>
                                                            <span v-if="item.before.length === 0" class="text-slate-400 italic text-[10px]">-</span>
                                                        </template>
                                                        <span v-else class="bg-rose-50 text-rose-700 px-1.5 py-0.5 text-[10px] line-through block w-fit max-w-xs truncate" :title="String(item.before)">
                                                            {{ formatDisplayValue(item.before) }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="py-2 w-3/8" v-if="log.event_name !== 'delete'">
                                                    <div class="flex flex-wrap gap-1">
                                                        <template v-if="Array.isArray(item.after)">
                                                            <span v-for="val in item.after" :key="val.id || val" class="bg-emerald-50 text-emerald-700 px-1.5 py-0.5 text-[9px] font-bold uppercase border border-emerald-200 shadow-sm">
                                                                {{ (val.name || val).toString().replace(/_/g, ' ') }}
                                                            </span>
                                                            <span v-if="item.after.length === 0" class="text-slate-400 italic text-[10px]">(Empty / Cleared)</span>
                                                        </template>
                                                        <span v-else class="bg-emerald-50 text-emerald-700 px-1.5 py-0.5 text-[10px] font-bold block w-fit max-w-xs truncate" :title="String(item.after)">
                                                            {{ formatDisplayValue(item.after) }}
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-100 px-6 py-3.5 bg-white flex justify-end shrink-0">
                    <button
                        type="button"
                        @click="closeHistoryModal"
                        class="px-5 py-2 bg-slate-100 border border-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-200 transition active:scale-95 shadow-sm"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Modal Konfirmasi Delete -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
    >
        <div v-if="showConfirmModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
            <div class="bg-white w-full max-w-sm border border-slate-200 shadow-2xl p-6">
                <div class="flex flex-col text-left">
                    <h3 class="text-lg font-black text-slate-900 mb-1">Confirm Deletion</h3>
                    <p class="text-xs text-slate-500 mb-4">
                        Are you sure you want to delete <span class="font-bold text-slate-900">{{ selectedIds.length }} items</span>? This action cannot be undone.
                    </p>

                    <div class="mb-6">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">
                            Reason for Deletion <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            v-model="deleteReason"
                            rows="2"
                            class="w-full p-2 border text-xs focus:outline-none focus:ring-1 transition-colors"
                            :class="deleteForm.errors.remark ? 'border-rose-500 focus:ring-rose-500' : 'border-slate-300 focus:border-blue-500 focus:ring-blue-500'"
                            placeholder="e.g. Obsolete role structure, security update..."
                            @input="deleteForm.clearErrors('remark')"
                        ></textarea>
                        <p v-if="deleteForm.errors.remark" class="mt-1 text-[10px] font-bold text-rose-500">
                            {{ deleteForm.errors.remark }}
                        </p>
                    </div>

                    <div class="flex gap-3 w-full">
                        <button
                            @click="showConfirmModal = false"
                            class="flex-1 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition"
                        >
                            Cancel
                        </button>
                        <button
                            @click="confirmAction"
                            :disabled="deleteForm.processing"
                            class="flex-1 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition"
                        >
                            {{ deleteForm.processing ? "Deleting..." : "Yes, Delete" }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>
