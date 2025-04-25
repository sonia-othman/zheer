<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
        default: true,
    },
    status: {
        type: String,
        default: '',
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);
const loginAttempts = ref(0);
const isLocked = ref(false);
const countdown = ref(0);

const submit = () => {
    form.post(route('login'), {
        preserveScroll: true,
        onSuccess: () => {
            // Reset attempts on successful login
            loginAttempts.value = 0;
        },
        onError: () => {
            loginAttempts.value++;
            if (loginAttempts.value >= 5) {
                lockAccountTemporarily();
            }
        },
    });
};

const lockAccountTemporarily = () => {
    isLocked.value = true;
    countdown.value = 60; // 1 minute lockout
    
    const timer = setInterval(() => {
        countdown.value--;
        if (countdown.value <= 0) {
            clearInterval(timer);
            isLocked.value = false;
            loginAttempts.value = 0;
        }
    }, 1000);
};

// Clear errors when user starts typing
watch(() => form.email, () => form.clearErrors('email'));
watch(() => form.password, () => form.clearErrors('password'));
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    :disabled="isLocked"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />
                <div class="relative">
                    <TextInput
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        class="mt-1 block w-full pr-10"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        :disabled="isLocked"
                    />
                    <button
                        type="button"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-500"
                        @click="showPassword = !showPassword"
                    >
                        <span class="material-icons">
                            {{ showPassword ? 'visibility_off' : 'visibility' }}
                        </span>
                    </button>
                </div>
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" :disabled="isLocked" />
                    <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">
                        Remember me
                    </span>
                </label>
            </div>

            <div v-if="isLocked" class="mt-4 text-sm text-red-600">
                Too many login attempts. Please try again in {{ countdown }} seconds.
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
                >
                    Forgot your password?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing || isLocked }"
                    :disabled="form.processing || isLocked"
                >
                    Log in
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>