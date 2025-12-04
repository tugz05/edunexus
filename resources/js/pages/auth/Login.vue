<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register, home } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Eye, EyeOff } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();

const showPassword = ref(false);
const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};
</script>

<template>
    <Head title="Log in" />

    <div
        class="relative flex min-h-screen items-center justify-center overflow-hidden bg-white px-4 py-12 sm:px-6 lg:px-8"
    >
        <!-- Wavy Background -->
        <div
            class="absolute inset-0 -z-0 overflow-hidden opacity-30"
        >
            <svg
                class="absolute bottom-0 left-0 w-full"
                viewBox="0 0 1440 320"
                preserveAspectRatio="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    fill="#F3E8FF"
                    d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"
                ></path>
            </svg>
            <svg
                class="absolute top-0 left-0 w-full rotate-180"
                viewBox="0 0 1440 320"
                preserveAspectRatio="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    fill="#E9D5FF"
                    d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"
                ></path>
            </svg>
        </div>
        <div
            class="relative z-10 w-full max-w-md rounded-xl bg-white p-8 shadow-lg sm:p-10"
        >
            <!-- Back to Home Button -->
            <Link
                :href="home()"
                class="mb-6 inline-flex items-center gap-2 text-sm text-[#6B7280] transition-colors hover:text-[#5B21B6]"
            >
                <ArrowLeft class="h-4 w-4" />
                <span>Back to Home</span>
            </Link>

            <!-- Header Section -->
            <div class="mb-8">
                <h1
                    class="mb-3 text-3xl font-bold text-brand-primary sm:text-4xl"
                >
                    Welcome Back
                </h1>
                <p
                    class="text-sm text-[#4B5563] sm:text-base"
                >
                    Access personalized learning resources powered by AI. Get
                    tailored content recommendations that adapt to your learning
                    style and goals.
                </p>
            </div>

            <!-- Status Message -->
            <div
                v-if="status"
                class="mb-6 rounded-lg bg-green-50 p-3 text-center text-sm font-medium text-green-600"
            >
                {{ status }}
            </div>

            <!-- Login Form -->
            <Form
                v-bind="store.form()"
                :reset-on-success="['password']"
                v-slot="{ errors, processing }"
                class="space-y-6"
            >
                <!-- Email Field -->
                <div class="space-y-2">
                    <Label
                        for="email"
                        class="text-sm font-medium text-[#374151]"
                    >
                        Email
                    </Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="Enter your email"
                        class="h-11 rounded-lg border-[#D1D5DB] bg-[#F9FAFB] text-[#111827] placeholder:text-[#9CA3AF] focus:border-[#5B21B6] focus:ring-[#5B21B6]"
                    />
                    <InputError :message="errors.email" />
                </div>

                <!-- Password Field -->
                <div class="space-y-2">
                    <Label
                        for="password"
                        class="text-sm font-medium text-[#374151]"
                    >
                        Password
                    </Label>
                    <div class="relative">
                        <Input
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            name="password"
                            required
                            :tabindex="2"
                            autocomplete="current-password"
                            placeholder="Enter your password"
                            class="h-11 rounded-lg border-[#D1D5DB] bg-[#F9FAFB] pr-10 text-[#111827] placeholder:text-[#9CA3AF] focus:border-[#5B21B6] focus:ring-[#5B21B6]"
                        />
                        <button
                            type="button"
                            @click="togglePasswordVisibility"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-[#6B7280] hover:text-[#374151]"
                            tabindex="-1"
                        >
                            <EyeOff
                                v-if="showPassword"
                                class="h-5 w-5"
                            />
                            <Eye
                                v-else
                                class="h-5 w-5"
                            />
                        </button>
                    </div>
                    <InputError :message="errors.password" />
                </div>

                <!-- Remember Me & Forgot Password -->
                <div
                    class="flex items-center justify-between"
                >
                    <Label
                        for="remember"
                        class="flex cursor-pointer items-center gap-2 text-sm text-[#6B7280]"
                    >
                        <Checkbox
                            id="remember"
                            name="remember"
                            :tabindex="3"
                            class="h-4 w-4 rounded border-[#D1D5DB] data-[state=checked]:bg-[#5B21B6] data-[state=checked]:border-[#5B21B6] focus:ring-[#5B21B6]"
                        />
                        <span>Remember me</span>
                    </Label>
                    <Link
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-sm text-[#6B7280] no-underline hover:text-[#111827]"
                        :tabindex="5"
                    >
                        Forgot Password ?
                    </Link>
                </div>

                <!-- Login Button -->
                <Button
                    type="submit"
                    class="h-11 w-full rounded-lg bg-brand-primary text-sm font-semibold uppercase tracking-wide text-white transition-colors hover:bg-brand-primary-hover"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <Spinner
                        v-if="processing"
                        class="mr-2"
                    />
                    <span v-if="!processing">LOGIN</span>
                    <span v-else>Logging in...</span>
                </Button>

                <!-- Google Sign In Button -->
                <Button
                    type="button"
                    class="h-11 w-full rounded-lg border border-[#D1D5DB] bg-[#F3E8FF] text-sm font-semibold text-[#374151] transition-colors hover:bg-[#E9D5FF]"
                    :tabindex="6"
                >
                    <svg
                        class="mr-2 h-5 w-5"
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                            fill="#4285F4"
                        />
                        <path
                            d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                            fill="#34A853"
                        />
                        <path
                            d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                            fill="#FBBC05"
                        />
                        <path
                            d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                            fill="#EA4335"
                        />
                    </svg>
                    SIGN IN WITH GOOGLE
                </Button>

                <!-- Sign Up Link -->
                <div
                    v-if="canRegister"
                    class="pt-4 text-center text-sm text-[#4B5563]"
                >
                    You don't have an account yet?
                    <Link
                        :href="register()"
                        class="ml-1 font-medium text-[#F97316] no-underline hover:text-[#EA580C]"
                        :tabindex="5"
                    >
                        Sign up
                    </Link>
                </div>
            </Form>
        </div>
    </div>
</template>
