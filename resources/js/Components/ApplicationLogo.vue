<script setup>
defineProps({
    alt: {
        type: String,
        default: 'MyTask',
    },
    /** Prefer the smaller asset in the nav for sharper downscales. */
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md', 'lg'].includes(v),
    },
});

const cacheBust = 'v3';
const srcBySize = {
    sm: `/images/mytask-logo-64.png?${cacheBust}`,
    md: `/images/mytask-logo-256.png?${cacheBust}`,
    lg: `/images/mytask-logo.png?${cacheBust}`,
};
</script>

<template>
    <img
        :src="srcBySize[size]"
        :alt="alt"
        class="block h-full w-full object-contain object-center"
        draggable="false"
    />
</template>
