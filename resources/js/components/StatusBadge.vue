<script setup>
import { computed } from 'vue';

const props = defineProps({
    value: {
        type: String,
        required: true,
    },
    kind: {
        type: String,
        default: 'status',
    },
});

const label = computed(() => props.value.replaceAll('_', ' '));
</script>

<template>
    <span :class="['badge', `${kind}-${value}`]">{{ label }}</span>
</template>
