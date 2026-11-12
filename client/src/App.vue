<script setup>
import { onMounted } from 'vue';
import { useAppStore } from './stores/app.store';

const store = useAppStore();
onMounted(() => store.boot());
</script>

<template>
    <RouterView />
</template>
