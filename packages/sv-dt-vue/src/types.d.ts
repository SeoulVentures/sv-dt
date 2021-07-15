import {OptCellRenderer} from "tui-grid/types/options";

export interface Options {
    scrollX: boolean;
    scrollY: boolean;
    perPage: number;
}

export interface Parameters {
    [key: string]: string | number;
}

export interface HeaderProp {
    name?: string;
    align?: 'left' | 'center' | 'right';
    target: string;
    sortable?: boolean;
    width?: number | 'auto';
    formatter?: 'number';
    renderer?: OptCellRenderer;
}

export interface KeyValueObject {
    [key: string]: string | number | undefined;
}

export interface ApiDataResponse {
    result: true;
    data: {
        contents: KeyValueObject[];
        pagination: {
            page: number;
            totalCount: number;
        }
    }
}

export interface ApiDataResponse {
    result: false;
    message?: string;
}
