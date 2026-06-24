<script setup>
import { router, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { useSidebar } from "../../Composables/useSidebar";

const { toggleSidebar } = useSidebar();


defineEmits(["toggle-sidebar"]);

const page = usePage();
const user = computed(() => page.props.auth.user);
const isProfileDropdownOpen = ref(false);

const pageTitle = computed(() => page.props.page_title || "Overview / Analytics");

const handleLogout = () => {
    router.post("/logout");
};

const hasAvatar = computed(() => {
    return user.value?.avatar && user.value.avatar.trim() !== "";
});

const userInitials = computed(() => {
    if (!user.value?.name) return "AP";

    const names = user.value.name.trim().split(" ");

    if (names.length === 1) {
        return names[0].substring(0, 2).toUpperCase();
    }

    return (
        names[0][0] +
        names[names.length - 1][0]
    ).toUpperCase();
});
</script>

<template>
    <header
        class="h-16 bg-white border-b border-slate-200/80 flex items-center justify-between px-6 z-10 flex-shrink-0"
    >
        <div class="flex items-center gap-4">
            <button
                @click="toggleSidebar"
                class="p-2 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition duration-150 focus:outline-none"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2.5"
                        d="M4 6h16M4 12h16M4 18h16"
                    />
                </svg>
            </button>
            <h2 class="text-sm font-semibold text-slate-400 hidden sm:block">
                {{ pageTitle }}
            </h2>
        </div>

        <div class="flex items-center gap-4">
            <button
                class="p-2 text-slate-400 hover:text-slate-600 rounded-xl relative hover:bg-slate-50 transition"
            >
                <span
                    class="absolute top-2 right-2 h-2 w-2 rounded-full bg-blue-600"
                ></span>
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                    />
                </svg>
            </button>

            <div class="h-6 w-[1px] bg-slate-200"></div>

            <div class="relative">
                <button
                    @click="isProfileDropdownOpen = !isProfileDropdownOpen"
                    class="flex items-center gap-3 p-1.5 rounded-xl hover:bg-slate-50 transition duration-150 focus:outline-none"
                >
                    <div
                        class="h-8 w-8 rounded-lg overflow-hidden bg-blue-600 flex items-center justify-center text-white font-bold text-sm shadow-sm"
                    >
                        <img
                            v-if="hasAvatar"
                            :src="user.avatar"
                            :alt="user.name"
                            class="w-full h-full object-cover"
                        />

                        <span v-else>
                            {{ userInitials }}
                        </span>
                    </div>
                    <div class="text-left hidden md:block">
                        <p
                            class="text-xs font-bold text-slate-800 leading-none"
                        >
                            {{ user?.name }}
                        </p>
                        <p
                            class="text-[10px] text-slate-400 font-medium mt-0.5"
                        >
                            {{ user?.email }}
                        </p>
                    </div>
                </button>

                <div
                    v-if="isProfileDropdownOpen"
                    class="absolute right-0 mt-2 w-56 bg-white border border-slate-200 rounded-2xl shadow-xl py-2 z-30 animate-in fade-in slide-in-from-top-2"
                >
                    <a
                        href="#"
                        class="flex items-center gap-2 px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50 transition"
                        >My Profile</a
                    >
                    <hr class="border-slate-100 my-1" />
                    <button
                        @click="handleLogout"
                        class="w-full flex items-center gap-2 px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition text-left"
                    >
                        Sign Out
                    </button>
                </div>
            </div>
        </div>
    </header>
</template>
