const { createElement, useState } = wp.element;
const { registerPaymentMethod } = wc.wcBlocksRegistry;
const { getSetting } = wc.wcSettings;

const settings = getSetting("cointopay_data", {});
const label = settings.title || "Cointopay";

const CcardPaymentFields = (props) => {
    const { eventRegistration, emitResponse } = props;
    const { onPaymentSetup } = eventRegistration;

    const [alt_coin, setAltCoin] = React.useState("");

    const handleAltCoinChange = (event) => {
        const value = event.target.value;
        setAltCoin(value);
        console.log("alt_coin updated:", value);
    };

    React.useEffect(() => {
        const unsubscribe = onPaymentSetup(async () => {

            console.log("ALT COIN VALUE:", alt_coin);

            if (!alt_coin) {
                return {
                    type: emitResponse.responseTypes.ERROR,
                    message: "Alt coin field is required!"
                };
            }

            return {
                type: emitResponse.responseTypes.SUCCESS,
                meta: {
                    paymentMethodData: {
                        alt_coin,
						alt_nonce: settings.ctp_nonce
                    }
                }
            };
        });

        return () => unsubscribe();
    }, [alt_coin, onPaymentSetup, emitResponse]);

    return createElement("div", {},

        createElement("label", {}, "Crypto Selection"),

        createElement(
            "select",
            {
                value: alt_coin,
				name: 'cointopay_crypto_alt_coin',
                onChange: (e) => setAltCoin(e.target.value)
            },

            createElement("option", { value: "" }, "Select Alt Coin"),

            ...Object.entries(settings.coins || {}).map(([key, value]) =>
                createElement("option", { value: key }, value)
            )
        )
    );
};

registerPaymentMethod({
    name: "cointopay",
    label: label,
	ariaLabel: 'Cointopay Crypto Payment Method',
    content: createElement(CcardPaymentFields),
    edit: createElement(CcardPaymentFields),
    canMakePayment: () => true
});