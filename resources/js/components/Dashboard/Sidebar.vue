<script setup>
import { ref, computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";

const page = usePage();

// RADAR ANTI-GAGAL: Kita potong string setelah tanda tanya (?) biar query string gak ngerusak status aktif
const currentPath = computed(() => page.url.split("?")[0]);

// Otomatis buka dropdown kalau jalurnya diawali dengan /users
const isUserManagementOpen = ref(currentPath.value.startsWith("/users"));
const isProcessOpen = ref(currentPath.value.startsWith("/process"));

const toggleUserManagement = () => {
    isUserManagementOpen.value = !isUserManagementOpen.value;
};

const toggleProcessList = () => {
    isProcessOpen.value = !isProcessOpen.value
};
</script>

<template>
    <aside
        class="w-64 min-h-screen bg-slate-900 text-slate-300 flex flex-col border-r border-slate-800 font-sans antialiased"
    >
        <div class="h-16 flex items-center px-6 border-b border-slate-800">
            <span
                class="text-sm font-black tracking-widest text-white uppercase"
            >
                PMFEA <span class="text-blue-500 font-normal">CORE</span>
            </span>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-7 overflow-y-auto">
            <div class="space-y-1">
                <Link
                    href="/dashboard"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition duration-150 group"
                    :class="
                        currentPath === '/dashboard'
                            ? 'bg-slate-800 text-white font-semibold shadow-sm'
                            : 'text-slate-300 hover:bg-slate-800 hover:text-white'
                    "
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 transition"
                        :class="
                            currentPath === '/dashboard'
                                ? 'text-blue-500'
                                : 'text-slate-400 group-hover:text-blue-500'
                        "
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z"
                        />
                    </svg>
                    <span>Dashboard</span>
                </Link>
            </div>

            <div class="space-y-1">
                <span
                    class="block px-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2"
                >
                    Master Data
                </span>
                <div>
                    <div
                        v-show="isProcessOpen"
                        class="mt-1 ml-4 pl-4 border-l space-y-1 transition duration-150"
                        :class="
                            currentPath.startsWith('/process')
                                ? 'border-blue-500'
                                : 'border-slate-800'
                        "
                    >
                        <Link
                                href="/process"
                                class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium transition duration-150"
                                :class="
                                    currentPath === '/process'
                                        ? 'text-white font-bold bg-slate-800/60'
                                        : 'text-slate-400 hover:text-white hover:bg-slate-800/20'
                                "
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75 2.25 12l4.179 2.25m0-4.5 5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0 4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0-5.571 3-5.571-3" />
                                </svg>

                                <span>Process Template</span>
                            </Link>
                    </div>
                </div>
            </div>

            <div class="space-y-1">
                <span
                    class="block px-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2"
                >
                    Application Setting
                </span>
                <div>
                    <button
                        @click="toggleUserManagement"
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition duration-150 group"
                        :class="
                            currentPath.startsWith('/users')
                                ? 'bg-slate-800 text-white font-semibold'
                                : 'text-slate-300 hover:bg-slate-800 hover:text-white'
                        "
                    >
                        <div class="flex items-center gap-3">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5 transition"
                                :class="
                                    currentPath.startsWith('/users')
                                        ? 'text-blue-500'
                                        : 'text-slate-400 group-hover:text-blue-500'
                                "
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"
                                />
                                <circle
                                    cx="9"
                                    cy="7"
                                    r="4"
                                    stroke="currentColor"
                                    stroke-width="2"
                                />
                            </svg>
                            <span>User Management</span>
                        </div>

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4 text-slate-500 transition-transform duration-200"
                            :class="{
                                'rotate-180 text-blue-500':
                                    isUserManagementOpen,
                            }"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 9l-7 7-7-7"
                            />
                        </svg>
                    </button>

                    <div
                        v-show="isUserManagementOpen"
                        class="mt-1 ml-4 pl-4 border-l space-y-1 transition duration-150"
                        :class="
                            currentPath.startsWith('/users')
                                ? 'border-blue-500'
                                : 'border-slate-800'
                        "
                    >
                        <Link
                            href="/users"
                            class="flex items-center px-3 py-2 rounded-lg text-xs font-medium transition duration-150"
                            :class="
                                currentPath === '/users'
                                    ? 'text-white font-bold bg-slate-800/60'
                                    : 'text-slate-400 hover:text-white hover:bg-slate-800/20'
                            "
                        >
                            Users List
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <div class="p-4 border-t border-slate-800 bg-slate-950/40 text-center">
            <span class="text-[10px] font-mono text-slate-600 tracking-wider">
                v{{ $page.props.app_version ?? "1.0.0" }}
            </span>
        </div>
    </aside>
</template>
