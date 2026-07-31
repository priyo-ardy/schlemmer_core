<script setup>
import { Head, useForm } from "@inertiajs/vue3";

defineProps({
    status: String,
});

const form = useForm({
    email: "",
});

const handleSubmit = () => {
    form.post("/forgot-password");
};
</script>

<template>
    <Head title="Forgot Password" />

    <div
        class="min-h-screen flex items-center justify-center bg-slate-50 font-sans text-slate-800"
    >
        <div
            class="w-full max-w-md p-8 bg-white border border-slate-200 shadow-sm"
        >
            <div class="mb-6">
                <h2 class="text-xl font-bold text-slate-900">
                    Forgot Password
                </h2>
                <p class="text-sm text-slate-500 mt-1">
                    Enter your email address, and we'll send a link to reset
                    your password to your email
                </p>
            </div>

            <div
                v-if="status"
                class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold"
            >
                {{ status }}
            </div>

            <div class="space-y-4">
                <div>
                    <label
                        class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2"
                        >Email Address</label
                    >
                    <input
                        type="email"
                        v-model="form.email"
                        placeholder="name@company.com"
                        required
                        :disabled="form.processing"
                        class="w-full px-4 py-3 bg-white border text-sm font-medium transition duration-150 focus:outline-none focus:ring-2 focus:ring-blue-600/20"
                        :class="
                            form.errors.email
                                ? 'border-rose-300 focus:border-rose-500'
                                : 'border-slate-200 focus:border-blue-600'
                        "
                    />
                    <p
                        v-if="form.errors.email"
                        class="text-xs text-rose-600 font-semibold mt-1"
                    >
                        {{ form.errors.email }}
                    </p>
                </div>

                <button
                    type="button"
                    @click="handleSubmit"
                    :disabled="form.processing"
                    class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm transition duration-150 disabled:opacity-50 focus:outline-none"
                >
                    {{ form.processing ? "Sending..." : "Send Reset Link" }}
                </button>
            </div>

            <div class="mt-5 text-center">
                <a
                    href="/"
                    class="text-xs font-bold text-blue-600 hover:text-blue-700"
                >
                    ← Back to Login Page
                </a>
            </div>
        </div>
    </div>
</template>
