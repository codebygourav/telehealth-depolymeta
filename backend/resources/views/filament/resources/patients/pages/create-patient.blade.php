<x-filament-panels::page>
    @php
        $checkoutPayload = session('razorpay_checkout_payload') ?? $this->autoCheckout;
    @endphp

    <div class="space-y-4">
        @if (!empty($checkoutPayload['payment']['order_id']))
            <section class="p-4 border border-emerald-200 rounded-xl bg-emerald-50">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-emerald-900">Online payment is ready</h3>
                        <p class="text-xs text-emerald-800">
                            Razorpay checkout opens automatically. If blocked, use the button.
                        </p>
                    </div>
                    <x-filament::button color="success" icon="heroicon-o-credit-card"
                        x-on:click='window.openRazorpayCheckout(@js($checkoutPayload))'>
                        Open Payment Checkout
                    </x-filament::button>
                </div>
            </section>
        @endif

        <form wire:submit="create" class="space-y-6">
            {{ $this->form }}

            <div class="flex justify-end">
                <x-filament::button type="submit" icon="heroicon-o-plus">
                    Create
                </x-filament::button>
            </div>
        </form>
    </div>

    @script
        <script>
            console.log('create-patient view script loaded');

            const loadRazorpayScript = () => {
                if (window.Razorpay) {
                    return Promise.resolve();
                }

                return new Promise((resolve, reject) => {
                    const existing = document.querySelector('script[data-razorpay-checkout="1"]');
                    if (existing) {
                        existing.addEventListener('load', () => resolve(), {
                            once: true
                        });
                        existing.addEventListener('error', () => reject(new Error(
                            'Failed to load Razorpay script')), {
                            once: true
                        });
                        return;
                    }

                    const script = document.createElement('script');
                    script.src = 'https://checkout.razorpay.com/v1/checkout.js';
                    script.setAttribute('data-razorpay-checkout', '1');
                    script.onload = () => resolve();
                    script.onerror = () => reject(new Error('Failed to load Razorpay script'));
                    document.head.appendChild(script);
                });
            };

            window.openRazorpayCheckout = async function(payload) {
                if (!payload || !payload.payment || !payload.payment.order_id || !payload.payment.razorpay_key_id) {
                    console.warn('Razorpay payload missing required keys', payload);
                    return;
                }

                console.log('payload', payload);

                try {
                    await loadRazorpayScript();
                } catch (error) {
                    console.error(error);
                    return;
                }

                const appointmentId = payload.appointment_id || '';
                const verifyUrl = payload.verify_url || '/api/v2/verify-payment';
                const redirectUrl = payload.redirect_url || '';
                const amountPaise = Number(payload.payment.amount_paise || 0);

                const options = {
                    key: payload.payment.razorpay_key_id,
                    amount: amountPaise,
                    currency: 'INR',
                    order_id: payload.payment.order_id,
                    name: 'Appointment Booking',
                    handler: function(response) {
                        if (!verifyUrl) {
                            return;
                        }

                        fetch(verifyUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    ?.content || '',
                            },
                            body: JSON.stringify({
                                appointment_id: appointmentId,
                                razorpay_order_id: response.razorpay_order_id,
                                razorpay_payment_id: response.razorpay_payment_id,
                                razorpay_signature: response.razorpay_signature,
                            }),
                        }).then((res) => {
                            if (!res || !res.ok) {
                                return null;
                            }
                            return res.json().catch(() => null);
                        }).then((json) => {
                            if (json && json.success) {
                                if (redirectUrl) {
                                    window.location.href = redirectUrl;
                                } else {
                                    console.warn(
                                        'Payment verified but redirect_url missing from payload');
                                }
                            }
                        }).catch(() => {});
                    },
                };

                try {
                    new Razorpay(options).open();
                } catch (error) {
                    console.error(error);
                }
            };
        </script>
    @endscript

    @if (!empty($checkoutPayload['payment']['order_id']))
        @script
            <script>
                setTimeout(() => window.openRazorpayCheckout(@js($checkoutPayload)), 250);
            </script>
        @endscript
    @endif
</x-filament-panels::page>
