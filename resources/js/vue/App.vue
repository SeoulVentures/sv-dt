<template>
  <!-- <router-view /> -->
  <sv-data-table :queryId="1" />
</template>

<script lang="ts">
import { defineComponent, reactive, toRefs } from 'vue';

export default defineComponent({
  setup () {
    const state = reactive({});
    return { ...toRefs(state) };
  }
});
</script>

<style lang="scss">
@import '../../../node_modules/tui-grid/dist/tui-grid.css';
html {
  font-size: 14px;
}

html, :root, html .p-component {
  font-family: 'Nanum Gothic', Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
}

html, body {
  margin: 0;
  padding: 0;
  width: 100%;
  height: 100%;
}

* {
  box-sizing: border-box;
}

#app {
  margin: 0;
  padding: 0;
  width: 100%;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  overflow: auto;
}

* {
  --color-gray-100: rgba(243, 244, 246);
  --color-gray-200: rgba(229, 231, 235);
  --color-gray-300: rgba(209, 213, 219);
  --color-gray-400: rgba(156, 163, 175);
  --color-gray-500: rgba(107, 114, 128);
  --color-gray-600: rgba(75, 85, 99);
  --color-gray-700: rgba(55, 65, 81);
  --color-gray-800: rgba(31, 41, 55);
  --color-gray-900: rgba(17, 24, 39);
}

h1, h2, h3, h4, h5 {
  margin-top: 0;
}
</style>

