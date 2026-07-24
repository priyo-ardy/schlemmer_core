<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";

defineProps({
    status: String,
});

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    // Bersihkan error lama setiap kali tombol ditekan[cite: 4]
    form.clearErrors();

    // Validasi instan di Frontend (Client-side)[cite: 4]
    if (!form.email) {
        form.setError("email", "Email address is required.");
    }

    if (!form.password) {
        form.setError("password", "Password is required.");
    }

    // Jika ada error, stop proses submit ke backend[cite: 4]
    if (form.hasErrors) {
        return;
    }

    // Jika semua terisi, kirim ke rute POST /login yang benar
    form.post("/login", {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <Head title=".:: User Authorization ::." />

    <div
        class="min-h-screen flex flex-col justify-between bg-slate-50 px-4 sm:px-6 lg:px-8"
    >
        <div class="flex-1 flex flex-col justify-center items-center">
            <div
                class="w-full max-w-md bg-white rounded-2xl shadow-sm border border-slate-100 p-8 sm:p-10"
            >
                <div class="text-center mb-8">
                    <img
                        src="/logo2.webp"
                        alt="Company Logo"
                        class="h-15 w-auto mx-auto object-contain mb-4"
                    />
                    <h2
                        class="text-2xl font-bold tracking-tight text-slate-900"
                    >
                        Sign In
                    </h2>
                    <p class="mt-2 text-sm text-slate-500">
                        Access your secure dashboard
                    </p>
                </div>

                <div
                    v-if="status"
                    class="mb-4 font-medium text-sm text-green-600 bg-green-50 p-3 rounded-lg border border-green-100"
                >
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label
                            for="email"
                            class="block text-sm font-medium text-slate-700 mb-1.5"
                            >Email Address</label
                        >
                        <input
                            id="email"
                            type="email"
                            v-model="form.email"
                            autofocus
                            class="w-full px-4 py-2.5 bg-slate-50 border rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none transition duration-200 text-sm"
                            :class="{
                                'border-rose-500 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500':
                                    form.errors.email,
                                'border-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600':
                                    !form.errors.email,
                            }"
                            placeholder="name@schlemmer.co.id"
                        />
                        <!-- Tempat munculnya error dari backend (AuthService) maupun frontend -->
                        <div
                            v-if="form.errors.email"
                            class="text-xs text-rose-500 mt-1.5 font-medium flex items-center gap-1"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-3.5 w-3.5 shrink-0"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span>{{ form.errors.email }}</span>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-1.5">
                            <label
                                for="password"
                                class="block text-sm font-medium text-slate-700"
                                >Password</label
                            >
                            <Link
                                href="/forgot-password"
                                class="text-xs font-semibold text-blue-600 hover:text-blue-700 transition duration-150"
                            >
                                Forgot Password?
                            </Link>
                        </div>
                        <input
                            id="password"
                            type="password"
                            v-model="form.password"
                            class="w-full px-4 py-2.5 bg-slate-50 border rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none transition duration-200 text-sm"
                            :class="{
                                'border-rose-500 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500':
                                    form.errors.password,
                                'border-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600':
                                    !form.errors.password,
                            }"
                            placeholder="••••••••"
                        />
                        <div
                            v-if="form.errors.password"
                            class="text-xs text-rose-500 mt-1.5 font-medium flex items-center gap-1"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-3.5 w-3.5 shrink-0"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span>{{ form.errors.password }}</span>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input
                            id="remember"
                            type="checkbox"
                            v-model="form.remember"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500/20 border-slate-300 rounded"
                        />
                        <label
                            for="remember"
                            class="ml-2 block text-xs text-slate-600 select-none"
                        >
                            Keep me signed in
                        </label>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 active:bg-blue-800 disabled:opacity-50 disabled:cursor-not-allowed transition duration-150 ease-in-out"
                    >
                        <span
                            v-if="form.processing"
                            class="flex items-center gap-2"
                        >
                            <svg
                                class="animate-spin h-4 w-4 text-white"
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
                            Processing...
                        </span>
                        <span v-else>Sign In</span>
                    </button>
                </form>
            </div>
        </div>

        <div class="py-6 text-center">
            <p class="text-xs text-slate-400 font-medium">
                &copy; {{ new Date().getFullYear() }} Enterprise App Platform.
                All rights reserved.
            </p>
        </div>
    </div>
</template>