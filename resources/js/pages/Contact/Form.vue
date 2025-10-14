<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Head } from '@inertiajs/vue3';
import { Send, CheckCircle, AlertCircle, Mail, User, MessageSquare } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    categories: Record<string, string>;
}

const props = defineProps<Props>();

const form = ref({
    name: '',
    email: '',
    description: '',
    category: ''
});

const isSubmitting = ref(false);
const submitted = ref(false);
const errors = ref<any>({});
const errorMessage = ref('');

const submitForm = async () => {
    if (!form.value.category) {
        errors.value = { category: ['Please select a category'] };
        return;
    }

    isSubmitting.value = true;
    errors.value = {};
    errorMessage.value = '';

    try {
        const response = await fetch(`/contact/${form.value.category}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify(form.value)
        });

        const data = await response.json();

        if (data.success) {
            submitted.value = true;
            // Reset form
            form.value = {
                name: '',
                email: '',
                description: '',
                category: ''
            };
        } else {
            if (data.errors) {
                errors.value = data.errors;
            } else {
                errorMessage.value = data.message || 'An error occurred while submitting the form.';
            }
        }
    } catch (error) {
        errorMessage.value = 'Network error. Please try again.';
    } finally {
        isSubmitting.value = false;
    }
};

const resetForm = () => {
    submitted.value = false;
    errors.value = {};
    errorMessage.value = '';
};
</script>

<template>
    <Head title="Contact Us" />
    
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-12 px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">Contact Us</h1>
                <p class="text-lg text-gray-600">
                    Get in touch with us. We'd love to hear from you!
                </p>
            </div>

            <Card class="shadow-lg">
                <!-- Success Message -->
                <div v-if="submitted" class="p-6">
                    <Alert class="border-green-200 bg-green-50">
                        <CheckCircle class="h-4 w-4 text-green-600" />
                        <AlertDescription class="text-green-800">
                            <div class="font-semibold mb-2">Thank you for your message!</div>
                            <p>Your contact form has been submitted successfully. We'll get back to you as soon as possible.</p>
                        </AlertDescription>
                    </Alert>
                    
                    <div class="mt-6 flex justify-center">
                        <Button @click="resetForm" variant="outline">
                            Send Another Message
                        </Button>
                    </div>
                </div>

                <!-- Contact Form -->
                <div v-else>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Mail class="h-5 w-5" />
                            Send us a message
                        </CardTitle>
                        <CardDescription>
                            Fill out the form below and we'll get back to you shortly.
                        </CardDescription>
                    </CardHeader>

                    <CardContent>
                        <!-- Error Message -->
                        <Alert v-if="errorMessage" class="mb-6 border-red-200 bg-red-50">
                            <AlertCircle class="h-4 w-4 text-red-600" />
                            <AlertDescription class="text-red-800">
                                {{ errorMessage }}
                            </AlertDescription>
                        </Alert>

                        <form @submit.prevent="submitForm" class="space-y-6">
                            <!-- Category Selection -->
                            <div class="space-y-2">
                                <Label for="category" class="flex items-center gap-2">
                                    <MessageSquare class="h-4 w-4" />
                                    Category *
                                </Label>
                                <Select v-model="form.category" required>
                                    <SelectTrigger :class="{ 'border-red-500': errors.category }">
                                        <SelectValue placeholder="Select a category" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem 
                                            v-for="(label, value) in categories" 
                                            :key="value" 
                                            :value="value"
                                        >
                                            {{ label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="errors.category" class="text-sm text-red-600">
                                    {{ errors.category[0] }}
                                </p>
                            </div>

                            <!-- Name -->
                            <div class="space-y-2">
                                <Label for="name" class="flex items-center gap-2">
                                    <User class="h-4 w-4" />
                                    Full Name *
                                </Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Enter your full name"
                                    required
                                    :class="{ 'border-red-500': errors.name }"
                                />
                                <p v-if="errors.name" class="text-sm text-red-600">
                                    {{ errors.name[0] }}
                                </p>
                            </div>

                            <!-- Email -->
                            <div class="space-y-2">
                                <Label for="email" class="flex items-center gap-2">
                                    <Mail class="h-4 w-4" />
                                    Email Address *
                                </Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    placeholder="your.email@example.com"
                                    required
                                    :class="{ 'border-red-500': errors.email }"
                                />
                                <p v-if="errors.email" class="text-sm text-red-600">
                                    {{ errors.email[0] }}
                                </p>
                            </div>

                            <!-- Description -->
                            <div class="space-y-2">
                                <Label for="description">
                                    Message *
                                </Label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    placeholder="Please describe your inquiry or message in detail..."
                                    rows="6"
                                    required
                                    :class="{ 'border-red-500': errors.description }"
                                />
                                <p v-if="errors.description" class="text-sm text-red-600">
                                    {{ errors.description[0] }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    Minimum 10 characters required
                                </p>
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-4">
                                <Button 
                                    type="submit" 
                                    :disabled="isSubmitting"
                                    class="w-full"
                                    size="lg"
                                >
                                    <Send class="mr-2 h-4 w-4" />
                                    {{ isSubmitting ? 'Sending Message...' : 'Send Message' }}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </div>
            </Card>

            <!-- Additional Info -->
            <div class="mt-8 text-center text-sm text-gray-500">
                <p>
                    This form is for testing purposes. All submissions are stored securely.
                </p>
            </div>
        </div>
    </div>
</template>
