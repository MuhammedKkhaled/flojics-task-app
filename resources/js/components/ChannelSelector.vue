<script setup>
defineProps({
    channels: {
        type: Array,
        required: true,
    },
    modelValue: {
        type: Array,
        required: true,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const update = (channelKey, checked, selected) => {
    const next = checked
        ? [...selected, channelKey]
        : selected.filter((key) => key !== channelKey);

    emit('update:modelValue', [...new Set(next)]);
};
</script>

<template>
    <fieldset class="channel-selector" :disabled="disabled">
        <legend>Notification channels</legend>
        <label v-for="channel in channels" :key="channel.key" class="channel-option">
            <input
                type="checkbox"
                :checked="modelValue.includes(channel.key)"
                @change="update(channel.key, $event.target.checked, modelValue)"
            >
            <span>{{ channel.label }}</span>
        </label>
    </fieldset>
</template>
