<script setup>
defineOptions({ inheritAttrs: false });
</script>

<template>
    <div
        class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end"
        v-bind="$attrs"
    >
        <slot />
    </div>
</template>
