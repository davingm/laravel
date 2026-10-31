import { registerFormConfirmations } from './confirmations';
import { navigateTo, registerNavigation } from './navigation';
import { readPagePayload } from './payload';
import { registerReveal } from './reveal';
import { registerTableSearch } from './table-search';
import { registerDevelopmentTelemetry } from './telemetry';
import type { LaravelFrontendRuntime } from './types';

const runtime: LaravelFrontendRuntime = {
    payload: readPagePayload(),
    navigate: navigateTo,
};

window.__AUTO_LARAVEL__ = runtime;

registerDevelopmentTelemetry();
registerNavigation(runtime);
registerFormConfirmations();
registerTableSearch();
registerReveal();
