<script setup>
defineOptions({ inheritAttrs: false });

const model = defineModel({
    type: [String, Number],
    default: '',
});
</script>

<template>
    <select
        v-model="model"
        class="app-input"
        v-bind="$attrs"
    >
        <slot />
    </select>
</template>
