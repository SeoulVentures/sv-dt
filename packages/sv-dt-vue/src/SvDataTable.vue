<template>
    <div ref="grid"></div>
</template>

<script lang="ts">
import { computed, defineComponent, reactive, toRefs, watch, ref, onMounted, ComponentPublicInstance, PropType } from 'vue';
import Grid, { Params } from 'tui-grid';
import { FilterState, NumberFilterCode, TextFilterCode } from 'tui-grid/types/store/filterLayerState';
import { OptColumn } from 'tui-grid/types/options';
import { ApiDataResponse, Options, Parameters } from './types';

export default defineComponent({
    props: {
        queryUrl: {
            type: String,
            default: '/api/svdt/data'
        },
        queryId: {
            type: Number,
            required: true,
            default: 0
        },
        options: {
            type: Object as PropType<Options>,
            default: {
                scrollX: false,
                scrollY: false,
                perPage: 50
            }
        },
        parameters: {
            type: Object as PropType<Parameters>,
            default: {}
        },
        headers: {
            type: Array as PropType<OptColumn[]>
        }
    },
    setup(props) {
        const store = reactive({
            headers: [] as OptColumn[],
            gridInstance: undefined as Grid | undefined,
            filters: {} as { [key: string]: { value: string; code: NumberFilterCode | TextFilterCode; } },
            pendingFilters: [] as [ string, FilterState[] ][]
        });
        const grid = ref<ComponentPublicInstance<HTMLDivElement>>();

        const serializer = (params: Params) => {
            params = Object.assign(params, {
                queryId: props.queryId,
                filters: JSON.stringify(store.filters),
                parameters: JSON.stringify(props.parameters)
            });
            return Object.keys(params).map(e => `${encodeURIComponent(e)}=${encodeURIComponent((params[e] === null || params[e] === undefined) ? '' : params[e])}`).join('&');
        }

        const applyPendingFilters = () => {
            if(!store.gridInstance || !store.headers.length || JSON.stringify(store.gridInstance.store.column.allColumnMap) === '{}') return;
            if(store.pendingFilters.length) {
                let currentFilter = null;
                while(currentFilter = store.pendingFilters.shift()) {
                    store.gridInstance.filter(...currentFilter);
                }
            }
        }

        const updateHeader = async () => {
            if(props.headers && props.headers.length > 0) {
                store.headers = props.headers;
                return;
            }
            const res = await fetch(`/api/svdt/data?${serializer({
                page: 1,
                perPage: 1
            })}`);
            const response = await res.json() as ApiDataResponse;
            if(!response.result && !response.data.contents.length) return;
            const row = response.data.contents[0];
            store.headers = Object.keys(row).filter(e => !e.startsWith('_')).map(e => {
                return {
                    name: e,
                    header: e.split('_').map(e => `${e.charAt(0).toUpperCase()}${e.slice(1)}`).join(' '),
                    filter: {
                        type: typeof row[e] === 'number' ? 'number' : 'text',
                        showApplyBtn: true,
                        showClearBtn: true
                    },
                    sortable: true
                }
            });
            if(store.gridInstance) {
                store.gridInstance.setColumns(store.headers);
            }
            applyPendingFilters();
        };

        onMounted(() => {
            Grid.applyTheme('striped');
            watch(computed(() => props.queryUrl), async () => {
                store.gridInstance = new Grid({
                    el: grid.value!,
                    scrollX: !!props.options.scrollX,
                    scrollY: !!props.options.scrollY,
                    minBodyHeight: 30,
                    pageOptions: {
                        perPage: props.options.perPage ?? 15
                    },
                    copyOptions: {
                        customValue: value => {
                            const e = document.createElement('div');
                            e.innerHTML = typeof value === 'string' ? value : value?.toString() || '';
                            return e.childNodes[0]?.nodeValue || '';
                        }
                    },
                    columns: store.headers,
                    useClientSort: false,
                    data: {
                        api: {
                            readData: { url: props.queryUrl, method: 'GET' }
                        },
                        serializer
                    }
                });
            }, { immediate: true });
            applyPendingFilters();
        });

        watch(computed(() => props.queryId), updateHeader, { immediate: true });

        watch(computed(() => store.gridInstance), async () => {
            if(!store.gridInstance) return;
            await updateHeader();
            store.gridInstance.on('onGridUpdated', (_ev) => {
                for(const [ key, value ] of Object.entries(store.filters)) {
                    store.gridInstance!.filter(key, [ value ]);
                }
            });

            store.gridInstance.on('beforeSort', (_ev, { columns } = store.gridInstance!.store.data.sortState) => columns.length && columns.shift()); // Issue #1379

            store.gridInstance.on('filter', (ev) => {
                const filters = ev.filterState?.map(e => {
                    const { columnName, state } = e;
                    return {
                        name: columnName,
                        code: `${state[0].code}` as NumberFilterCode | TextFilterCode,
                        value: `${state[0].value}`
                    };
                }).reduce((o, v) => (o[v.name] = { value: v.value, code: v.code }, o), {} as typeof store.filters);
                if(!filters) return;
                if(JSON.stringify(store.filters) === JSON.stringify(filters)) return;
                store.filters = filters;
                store.gridInstance!.resetData([]);
                store.gridInstance!.readData(1);
                document.querySelectorAll('.tui-grid-filter-btn-clear').forEach(e => e.addEventListener('click', () => {
                    store.filters = {};
                    const { data, filterLayerState } = store.gridInstance!.store;
                    filterLayerState.activeFilterState = null;
                    filterLayerState.activeColumnAddress = null;
                    data.filters = null;
                    store.gridInstance!.resetData([]);
                    store.gridInstance!.readData(1);
                }));
                document.querySelectorAll('.tui-grid-filter-btn-apply').forEach(e => e.addEventListener('click', () => {
                    if(e.parentElement?.parentElement?.querySelector('input')?.value.length === 0) { // Element exists & value length 0
                        e.parentElement?.querySelector('.tui-grid-filter-btn-clear')?.dispatchEvent(new Event('click'));
                    }
                }));
            });
        });

        watch(computed(() => JSON.stringify(props.parameters)), async () => {
            if(!store.gridInstance) return;
            store.filters = {};
            const { data, filterLayerState } = store.gridInstance.store;
            filterLayerState.activeFilterState = null;
            filterLayerState.activeColumnAddress = null;
            data.filters = null;
            await updateHeader();
            store.gridInstance.resetData([]);
            store.gridInstance.readData(1);
        });

        watch(computed(() => JSON.stringify(props.headers)), async () => {
            if(!props.headers || !props.headers.length) return await updateHeader();
            store.headers = props.headers;
            if(store.gridInstance) store.gridInstance.setColumns(store.headers);
            applyPendingFilters();
        });

        const methods = {
            filter: (columnName: string, state: FilterState) => {
                if(!store.gridInstance || !store.headers.length || JSON.stringify(store.gridInstance.store.column.allColumnMap) === '{}') {
                    store.pendingFilters.push([ columnName, [ state ] ]);
                    return;
                }
                return store.gridInstance.filter(columnName, [ state ]);
            },
            unfilter: (columnName?: string) => {
                if(!store.gridInstance || !store.headers.length || JSON.stringify(store.gridInstance.store.column.allColumnMap) === '{}') {
                    if(columnName === undefined) store.pendingFilters = [];
                    store.pendingFilters = store.pendingFilters.filter(([ name ]) => name !== columnName);
                    return;
                }
                return store.gridInstance.unfilter(columnName);
            }
        };

        return {
            ...methods,
            grid
        }
    }
});
</script>

<style lang="scss">
@import 'tui-grid/dist/tui-grid.min.css';
@import 'tui-pagination/dist/tui-pagination.min.css';
</style>
