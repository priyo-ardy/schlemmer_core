<script setup>
import { ref } from "vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import Sidebar from "@/Components/Dashboard/Sidebar.vue";

// State Kontrol UI Slide-over Drawer
const isSlideOverOpen = ref(false);
const slideOverTitle = ref("New User Entry");
const isEditMode = ref(false);

// State Pencarian & Filter Tabel
const searchQuery = ref("");
const selectedFilter = ref("all");

// --- UTILITY FUNCTION: SENSOR EMAIL (Anti-Spam / Data Privacy) ---
const maskEmail = (email) => {
    if (!email) return "-";
    const [local, domain] = email.split("@");
    if (local.length <= 2) {
        return `${local[0]}*@${domain}`;
    }
    // Mengambil huruf pertama & terakhir dari username email, tengahnya disensor bintang
    return `${local[0]}${"*".repeat(local.length - 2)}${local[local.length - 1]}@${domain}`;
};

// Dummy Data Karyawan Baru Sesuai Spesifikasi Keamanan Lu (Simulasi Read)
const users = ref([
    {
        id: 1,
        avatar: "https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=100&q=80",
        name: "Ardy Priyo Sudiyantoko",
        email: "priyo.ardy@schlemmer.co.id",
        login_attempt: 0,
        is_locked: false,
        is_active: true,
        last_login_at: "2026-06-12 15:30:22",
        last_login_ip: "192.168.10.45",
    },
    {
        id: 2,
        avatar: "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=100&q=80",
        name: "Budi Santoso",
        email: "budi.s@schlemmer.co.id",
        login_attempt: 4,
        is_locked: true,
        is_active: true,
        last_login_at: "2026-06-11 09:12:05",
        last_login_ip: "192.168.10.112",
    },
    {
        id: 3,
        avatar: "https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=100&q=80",
        name: "Siti Rahma",
        email: "siti.rahma99@schlemmer.co.id",
        login_attempt: 1,
        is_locked: false,
        is_active: false,
        last_login_at: "2026-05-30 22:45:10",
        last_login_ip: "180.242.4.15",
    },
]);

// Inertia Form Instance (CRUD Binding)
const form = useForm({
    id: null,
    name: "",
    email: "",
    login_attempt: 0,
    is_locked: false,
    is_active: true,
});

// Handling Drawer Toggles
const openCreateDrawer = () => {
    isEditMode.value = false;
    slideOverTitle.value = "Register New User";
    form.reset();
    form.clearErrors();
    isSlideOverOpen.value = true;
};

const openEditDrawer = (user) => {
    isEditMode.value = true;
    slideOverTitle.value = "Update Security Parameters";
    form.clearErrors();

    form.id = user.id;
    form.name = user.name;
    form.email = user.email;
    form.login_attempt = user.login_attempt;
    form.is_locked = user.is_locked;
    form.is_active = user.is_active;

    isSlideOverOpen.value = true;
};

const handleSubmit = () => {
    const routeName = isEditMode.value ? "users.update" : "users.store";
    const method = isEditMode.value ? "put" : "post";
    const param = isEditMode.value ? form.id : undefined;

    form[method](route(routeName, param), {
        onSuccess: () => {
            isSlideOverOpen.value = false;
            form.reset();
        },
    });
};

const handleDelete = (user) => {
    if (
        confirm(`Apakah lu yakin mau menghapus atau suspend akun ${user.name}?`)
    ) {
        router.delete(route("users.destroy", user.id));
    }
};
</script>

<template>
    <Head title="User Management" />

    <div
        class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800"
    >
        <Sidebar class="flex-shrink-0" />

        <div
            class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto p-6 md:p-8"
        >
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8"
            >
                <div>
                    <h1
                        class="text-2xl font-black text-slate-900 tracking-tight"
                    >
                        User Directory
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Audit credentials, track system logs, and monitor
                        authentication layers.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        @click="openCreateDrawer"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-md shadow-blue-600/10 transition duration-150"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        New User
                    </button>
                </div>
            </div>

            <div
                class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mb-auto"
            >
                <div class="overflow-x-auto">
                    <table
                        class="min-w-full divide-y divide-slate-200 text-left"
                    >
                        <thead
                            class="bg-slate-50 text-[11px] font-bold text-slate-400 uppercase tracking-wider"
                        >
                            <tr>
                                <th class="px-6 py-4">
                                    User Account & Profile
                                </th>
                                <th class="px-6 py-4 text-center">Attempts</th>
                                <th class="px-6 py-4">Lock Status</th>
                                <th class="px-6 py-4">Active Status</th>
                                <th class="px-6 py-4">Last Login At</th>
                                <th class="px-6 py-4">Last Login IP</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-slate-100 text-xs font-medium text-slate-700"
                        >
                            <tr
                                v-for="user in users"
                                :key="user.id"
                                class="hover:bg-slate-50/80 transition"
                            >
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img
                                            :src="user.avatar"
                                            alt="Avatar"
                                            class="h-10 w-10 rounded-full object-cover border border-slate-200 shadow-sm flex-shrink-0"
                                        />
                                        <div class="flex flex-col min-w-0">
                                            <span
                                                class="text-sm font-bold text-slate-900 truncate"
                                                title="Full Name"
                                                >{{ user.name }}</span
                                            >
                                            <span
                                                class="text-xs text-slate-400 font-mono mt-0.5"
                                                title="Email Address"
                                                >{{
                                                    maskEmail(user.email)
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="px-2 py-0.5 rounded-md font-mono font-bold text-xs"
                                        :class="
                                            user.login_attempt >= 3
                                                ? 'bg-rose-50 text-rose-600'
                                                : 'bg-slate-100 text-slate-600'
                                        "
                                    >
                                        {{ user.login_attempt }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide"
                                        :class="
                                            user.is_locked
                                                ? 'bg-rose-50 text-rose-700 border border-rose-200'
                                                : 'bg-slate-50 text-slate-600 border border-slate-200'
                                        "
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :class="
                                                user.is_locked
                                                    ? 'bg-rose-500'
                                                    : 'bg-slate-400'
                                            "
                                        ></span>
                                        {{
                                            user.is_locked
                                                ? "Locked"
                                                : "Unlocked"
                                        }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide"
                                        :class="
                                            user.is_active
                                                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                                : 'bg-slate-100 text-slate-500 border border-slate-200'
                                        "
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :class="
                                                user.is_active
                                                    ? 'bg-emerald-500'
                                                    : 'bg-slate-300'
                                            "
                                        ></span>
                                        {{
                                            user.is_active
                                                ? "Active"
                                                : "Inactive"
                                        }}
                                    </span>
                                </td>

                                <td
                                    class="px-6 py-4 font-mono text-slate-500 text-xs"
                                >
                                    {{ user.last_login_at ?? "Never" }}
                                </td>

                                <td
                                    class="px-6 py-4 font-mono text-slate-500 text-xs"
                                >
                                    {{ user.last_login_ip ?? "-" }}
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div
                                        class="flex items-center justify-end gap-1"
                                    >
                                        <button
                                            @click="openEditDrawer(user)"
                                            class="px-2.5 py-1.5 text-slate-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition font-semibold"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            @click="handleDelete(user)"
                                            class="px-2.5 py-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition font-semibold"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div
            v-show="isSlideOverOpen"
            class="fixed inset-0 z-50 overflow-hidden"
            role="dialog"
            aria-modal="true"
        >
            <div class="absolute inset-0 overflow-hidden">
                <transition
                    enter-active-class="ease-in-out duration-300"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="ease-in-out duration-300"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div
                        v-show="isSlideOverOpen"
                        @click="isSlideOverOpen = false"
                        class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"
                    ></div>
                </transition>

                <div
                    class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10"
                >
                    <transition
                        enter-active-class="transform transition ease-in-out duration-300"
                        enter-from-class="translate-x-full"
                        enter-to-class="translate-x-0"
                        leave-active-class="transform transition ease-in-out duration-300"
                        leave-from-class="translate-x-0"
                        leave-to-class="translate-x-full"
                    >
                        <div
                            v-show="isSlideOverOpen"
                            class="pointer-events-auto w-screen max-w-md"
                        >
                            <form
                                @submit.prevent="handleSubmit"
                                class="flex h-full flex-col bg-white shadow-2xl border-l border-slate-200"
                            >
                                <div
                                    class="bg-slate-900 px-6 py-5 flex items-center justify-between"
                                >
                                    <div>
                                        <h2
                                            class="text-base font-black text-white tracking-tight"
                                        >
                                            {{ slideOverTitle }}
                                        </h2>
                                        <p
                                            class="text-[10px] text-slate-400 mt-0.5"
                                        >
                                            Override authentication guard
                                            parameters.
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="isSlideOverOpen = false"
                                        class="rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 p-1.5 transition"
                                    >
                                        <svg
                                            class="h-5 w-5"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="2"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </button>
                                </div>

                                <div
                                    class="flex-1 overflow-y-auto p-6 space-y-5 bg-slate-50/50"
                                >
                                    <div>
                                        <label
                                            class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2"
                                            >Full Name</label
                                        >
                                        <input
                                            type="text"
                                            v-model="form.name"
                                            required
                                            placeholder="Full name of staff"
                                            class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:border-blue-500 transition"
                                        />
                                    </div>

                                    <div>
                                        <label
                                            class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2"
                                            >Email Address</label
                                        >
                                        <input
                                            type="email"
                                            v-model="form.email"
                                            required
                                            placeholder="username@schlemmer.co.id"
                                            class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:border-blue-500 transition"
                                        />
                                    </div>

                                    <div v-show="isEditMode">
                                        <label
                                            class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2"
                                            >Failed Login Attempts</label
                                        >
                                        <input
                                            type="number"
                                            v-model="form.login_attempt"
                                            class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:border-blue-500 transition"
                                        />
                                    </div>

                                    <div
                                        v-show="isEditMode"
                                        class="grid grid-cols-2 gap-4"
                                    >
                                        <div>
                                            <label
                                                class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2"
                                                >Security Lock</label
                                            >
                                            <select
                                                v-model="form.is_locked"
                                                class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:border-blue-500 transition"
                                            >
                                                <option :value="true">
                                                    Locked
                                                </option>
                                                <option :value="false">
                                                    Unlocked
                                                </option>
                                            </select>
                                        </div>
                                        <div>
                                            <label
                                                class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2"
                                                >Activation Status</label
                                            >
                                            <select
                                                v-model="form.is_active"
                                                class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:border-blue-500 transition"
                                            >
                                                <option :value="true">
                                                    Active
                                                </option>
                                                <option :value="false">
                                                    Inactive
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="border-t border-slate-200 px-6 py-4 bg-white flex items-center justify-end gap-3"
                                >
                                    <button
                                        type="button"
                                        @click="isSlideOverOpen = false"
                                        class="px-4 py-2.5 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-50 transition"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="px-5 py-2.5 bg-blue-600 text-white font-bold text-xs rounded-xl shadow-md hover:bg-blue-700 transition disabled:opacity-50"
                                    >
                                        {{
                                            form.processing
                                                ? "Saving..."
                                                : isEditMode
                                                  ? "Update Security"
                                                  : "Register Account"
                                        }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </transition>
                </div>
            </div>
        </div>
    </div>
</template>
