<script setup lang="ts">
import { dashboard, login } from '@/routes';
import { form as contactForm } from '@/routes/contact';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="CRM Hub - Multi-Brand Contact Management">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div
        class="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]"
    >
        <header
            class="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-4xl"
        >
            <nav class="flex items-center justify-end gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="login()"
                        class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                    >
                        Staff Login
                    </Link>
                </template>
            </nav>
        </header>
        <div
            class="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0"
        >
            <main
                class="flex w-full max-w-[335px] flex-col-reverse overflow-hidden rounded-lg lg:max-w-4xl lg:flex-row"
            >
                <div
                    class="flex-1 rounded-br-lg rounded-bl-lg bg-white p-6 pb-12 text-[13px] leading-[20px] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:rounded-tl-lg lg:rounded-br-none lg:p-20 dark:bg-[#161615] dark:text-[#EDEDEC] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
                >
                    <h1 class="mb-4 text-2xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]">Welcome to CRM Hub</h1>
                    <p class="mb-6 text-[#706f6c] dark:text-[#A1A09A] text-base leading-relaxed">
                        Your centralized contact management system for all our media brands. 
                        Manage customer inquiries, track communications, and provide excellent support across all channels.
                    </p>
                    
                    <div class="mb-6">
                        <h2 class="mb-3 text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Our Brands</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="flex items-center gap-3 p-3 rounded-lg bg-[#f8f9fa] dark:bg-[#2a2a2a] border border-[#e3e3e0] dark:border-[#3E3E3A]">
                                <span class="w-3 h-3 rounded-full bg-[#ff6b35]"></span>
                                <span class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">BigFM</span>
                            </div>
                            <div class="flex items-center gap-3 p-3 rounded-lg bg-[#f8f9fa] dark:bg-[#2a2a2a] border border-[#e3e3e0] dark:border-[#3E3E3A]">
                                <span class="w-3 h-3 rounded-full bg-[#4285f4]"></span>
                                <span class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">RPR1</span>
                            </div>
                            <div class="flex items-center gap-3 p-3 rounded-lg bg-[#f8f9fa] dark:bg-[#2a2a2a] border border-[#e3e3e0] dark:border-[#3E3E3A]">
                                <span class="w-3 h-3 rounded-full bg-[#34a853]"></span>
                                <span class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Regenbogen</span>
                            </div>
                            <div class="flex items-center gap-3 p-3 rounded-lg bg-[#f8f9fa] dark:bg-[#2a2a2a] border border-[#e3e3e0] dark:border-[#3E3E3A]">
                                <span class="w-3 h-3 rounded-full bg-[#ea4335]"></span>
                                <span class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">RockFM</span>
                            </div>
                            <div class="flex items-center gap-3 p-3 rounded-lg bg-[#f8f9fa] dark:bg-[#2a2a2a] border border-[#e3e3e0] dark:border-[#3E3E3A] sm:col-span-2">
                                <span class="w-3 h-3 rounded-full bg-[#fbbc04]"></span>
                                <span class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">BigKarriere</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h2 class="mb-3 text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">System Features</h2>
                        <ul class="space-y-2 text-[#706f6c] dark:text-[#A1A09A]">
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#34a853]"></span>
                                <span>Multi-brand contact form management</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#34a853]"></span>
                                <span>Real-time read status tracking</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#34a853]"></span>
                                <span>Advanced filtering and search</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#34a853]"></span>
                                <span>Role-based access control</span>
                            </li>
                        </ul>
                    </div>

                    <div class="flex gap-3 text-sm leading-normal">
                        <Link
                            :href="contactForm()"
                            class="inline-block rounded-sm border border-[#19140035] bg-[#1b1b18] px-5 py-1.5 text-sm leading-normal text-white hover:border-black hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:border-white dark:hover:bg-white"
                        >
                            Test Contact Form
                        </Link>
                        <Link
                            v-if="$page.props.auth.user"
                            :href="dashboard()"
                            class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                        >
                            Go to Dashboard
                        </Link>
                    </div>
                </div>
                <div
                    class="relative -mb-px aspect-335/376 w-full shrink-0 overflow-hidden rounded-t-lg bg-gradient-to-br from-blue-50 to-indigo-100 lg:mb-0 lg:-ml-px lg:aspect-auto lg:w-[438px] lg:rounded-t-none lg:rounded-r-lg dark:bg-gradient-to-br dark:from-gray-900 dark:to-blue-900"
                >
                    <!-- CRM Visual Elements -->
                    <div class="absolute inset-0 flex items-center justify-center p-8">
                        <div class="text-center">
                            <!-- CRM Icon -->
                            <div class="mb-6 flex justify-center">
                                <div class="relative">
                                    <div class="w-24 h-24 bg-white dark:bg-gray-800 rounded-2xl shadow-lg flex items-center justify-center">
                                        <svg class="w-12 h-12 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                    </div>
                                    <!-- Floating elements -->
                                    <div class="absolute -top-2 -right-2 w-6 h-6 bg-green-500 rounded-full flex items-center justify-center">
                                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div class="absolute -bottom-2 -left-2 w-6 h-6 bg-orange-500 rounded-full flex items-center justify-center">
                                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Stats -->
                            <div class="space-y-4">
                                <div class="flex justify-center space-x-6">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">5+</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Brands</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">24/7</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Support</div>
                                    </div>
                                </div>
                                
                                <!-- Connection lines -->
                                <div class="flex justify-center items-center space-x-4 mt-8">
                                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                        </svg>
                                    </div>
                                    <div class="w-16 h-0.5 bg-gray-300 dark:bg-gray-600"></div>
                                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                        </svg>
                                    </div>
                                    <div class="w-16 h-0.5 bg-gray-300 dark:bg-gray-600"></div>
                                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Border overlay -->
                    <div
                        class="absolute inset-0 rounded-t-lg shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:overflow-hidden lg:rounded-t-none lg:rounded-r-lg dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
                    />
                </div>
            </main>
        </div>
        <div class="hidden h-14.5 lg:block"></div>
    </div>
</template>