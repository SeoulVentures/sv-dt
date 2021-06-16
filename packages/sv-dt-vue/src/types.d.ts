export interface Options {
    scrollX: boolean;
    scrollY: boolean;
    perPage: number;
}

export interface Parameters {
    [key: string]: string | number;
}

export interface KeyValueObject {
    [key: string]: string | undefined;
}
