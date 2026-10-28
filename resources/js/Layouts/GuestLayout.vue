<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center bg-app pt-8 sm:justify-center sm:pt-0">
        <div class="flex flex-col items-center gap-3">
            <Link href="/" class="flex flex-col items-center gap-3 text-ink">
                <span class="inline-flex h-20 w-20 items-center justify-center sm:h-24 sm:w-24">
                    <ApplicationLogo size="lg" />
                </span>
                <span class="text-xl font-semibold tracking-tight">MyTask</span>
            </Link>
            <p class="text-sm text-ink-muted">Delivery, quality, and outcomes</p>
        </div>

        <div class="mt-6 w-full overflow-hidden rounded-lg border border-border bg-surface px-6 py-5 sm:max-w-md">
            <slot />
        </div>
    </div>
</template>
