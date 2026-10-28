<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const success = computed(() => page.props.flash?.success ?? null);
const error = computed(() => page.props.flash?.error ?? page.props.errors?.error ?? null);
const warning = computed(() => page.props.flash?.warning ?? null);
</script>

<template>
    <div v-if="success || error || warning" class="app-page space-y-2 pt-4">
        <div
            v-if="success"
            class="rounded-control bg-success-soft px-4 py-3 text-sm text-success"
            role="status"
        >
            {{ success }}
        </div>
        <div
            v-if="error"
            class="rounded-control bg-danger-soft px-4 py-3 text-sm text-danger"
            role="alert"
        >
            {{ error }}
        </div>
        <div
            v-if="warning"
            class="rounded-control bg-warning-soft px-4 py-3 text-sm text-warning"
            role="status"
        >
            {{ warning }}
        </div>
    </div>
</template>
