<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SectionCard from '@/Components/SectionCard.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    message: String,
});
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="Dashboard" />
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page">
                <SectionCard>
                    <p class="text-sm text-ink">{{ message || "You're logged in!" }}</p>
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
