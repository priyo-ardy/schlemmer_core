<script setup>
import 'vue3-toastify/dist/index.css';
import { ref, computed } from "vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import Sidebar from "@/Components/Dashboard/Sidebar.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { toast } from 'vue3-toastify';
import dayjs from 'dayjs'; // Ganti jadi default import
import 'dayjs/locale/id';    // Tetep pake ini buat lokalisasi

// Set locale ke Indonesia
dayjs.locale('id');

defineOptions({layout: AuthenticatedLayout});
// IMPORT PLUGIN BARU YANG AMAN & BERSIH
import { Cropper } from "vue-advanced-cropper";
import "vue-advanced-cropper/dist/style.css"; // Stylesheet resmi vue-advanced-cropper

// Props dari Laravel
const props = defineProps({
    users: Array,
});

// State UI Slide-over & Search
const isSlideOverOpen = ref(false);
const slideOverTitle = ref("New User Entry");
const isEditMode = ref(false);
const searchQuery = ref("");
const selectedFilter = ref("all");
const selectedUsers = ref([]);

// --- STATE EDIT FOTO (VUE-ADVANCED-CROPPER VERSION) ---
const imagePreview = ref(null);         // Preview sirkular di bodi form
const imageToCropSrc = ref(null);       // Menyimpan URL string gambar asli untuk dicrop
const isCropperModalOpen = ref(false);    // Kontrol modal editor
const cropperRef = ref(null);           // Referensi DOM komponen cropper

// Sensor Email
const maskEmail = (email) => {
    if (!email) return "-";
    const [local, domain] = email.split("@");
    if (local.length <= 2) return `${local[0]}*@${domain}`;
    return `${local[0]}${"*".repeat(local.length - 2)}${local[local.length - 1]}@${domain}`;
};

// Inisial Nama 2 Digit
const getInitials = (name) => {
    if (!name) return "??";
    const words = name.trim().split(" ");
    if (words.length >= 2) return (words[0][0] + words[1][0]).toUpperCase();
    return words[0].substring(0, 2).toUpperCase();
};

// Filter Pencarian
const filteredUsers = computed(() => {
    return (props.users || []).filter((user) => {
        const matchesSearch = 
            user.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            user.email.toLowerCase().includes(searchQuery.value.toLowerCase());

        if (selectedFilter.value === "active") return matchesSearch && user.is_active && !user.is_locked;
        if (selectedFilter.value === "inactive") return matchesSearch && !user.is_active;
        if (selectedFilter.value === "locked") return matchesSearch && user.is_locked;
        return matchesSearch;
    });
});

// Checkbox Logic
const isAllSelected = computed(() => filteredUsers.value.length > 0 && selectedUsers.value.length === filteredUsers.value.length);
const toggleSelectAll = () => { selectedUsers.value = isAllSelected.value ? [] : filteredUsers.value.map(user => user.id); };
const toggleSelectUser = (id) => {
    const index = selectedUsers.value.indexOf(id);
    index > -1 ? selectedUsers.value.splice(index, 1) : selectedUsers.value.push(id);
};

// Form Binding Inertia
const form = useForm({
    id: null,
    name: "",
    email: "",
    password: "",
    avatar: null, 
    login_attempts: 0,
    is_locked: false,
    is_active: true,
});

// --- LOGIKA AUTO-PREVIEW & EDITOR BARU ---
const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    // Buat URL sementara untuk di-load ke kanvas penyeleksi
    imageToCropSrc.value = URL.createObjectURL(file);
    isCropperModalOpen.value = true;
};

const applyCrop = () => {
    if (!cropperRef.value) return;
    const { canvas } = cropperRef.value.getResult();
    if (canvas) {
        // CONVERT KE WEBP DI SINI
        canvas.toBlob((blob) => {
            // Ganti nama file jadi .webp dan mime type jadi image/webp
            const croppedFile = new File([blob], `${form.name || 'avatar'}.webp`, { type: 'image/webp' });
            
            form.avatar = croppedFile; 
            imagePreview.value = URL.createObjectURL(blob); 
            isCropperModalOpen.value = false;
        }, 'image/webp', 0.8); // 0.8 adalah kompresi (80%), udah sangat optimal dan ringan buat WebP
    }
};

const cancelCrop = () => {
    isCropperModalOpen.value = false;
    imageToCropSrc.value = null;
};

// Open Drawer Create
const openCreateDrawer = () => {
    isEditMode.value = false;
    slideOverTitle.value = "Register New User";
    imagePreview.value = null;
    form.reset();
    form.clearErrors();
    isSlideOverOpen.value = true;
};

// Open Drawer Edit
const openEditDrawer = (user) => {
    isEditMode.value = true;
    slideOverTitle.value = `Security Parameters: ${user.name}`;
    form.clearErrors();

    form.id = user.id;
    form.name = user.name;
    form.email = user.email;
    form.password = ""; 
    form.avatar = null; 
    form.login_attempts = user.login_attempts;
    form.is_locked = user.is_locked ? true : false;
    form.is_active = user.is_active ? true : false;

    imagePreview.value = user.avatar; // Tampilkan foto lama dari disk storage
    isSlideOverOpen.value = true;
};

// Submit handler url manual anti-Ziggy
const handleSubmit = () => {
    if (isEditMode.value) {
        form.transform((data) => ({ ...data, _method: 'put' }))
            .post(`/users/${form.id}`, { onSuccess: () => { isSlideOverOpen.value = false; form.reset(); } });
    } else {
        form.post('/users', { onSuccess: () => { isSlideOverOpen.value = false; form.reset(); } });
    }
};

const handleBulkDelete = () => {
    if (selectedUsers.value.length === 0) return;
    if (confirm(`🚨 SECURITY WARNING: Bulk delete ${selectedUsers.value.length} accounts?`)) {
        router.post('/users/bulk-delete', { ids: selectedUsers.value }, { onSuccess: () => selectedUsers.value = [] });
    }
};
</script>

<template>
    <Head title="User Management" />

    <div class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800">
        <!-- <Sidebar class="flex-shrink-0" /> -->

        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">User Management</h1>
                    <p class="text-xs text-slate-500 mt-1">Audit credentials, track system logs, and monitor authentication layers.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button v-if="selectedUsers.length > 0" @click="handleBulkDelete" class="inline-flex items-center gap-2 px-4 py-2.5 bg-rose-50 border border-rose-200 text-rose-700 font-bold text-xs rounded-xl shadow-sm transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        Delete Selected ({{ selectedUsers.length }})
                    </button>
                    <button @click="openCreateDrawer" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-md transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                        New User
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="relative flex-1 max-w-md">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </span>
                    <input type="text" v-model="searchQuery" placeholder="Search dynamically by name or email..." class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 focus:border-blue-500 rounded-xl text-xs font-semibold focus:outline-none transition" />
                </div>
                <div class="flex items-center gap-3">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Status:</label>
                    <select v-model="selectedFilter" class="px-4 py-2.5 bg-white border border-slate-200 text-slate-700 rounded-xl text-xs font-semibold focus:outline-none cursor-pointer">
                        <option value="all">All Accounts</option>
                        <option value="active">Active & Unlocked</option>
                        <option value="inactive">Inactive</option>
                        <option value="locked">Security Locked</option>
                    </select>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mb-auto">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left">
                        <thead class="bg-slate-50 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-4 w-12 text-center">
                                    <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" class="rounded border-slate-300 text-blue-600 h-4 w-4 transition cursor-pointer" />
                                </th>
                                <th class="px-6 py-4">User Account & Profile</th>
                                <th class="px-6 py-4 text-center">Attempts</th>
                                <th class="px-6 py-4">Lock Status</th>
                                <th class="px-6 py-4">Active Status</th>
                                <th class="px-6 py-4">Last Login At</th>
                                <th class="px-6 py-4">Last Login IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                            <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-slate-50/80 transition" :class="{'bg-blue-50/30': selectedUsers.includes(user.id)}">
                                <td class="px-6 py-4 text-center">
                                    <input type="checkbox" :checked="selectedUsers.includes(user.id)" @change="toggleSelectUser(user.id)" class="rounded border-slate-300 text-blue-600 h-4 w-4 transition cursor-pointer" />
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img v-if="user.avatar" :src="user.avatar" alt="Avatar" class="h-10 w-10 rounded-full object-cover border border-slate-200 shadow-sm flex-shrink-0" />
                                        <div v-else class="h-10 w-10 rounded-full bg-slate-800 text-slate-200 border border-slate-700 shadow-sm flex items-center justify-center text-xs font-bold uppercase flex-shrink-0">
                                            {{ getInitials(user.name) }}
                                        </div>
                                        <div class="flex flex-col min-w-0">
                                            <button type="button" @click="openEditDrawer(user)" class="text-sm font-bold text-slate-900 text-left hover:text-blue-600 transition underline decoration-dotted underline-offset-4 decoration-slate-300">
                                                {{ user.name }}
                                            </button>
                                            <span class="text-xs text-slate-400 font-mono mt-0.5">{{ maskEmail(user.email) }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2 py-0.5 rounded-md font-mono font-bold text-xs" :class="user.login_attempts >= 3 ? 'bg-rose-50 text-rose-600' : 'bg-slate-100 text-slate-600'">
                                        {{ user.login_attempts }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide" :class="user.is_locked ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-slate-50 text-slate-600 border border-slate-200'">
                                        <span class="h-1.5 w-1.5 rounded-full" :class="user.is_locked ? 'bg-rose-500' : 'bg-slate-400'"></span>
                                        {{ user.is_locked ? "Locked" : "Unlocked" }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide" :class="user.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200'">
                                        <span class="h-1.5 w-1.5 rounded-full" :class="user.is_active ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                                        {{ user.is_active ? "Active" : "Inactive" }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-mono text-slate-500 text-xs">{{ dayjs(user.last_login_at).format('DD-MMM-YYYY HH:mm:ss') }}</td>
                                <td class="px-6 py-4 font-mono text-slate-500 text-xs">{{ user.last_login_ip ?? "-" }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-show="isSlideOverOpen" class="fixed inset-0 z-40 overflow-hidden" role="dialog" aria-modal="true">
            <div class="absolute inset-0 overflow-hidden">
                <transition enter-active-class="ease-in-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in-out duration-300" leave-from-class="opacity-100" leave-to-class="opacity-0">
                    <div v-show="isSlideOverOpen" @click="isSlideOverOpen = false" class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"></div>
                </transition>

                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <transition enter-active-class="transform transition ease-in-out duration-300" enter-from-class="translate-x-full" enter-to-class="translate-x-0" leave-active-class="transform transition ease-in-out duration-300" leave-from-class="translate-x-0" leave-to-class="translate-x-full">
                        <div v-show="isSlideOverOpen" class="pointer-events-auto w-screen max-w-md">
                            <form @submit.prevent="handleSubmit" class="flex h-full flex-col bg-white shadow-2xl border-l border-slate-200">
                                <div class="bg-slate-900 px-6 py-5 flex items-center justify-between">
                                    <div>
                                        <h2 class="text-base font-black text-white tracking-tight">{{ slideOverTitle }}</h2>
                                        <p class="text-[10px] text-slate-400 mt-0.5">Override authentication guard parameters.</p>
                                    </div>
                                    <button type="button" @click="isSlideOverOpen = false" class="rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 p-1.5 transition">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>

                                <div class="flex-1 overflow-y-auto p-6 space-y-5 bg-slate-50/50">
                                    
                                    <div class="p-4 bg-white border border-slate-200 rounded-2xl shadow-sm space-y-4">
                                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Profile Avatar (Optional)</label>
                                        
                                        <div class="flex items-center gap-4">
                                            <div class="h-16 w-16 rounded-full bg-slate-800 border border-slate-700 shadow-sm overflow-hidden flex items-center justify-center text-white text-sm font-black uppercase">
                                                <img v-if="imagePreview" :src="imagePreview" class="h-full w-full object-cover" />
                                                <span v-else>{{ getInitials(form.name) }}</span>
                                            </div>
                                            <div class="flex-1">
                                                <input type="file" @change="handleFileChange" accept="image/png, image/jpeg, image/jpg" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 file:cursor-pointer transition" />
                                                <p class="text-[10px] text-slate-400 mt-1">Accepts PNG, JPG or JPEG. Max size 2MB.</p>
                                            </div>
                                        </div>
                                        <p v-if="form.errors.avatar" class="text-xs text-rose-600 mt-1 font-medium">{{ form.errors.avatar }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Full Name</label>
                                        <input type="text" v-model="form.name" required placeholder="Full name of staff" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:border-blue-500 transition" />
                                        <p v-if="form.errors.name" class="text-xs text-rose-600 mt-1 font-medium">{{ form.errors.name }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Email Address</label>
                                        <input type="email" v-model="form.email" required placeholder="username@schlemmer.co.id" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:border-blue-500 transition" />
                                        <p v-if="form.errors.email" class="text-xs text-rose-600 mt-1 font-medium">{{ form.errors.email }}</p>
                                    </div>
                                    <div v-if="!isEditMode">
                                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Password</label>
                                        <input type="password" v-model="form.password" :required="!isEditMode" placeholder="Minimum 8 characters" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:border-blue-500 transition" />
                                        <p v-if="form.errors.password" class="text-xs text-rose-600 mt-1 font-medium">{{ form.errors.password }}</p>
                                    </div>
                                    <div v-show="isEditMode">
                                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Failed Login Attempts</label>
                                        <input type="number" v-model="form.login_attempts" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:border-blue-500 transition" />
                                    </div>

                                    <div v-show="isEditMode" class="space-y-4 pt-2">
                                        <div class="flex items-center justify-between p-3.5 bg-white border border-slate-200/80 rounded-2xl shadow-sm">
                                            <div class="flex flex-col min-w-0 pr-4">
                                                <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">Security Lock</span>
                                                <span class="text-[11px] font-medium text-slate-400 mt-0.5 truncate">{{ form.is_locked ? "🔴 Locked out." : "🟢 Accessible." }}</span>
                                            </div>
                                            <button type="button" @click="form.is_locked = !form.is_locked" :class="form.is_locked ? 'bg-rose-500' : 'bg-slate-200'" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out"><span :class="form.is_locked ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md transition duration-200"></span></button>
                                        </div>
                                        <div class="flex items-center justify-between p-3.5 bg-white border border-slate-200/80 rounded-2xl shadow-sm">
                                            <div class="flex flex-col min-w-0 pr-4">
                                                <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">Activation Status</span>
                                                <span class="text-[11px] font-medium text-slate-400 mt-0.5 truncate">{{ form.is_active ? "🟢 Active." : "🔴 Inactive." }}</span>
                                            </div>
                                            <button type="button" @click="form.is_active = !form.is_active" :class="form.is_active ? 'bg-emerald-500' : 'bg-slate-200'" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out"><span :class="form.is_active ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md transition duration-200"></span></button>
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t border-slate-200 px-6 py-4 bg-white flex items-center justify-end gap-3">
                                    <button type="button" @click="isSlideOverOpen = false" class="px-4 py-2.5 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-50 transition">Cancel</button>
                                    <button type="submit" :disabled="form.processing" class="px-5 py-2.5 bg-blue-600 text-white font-bold text-xs rounded-xl shadow-md hover:bg-blue-700 transition disabled:opacity-50">
                                        {{ form.processing ? "Saving..." : isEditMode ? "Update Security" : "Register Account" }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </transition>
                </div>
            </div>
        </div>

        <div v-if="isCropperModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full overflow-hidden flex flex-col">
                <div class="px-5 py-4 bg-slate-900 text-white flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider">Adjust & Crop Photo</h3>
                    <span class="text-[10px] text-slate-400 font-medium">Native Vue Cropper</span>
                </div>
                
                <div class="p-6 bg-slate-100 flex items-center justify-center max-h-[400px] overflow-hidden">
                    <cropper
                        ref="cropperRef"
                        :src="imageToCropSrc"
                        :stencil-props="{
                            aspectRatio: 1/1,
                            movable: true,
                            resizable: true
                        }"
                        class="max-w-full max-h-[350px] bg-slate-200"
                    />
                </div>

                <div class="px-5 py-4 bg-white border-t border-slate-100 flex items-center justify-end gap-2.5">
                    <button type="button" @click="cancelCrop" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                        Cancel
                    </button>
                    <button type="button" @click="applyCrop" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-md transition">
                        ✓ Apply Crop
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
/* Kostumisasi CSS pembungkus kanvas biar keliatan rapi kotak */
.vue-advanced-cropper {
    background: #e2e8f0;
    max-height: 350px;
}
</style>