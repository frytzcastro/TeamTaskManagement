<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SectionCard from '@/Components/SectionCard.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({ notifications: Object });
const markRead = (id) => router.post(route('notifications.read', id));
const markAll = () => router.post(route('notifications.read-all'));
</script>

<template>
    <Head title="Notifications" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="Notifications" description="Updates on assignments, status changes, and items you follow.">
                <template #actions>
                    <SecondaryButton type="button" @click="markAll">Mark all read</SecondaryButton>
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <SectionCard flush>
                    <ul v-if="notifications.data?.length" class="divide-y divide-border">
                        <li
                            v-for="n in notifications.data"
                            :key="n.id"
                            class="flex items-start justify-between gap-4 px-4 py-4 sm:px-6"
                            :class="!n.read_at ? 'bg-brand/5' : ''"
                        >
                            <div class="min-w-0 text-sm">
                                <div class="font-medium text-ink">{{ n.data?.message || 'Notification' }}</div>
                                <div class="mt-1 text-xs text-ink-muted">{{ n.created_at }}</div>
                                <Link v-if="n.data?.url" :href="n.data.url" class="app-link mt-2 inline-block text-sm">Open →</Link>
                            </div>
                            <SecondaryButton v-if="!n.read_at" type="button" class="shrink-0 !px-2 !py-1 text-xs" @click="markRead(n.id)">
                                Mark read
                            </SecondaryButton>
                        </li>
                    </ul>
                    <EmptyState v-else title="No notifications" description="You're all caught up." />
                    <template v-if="notifications.data?.length" #footer>
                        <Pagination :links="notifications" />
                    </template>
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
