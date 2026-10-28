<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SectionCard from '@/Components/SectionCard.vue';
import FilterBar from '@/Components/FilterBar.vue';
import DataTable from '@/Components/DataTable.vue';
import EmptyState from '@/Components/EmptyState.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const props = defineProps({
    users: Array,
    roles: Array,
});

const search = ref('');

const filteredUsers = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.users;
    return props.users.filter(
        (user) =>
            user.name?.toLowerCase().includes(q) ||
            user.email?.toLowerCase().includes(q) ||
            (user.roles || []).some((r) => r.toLowerCase().includes(q)),
    );
});

const forms = reactive(
    Object.fromEntries(
        props.users.map((user) => [user.id, useForm({ roles: [...user.roles] })]),
    ),
);

const save = (userId) => {
    forms[userId].put(route('admin.users.roles', userId));
};

const roleLabel = (role) =>
    ({
        admin: 'Admin',
        improvement_lead: 'Improvement Lead',
        product_owner: 'Product Owner',
        project_manager: 'Project Manager',
        process_owner: 'Process Owner',
    }[role] || role.replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase()));

const multiSelectClass = 'min-w-[12rem] rounded-md border-border text-sm shadow-sm focus:border-brand focus:ring-brand';
</script>

<template>
    <Head title="Users & Roles" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="Users & roles" description="Assign application roles to team members." />
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <SectionCard>
                    <FilterBar>
                        <TextInput
                            v-model="search"
                            type="search"
                            class="min-w-[14rem]"
                            placeholder="Search name, email, or role…"
                        />
                    </FilterBar>
                </SectionCard>

                <SectionCard flush>
                    <DataTable v-if="filteredUsers.length">
                        <template #head>
                            <tr>
                                <th class="app-th">User</th>
                                <th class="app-th">Roles</th>
                                <th class="app-th"><span class="sr-only">Actions</span></th>
                            </tr>
                        </template>
                        <tr v-for="user in filteredUsers" :key="user.id" class="align-top hover:bg-slate-50">
                            <td class="app-td">
                                <div class="font-medium text-ink">{{ user.name }}</div>
                                <div class="text-sm text-ink-muted">{{ user.email }}</div>
                            </td>
                            <td class="app-td">
                                <select v-model="forms[user.id].roles" multiple size="4" :class="multiSelectClass">
                                    <option v-for="role in roles" :key="role" :value="role">{{ roleLabel(role) }}</option>
                                </select>
                            </td>
                            <td class="app-td text-right">
                                <PrimaryButton type="button" @click="save(user.id)" :disabled="forms[user.id].processing">
                                    Save roles
                                </PrimaryButton>
                            </td>
                        </tr>
                    </DataTable>
                    <EmptyState v-else title="No users match your search" description="Try a different name, email, or role." />
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
