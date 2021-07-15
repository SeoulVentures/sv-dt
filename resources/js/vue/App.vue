<template>
	<!-- <router-view /> -->
	<sv-data-table :queryId="queryId" :options="{ scrollX: false }" v-model:parameters="params" v-model:headers="headers" ref="dt" />
    <button @click="reloadData">reloadData</button>
</template>

<script lang="ts">
import { ComponentPublicInstance, computed, defineComponent, onMounted, reactive, ref, toRefs, watch } from 'vue';
import { SvDataTable } from '@seoulventures/sv-dt';
import { NumberFilterCode, TextFilterCode } from 'tui-grid/types/store/filterLayerState';

interface KeyValueObject {
		[key: string]: string | number | undefined;
}

class CustomSliderRenderer {
    el;
    constructor(props: any) {
        const el = document.createElement('button');
        el.innerText = props.value;
        el.addEventListener('click', function() {
            alert(props.value);
        });

        this.el = el;
        this.render(props);
    }

    getElement() {
        return this.el;
    }

    render(props: any) {
        this.el.value = String(props.value);
    }
}

export default defineComponent({
    setup() {
        const state = reactive({
            queryId: 1,
            params: {} as KeyValueObject,
            headers: [
                {
                    name: '날짜',
                    target: 'created_at',
                },
                {
                    name: '주문번호',
                    target: 'id',
                },
                {
                    name: '충전금액',
                    target: 'amount',
                    align: 'right',
                    formatter: 'number',
                },
                {
                    name: 'VAT',
                    target: 'tax',
                    align: 'right',
                    formatter: 'number',
                },
                {
                    name: '결제금액',
                    target: 'total',
                    align: 'right',
                    renderer: {
                        type: CustomSliderRenderer,
                    }
                }
            ] as { name?: string; target: string; }[]
        });

        const dt = ref<ComponentPublicInstance<SvDataTable>>();

        watch(computed(() => window.location.search), () => {
            if(!window.location.search.length) return state.params = reactive({});
            const params = window.location.search.substr(1).split('&').map(e => e.split('=')).reduce((o, v) => {
                o[v.shift()!] = v.join('=');
                return o;
            }, {} as KeyValueObject);
            state.queryId = Number(params.queryId!);
            delete params.queryId;
            if(params.headers) {
                state.headers = (params.headers! as string).split(',').map(e => e.trim()).map(decodeURI).map(e => e.split(';')).map(e => {
                    return {
                        target: e.shift()!,
                        name: e.shift(),
                        align: 'center',
                        width: e.shift(),
                        formatter: e.shift()
                    };
                });
            }
            delete params.headers;
            if(params.filters) {
                const { filters } = params;

                onMounted(() => {
                    watch(computed(() => dt.value), () => {
                        (filters as string).split(';').map(e => e.trim()).map(decodeURI).map(e => e.split('+')).forEach(e => {
                            if(!dt.value) return;
                            const [ columnName, code, value ] = e;
                            dt.value.filter(columnName, { code: (code as NumberFilterCode | TextFilterCode), value });
                        });
                    }, { immediate: true });
                });
            }
            delete params.filters;
            state.params = reactive(params);
        }, { immediate: true });

        const reloadData = () => {
            return dt.value?.reloadData();
        }

        return { ...toRefs(state), dt, reloadData };
    }
});
</script>

<style lang="scss">
@import '../../../node_modules/@seoulventures/sv-dt/dist/svDataTable.css';
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

// th {
//     font-weight: 100;
// }
</style>

