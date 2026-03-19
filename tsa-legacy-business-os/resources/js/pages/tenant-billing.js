const root = document.getElementById('tenant-billing-root');

if (root) {
    const selectUrl = root.dataset.selectUrl;
    const checkoutUrl = root.dataset.checkoutUrl;
    const successUrl = root.dataset.successUrl;
    const csrfToken = root.dataset.csrfToken;

    const postJson = async (url, body) => {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(body),
        });

        let payload = {};
        try {
            payload = await response.json();
        } catch {
            payload = { message: 'Unexpected server response.' };
        }

        return { response, payload };
    };

    const pickErrorMessage = (payload) => {
        if (payload?.errors && typeof payload.errors === 'object') {
            const firstField = Object.keys(payload.errors)[0];
            const firstMessage = firstField ? payload.errors[firstField]?.[0] : null;
            if (firstMessage) {
                return firstMessage;
            }
        }

        return payload?.message || 'Unable to complete billing action.';
    };

    const notifyError = (message) => {
        window.alert(message || 'Unable to complete billing action.');
    };

    const startCheckout = async (planId) => {
        const { response: selectResponse, payload: selectPayload } = await postJson(selectUrl, {
            plan_id: Number(planId),
            billing_cycle: 'monthly',
        });

        if (!selectResponse.ok) {
            notifyError(pickErrorMessage(selectPayload));
            return;
        }

        const { response: checkoutResponse, payload: checkoutPayload } = await postJson(checkoutUrl, {
            subscription_id: selectPayload.subscription_id,
        });

        if (!checkoutResponse.ok) {
            notifyError(pickErrorMessage(checkoutPayload));
            return;
        }

        if (typeof window.Razorpay === 'undefined') {
            notifyError('Payment gateway not available. Please refresh and try again.');
            return;
        }

        const options = {
            key: checkoutPayload.key,
            amount: checkoutPayload.amount,
            currency: checkoutPayload.currency,
            name: checkoutPayload.name,
            description: checkoutPayload.description,
            order_id: checkoutPayload.order_id,
            theme: { color: '#0891b2' },
            handler: async (gatewayResponse) => {
                const { response: successResponse, payload: successPayload } = await postJson(successUrl, gatewayResponse);

                if (!successResponse.ok) {
                    notifyError(pickErrorMessage(successPayload));
                    return;
                }

                window.alert(successPayload.message || 'Payment captured successfully.');
                window.location.reload();
            },
        };

        const razorpay = new window.Razorpay(options);
        razorpay.open();
    };

    root.querySelectorAll('.js-select-plan').forEach((button) => {
        button.addEventListener('click', () => {
            const planId = button.dataset.planId;
            if (!planId) {
                notifyError('Plan identifier is missing.');
                return;
            }
            startCheckout(planId);
        });
    });
}
