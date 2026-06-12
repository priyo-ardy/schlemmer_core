<script setup>
import { Head, useForm } from "@inertiajs/vue3";

// Props yang dilempar dari AuthController
const props = defineProps({
    token: String,
    email: String,
});

// Inertia Form Helper
const form = useForm({
    token: props.token,
    email: props.email || "",
    password: "",
    password_confirmation: "",
});

const handleSubmit = () => {
    form.post("/reset-password", {
        onFinish: () => form.reset("password", "password_confirmation"),
    });
};
</script>

<template>
    <Head title="Secure Password Reset" />

    <div
        class="min-h-screen w-screen flex items-center justify-center bg-slate-50 font-sans antialiased text-slate-800 relative overflow-hidden"
    >
        <div
            class="absolute -top-40 -right-40 w-96 h-96 bg-blue-600/10 rounded-full blur-3xl pointer-events-none"
        ></div>
        <div
            class="absolute -bottom-40 -left-40 w-96 h-96 bg-emerald-500/5 rounded-full blur-3xl pointer-events-none"
        ></div>

        <div class="w-full max-w-md p-4 sm:p-6 z-10">
            <div class="flex flex-col items-center mb-8">
                <div
                    class="h-12 w-12 bg-blue-600 rounded-2xl flex items-center justify-center shadow-xl shadow-blue-600/20 mb-4"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-6 w-6 text-white"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2.5"
                            d="M13 10V3L4 14h7v7l9-11h-7z"
                        />
                    </svg>
                </div>
                <h1
                    class="text-xl font-black text-slate-900 tracking-tight text-center"
                >
                    Schlemmer Indonesia<br />
                    <span class="text-blue-600 font-medium"
                        >Security System</span
                    >
                </h1>
                <p class="text-xs text-slate-400 mt-1 font-medium">
                    Account Access Recovery Protocol
                </p>
            </div>

            <div
                class="bg-white rounded-3xl border border-slate-200/70 shadow-xl shadow-slate-100/50 p-6 sm:p-8"
            >
                <div class="mb-6">
                    <h2 class="text-lg font-bold text-slate-900">
                        Set New Password
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">
                        Make sure your new password is strong, unique, and can’t
                        be guessed by others.
                    </p>
                </div>

                <form @submit.prevent="handleSubmit" class="space-y-5">
                    <div>
                        <label
                            class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2"
                            >Registered Email</label
                        >
                        <div class="relative">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400"
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
                                        stroke-width="2"
                                        d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206"
                                    />
                                </svg>
                            </span>
                            <input
                                type="email"
                                v-model="form.email"
                                readonly
                                class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 text-slate-500 rounded-xl text-sm font-medium focus:outline-none cursor-not-allowed"
                            />
                        </div>
                        <p
                            v-if="form.errors.email"
                            class="text-xs text-rose-600 font-semibold mt-1.5 flex items-center gap-1"
                        >
                            <i
                                class="fa-solid fa-circle-exclamation text-[10px]"
                            ></i>
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2"
                            >New Password</label
                        >
                        <div class="relative">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400"
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
                                        stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                    />
                                </svg>
                            </span>
                            <input
                                type="password"
                                v-model="form.password"
                                placeholder="••••••••"
                                required
                                class="w-full pl-10 pr-4 py-3 bg-white border rounded-xl text-sm font-medium transition duration-150 focus:outline-none focus:ring-2 focus:ring-blue-600/20"
                                :class="
                                    form.errors.password
                                        ? 'border-rose-300 focus:border-rose-500 focus:ring-rose-600/10'
                                        : 'border-slate-200 focus:border-blue-600'
                                "
                            />
                        </div>
                        <p
                            v-if="form.errors.password"
                            class="text-xs text-rose-600 font-semibold mt-1.5 flex items-center gap-1"
                        >
                            <i
                                class="fa-solid fa-circle-exclamation text-[10px]"
                            ></i>
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2"
                            >Confirm Password</label
                        >
                        <div class="relative">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400"
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
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                    />
                                </svg>
                            </span>
                            <input
                                type="password"
                                v-model="form.password_confirmation"
                                placeholder="••••••••"
                                required
                                class="w-full pl-10 pr-4 py-3 bg-white border rounded-xl text-sm font-medium transition duration-150 focus:outline-none focus:ring-2 focus:ring-blue-600/20"
                                :class="
                                    form.errors.password_confirmation
                                        ? 'border-rose-300 focus:border-rose-500 focus:ring-rose-600/10'
                                        : 'border-slate-200 focus:border-blue-600'
                                "
                            />
                        </div>
                    </div>

                    <div
                        class="bg-slate-50 rounded-xl p-3.5 border border-slate-100 flex items-start gap-2.5"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4 text-blue-600 flex-shrink-0 mt-0.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <p
                            class="text-[11px] text-slate-500 font-medium leading-relaxed"
                        >
                            The password must be at least
                            <strong
                                >8 characters long and include a combination of
                                letters, numbers, uppercase letters, and special
                                characters</strong
                            >
                        </p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold text-sm rounded-xl transition duration-150 shadow-lg shadow-blue-500/10 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none"
                    >
                        <svg
                            v-if="form.processing"
                            class="animate-spin h-4 w-4 text-white"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        <span>{{
                            form.processing
                                ? "Updating Secure Data..."
                                : "Reset & Update Password"
                        }}</span>
                    </button>
                </form>
            </div>

            <p class="text-center text-[11px] text-slate-400 mt-8 font-medium">
                Secured by Schlemmer Indonesia Security Framework.
            </p>
        </div>
    </div>
</template>
