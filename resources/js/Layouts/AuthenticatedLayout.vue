<script setup>
import { computed, ref, watch } from 'vue';
import AppSidebar from '@/Components/AppSidebar.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import FlashAlerts from '@/Components/FlashAlerts.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
const page = usePage();
const unreadCount = computed(() => page.props.notifications?.unread_count || 0);
const userName = computed(() => page.props.auth?.user?.name || 'Account');
const userInitials = computed(() => {
    const parts = String(userName.value).trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return '?';
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
});

watch(
    () => page.url,
    () => {
        showingNavigationDropdown.value = false;
    },
);
</script>

<template>
    <div class="min-h-screen bg-app lg:flex">
        <aside class="hidden w-64 shrink-0 border-r border-border/70 bg-sidebar lg:fixed lg:inset-y-0 lg:flex lg:flex-col">
            <AppSidebar />
        </aside>

        <div class="flex min-h-screen min-w-0 flex-1 flex-col lg:pl-64">
            <header class="app-topbar sticky top-0 z-20 flex h-14 shrink-0 items-center justify-between gap-3 border-b border-border/60 bg-surface/90 px-4 backdrop-blur-md sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-control text-ink-muted transition duration-fast ease-out hover:bg-app hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-brand lg:hidden"
                        @click="showingNavigationDropdown = !showingNavigationDropdown"
                    >
                        <span class="sr-only">Toggle navigation</span>
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex items-center gap-1.5 sm:gap-2">
                    <Link
                        :href="route('notifications.index')"
                        class="relative inline-flex min-h-10 min-w-10 items-center justify-center rounded-control text-ink-muted transition duration-fast ease-out hover:bg-app hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                        title="Notifications"
                    >
                        <span class="sr-only">Notifications</span>
                        <svg class="h-5 w-5 transition-colors duration-fast ease-out" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
                        <span
                            v-if="unreadCount > 0"
                            class="app-badge-pulse absolute right-1 top-1 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-danger px-1 text-[10px] font-semibold text-white shadow-soft"
                        >{{ unreadCount }}</span>
                    </Link>
                    <div class="relative">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <span class="inline-flex rounded-control">
                                    <button
                                        type="button"
                                        class="inline-flex min-h-10 items-center gap-2 rounded-control px-1.5 py-1.5 text-sm font-medium text-ink-muted transition duration-fast ease-out hover:bg-app hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-brand sm:px-2"
                                        :title="userName"
                                    >
                                        <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-soft text-[11px] font-semibold text-brand ring-2 ring-white">
                                            {{ userInitials }}
                                        </span>
                                        <span class="hidden max-w-[10rem] truncate sm:inline">{{ userName }}</span>
                                        <svg class="hidden h-4 w-4 shrink-0 sm:inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                        <span class="sr-only">Account menu</span>
                                    </button>
                                </span>
                            </template>
                            <template #content>
                                <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </header>

            <div
                v-if="showingNavigationDropdown"
                class="fixed inset-0 z-30 lg:hidden"
            >
                <div class="absolute inset-0 bg-ink/40" @click="showingNavigationDropdown = false" />
                <aside class="absolute inset-y-0 left-0 flex w-72 max-w-[85vw] flex-col bg-sidebar shadow-lift">
                    <AppSidebar mobile />
                </aside>
            </div>

            <!-- Page identity sits on the canvas, not a competing card band -->
            <div v-if="$slots.header" class="border-b border-border/50 bg-surface">
                <div class="app-page py-6 sm:py-7">
                    <slot name="header" />
                </div>
            </div>

            <main class="flex-1 bg-surface">
                <FlashAlerts />
                <slot />
            </main>
        </div>
    </div>
</template>
