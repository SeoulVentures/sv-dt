<template>
    <span class="tui-grid-cell" style="display: none; font-family: Arial, '\B3CB\C6C0', Dotum, sans-serif; font-size: 13px; font-weight: 400;"></span>
    <div ref="grid"></div>
</template>

<script lang="ts">
import { computed, defineComponent, reactive, toRefs, watch, ref, onMounted, ComponentPublicInstance, PropType, h, VNode, DefineComponent, render } from 'vue';
import Grid, { Params } from 'tui-grid';
import { FilterState, NumberFilterCode, TextFilterCode } from 'tui-grid/types/store/filterLayerState';
import { OptColumn } from 'tui-grid/types/options';
import { CellRendererClass } from 'tui-grid/types/renderer';
import { ApiDataResponse, HeaderComponentProp, HeaderProp, Options, Parameters } from './types';
import { FormatterProps } from 'tui-grid/types/store/column';
import { CellRendererProps } from 'tui-grid/types/renderer';

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
                perPage: 15
            }
        },
        parameters: {
            type: Object as PropType<Parameters>,
            default: {}
        },
        headers: {
            type: Array as PropType<HeaderProp[]>
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

        const formatter = (type?: HeaderProp['formatter']) => {
            return (props: FormatterProps) => {
                let { value } = props;
                if(value === undefined || value === null) return '';
                switch(type) {
                    case 'number': {
                        switch(typeof value) {
                            case 'object': value = value ? 1 : 0; break;
                            case 'boolean': value = value ? 1 : 0; break;
                            case 'string': value = Number(value.replace(/[^0-9.-]/g, '')); break;
                        }
                        value = Intl.NumberFormat('en-US').format(value);
                    } break;
                }
                return String(value || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            }
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

        const createHeaderObject = (header: HeaderProp): OptColumn => {
            return {
                name: header.target,
                header: header.name ?? header.target.split('_').map(e => `${e.charAt(0).toUpperCase()}${e.slice(1)}`).join(' '),
                sortable: header.sortable,
                align: header.align,
                width: header.width,
                formatter: formatter(header.formatter),
                renderer: header.component ? getRenderVueCompoenent(header.component) : header.renderer
            }
        }

        const updateHeader = async () => {
            if(props.headers && props.headers.length > 0) {
                store.headers = props.headers.map(createHeaderObject);
                return;
            }
            const res = await fetch(`${props.queryUrl}?${serializer({
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
                    sortable: true,
                    formatter: formatter()
                }
            });
            if(store.gridInstance) {
                store.gridInstance.setColumns(store.headers);
            }
            applyPendingFilters();
        };

        onMounted(() => {
            Grid.applyTheme('striped');
            watch(computed(() => [ props.queryId, props.queryUrl ]), async () => {
                if(store.gridInstance) store.gridInstance.destroy();
                await updateHeader();
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
                            e.innerHTML = typeof value === 'string' ? value : value?.toString().trim() || '';
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
            store.gridInstance.on('beforeSort', (_ev, { columns } = store.gridInstance!.store.data.sortState) => columns.length && columns.shift()); // Issue #1379
        });

        watch(computed(() => JSON.stringify(props.parameters)), async () => {
            if(!store.gridInstance) return;
            store.filters = {};
            await updateHeader();
            store.gridInstance.resetData([]);
            store.gridInstance.readData(1);
        });

        watch(computed(() => JSON.stringify(props.headers)), async () => {
            if(!props.headers || !props.headers.length) return await updateHeader();
            store.headers = props.headers.map(createHeaderObject);
            if(store.gridInstance) store.gridInstance.setColumns(store.headers);
            applyPendingFilters();
        });

        const methods = {
            filter: (columnName: string, state: FilterState) => {
                store.filters[columnName] = state as typeof store['filters'][typeof columnName];
                return;
            },
            unfilter: (columnName?: string) => {
                if(columnName) {
                    delete store.filters[columnName];
                    return;
                } else {
                    store.filters = {};
                    return;
                }
            },
            reloadData: () => {
                store.gridInstance?.reloadData();
                return;
            }
        };

        return {
            ...methods,
            grid
        }
    }
});

function getRenderVueCompoenent(component: HeaderComponentProp): CellRendererClass {
    return class RenderVueComponent {
        private $element: VNode;
        private $container: HTMLElement;

        constructor(props: CellRendererProps) {
            const rowData = toRefs(props.grid.store.data.rawData[props.rowKey as number]);
            const { rowKey, rowSpanKey, sortKey, uniqueKey, rowSpanMap, _attributes, _relationListItemMap, _disabledPriority, _children, _leaf, ...componentProps } = rowData;
            this.$container = document.createElement('div');
            this.$element = h(component.value, Object.assign(componentProps, component.attrs));
            render(this.$element, this.$container);
        }

        getElement() {
            return this.$container;
        }
    };
}
</script>

<style lang="scss">
@import 'tui-grid/dist/tui-grid.min.css';
@import 'tui-pagination/dist/tui-pagination.min.css';
</style>
