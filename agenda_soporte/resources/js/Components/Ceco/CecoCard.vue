<script setup>
defineProps({
    ceco: {
        type: Object,
        required: true
    }
})

defineEmits(['select'])
</script>

<template>
    <div
        @click="$emit('select', ceco)"
        class="bg-white border border-gray-100 rounded-xl p-4
               cursor-pointer transition-all duration-200
               hover:shadow-md hover:scale-[1.02]"
    >
        <div class="text-xs text-gray-500 mb-1">
            CECO {{ ceco.ceco }}
        </div>

        <div class="text-sm font-semibold text-gray-800">
            {{ ceco.name }}
        </div>
    </div>
</template>
