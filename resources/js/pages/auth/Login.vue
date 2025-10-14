<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Badge } from '@/components/ui/badge';
import AuthBase from '@/layouts/AuthLayout.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { useForm, Head, router } from '@inertiajs/vue3';
import { LoaderCircle, ShieldCheck, UserCog, User } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const quickLoggingIn = ref<string | null>(null);

const quickLogin = (email: string, password: string, role: string) => {
    quickLoggingIn.value = role;
    
    router.post('/login', {
        email,
        password,
        remember: false,
    }, {
        onFinish: () => {
            quickLoggingIn.value = null;
        },
    });
};

const submit = () => {
    form.post(store.url(), {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <AuthBase
        title="Log in to your account"
        description="Enter your email and password below to log in"
    >
        <Head title="Log in" />

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password">Password</Label>
                        <TextLink
                            v-if="canResetPassword"
                            :href="request()"
                            class="text-sm"
                            :tabindex="5"
                        >
                            Forgot password?
                        </TextLink>
                    </div>
                    <Input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="Password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <Label for="remember" class="flex items-center space-x-3">
                        <Checkbox id="remember" v-model:checked="form.remember" :tabindex="3" />
                        <span>Remember me</span>
                    </Label>
                </div>

                <Button
                    type="submit"
                    class="mt-4 w-full"
                    :tabindex="4"
                    :disabled="form.processing"
                    data-test="login-button"
                >
                    <LoaderCircle
                        v-if="form.processing"
                        class="h-4 w-4 animate-spin"
                    />
                    Log in
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Don't have an account?
                <TextLink :href="register()" :tabindex="5">Sign up</TextLink>
            </div>
        </form>

        <!-- Quick Login Section (Development/Testing) -->
        <div class="mt-6">
            <Separator class="my-4" />
            <div class="text-center mb-3">
                <p class="text-sm font-medium text-muted-foreground">Quick Login (Dev Mode)</p>
                <p class="text-xs text-muted-foreground mt-1">Click to login as seeded user</p>
            </div>
            
            <div class="grid gap-2">
                <!-- Superadmin Quick Login -->
                <Button
                    variant="outline"
                    class="w-full justify-between"
                    @click="quickLogin('superadmin@example.com', 'password', 'superadmin')"
                    :disabled="quickLoggingIn !== null"
                >
                    <div class="flex items-center gap-2">
                        <ShieldCheck class="h-4 w-4 text-red-600" />
                        <span class="text-sm">Superadmin</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <Badge variant="destructive" class="text-xs">SUPERADMIN</Badge>
                        <LoaderCircle
                            v-if="quickLoggingIn === 'superadmin'"
                            class="h-4 w-4 animate-spin"
                        />
                    </div>
                </Button>

                <!-- Admin Quick Login -->
                <Button
                    variant="outline"
                    class="w-full justify-between"
                    @click="quickLogin('admin@example.com', 'password', 'admin')"
                    :disabled="quickLoggingIn !== null"
                >
                    <div class="flex items-center gap-2">
                        <UserCog class="h-4 w-4 text-blue-600" />
                        <span class="text-sm">Admin</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <Badge variant="default" class="text-xs">ADMIN</Badge>
                        <LoaderCircle
                            v-if="quickLoggingIn === 'admin'"
                            class="h-4 w-4 animate-spin"
                        />
                    </div>
                </Button>

                <!-- User Quick Login -->
                <Button
                    variant="outline"
                    class="w-full justify-between"
                    @click="quickLogin('user@example.com', 'password', 'user')"
                    :disabled="quickLoggingIn !== null"
                >
                    <div class="flex items-center gap-2">
                        <User class="h-4 w-4 text-green-600" />
                        <span class="text-sm">User</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <Badge variant="secondary" class="text-xs">USER</Badge>
                        <LoaderCircle
                            v-if="quickLoggingIn === 'user'"
                            class="h-4 w-4 animate-spin"
                        />
                    </div>
                </Button>
            </div>

            <div class="mt-3 text-center">
                <p class="text-xs text-muted-foreground">
                    All accounts use password: <code class="bg-muted px-1 py-0.5 rounded">password</code>
                </p>
            </div>
        </div>
    </AuthBase>
</template>
