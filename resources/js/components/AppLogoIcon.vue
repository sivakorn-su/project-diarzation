<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60" :class="className" v-bind="$attrs">
        <rect width="60" height="60" rx="10" fill="currentColor" fill-opacity="0.05"/>
        <rect x="8" y="14" width="34" height="32" rx="4" fill="currentColor" fill-opacity="0.15"/>
        <path d="M38 47.5H12A5.506 5.506 0 0 1 6.5 42V18A5.506 5.506 0 0 1 12 12.5H38A5.506 5.506 0 0 1 43.5 18V42A5.506 5.506 0 0 1 38 47.5Zm-26-32A2.5 2.5 0 0 0 9.5 18V42A2.5 2.5 0 0 0 12 44.5H38A2.5 2.5 0 0 0 40.5 42V18A2.5 2.5 0 0 0 38 15.5Z" fill="currentColor" fill-opacity="0.4"/>
        <polygon points="52 16 42 22 42 38 52 44 52 16" fill="currentColor" fill-opacity="0.15"/>
        <path d="M52 45.5a1.488 1.488 0 0 1-.771-.214l-10-6A1.5 1.5 0 0 1 40.5 38V22a1.5 1.5 0 0 1 .729-1.286l10-6A1.5 1.5 0 0 1 53.5 16V44A1.5 1.5 0 0 1 52 45.5Zm-8.5-8.349 7 4.2v-22.7l-7 4.2Z" fill="currentColor" fill-opacity="0.4"/>
        <path d="M8 43.5A1.5 1.5 0 0 1 6.5 42V18A5.506 5.506 0 0 1 12 12.5H38a1.5 1.5 0 0 1 0 3H12A2.5 2.5 0 0 0 9.5 18V42A1.5 1.5 0 0 1 8 43.5Z" fill="currentColor" fill-opacity="0.3"/>
    </svg>

</template>
