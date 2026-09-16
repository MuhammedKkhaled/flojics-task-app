import { onScopeDispose, ref } from 'vue';

export const usePolling = (callback, interval = 3000) => {
    const isPolling = ref(false);
    let timer = null;
    let requestInFlight = false;

    const tick = async () => {
        if (requestInFlight) {
            return;
        }

        requestInFlight = true;

        try {
            await callback();
        } catch {
            // The callback owns its error state; the next interval can retry.
        } finally {
            requestInFlight = false;
        }
    };

    const stop = () => {
        if (timer !== null) {
            window.clearInterval(timer);
            timer = null;
        }

        isPolling.value = false;
    };

    const start = () => {
        if (timer !== null) {
            return;
        }

        isPolling.value = true;
        timer = window.setInterval(tick, interval);
    };

    onScopeDispose(stop);

    return { isPolling, start, stop };
};
