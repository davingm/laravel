export interface PagePayload {
    page: string;
    url: string;
    path: string;
    data: Record<string, unknown>;
    meta: Record<string, string | null>;
    generated_at: string;
}

export interface LaravelFrontendRuntime {
    payload: PagePayload | null;
    navigate(url: string): Promise<void>;
}

declare global {
    interface Window {
        __AUTO_LARAVEL__: LaravelFrontendRuntime;
    }
}

export {};
