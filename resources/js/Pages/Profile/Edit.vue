<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SectionCard from '@/Components/SectionCard.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="Profile" description="Manage your account settings and security." />
        </template>

        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <SectionCard>
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </SectionCard>

                <SectionCard>
                    <UpdatePasswordForm class="max-w-xl" />
                </SectionCard>

                <SectionCard>
                    <DeleteUserForm class="max-w-xl" />
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
