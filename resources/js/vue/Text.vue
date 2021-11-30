<template>
	<button @click="$emit('test', $event)">{{ id }} {{ description }}</button>
</template>

<script lang="ts">
import { defineComponent, onMounted, Ref, ref, toRefs } from "@vue/runtime-core";

export default defineComponent({
	props: ['id', 'description', 'connection', 'query', 'config', 'params', 'created_at', 'updated_at'],
	setup(props) {
		console.log(props);
		const element = ref(null) as unknown as Ref<HTMLElement>;

		return {
			...toRefs(props),
			element
		};
	}
})
</script>

<style>

</style>
