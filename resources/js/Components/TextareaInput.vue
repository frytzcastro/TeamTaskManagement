<script setup>
const model = defineModel({
    type: String,
    default: '',
});

defineProps({
    rows: {
        type: [Number, String],
        default: 4,
    },
});
</script>

<template>
    <textarea
        v-model="model"
        :rows="rows"
        class="app-input"
    />
</template>
