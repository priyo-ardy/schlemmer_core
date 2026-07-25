<script setup>
import "vue3-toastify/dist/index.css";
import { ref, computed, watch } from "vue";
import { Head, useForm, router, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "../../Layouts/AuthenticatedLayout.vue";
import { toast } from "vue3-toastify";
import dayjs from "dayjs";
import "dayjs/locale/id";

dayjs.locale("id");

const page = usePage();
const errors = computed(() => page.props.errors || {});
const deleteForm = useForm({
    ids: [],
    remark: ''
});

defineOptions({ layout: AuthenticatedLayout });
import { Cropper } from "vue-advanced-cropper";
import "vue-advanced-cropper/dist/style.css";

const props = defineProps({
    users: Array,
    roles: Array,
});

const isSlideOverOpen = ref(false);
const slideOverTitle = ref("New User Entry");
const isEditMode = ref(false);
const searchQuery = ref("");
const selectedFilter = ref("all");
const selectedUsers = ref([]);
const isRefreshing = ref(false);
const showConfirmModal = ref(false);
const deleteReason = ref("");

// --- STATE SELECT2 / SEARCHABLE DROPDOWN ROLE ---
const isRoleDropdownOpen = ref(false);
const roleSearchQuery = ref("");

const filteredRoles = computed(() => {
    if (!props.roles) return [];
    return props.roles.filter((role) =>
        role.name.toLowerCase().includes(roleSearchQuery.value.toLowerCase())
    );
});

const selectRole = (roleName) => {
    form.role = roleName;
    isRoleDropdownOpen.value = false;
    roleSearchQuery.value = "";
};

const clearRole = (event) => {
    event.stopPropagation(); // Mencegah dropdown terbuka saat tombol X diklik
    form.role = "";
    roleSearchQuery.value = "";
};
// -----------------------------------------------

const imagePreview = ref(null);
const imageToCropSrc = ref(null);
const isCropperModalOpen = ref(false);
const cropperRef = ref(null);

const maskEmail = (email) => {
    if (!email) return "-";
    const [local, domain] = email.split("@");
    if (local.length <= 2) return `${local[0]}*@${domain}`;
    return `${local[0]}${"*".repeat(local.length - 2)}${local[local.length - 1]}@${domain}`;
};

const getInitials = (name) => {
    if (!name) return "??";
    const words = name.trim().split(" ");
    if (words.length >= 2) return (words[0][0] + words[1][0]).toUpperCase();
    return words[0].substring(0, 2).toUpperCase();
};

const filteredUsers = computed(() => {
    return (props.users || []).filter((user) => {
        const matchesSearch =
            user.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            user.email.toLowerCase().includes(searchQuery.value.toLowerCase());

        if (selectedFilter.value === "active")
            return matchesSearch && user.is_active && !user.is_locked;
        if (selectedFilter.value === "inactive")
            return matchesSearch && !user.is_active;
        if (selectedFilter.value === "locked")
            return matchesSearch && user.is_locked;
        return matchesSearch;
    });
});

const refreshTable = () => {
    isRefreshing.value = true;
    router.reload({
        only: ["users", "roles"],
        onSuccess: () => {
            isRefreshing.value = false;
            toast.success('Refresh Success');
        },
        onError: () => {
            isRefreshing.value = false;
            toast.error("Failed to refresh data");
        },
    });
};

// --- STATE PAGINATION ---
const itemsPerPage = ref(10);
const currentPage = ref(1);

watch([searchQuery, selectedFilter, itemsPerPage], () => {
    currentPage.value = 1;
});

const paginatedUsers = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredUsers.value.slice(start, end);
});

const totalFiltered = computed(() => filteredUsers.value.length);
const totalPages = computed(() => Math.ceil(totalFiltered.value / itemsPerPage.value));

const paginationStart = computed(() => {
    if (totalFiltered.value === 0) return 0;
    return (currentPage.value - 1) * itemsPerPage.value + 1;
});

const paginationEnd = computed(() => {
    return Math.min(currentPage.value * itemsPerPage.value, totalFiltered.value);
});

const goToPrev = () => { if (currentPage.value > 1) currentPage.value--; };
const goToNext = () => { if (currentPage.value < totalPages.value) currentPage.value++; };

// --- CHECKBOX LOGIC ---
const isAllSelected = computed(
    () =>
        paginatedUsers.value.length > 0 &&
        paginatedUsers.value.every((u) => selectedUsers.value.includes(u.id))
);

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedUsers.value = selectedUsers.value.filter(
            (id) => !paginatedUsers.value.some((u) => u.id === id)
        );
    } else {
        paginatedUsers.value.forEach((user) => {
            if (!selectedUsers.value.includes(user.id)) {
                selectedUsers.value.push(user.id);
            }
        });
    }
};

const toggleSelectUser = (id) => {
    const index = selectedUsers.value.indexOf(id);
    index > -1
        ? selectedUsers.value.splice(index, 1)
        : selectedUsers.value.push(id);
};

const form = useForm({
    id: null,
    name: "",
    email: "",
    password: "",
    avatar: null,
    role: "",
    login_attempts: 0,
    is_locked: false,
    is_active: true,
    reason: ''
});

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    imageToCropSrc.value = URL.createObjectURL(file);
    isCropperModalOpen.value = true;
};

const applyCrop = () => {
    if (!cropperRef.value) return;
    const { canvas } = cropperRef.value.getResult();
    if (canvas) {
        canvas.toBlob(
            (blob) => {
                const croppedFile = new File(
                    [blob],
                    `${form.name || "avatar"}.webp`,
                    { type: "image/webp" },
                );
                form.avatar = croppedFile;
                imagePreview.value = URL.createObjectURL(blob);
                isCropperModalOpen.value = false;
            },
            "image/webp",
            0.8,
        );
    }
};

const cancelCrop = () => {
    isCropperModalOpen.value = false;
    imageToCropSrc.value = null;
};

const openCreateDrawer = () => {
    isEditMode.value = false;
    slideOverTitle.value = "Register New User";
    imagePreview.value = null;
    form.reset();
    form.clearErrors();
    roleSearchQuery.value = "";
    isRoleDropdownOpen.value = false;
    isSlideOverOpen.value = true;
};

const openEditDrawer = (user) => {
    isEditMode.value = true;
    slideOverTitle.value = `Security Parameters: ${user.name}`;
    form.clearErrors();
    form.id = user.id;
    form.name = user.name;
    form.email = user.email;
    form.password = "";
    form.avatar = null;
    form.role = user.roles && user.roles.length > 0 ? user.roles[0].name : "";
    form.login_attempts = user.login_attempts;
    form.is_locked = user.is_locked ? true : false;
    form.is_active = user.is_active ? true : false;
    imagePreview.value = user.avatar;
    roleSearchQuery.value = "";
    isRoleDropdownOpen.value = false;
    isSlideOverOpen.value = true;
};

const handleSubmit = () => {
    form.transform((data) => {
        const payload = { ...data };

        if (isEditMode.value && !payload.password) {
            delete payload.password;
        }

        if (isEditMode.value) {
            payload._method = "put";
        } else {
            delete payload._method;
        }

        return payload;
    }).post(isEditMode.value ? `/users/${form.id}` : "/users", {
        preserveScroll: true,
        onSuccess: () => {
            isSlideOverOpen.value = false;
            form.reset();
            toast.success(isEditMode.value ? "User updated successfully" : "User registered successfully");
        },
        onError: (errors) => {
            console.error(errors);
        }
    });
};

watch(
    errors,
    (newErrors) => {
        if (newErrors && newErrors.error) {
            toast.error(newErrors.error);
        }
    },
    { deep: true }
);

const deleteSelected = () => {
    if (selectedUsers.value.length > 0) showConfirmModal.value = true;
};

const confirmAction = () => {
    if (!deleteReason.value.trim()) {
        deleteForm.setError('remark', 'Please fill the deletion reason');
        return;
    }
    deleteForm.ids = selectedUsers.value;
    deleteForm.remark = deleteReason.value;
    deleteForm.post("/users/bulk-delete", {
        preserveScroll: true,
        onSuccess: () => {
            selectedUsers.value = [];
            showConfirmModal.value = false;
            deleteReason.value = "";
            deleteForm.clearErrors();
            toast.success("Users deleted successfully");
        }
    });
};
</script>

<template>
    <Head title="User Management" />

    <div class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800">
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                        User Management
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Audit credentials, track system logs, and monitor authentication layers.
                    </p>
                </div>
                 <div class="sticky top-0 z-30 bg-white/95 backdrop-blur-sm border border-slate-200 px-3 py-2 shadow-sm">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-1.5">
                            <button
                                @click="openCreateDrawer"
                                type="button"
                                class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-blue-600 bg-blue-50 border border-blue-100 transition-all hover:bg-blue-100 active:scale-95 shadow-sm"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                <span>New</span>
                            </button>

                            <button
                                type="button"
                                @click="refreshTable"
                                :disabled="isRefreshing"
                                class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-slate-600 bg-orange-50 border border-slate-200 transition-all hover:bg-orange-100 active:scale-95 shadow-sm disabled:opacity-60"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" :class="isRefreshing ? 'animate-spin text-blue-600' : 'text-slate-500'" class="h-3.5 w-3.5 transition-colors duration-150" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                <span>{{ isRefreshing ? "Refreshing..." : "Refresh" }}</span>
                            </button>

                            <button
                                type="button"
                                @click="deleteSelected"
                                :disabled="selectedUsers.length === 0"
                                class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-rose-600 bg-rose-50 border border-rose-100 transition-all hover:bg-rose-100 active:scale-95 shadow-sm disabled:opacity-40 disabled:active:scale-100"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <span>Delete ({{ selectedUsers.length }})</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TOP BAR SEARCH & FILTER -->
            <div class="bg-white border border-slate-200/80 shadow-sm p-4 mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="relative flex-1 max-w-md">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input
                        type="text"
                        v-model="searchQuery"
                        placeholder="Search dynamically..."
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 focus:border-blue-500 text-xs font-semibold focus:outline-none transition"
                    />
                </div>

                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-2">
                        <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Per Page:</label>
                        <select
                            v-model="itemsPerPage"
                            class="px-3 py-2 bg-white border border-slate-200 text-slate-700 text-xs font-semibold focus:outline-none cursor-pointer w-20"
                        >
                            <option :value="5">5</option>
                            <option :value="10">10</option>
                            <option :value="20">20</option>
                            <option :value="50">50</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Status:</label>
                        <select
                            v-model="selectedFilter"
                            class="px-3 py-2 bg-white border border-slate-200 text-slate-700 text-xs font-semibold focus:outline-none cursor-pointer min-w-[100px]"
                        >
                            <option value="all">All</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="locked">Locked</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- TABLE SECTION -->
            <div class="bg-white border border-slate-200/80 shadow-sm overflow-hidden mb-auto flex flex-col">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left">
                        <thead class="bg-blue-300 text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-4 w-12 text-center">
                                    <input
                                        type="checkbox"
                                        :checked="isAllSelected"
                                        @change="toggleSelectAll"
                                        class="border-white text-blue-600 h-4 w-4 transition cursor-pointer"
                                    />
                                </th>
                                <th class="px-6 py-4">User Account & Profile</th>
                                <th class="px-6 py-4">Role Assigned</th>
                                <th class="px-6 py-4">Revision</th>
                                <th class="px-6 py-4 text-center">Attempts</th>
                                <th class="px-6 py-4">Lock Status</th>
                                <th class="px-6 py-4">Active Status</th>
                                <th class="px-6 py-4">Last Login At</th>
                                <th class="px-6 py-4">Last Login IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                            <tr
                                v-for="user in paginatedUsers"
                                :key="user.id"
                                class="hover:bg-slate-50/80 transition"
                                :class="{ 'bg-blue-50/30': selectedUsers.includes(user.id) }"
                            >
                                <td class="px-6 py-4 text-center">
                                    <input
                                        type="checkbox"
                                        :checked="selectedUsers.includes(user.id)"
                                        @change="toggleSelectUser(user.id)"
                                        class="border-slate-300 text-blue-600 h-4 w-4 transition cursor-pointer"
                                    />
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img
                                            v-if="user.avatar"
                                            :src="user.avatar"
                                            alt="Avatar"
                                            class="h-10 w-10 rounded-full object-cover border border-slate-200 shadow-sm flex-shrink-0"
                                        />
                                        <div
                                            v-else
                                            class="h-10 w-10 rounded-full bg-slate-800 text-slate-200 border border-slate-700 shadow-sm flex items-center justify-center text-xs font-bold uppercase flex-shrink-0"
                                        >
                                            {{ getInitials(user.name) }}
                                        </div>
                                        <div class="flex flex-col min-w-0">
                                            <button
                                                type="button"
                                                @click="openEditDrawer(user)"
                                                class="text-sm font-bold text-slate-900 text-left hover:text-blue-600 transition underline decoration-dotted underline-offset-4 decoration-slate-300"
                                            >
                                                {{ user.name }}
                                            </button>
                                            <span class="text-xs text-slate-400 font-mono mt-0.5">
                                                {{ maskEmail(user.email) }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="user.roles && user.roles.length > 0" class="px-2.5 py-1 text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ user.roles[0].name }}
                                    </span>
                                    <span v-else class="text-slate-400 italic text-[11px]">No Role</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 tracking-wide text-xs font-bold text-blue-600 bg-blue-50 border border-blue-100">
                                    Rev. {{ user.revision }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-1 tracking-wide text-xs font-bold "
                                        :class="user.login_attempts >= 3 ? 'bg-rose-50 text-rose-600 border border-rose-200' : 'bg-slate-100 text-slate-600 border border-slate-200'"
                                    >
                                        {{ user.login_attempts }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide"
                                        :class="user.is_locked ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-slate-50 text-slate-600 border border-slate-200'"
                                    >
                                        <span class="h-1.5 w-1.5" :class="user.is_locked ? 'bg-rose-500' : 'bg-slate-400'"></span>
                                        {{ user.is_locked ? "Locked" : "Unlocked" }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide"
                                        :class="user.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200'"
                                    >
                                        <span class="h-1.5 w-1.5" :class="user.is_active ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                                        {{ user.is_active ? "Active" : "Inactive" }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-mono text-slate-500 text-xs">
                                    {{ user.last_login_at ? dayjs(user.last_login_at).format("DD-MMM-YYYY HH:mm:ss") : '-' }}
                                </td>
                                <td class="px-6 py-4 font-mono text-slate-500 text-xs">
                                    {{ user.last_login_ip ?? "-" }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION BAR -->
                <div class="flex items-center justify-between px-6 py-4 bg-white border-t border-slate-200 mt-auto">
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        Showing {{ totalFiltered === 0 ? 0 : paginationStart }} to {{ paginationEnd }} of {{ totalFiltered }}
                    </div>

                    <div class="flex items-center gap-1 text-[11px] font-bold">
                        <button
                            @click="goToPrev"
                            :disabled="currentPage === 1"
                            class="px-2 py-1.5 text-slate-400 hover:text-blue-600 disabled:opacity-50 disabled:hover:text-slate-400 transition"
                        >
                            &laquo; Previous
                        </button>

                        <div class="bg-blue-600 text-white rounded-sm w-7 h-7 flex items-center justify-center shadow-sm">
                            {{ currentPage }}
                        </div>

                        <button
                            @click="goToNext"
                            :disabled="currentPage === totalPages || totalFiltered === 0"
                            class="px-2 py-1.5 text-slate-400 hover:text-blue-600 disabled:opacity-50 disabled:hover:text-slate-400 transition"
                        >
                            Next &raquo;
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- SLIDEOVER / DRAWER FORM -->
        <div v-show="isSlideOverOpen" class="fixed inset-0 z-40 overflow-hidden" role="dialog" aria-modal="true">
            <div class="absolute inset-0 overflow-hidden">
                <transition enter-active-class="ease-in-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in-out duration-300" leave-from-class="opacity-100" leave-to-class="opacity-0">
                    <div v-show="isSlideOverOpen" @click="isSlideOverOpen = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
                </transition>

                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <transition enter-active-class="transform transition ease-in-out duration-300" enter-from-class="translate-x-full" enter-to-class="translate-x-0" leave-active-class="transform transition ease-in-out duration-300" leave-from-class="translate-x-0" leave-to-class="translate-x-full">
                        <div v-show="isSlideOverOpen" class="pointer-events-auto w-screen max-w-2xl bg-white shadow-2xl flex flex-col h-full border-l border-slate-200">
                            <div class="bg-slate-900 px-6 py-5 flex items-center justify-between shrink-0">
                                <div>
                                    <h2 class="text-base font-black text-white tracking-tight">{{ slideOverTitle }}</h2>
                                    <p class="text-[10px] text-slate-400 mt-0.5">Override authentication guard parameters.</p>
                                </div>
                                <button type="button" @click="isSlideOverOpen = false" class="text-slate-400 hover:text-white hover:bg-slate-800 p-1.5 transition">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <form @submit.prevent="handleSubmit" class="flex-1 p-6 overflow-y-auto space-y-6 bg-slate-200/70">
                                <div class="flex-1 overflow-y-auto p-6 space-y-5 bg-slate-50/50">
                                    <div class="p-4 bg-white border border-slate-200 shadow-sm space-y-4">
                                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Profile Avatar (Optional)</label>
                                        <div class="flex items-center gap-4">
                                            <div class="h-16 w-16 rounded-full bg-slate-800 border border-slate-700 shadow-sm overflow-hidden flex items-center justify-center text-white text-sm font-black uppercase">
                                                <img v-if="imagePreview" :src="imagePreview" class="h-full w-full object-cover" />
                                                <span v-else>{{ getInitials(form.name) }}</span>
                                            </div>
                                            <div class="flex-1">
                                                <input type="file" @change="handleFileChange" accept="image/png, image/jpeg, image/jpg" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 file:cursor-pointer transition" />
                                                <p class="text-[10px] text-slate-400 mt-1">Accepts PNG, JPG or JPEG. Max size 2MB.</p>
                                            </div>
                                        </div>
                                        <p v-if="form.errors.avatar" class="text-xs text-rose-600 mt-1 font-medium">{{ form.errors.avatar }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Full Name</label>
                                        <input type="text" v-model="form.name" required placeholder="Full name of staff" class="w-full px-4 py-2.5 bg-white border border-slate-200 text-xs font-semibold focus:outline-none focus:border-blue-500 transition" />
                                        <p v-if="form.errors.name" class="text-xs text-rose-600 mt-1 font-medium">{{ form.errors.name }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Email Address</label>
                                        <input type="email" v-model="form.email" required placeholder="username@schlemmer.co.id" class="w-full px-4 py-2.5 bg-white border border-slate-200 text-xs font-semibold focus:outline-none focus:border-blue-500 transition" />
                                        <p v-if="form.errors.email" class="text-xs text-rose-600 mt-1 font-medium">{{ form.errors.email }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Password</label>
                                        <input type="password" v-model="form.password" :required="!isEditMode" placeholder="Minimum 8 characters" class="w-full px-4 py-2.5 bg-white border border-slate-200 text-xs font-semibold focus:outline-none focus:border-blue-500 transition" />
                                        <p v-if="form.errors.password" class="text-xs text-rose-600 mt-1 font-medium">{{ form.errors.password }}</p>
                                    </div>

                                    <!-- CUSTOM SELECT2 DROPDOWN ROLE WITH CLEAR BUTTON -->
                                    <div class="relative">
                                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Assign Role</label>

                                        <!-- Trigger Box -->
                                        <div
                                            @click="isRoleDropdownOpen = !isRoleDropdownOpen"
                                            class="w-full px-4 py-2.5 bg-white border border-slate-200 text-xs font-semibold flex items-center justify-between cursor-pointer focus:border-blue-500 transition"
                                        >
                                            <span :class="form.role ? 'text-slate-800' : 'text-slate-400'">
                                                {{ form.role || '-- Select Role --' }}
                                            </span>

                                            <div class="flex items-center gap-1.5">
                                                <!-- Tombol Clear (X) - Hanya muncul jika ada data yang terisi -->
                                                <button
                                                    v-if="form.role"
                                                    type="button"
                                                    @click="clearRole"
                                                    class="text-slate-400 hover:text-rose-600 p-0.5 transition-colors"
                                                    title="Clear selection"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>

                                                <!-- Arrow Icon -->
                                                <svg class="h-4 w-4 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': isRoleDropdownOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </div>
                                        </div>

                                        <!-- Dropdown Menu Options -->
                                        <div v-if="isRoleDropdownOpen" class="absolute z-50 mt-1 w-full bg-white border border-slate-200 shadow-xl rounded-sm">
                                            <!-- Search Input inside dropdown -->
                                            <div class="p-2 border-b border-slate-100 bg-slate-50">
                                                <input
                                                    type="text"
                                                    v-model="roleSearchQuery"
                                                    placeholder="Search role..."
                                                    class="w-full px-3 py-1.5 bg-white border border-slate-200 text-xs font-medium focus:outline-none focus:border-blue-500"
                                                    @click.stop
                                                />
                                            </div>
                                            <div class="max-h-48 overflow-y-auto divide-y divide-slate-50">
                                                <div
                                                    v-for="role in filteredRoles"
                                                    :key="role.id"
                                                    @click="selectRole(role.name)"
                                                    class="px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-blue-50 hover:text-blue-600 cursor-pointer flex items-center justify-between transition-colors"
                                                >
                                                    <span>{{ role.name }}</span>
                                                    <span v-if="form.role === role.name" class="text-blue-600 font-bold">✓</span>
                                                </div>
                                                <div v-if="filteredRoles.length === 0" class="px-4 py-3 text-xs text-slate-400 text-center italic">
                                                    No roles found
                                                </div>
                                            </div>
                                        </div>
                                        <p v-if="form.errors.role" class="text-xs text-rose-600 mt-1 font-medium">{{ form.errors.role }}</p>
                                    </div>

                                    <div v-if="isEditMode">
                                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Update Reason</label>
                                        <textarea v-model="form.remark" :required="isEditMode" class="w-full px-4 py-2.5 bg-white border border-slate-200 text-xs font-semibold focus:outline-none focus:border-blue-500 transition" placeholder="Describe change reason here ..."></textarea>
                                        <p v-if="form.errors.reason" class="text-xs text-rose-600 mt-1 font-medium">{{ form.errors.reason }}</p>
                                    </div>
                                    <div v-show="isEditMode">
                                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Failed Login Attempts</label>
                                        <input type="number" v-model="form.login_attempts" class="w-full px-4 py-2.5 bg-white border border-slate-200 text-xs font-semibold focus:outline-none focus:border-blue-500 transition" />
                                    </div>

                                    <div v-show="isEditMode" class="space-y-4 pt-2">
                                        <div class="flex items-center justify-between p-3.5 bg-white border border-slate-200/80 shadow-sm">
                                            <div class="flex flex-col min-w-0 pr-4">
                                                <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">Security Lock</span>
                                                <span class="text-[11px] font-medium text-slate-400 mt-0.5 truncate">{{ form.is_locked ? "🔴 Locked out." : "🟢 Accessible." }}</span>
                                            </div>
                                            <button type="button" @click="form.is_locked = !form.is_locked" :class="form.is_locked ? 'bg-rose-500' : 'bg-slate-200'" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out">
                                                <span :class="form.is_locked ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md transition duration-200"></span>
                                            </button>
                                        </div>
                                        <div class="flex items-center justify-between p-3.5 bg-white border border-slate-200/80 shadow-sm">
                                            <div class="flex flex-col min-w-0 pr-4">
                                                <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">Activation Status</span>
                                                <span class="text-[11px] font-medium text-slate-400 mt-0.5 truncate">{{ form.is_active ? "🟢 Active." : "🔴 Inactive." }}</span>
                                            </div>
                                            <button type="button" @click="form.is_active = !form.is_active" :class="form.is_active ? 'bg-emerald-500' : 'bg-slate-200'" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out">
                                                <span :class="form.is_active ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md transition duration-200"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <div class="px-6 py-4 bg-white border-t border-slate-200 flex justify-end gap-3 shrink-0">
                                <button type="button" @click="isSlideOverOpen = false" class="px-5 py-2.5 text-xs font-bold text-slate-600 bg-white border border-slate-300 hover:bg-slate-50 active:scale-95 transition">Cancel</button>
                                <button type="button" @click="handleSubmit" :disabled="form.processing" class="px-6 py-2.5 bg-blue-600 text-white font-bold text-xs shadow-md hover:bg-blue-700 transition-all active:scale-95 disabled:opacity-50 flex items-center gap-2">
                                    {{ form.processing ? "Saving..." : isEditMode ? "Update Security" : "Register Account" }}
                                </button>
                            </div>
                        </div>
                    </transition>
                </div>
            </div>
        </div>

        <!-- CROPPER MODAL -->
        <div v-if="isCropperModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in">
            <div class="bg-white border border-slate-200 shadow-2xl max-w-lg w-full overflow-hidden flex flex-col">
                <div class="px-5 py-4 bg-slate-900 text-white flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider">Adjust & Crop Photo</h3>
                    <span class="text-[10px] text-slate-400 font-medium">Native Vue Cropper</span>
                </div>
                <div class="p-6 bg-slate-100 flex items-center justify-center max-h-[400px] overflow-hidden">
                    <cropper ref="cropperRef" :src="imageToCropSrc" :stencil-props="{ aspectRatio: 1 / 1, movable: true, resizable: true }" class="max-w-full max-h-[350px] bg-slate-200" />
                </div>
                <div class="px-5 py-4 bg-white border-t border-slate-100 flex items-center justify-end gap-2.5">
                    <button type="button" @click="cancelCrop" class="px-4 py-2 border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">Cancel</button>
                    <button type="button" @click="applyCrop" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md transition">✓ Apply Crop</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL KONFIRMASI DELETE -->
    <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
    >
        <div
            v-if="showConfirmModal"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
        >
            <div
                class="bg-white w-full max-w-sm border border-slate-200 shadow-2xl p-6"
            >
                <div class="flex flex-col text-left">
                    <h3 class="text-lg font-black text-slate-900 mb-1">
                        Confirm Deletion
                    </h3>
                    <p class="text-xs text-slate-500 mb-4">
                        Are you sure you want to delete
                        <span class="font-bold text-slate-900"
                            >{{ selectedUsers.length }} items</span
                        >? This action cannot be undone.
                    </p>

                    <div class="mb-6">
                        <label
                            class="block text-[10px] font-bold text-slate-500 uppercase mb-1"
                            >Reason for Deletion
                            <span class="text-rose-500">*</span></label
                        >
                        <textarea
                            v-model="deleteReason"
                            rows="2"
                            @input="deleteForm.clearErrors('remark')"
                            :class="deleteForm.errors.remark ? 'border-rose-500' : 'border-slate-300'"
                            class="w-full p-2 border text-xs focus:outline-none focus:border-blue-500 transition-all"
                            placeholder="e.g. Data redundancy, wrong entry, etc."
                        ></textarea>
                        <p v-if="deleteForm.errors.remark" class="text-xs text-rose-600 mt-1 font-medium">{{ deleteForm.errors.remark }}</p>
                    </div>

                    <div class="flex gap-3 w-full">
                        <button
                            @click="showConfirmModal = false"
                            @input="deleteForm.clearErrors('remark')"
                            class="flex-1 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition"
                        >
                            Cancel
                        </button>
                        <button
                            @click="confirmAction"
                            :disabled="deleteForm.processing"
                            class="flex-1 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition"
                        >
                            {{
                                deleteForm.processing
                                    ? "Deleting..."
                                    : "Yes, Delete"
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<style>
.vue-advanced-cropper {
    background: #e2e8f0;
    max-height: 350px;
}
</style>
